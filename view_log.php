<?php
/**
 * ManyPing - Log Viewer
 * Displays results from a stored log file
 */

// Security configuration
define('MANYPING_SECURITY', true);
require_once __DIR__ . '/security_config.php';

// Initialize secure session
initSecureSession();

// Set security headers
setSecurityHeaders();

// Get session ID from query parameter
$sessionId = isset($_GET['session']) ? $_GET['session'] : '';

// Validate session ID (alphanumeric, dash, underscore only)
if (empty($sessionId)) {
    logSecurityEvent('LOG_ACCESS_DENIED', 'No session ID provided');
    die('Error: No session ID provided');
}

$sessionId = sanitizeSessionId($sessionId);
if ($sessionId === false) {
    logSecurityEvent('LOG_ACCESS_DENIED', 'Invalid session ID format: ' . $_GET['session']);
    die('Error: Invalid session ID format');
}

$logFile = __DIR__ . '/logs/' . $sessionId . '.log';

// Prevent directory traversal
$logsDir = realpath(__DIR__ . '/logs');
$requestedFile = realpath($logFile);

if ($requestedFile === false || strpos($requestedFile, $logsDir) !== 0) {
    logSecurityEvent('PATH_TRAVERSAL', 'Attempted path traversal: ' . $sessionId);
    die('Error: Invalid log file path');
}

if (!file_exists($logFile)) {
    die('Log file not found');
}

// Read log file
$logContents = file_get_contents($logFile);
$lines = explode("\n", trim($logContents));
$results = [];

foreach ($lines as $line) {
    if (!empty($line)) {
        $result = json_decode($line, true);
        if ($result) {
            $results[] = $result;
        }
    }
}

// Group results by IP address to show history
$ipGroups = [];
foreach ($results as $result) {
    $ip = $result['ip'];
    if (!isset($ipGroups[$ip])) {
        $ipGroups[$ip] = [
            'ip' => $ip,
            'name' => $result['name'] ?? '',
            'host_info' => $result['host_info'] ?? '',
            'history' => [],
            'online_count' => 0,
            'offline_count' => 0,
            'total_response' => 0,
            'response_count' => 0,
            'latest' => $result
        ];
    }
    
    $ipGroups[$ip]['history'][] = $result;
    $ipGroups[$ip]['latest'] = $result; // Keep track of latest result
    
    if (isset($result['online'])) {
        if ($result['online']) {
            $ipGroups[$ip]['online_count']++;
            if (isset($result['response_time']) && $result['response_time'] !== null) {
                $ipGroups[$ip]['total_response'] += $result['response_time'];
                $ipGroups[$ip]['response_count']++;
            }
        } else {
            $ipGroups[$ip]['offline_count']++;
        }
    }
}

// Calculate statistics
$totalCount = count($ipGroups);
$onlineCount = 0;
$offlineCount = 0;
$totalResponse = 0;
$responseCount = 0;

foreach ($ipGroups as $ipGroup) {
    if ($ipGroup['latest']['online']) {
        $onlineCount++;
    } else {
        $offlineCount++;
    }
    $totalResponse += $ipGroup['total_response'];
    $responseCount += $ipGroup['response_count'];
}

