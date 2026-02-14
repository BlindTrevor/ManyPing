<?php
/**
 * ManyPing - Concurrent IP Ping Handler
 * Handles parsing of IPs, ranges, and CIDR notation
 * Performs concurrent pings with rate limiting
 * Uses sessions for rate limiting - no persistent storage
 */

// Configure PHP execution and session settings
// Set execution time limit to allow for long-running scans
// MAX_IPS_PER_SCAN (50) * PING_TIMEOUT (1s) = 50s minimum + overhead
ini_set('max_execution_time', '120'); // Allow up to 2 minutes for scan completion

// Disable output buffering to prevent early timeouts
// This ensures the script can run without proxy timeouts
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
ini_set('output_buffering', 'Off');
ini_set('implicit_flush', '1');
ob_implicit_flush(true);

// Configure session to expire after 60 minutes of inactivity
ini_set('session.gc_maxlifetime', 3600); // 60 minutes
session_set_cookie_params(3600); // Cookie expires in 60 minutes
session_start();

// Check session age and clear if needed
if (isset($_SESSION['created']) && (time() - $_SESSION['created'] > 3600)) {
    session_unset();
    session_destroy();
    session_start();
}
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
}

header('Content-Type: application/json');

// Set headers to prevent proxy timeouts
// Inform proxies that this is a long-running request
header('X-Accel-Buffering: no'); // Disable nginx buffering
header('Connection: keep-alive'); // Keep connection alive during processing
// Set a reasonable timeout expectation for proxies (120 seconds)
header('Keep-Alive: timeout=120, max=1');

// Rate limiting configuration
const MAX_IPS_PER_SCAN = 50;
const PING_TIMEOUT = 1; // seconds
const MAX_CONCURRENT = 10; // Maximum concurrent ping processes
const MIN_SCAN_INTERVAL = 5; // Minimum seconds between scans
const FLUSH_INTERVAL = 5; // Flush output every N results to keep connection alive

/**
 * Parse input and extract IPs with optional names
 */
function parseInput($input) {
    $lines = explode("\n", trim($input));
    $targets = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        // Split IP/range and name
        $parts = preg_split('/\s+/', $line, 2);
        $ipPart = $parts[0];
        $name = isset($parts[1]) ? trim($parts[1]) : '';
        
        // Check if it's a CIDR notation
        if (strpos($ipPart, '/') !== false) {
            $ips = expandCIDR($ipPart);
            foreach ($ips as $ip) {
                $targets[] = ['ip' => $ip, 'name' => $name];
            }
        }
        // Check if it's a range
        elseif (preg_match('/^(\d+\.\d+\.\d+\.)(\d+)-(\d+)$/', $ipPart, $matches)) {
            $base = $matches[1];
            $start = (int)$matches[2];
            $end = (int)$matches[3];
            
            for ($i = $start; $i <= $end; $i++) {
                $targets[] = ['ip' => $base . $i, 'name' => $name];
            }
        }
        // Single IP
        else {
            if (filter_var($ipPart, FILTER_VALIDATE_IP)) {
                $targets[] = ['ip' => $ipPart, 'name' => $name];
            }
        }
    }
    
    return $targets;
}

/**
 * Expand CIDR notation to individual IPs
 * Limits to reasonable subnet sizes to prevent abuse
 */
function expandCIDR($cidr) {
    list($ip, $prefix) = explode('/', $cidr);
    
    // Limit to /24 or smaller (max 256 IPs) to prevent abuse
    if ($prefix < 24) {
        $prefix = 24;
    }
    
    $ip_long = ip2long($ip);
    if ($ip_long === false) {
        return [];
    }
    
    $mask = -1 << (32 - $prefix);
    $network = $ip_long & $mask;
    $broadcast = $network | ~$mask;
    
    $ips = [];
    for ($i = $network + 1; $i < $broadcast && count($ips) < MAX_IPS_PER_SCAN; $i++) {
        $ips[] = long2ip($i);
    }
    
    return $ips;
}

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
        // Windows
        $command = sprintf('ping -n 1 -w %d %s', PING_TIMEOUT * 1000, escapeshellarg($ip));
    } else {
        // Linux/Unix/Mac
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
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Try to extract more accurate time from ping output
        if ($os === 'WIN') {
            // Windows format: time=XXms or time<1ms
            if (preg_match('/time[=<](\d+)ms/i', $outputStr, $matches)) {
                $responseTime = (float)$matches[1];
            }
        } else {
            // Linux format: time=XX.X ms
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
 * Ping multiple IPs in batches
 * Processes IPs sequentially in chunks to manage resource usage
 */
function pingInBatches($targets) {
    $results = [];
    $chunks = array_chunk($targets, MAX_CONCURRENT);
    
    foreach ($chunks as $chunk) {
        // Process each batch sequentially
        // For production with true concurrency, consider using pcntl_fork or parallel processing
        foreach ($chunk as $target) {
            $result = pingIP($target['ip']);
            $result['name'] = $target['name'];
            $results[] = $result;
            
            // Flush output periodically to keep connection alive and prevent proxy timeouts
            // This sends data to the web server, preventing Apache/Nginx from timing out
            if (count($results) > 0 && count($results) % FLUSH_INTERVAL == 0) {
                flush();
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
            }
        }
    }
    
    return $results;
}

// Main execution
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Server-side rate limiting using sessions
    $currentTime = time();
    if (isset($_SESSION['last_scan_time'])) {
        $timeSinceLastScan = $currentTime - $_SESSION['last_scan_time'];
        if ($timeSinceLastScan < MIN_SCAN_INTERVAL) {
            $waitTime = MIN_SCAN_INTERVAL - $timeSinceLastScan;
            throw new Exception("Rate limit: Please wait {$waitTime} seconds before next scan");
        }
    }
    
    if (!isset($_POST['ips']) || empty($_POST['ips'])) {
        throw new Exception('No IPs provided');
    }
    
    $input = $_POST['ips'];
    $targets = parseInput($input);
    
    if (empty($targets)) {
        throw new Exception('No valid IPs found in input');
    }
    
    // Apply rate limiting
    if (count($targets) > MAX_IPS_PER_SCAN) {
        $targets = array_slice($targets, 0, MAX_IPS_PER_SCAN);
    }
    
    // Update last scan time in session
    $_SESSION['last_scan_time'] = $currentTime;
    
    $results = pingInBatches($targets);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
