<?php
/**
 * Clear all captured data
 */
$logDir = __DIR__ . '/logs';
$dataDir = $logDir . '/captures';

if (is_dir($dataDir)) {
    $files = glob($dataDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
    }
}

$ipFile = $logDir . '/ip_log.txt';
if (file_exists($ipFile)) unlink($ipFile);

echo json_encode(['status' => 'cleared']);
