<?php
// Handles POST requests from citizen_concern_station.html
// Expects: barangay (slug like "bagong-ilog"), concern_text
//
// Classification is done by the Python ML engine (ml_engine/predict.py):
// sector + confidence from the Naive Bayes model, priority from ml_engine/priority.py.
// If the engine can't run, or its confidence is too low, the concern is saved with
// no sector/priority and status 'for_review' so an admin classifies it manually.

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

// Only these barangays are accepted. Must match the <option> values in citizen_concern_station.html.
const VALID_BARANGAYS = [
    'bagong-ilog', 'bambang', 'caniogan', 'kapitolyo', 'malinao',
    'oranbo', 'pinagbuhatan', 'san-antonio', 'san-joaquin', 'ugong'
];
const SUBMIT_COOLDOWN_SECONDS = 20;   // minimum gap between submissions from the same visitor
const CAPTCHA_MAX_AGE_SECONDS = 600;  // a captcha image expires after 10 minutes

$barangaySlug = trim($_POST['barangay'] ?? '');
$concernText  = trim($_POST['concern_text'] ?? '');
$captchaInput = strtoupper(trim($_POST['captcha'] ?? ''));

// Honeypot: the form has a hidden "website" field humans never fill in; bots usually do.
// Pretend it worked so the bot learns nothing.
if (trim($_POST['website'] ?? '') !== '') {
    echo json_encode(['success' => true, 'message' => 'Your concern has been submitted.']);
    exit;
}

if ($barangaySlug === '' || $concernText === '') {
    $response['message'] = 'Please select a barangay and write your concern.';
    echo json_encode($response);
    exit;
}

if (!in_array($barangaySlug, VALID_BARANGAYS, true)) {
    $response['message'] = 'Please select a valid barangay.';
    echo json_encode($response);
    exit;
}

if (mb_strlen($concernText) < 15) {
    $response['message'] = 'Please provide a more complete description of your concern.';
    echo json_encode($response);
    exit;
}

if (mb_strlen($concernText) > 2000) {
    $response['message'] = 'Your concern is too long. Please keep it under 2000 characters.';
    echo json_encode($response);
    exit;
}

// Concerns are only accepted for barangays that already have an approved admin.
// A barangay row is created only when an admin applies for it (register.php) — never here.
$barangayName = ucwords(str_replace('-', ' ', $barangaySlug));
try {
    $stmt = $pdo->prepare("SELECT barangay_id FROM tbl_barangay WHERE barangay_name = ? AND approval_status = 'approved'");
    $stmt->execute([$barangayName]);
    $barangayId = $stmt->fetchColumn();
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Something went wrong. Please try again.';
    echo json_encode($response);
    exit;
}
if ($barangayId === false) {
    $response['message'] = 'This barangay is not accepting concerns yet. Please choose another barangay.';
    echo json_encode($response);
    exit;
}

// CAPTCHA: single-use. The stored answer is removed whether or not the guess was right,
// so the page must request a fresh image after every attempt (captcha_failed tells it to).
$expected = $_SESSION['captcha_code'] ?? '';
$issuedAt = $_SESSION['captcha_time'] ?? 0;
unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);

if ($expected === '' || $captchaInput === '' || !hash_equals($expected, $captchaInput)
    || (time() - $issuedAt) > CAPTCHA_MAX_AGE_SECONDS) {
    $response['message'] = 'The verification code was incorrect. Please try again with the new code.';
    $response['captcha_failed'] = true;
    echo json_encode($response);
    exit;
}

// Rate limit (per browser session)
$wait = SUBMIT_COOLDOWN_SECONDS - (time() - ($_SESSION['last_submit_at'] ?? 0));
if ($wait > 0) {
    $response['message'] = "Please wait $wait more seconds before submitting another concern.";
    $response['captcha_failed'] = true;   // the code was just consumed; reload a fresh one
    echo json_encode($response);
    exit;
}

// Model confidence at or below this means "don't trust the model": the concern is
// saved with no sector/priority and an admin must set them manually (status 'for_review').
const LOW_CONFIDENCE_THRESHOLD = 0.4;

// Calls the Python model. Returns null on any failure so the caller can fall back.
function classifyWithModel(string $text): ?array
{
    $python = defined('PYTHON_BIN') ? PYTHON_BIN : 'python';
    $script = __DIR__ . '/../ml_engine/predict.py';

    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($text);
    $output = shell_exec($cmd);
    $data = json_decode($output ?? '', true);

    if (!is_array($data) || isset($data['error'])) {
        return null;
    }
    return $data;   // keys: sector, confidence, priority, keywords
}

try {
    // $barangayId was looked up earlier (the barangay must already exist and be approved)
    $classification = classifyWithModel($concernText);   // null if the engine failed

    $confidence = $classification['confidence'] ?? null;
    // Engine failed (null) or model isn't confident enough -> manual review
    $needsReview = $classification === null || (float) $confidence <= LOW_CONFIDENCE_THRESHOLD;

    $stmt = $pdo->prepare(
        "INSERT INTO tbl_concerns (barangay_id, raw_text, extracted_keywords, sector, confidence_score, priority_level, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $barangayId,
        $concernText,
        $classification['keywords'] ?? '',
        $needsReview ? null : $classification['sector'],
        $confidence,
        $needsReview ? null : $classification['priority'],
        $needsReview ? 'for_review' : 'ongoing'
    ]);

    $_SESSION['last_submit_at'] = time();
    $response['success'] = true;
    $response['message'] = 'Your concern has been submitted.';
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Something went wrong. Please try again.';
    // error_log($e->getMessage());
}

echo json_encode($response);
