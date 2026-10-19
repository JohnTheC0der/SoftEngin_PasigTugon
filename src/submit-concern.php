<?php
// Handles POST requests from Citizen_Concern_Station.php
// Expects: barangay (slug like "bagong-ilog"), concern_text, captcha (+ hidden "website" honeypot)
//
// Only a signed-in citizen can submit; the concern is linked to their account (user_id).
// EVERY concern is assessed automatically by the Python engine (ml_engine/predict.py):
// sector + confidence from the Naive Bayes model, priority from ml_engine/priority.py.
// There is no manual-review state any more — barangay staff can overturn the sector or
// priority afterwards (override-concern.php).

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'classifier.php';

// Only these barangays are accepted. Must match the <option> values in Citizen_Concern_Station.php.
const VALID_BARANGAYS = [
    'bagong-ilog', 'bambang', 'caniogan', 'kapitolyo', 'malinao',
    'oranbo', 'pinagbuhatan', 'san-antonio', 'san-joaquin', 'ugong'
];
const SUBMIT_COOLDOWN_SECONDS = 20;   // minimum gap between submissions from the same citizen

function reply(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

try {
    $user = require_api_role($pdo, 'citizen');
} catch (PDOException $e) {
    http_response_code(500);
    reply(false, 'Something went wrong. Please try again.');
}

// Honeypot: the form has a hidden "website" field humans never fill in; bots usually do.
// Pretend it worked so the bot learns nothing.
if (trim($_POST['website'] ?? '') !== '') {
    reply(true, 'Your concern has been submitted.');
}

$barangaySlug = trim($_POST['barangay'] ?? '');
$concernText  = trim($_POST['concern_text'] ?? '');

if ($barangaySlug === '' || $concernText === '') {
    reply(false, 'Please select a barangay and write your concern.');
}
if (!in_array($barangaySlug, VALID_BARANGAYS, true)) {
    reply(false, 'Please select a valid barangay.');
}
if (mb_strlen($concernText) < 15) {
    reply(false, 'Please provide a more complete description of your concern.');
}
if (mb_strlen($concernText) > 2000) {
    reply(false, 'Your concern is too long. Please keep it under 2000 characters.');
}

try {
    // Concerns are only accepted for barangays that already have an approved staff account.
    $barangayName = ucwords(str_replace('-', ' ', $barangaySlug));
    $stmt = $pdo->prepare("SELECT barangay_id FROM tbl_barangay WHERE barangay_name = ? AND approval_status = 'approved'");
    $stmt->execute([$barangayName]);
    $barangayId = $stmt->fetchColumn();
    if ($barangayId === false) {
        reply(false, 'This barangay is not accepting concerns yet. Please choose another barangay.');
    }

    // CAPTCHA (single-use; captcha_failed tells the page to fetch a fresh code)
    if (!captcha_check($_POST['captcha'] ?? '')) {
        reply(false, 'The verification code was incorrect. Please try again with the new code.', ['captcha_failed' => true]);
    }

    // Rate limit per citizen account (checked in the database, so a new browser doesn't reset it)
    $stmt = $pdo->prepare(
        "SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(MAX(submitted_at)) FROM tbl_concerns WHERE user_id = ?"
    );
    $stmt->execute([$user['user_id']]);
    $secondsSinceLast = $stmt->fetchColumn();
    if ($secondsSinceLast !== null && $secondsSinceLast !== false && (int) $secondsSinceLast < SUBMIT_COOLDOWN_SECONDS) {
        $wait = SUBMIT_COOLDOWN_SECONDS - (int) $secondsSinceLast;
        reply(false, "Please wait $wait more seconds before submitting another concern.", ['captcha_failed' => true]);
    }

    // Assess the concern. Without a sector and priority it cannot be routed, and a wrong
    // guess would be worse than none, so if the engine is down we ask the citizen to retry.
    $result = classifyWithModel($concernText);
    if ($result === null) {
        http_response_code(503);
        reply(false, 'The concern assessment system is temporarily unavailable. Please try again in a few minutes.', ['captcha_failed' => true]);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO tbl_concerns (barangay_id, user_id, raw_text, extracted_keywords, sector, confidence_score, priority_level, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'ongoing')"
    );
    $stmt->execute([
        $barangayId,
        $user['user_id'],
        $concernText,
        $result['keywords'],
        $result['sector'],
        $result['confidence'],
        $result['priority']
    ]);

    reply(true, 'Your concern has been submitted.', ['case_number' => (int) $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    reply(false, 'Something went wrong. Please try again.');
    // error_log($e->getMessage());
}
