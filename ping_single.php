<?php
/**
 * ManyPing - Single IP Ping Handler
 * Handles individual ping requests for staggered execution
 * Supports streaming and session logging
 */

// Configure PHP execution and session settings
ini_set('max_execution_time', '30');
ini_set('output_buffering', 'Off');
ini_set('implicit_flush', '1');
ob_implicit_flush(true);

// Configure session
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start();

// Check session age
if (isset($_SESSION['created']) && (time() - $_SESSION['created'] > 3600)) {
    session_unset();
    session_destroy();
    session_start();
}
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
}

header('Content-Type: application/json');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');
header('Keep-Alive: timeout=30, max=1');

const PING_TIMEOUT = 1; // seconds

/**
 * Ping a single IP address
 */
function pingIP($ip) {
    $result = [
        'ip' => $ip,
        'online' => false,
        'response_time' => null,
        'host_info' => null,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Determine OS and appropriate ping command
    $os = strtoupper(substr(PHP_OS, 0, 3));
    
    if ($os === 'WIN') {
        $command = sprintf('ping -n 1 -w %d %s', PING_TIMEOUT * 1000, escapeshellarg($ip));
    } else {
        $command = sprintf('ping -c 1 -W %d %s 2>&1', PING_TIMEOUT, escapeshellarg($ip));
    }
    
    $startTime = microtime(true);
    exec($command, $output, $returnCode);
    $endTime = microtime(true);
    
    $outputStr = implode("\n", $output);
    
    // Check if ping was successful
    if ($returnCode === 0) {
        $result['online'] = true;
        
        // Calculate response time
        $responseTime = ($endTime - $startTime) * 1000;
        
        // Try to extract more accurate time from ping output
        if ($os === 'WIN') {
            if (preg_match('/time[=<](\d+)ms/i', $outputStr, $matches)) {
                $responseTime = (float)$matches[1];
            }
        } else {
            if (preg_match('/time=([\d.]+)\s*ms/i', $outputStr, $matches)) {
                $responseTime = (float)$matches[1];
            }
        }
        
        $result['response_time'] = round($responseTime, 2);
        
        // Try to get hostname
        $hostname = @gethostbyaddr($ip);
        if ($hostname !== $ip) {
            $result['host_info'] = $hostname;
        }
    }
    
    return $result;
}

/**
 * Log ping result to session log
 */
function logResult($sessionId, $result) {
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        if (!mkdir($logDir, 0750, true)) {
            error_log("Failed to create logs directory");
            return false;
        }
    }
    
    $logFile = $logDir . '/' . $sessionId . '.log';
    $logEntry = json_encode($result) . "\n";
    if (file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
        error_log("Failed to write to log file: $logFile");
        return false;
    }
    return true;
}

// Main execution
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    if (!isset($_POST['ip']) || empty($_POST['ip'])) {
        throw new Exception('No IP provided');
    }
    
    $ip = trim($_POST['ip']);
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $sessionId = isset($_POST['session_id']) ? trim($_POST['session_id']) : '';
    
    // Validate IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        throw new Exception('Invalid IP address');
    }
    
    // Check if this IP timed out before (stored in session)
    if (!empty($sessionId)) {
        $timedOutKey = 'timed_out_' . $sessionId;
        if (!isset($_SESSION[$timedOutKey])) {
            $_SESSION[$timedOutKey] = [];
        }
        
        if (in_array($ip, $_SESSION[$timedOutKey])) {
            // Return skipped status for timed out IPs
            echo json_encode([
                'success' => true,
                'result' => [
                    'ip' => $ip,
                    'name' => $name,
                    'online' => false,
                    'response_time' => null,
                    'host_info' => null,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'skipped' => true,
                    'reason' => 'Previously timed out'
                ]
            ]);
            exit;
        }
    }
    
    $result = pingIP($ip);
    $result['name'] = $name;
    
    // If result indicates timeout (not online after PING_TIMEOUT), mark it
    if (!$result['online']) {
        if (!empty($sessionId)) {
            $timedOutKey = 'timed_out_' . $sessionId;
            $_SESSION[$timedOutKey][] = $ip;
        }
    }
    
    // Log result if session ID provided
    if (!empty($sessionId)) {
        logResult($sessionId, $result);
    }
    
    echo json_encode([
        'success' => true,
        'result' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
