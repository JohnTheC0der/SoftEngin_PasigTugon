<?php
// Handles POST requests from citizen_concern_station.html
// Expects: barangay (slug like "bagong-ilog"), concern_text
//
// IMPORTANT: Your Python/ML teammate's Multinomial Naive Bayes classifier isn't
// wired in yet (per the PHP<->Python bridge discussed earlier — shell_exec or a
// Flask microservice). Until that's ready, this uses a simple keyword lookup
// against tbl_urgency_keywords as a placeholder so the pipeline is end-to-end
// testable. Swap the classifyText() function below for the real model call
// once it's available — nothing else in this file needs to change.

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

$barangayName = ucwords(str_replace('-', ' ', $barangaySlug));

// TEMPORARY classifier — replace with a call to the real MNB model later.
// Looks for known urgency keywords in the text and picks the sector/priority
// of whichever matched keyword has the highest urgency_weight.
function classifyText(PDO $pdo, string $text): array
{
    $stmt = $pdo->query("SELECT keyword, sector, urgency_weight FROM tbl_urgency_keywords");
    $keywords = $stmt->fetchAll();

    $bestMatch = null;
    $matchedKeywords = [];
    $textLower = mb_strtolower($text);

    foreach ($keywords as $row) {
        if (mb_strpos($textLower, mb_strtolower($row['keyword'])) !== false) {
            $matchedKeywords[] = $row['keyword'];
            if ($bestMatch === null || $row['urgency_weight'] > $bestMatch['urgency_weight']) {
                $bestMatch = $row;
            }
        }
    }

    if ($bestMatch === null) {
        // No known keyword matched — fall back to a low-priority Infrastructure default
        return [
            'sector'   => 'Infrastructure',
            'priority' => 'Low',
            'keywords' => ''
        ];
    }

    $priorityMap = [3 => 'High', 2 => 'Medium', 1 => 'Low'];

    return [
        'sector'   => $bestMatch['sector'],
        'priority' => $priorityMap[$bestMatch['urgency_weight']] ?? 'Low',
        'keywords' => implode(', ', array_unique($matchedKeywords))
    ];
}

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

    $classification = classifyWithModel($concernText) ?? classifyText($pdo, $concernText);

    $stmt = $pdo->prepare(
        "INSERT INTO tbl_concerns (barangay_id, raw_text, extracted_keywords, sector, confidence_score, priority_level, status)
        VALUES (?, ?, ?, ?, ?, ?, 'ongoing')"
    );
    
    $stmt->execute([
        $barangayId,
        $concernText,
        $classification['keywords'],
        $classification['sector'],
        $classification['confidence'] ?? null,
        $classification['priority']
    ]);

    $response['success'] = true;
    $response['message'] = 'Your concern has been submitted.';
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Something went wrong. Please try again.';
    // error_log($e->getMessage());
}

echo json_encode($response);