$avgResponse = $responseCount > 0 ? round($totalResponse / $responseCount, 2) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ManyPing - Session Log Viewer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0e1a;
            min-height: 100vh;
            padding: 20px;
            color: #e4e7eb;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: linear-gradient(135deg, #1a1f2e 0%, #151923 100%);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            padding: 30px;
            border: 1px solid rgba(0, 217, 255, 0.1);
        }
        h1 {
            color: #ffffff;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .session-info {
            color: #8b92a7;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(14, 18, 28, 0.6);
            border-radius: 8px;
            border: 1px solid rgba(0, 217, 255, 0.1);
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #00d9ff;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 12px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .status-card {
            background: linear-gradient(135deg, rgba(26, 31, 46, 0.8) 0%, rgba(21, 25, 35, 0.9) 100%);
            border: 1px solid rgba(75, 85, 99, 0.3);
            border-radius: 8px;
            padding: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            transition: left 0.3s ease;
        }
        .status-card:hover {
            box-shadow: 0 8px 24px rgba(0, 217, 255, 0.2);
            transform: translateY(-2px);
            border-color: rgba(0, 217, 255, 0.5);
        }
        .status-card.online {
            border-color: rgba(0, 255, 136, 0.4);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.1);
        }
        .status-card.online::before {
            background: linear-gradient(90deg, transparent, rgba(0, 255, 136, 0.6), transparent);
        }
        .status-card.offline {
            border-color: rgba(255, 51, 102, 0.4);
            box-shadow: 0 0 20px rgba(255, 51, 102, 0.1);
        }
        .status-card.offline::before {
            background: linear-gradient(90deg, transparent, rgba(255, 51, 102, 0.6), transparent);
        }
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .status-icon {
            font-size: 24px;
        }
        .ip-address {
            font-weight: 600;
            color: #00d9ff;
            font-family: 'Courier New', monospace;
            font-size: 15px;
        }
        .friendly-name {
            color: #9ca3af;
            font-size: 13px;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .status-info {
            font-size: 12px;
            color: #8b92a7;
            margin-top: 5px;
            font-family: monospace;
        }
        .response-time {
            font-weight: 600;
            color: #00ff88;
        }
        .timestamp {
            font-size: 10px;
            color: #6b7280;
            font-family: monospace;
        }
        .mini-chart-container {
            margin-top: 10px;
            height: 60px;
            background: transparent;
            border-radius: 0;
            padding: 0;
            border: none;
            margin-left: -10px;
            margin-right: -10px;
            margin-bottom: -10px;
        }
        .mini-chart-canvas {
            width: 100% !important;
            height: 60px !important;
        }
        .ping-stats {
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: rgba(0, 217, 255, 0.1);
            border: 1px solid rgba(0, 217, 255, 0.3);
            border-radius: 6px;
            color: #00d9ff;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        .back-link:hover {
            background: rgba(0, 217, 255, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Session Log Viewer</h1>
        <div class="session-info">
            Session ID: <strong><?php echo htmlspecialchars($sessionId); ?></strong>
        </div>
        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value"><?php echo $totalCount; ?></div>
                <div class="stat-label">Total IPs</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #28a745;"><?php echo $onlineCount; ?></div>
                <div class="stat-label">Online</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #dc3545;"><?php echo $offlineCount; ?></div>
                <div class="stat-label">Offline</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $avgResponse > 0 ? $avgResponse . ' ms' : '--'; ?></div>
                <div class="stat-label">Avg Response</div>
            </div>
        </div>
        
        <div class="status-grid">
            <?php foreach ($ipGroups as $ipGroup): 
                $latest = $ipGroup['latest'];
                $isOnline = $latest['online'];
                $cardClass = $isOnline ? 'online' : 'offline';
                $statusIcon = $isOnline ? '✅' : '❌';
                $statusText = $isOnline ? 'ONLINE' : 'OFFLINE';
                $statusColor = $isOnline ? '#28a745' : '#dc3545';
                
                // Calculate average response time for this IP
                $avgIpResponse = $ipGroup['response_count'] > 0 ? 
                    round($ipGroup['total_response'] / $ipGroup['response_count'], 2) : 0;
            ?>
            <div class="status-card <?php echo $cardClass; ?>" data-ip="<?php echo htmlspecialchars($ipGroup['ip']); ?>">
                <div class="status-header">
                    <span class="status-icon"><?php echo $statusIcon; ?></span>
                    <span style="color: <?php echo $statusColor; ?>; font-weight: 600;"><?php echo $statusText; ?></span>
                </div>
                <?php if (!empty($ipGroup['name'])): ?>
                    <div class="friendly-name"><?php echo htmlspecialchars($ipGroup['name']); ?></div>
                <?php endif; ?>
                <div class="ip-address"><?php echo htmlspecialchars($ipGroup['ip']); ?></div>
                <div class="status-info">
                    <?php if ($isOnline && isset($latest['response_time']) && $latest['response_time'] !== null): ?>
                        Response: <span class="response-time"><?php echo $latest['response_time']; ?> ms</span>
                    <?php elseif ($isOnline): ?>
                        Status: Online
                    <?php else: ?>
                        Status: Offline
                    <?php endif; ?>
                </div>
                <?php if ($avgIpResponse > 0): ?>
                    <div class="ping-stats">
                        Avg: <?php echo $avgIpResponse; ?> ms | 
                        ✓ <?php echo $ipGroup['online_count']; ?> / 
                        ✗ <?php echo $ipGroup['offline_count']; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($ipGroup['host_info'])): ?>
                    <div class="timestamp"><?php echo htmlspecialchars($ipGroup['host_info']); ?></div>
                <?php endif; ?>
                <div class="timestamp">Last: <?php echo htmlspecialchars($latest['timestamp'] ?? ''); ?></div>
                
                <?php if ($isOnline && count($ipGroup['history']) > 1): ?>
                    <div class="mini-chart-container">
                        <canvas class="mini-chart-canvas" id="chart-<?php echo str_replace('.', '-', $ipGroup['ip']); ?>"></canvas>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <a href="index.php" class="back-link">← Back to ManyPing</a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Initialize mini charts for each IP
        const ipGroups = <?php echo json_encode($ipGroups); ?>;
        
        // Check if Chart.js is available
        if (typeof Chart !== 'undefined') {
            Object.keys(ipGroups).forEach(ip => {
                const ipGroup = ipGroups[ip];
                
                // Only create chart if IP is online and has history
                if (!ipGroup.latest.online || ipGroup.history.length <= 1) {
                    return;
                }
                
                // Extract response times from history (only for online pings)
                const responseTimes = ipGroup.history
                    .filter(h => h.online && h.response_time !== null)
                    .map(h => h.response_time);
                
                if (responseTimes.length === 0) {
                    return;
                }
                
                const canvasId = 'chart-' + ip.replace(/\./g, '-');
                const canvas = document.getElementById(canvasId);
                
                if (!canvas) {
                    return;
                }
                
                const ctx = canvas.getContext('2d');
                
                // Create gradient for background (using green color for online status)
                const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
                gradient.addColorStop(0, 'rgba(0, 255, 136, 0)');
                gradient.addColorStop(1, 'rgba(0, 255, 136, 0.2)');
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: responseTimes.map((_, i) => ''),
                        datasets: [{
                            data: responseTimes,
                            borderColor: 'rgb(0, 255, 136)',
                            backgroundColor: gradient,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 0,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                left: 0,
                                right: 0,
                                top: 0,
                                bottom: 0
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: true,
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 8,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + 'ms';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                display: false,
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                display: false,
                                grid: {
                                    display: false
                                },
                                beginAtZero: true
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            });
        }
    </script>
</body>
</html>
