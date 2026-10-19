<?php
// Runs the Python model (ml_engine/predict.py) on a concern's text.
// Used by submit-concern.php and the legacy re-classification script.

const CONCERN_SECTORS    = ['Health', 'Infrastructure', 'Crime', 'Environment'];
const CONCERN_PRIORITIES = ['High', 'Medium', 'Low'];

// Returns ['sector', 'confidence', 'priority', 'keywords' (string)] or null if the
// engine could not run or returned something unusable.
function classifyWithModel(string $text): ?array
{
    $python = defined('PYTHON_BIN') ? PYTHON_BIN : 'python';
    $script = __DIR__ . '/../ml_engine/predict.py';

    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($text);
    $output = shell_exec($cmd);
    $data = json_decode($output ?? '', true);

    if (!is_array($data) || isset($data['error'])
        || !in_array($data['sector'] ?? null, CONCERN_SECTORS, true)
        || !in_array($data['priority'] ?? null, CONCERN_PRIORITIES, true)) {
        return null;
    }

    $keywords = $data['keywords'] ?? '';
    if (is_array($keywords)) {
        $keywords = implode(', ', $keywords);
    }

    return [
        'sector'     => $data['sector'],
        'priority'   => $data['priority'],
        'confidence' => isset($data['confidence']) ? max(0, min(1, (float) $data['confidence'])) : null,
        'keywords'   => mb_substr((string) $keywords, 0, 500),
    ];
}
