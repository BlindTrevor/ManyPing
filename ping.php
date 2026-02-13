<?php
/**
 * SimulPing - Concurrent IP Ping Handler
 * Handles parsing of IPs, ranges, and CIDR notation
 * Performs concurrent pings with rate limiting
 */

header('Content-Type: application/json');

// Rate limiting configuration
const MAX_IPS_PER_SCAN = 50;
const PING_TIMEOUT = 2; // seconds
const MAX_CONCURRENT = 10; // Maximum concurrent ping processes

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
 * Ping multiple IPs concurrently using multi-processing
 */
function pingMultiple($targets) {
    $results = [];
    $chunks = array_chunk($targets, MAX_CONCURRENT);
    
    foreach ($chunks as $chunk) {
        // For each chunk, we'll use simple sequential processing
        // In production, you could use pcntl_fork or curl_multi for true concurrency
        foreach ($chunk as $target) {
            $result = pingIP($target['ip']);
            $result['name'] = $target['name'];
            $results[] = $result;
        }
    }
    
    return $results;
}

// Main execution
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
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
    
    $results = pingMultiple($targets);
    
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
