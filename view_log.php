<?php
/**
 * ManyPing - Log Viewer
 * Displays results from a stored log file
 */

// Get session ID from query parameter
$sessionId = isset($_GET['session']) ? $_GET['session'] : '';

// Validate session ID (alphanumeric only)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $sessionId)) {
    die('Invalid session ID');
}

$logFile = __DIR__ . '/logs/' . $sessionId . '.log';

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

// Calculate statistics
$totalCount = count($results);
$onlineCount = 0;
$offlineCount = 0;
$totalResponse = 0;
$responseCount = 0;

foreach ($results as $result) {
    if (isset($result['online'])) {
        if ($result['online']) {
            $onlineCount++;
            if (isset($result['response_time']) && $result['response_time'] !== null) {
                $totalResponse += $result['response_time'];
                $responseCount++;
            }
        } else {
            $offlineCount++;
        }
    }
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
        .results-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(14, 18, 28, 0.6);
            border-radius: 8px;
            overflow: hidden;
        }
        .results-table th {
            background: rgba(0, 217, 255, 0.1);
            padding: 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #00d9ff;
        }
        .results-table td {
            padding: 12px;
            border-top: 1px solid rgba(0, 217, 255, 0.1);
            font-size: 13px;
        }
        .results-table tr:hover {
            background: rgba(0, 217, 255, 0.05);
        }
        .status-online {
            color: #28a745;
            font-weight: 600;
        }
        .status-offline {
            color: #dc3545;
            font-weight: 600;
        }
        .status-skipped {
            color: #ff9500;
            font-weight: 600;
        }
        .ip-address {
            font-family: 'Courier New', monospace;
            color: #00d9ff;
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
        
        <table class="results-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>IP Address</th>
                    <th>Name</th>
                    <th>Response Time</th>
                    <th>Host Info</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                <tr>
                    <td>
                        <?php 
                        if (isset($result['skipped']) && $result['skipped']) {
                            echo '<span class="status-skipped">⏩ SKIPPED</span>';
                        } elseif ($result['online']) {
                            echo '<span class="status-online">✅ ONLINE</span>';
                        } else {
                            echo '<span class="status-offline">❌ OFFLINE</span>';
                        }
                        ?>
                    </td>
                    <td class="ip-address"><?php echo htmlspecialchars($result['ip']); ?></td>
                    <td><?php echo htmlspecialchars($result['name'] ?? ''); ?></td>
                    <td><?php echo $result['response_time'] ? $result['response_time'] . ' ms' : '--'; ?></td>
                    <td><?php echo htmlspecialchars($result['host_info'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($result['timestamp'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <a href="index.php" class="back-link">← Back to ManyPing</a>
    </div>
</body>
</html>
