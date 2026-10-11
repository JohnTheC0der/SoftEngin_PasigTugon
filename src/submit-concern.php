<?php
// Handles POST requests from citizen_concern_station.html
// Expects: barangay (slug like "bagong-ilog"), concern_text
//
// Classification is done by the Python ML engine (ml_engine/predict.py):
// sector + confidence from the Naive Bayes model, priority from ml_engine/priority.py.
// If the engine can't run, or its confidence is too low, the concern is saved with
// no sector/priority and status 'for_review' so an admin classifies it manually.

header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

$barangaySlug = trim($_POST['barangay'] ?? '');
$concernText  = trim($_POST['concern_text'] ?? '');

if ($barangaySlug === '' || $concernText === '') {
    $response['message'] = 'Please select a barangay and write your concern.';
    echo json_encode($response);
    exit;
}

if (strlen($concernText) < 15) {
    $response['message'] = 'Please provide a more complete description of your concern.';
    echo json_encode($response);
    exit;
}

// Model confidence at or below this means "don't trust the model": the concern is
// saved with no sector/priority and an admin must set them manually (status 'for_review').
const LOW_CONFIDENCE_THRESHOLD = 0.4;

$barangayName = ucwords(str_replace('-', ' ', $barangaySlug));

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
    // Find or create the barangay row, same pattern as register.php —
    // citizens can submit concerns to a barangay before any admin has signed up for it.
    $stmt = $pdo->prepare("SELECT barangay_id FROM tbl_barangay WHERE barangay_name = ?");
    $stmt->execute([$barangayName]);
    $barangay = $stmt->fetch();

    if ($barangay) {
        $barangayId = $barangay['barangay_id'];
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tbl_barangay (barangay_name, approval_status) VALUES (?, 'pending')"
        );
        $stmt->execute([$barangayName]);
        $barangayId = $pdo->lastInsertId();
    }

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

    $response['success'] = true;
    $response['message'] = 'Your concern has been submitted.';
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Something went wrong. Please try again.';
    // error_log($e->getMessage());
}

echo json_encode($response);
