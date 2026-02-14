<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ManyPing - Concurrent IP Monitor</title>
    <link rel="icon" type="image/png" id="favicon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='40' fill='%2300d9ff'/></svg>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0e1a;
            background-image: 
                repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0, 217, 255, 0.03) 2px, rgba(0, 217, 255, 0.03) 4px),
                repeating-linear-gradient(90deg, transparent, transparent 2px, rgba(0, 217, 255, 0.03) 2px, rgba(0, 217, 255, 0.03) 4px);
            min-height: 100vh;
            padding: 20px;
            color: #e4e7eb;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: linear-gradient(135deg, #1a1f2e 0%, #151923 100%);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 1px rgba(0, 217, 255, 0.3);
            padding: 30px;
            border: 1px solid rgba(0, 217, 255, 0.1);
        }
        h1 {
            color: #ffffff;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .logo {
            width: 40px;
            height: 40px;
        }
        .subtitle {
            color: #8b92a7;
            margin-bottom: 30px;
            font-size: 14px;
            font-weight: 500;
        }
        .input-section {
            background: rgba(14, 18, 28, 0.6);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 217, 255, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #e4e7eb;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0, 217, 255, 0.2);
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            resize: vertical;
            min-height: 150px;
            background: rgba(10, 14, 26, 0.8);
            color: #00ff88;
        }
        textarea:focus {
            outline: none;
            border-color: #00d9ff;
            box-shadow: 0 0 0 3px rgba(0, 217, 255, 0.1);
        }
        textarea::placeholder {
            color: #4a5568;
        }
        .help-text {
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
            font-family: monospace;
        }
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        button {
            padding: 14px 28px;
            border: 2px solid transparent;
            border-radius: 4px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-family: 'Courier New', monospace;
            position: relative;
            overflow: hidden;
        }
        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        button:hover::before {
            left: 100%;
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        button:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        button:disabled::before {
            display: none;
        }
        .btn-toggle {
            background: linear-gradient(135deg, #00d9ff 0%, #0099cc 100%);
            color: #0a0e1a;
            box-shadow: 0 0 20px rgba(0, 217, 255, 0.4), inset 0 0 10px rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(0, 217, 255, 0.6);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        .btn-toggle:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(0, 217, 255, 0.6), inset 0 0 15px rgba(255, 255, 255, 0.2);
            border-color: rgba(0, 217, 255, 1);
        }
        .btn-toggle.scanning {
            background: linear-gradient(135deg, #ff3366 0%, #cc0033 100%);
            box-shadow: 0 0 20px rgba(255, 51, 102, 0.5), inset 0 0 10px rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 51, 102, 0.8);
            animation: pulse-button 1.5s ease-in-out infinite;
            position: relative;
            overflow: hidden;
            --progress: 0%;
        }
        .btn-toggle.scanning::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.5);
            width: var(--progress, 0%);
            transition: none;
        }
        .btn-toggle.scanning:hover:not(:disabled) {
            box-shadow: 0 0 30px rgba(255, 51, 102, 0.7), inset 0 0 15px rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 51, 102, 1);
        }
        @keyframes pulse-button {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.85; }
        }
        .btn-danger {
            background: linear-gradient(135deg, rgba(75, 85, 99, 0.9) 0%, rgba(55, 65, 81, 0.9) 100%);
            color: #e4e7eb;
            border: 2px solid rgba(156, 163, 175, 0.4);
            box-shadow: 0 0 10px rgba(75, 85, 99, 0.3);
        }
        .btn-danger:hover:not(:disabled) {
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.95) 0%, rgba(75, 85, 99, 0.95) 100%);
            border-color: rgba(156, 163, 175, 0.6);
            transform: translateY(-1px);
            box-shadow: 0 0 15px rgba(75, 85, 99, 0.4);
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            background: rgba(0, 217, 255, 0.1);
            border: 1px solid rgba(0, 217, 255, 0.3);
            color: #00d9ff;
            animation: fade-in 0.3s ease-in-out;
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .status-indicator.scanning {
            background: rgba(0, 255, 136, 0.1);
            border-color: rgba(0, 255, 136, 0.3);
            color: #00ff88;
        }
        .status-indicator.complete {
            background: rgba(0, 217, 255, 0.15);
            border-color: rgba(0, 217, 255, 0.4);
            color: #00d9ff;
        }
        .status-indicator.error {
            background: rgba(255, 51, 102, 0.1);
            border-color: rgba(255, 51, 102, 0.3);
            color: #ff3366;
        }
        .status-indicator .spinner-small {
            width: 14px;
            height: 14px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        textarea:read-only {
            background: rgba(10, 14, 26, 0.6);
            cursor: not-allowed;
            opacity: 0.7;
        }
        }
        .scan-mode {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .scan-mode label {
            margin-bottom: 0;
            font-weight: normal;
            text-transform: none;
            color: #9ca3af;
        }
        .scan-mode input[type="radio"] {
            margin-right: 5px;
            accent-color: #00d9ff;
        }
        .results-section {
            margin-top: 30px;
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
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(0, 217, 255, 0.5), transparent);
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
        .status-card.scanning {
            border-color: rgba(0, 217, 255, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }
        .status-card.scanning::before {
            background: linear-gradient(90deg, transparent, rgba(0, 217, 255, 0.8), transparent);
            animation: scan-line 2s linear infinite;
        }
        .status-card.timeout {
            border-color: rgba(255, 165, 0, 0.4);
            box-shadow: 0 0 20px rgba(255, 165, 0, 0.1);
        }
        .status-card.timeout::before {
            background: linear-gradient(90deg, transparent, rgba(255, 165, 0, 0.6), transparent);
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        @keyframes scan-line {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
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
        .chart-container {
            margin-top: 30px;
            background: rgba(14, 18, 28, 0.6);
            padding: 20px;
            border-radius: 8px;
            display: none;
            border: 1px solid rgba(0, 217, 255, 0.1);
        }
        canvas {
            max-height: 400px;
        }
        .countdown-timer {
            text-align: center;
            padding: 20px;
            color: #00d9ff;
            font-size: 18px;
            font-weight: 600;
            display: none;
            font-family: monospace;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #8b92a7;
        }
        .spinner {
            border: 3px solid rgba(75, 85, 99, 0.3);
            border-top: 3px solid #00d9ff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .rate-limit-info {
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #fbbf24;
        }
        .stats-bar {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: rgba(14, 18, 28, 0.6);
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 217, 255, 0.1);
        }
        .stat-item {
            flex: 1;
            text-align: center;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #00d9ff;
            font-family: monospace;
        }
        .stat-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        .interval-control {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .interval-control input {
            width: 80px;
            padding: 8px;
            border: 1px solid rgba(0, 217, 255, 0.2);
            border-radius: 5px;
            background: rgba(10, 14, 26, 0.8);
            color: #00ff88;
            font-family: monospace;
        }
        .interval-control input:focus {
            outline: none;
            border-color: #00d9ff;
        }
        input[type="number"] {
            background: rgba(10, 14, 26, 0.8);
            color: #00ff88;
            border: 1px solid rgba(0, 217, 255, 0.2);
            border-radius: 5px;
            padding: 8px;
            font-family: monospace;
        }
        input[type="number"]:focus {
            outline: none;
            border-color: #00d9ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <svg class="logo" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Network nodes -->
                <circle cx="50" cy="50" r="8" fill="#00d9ff"/>
                <circle cx="25" cy="25" r="6" fill="#00ff88"/>
                <circle cx="75" cy="25" r="6" fill="#00ff88"/>
                <circle cx="25" cy="75" r="6" fill="#00ff88"/>
                <circle cx="75" cy="75" r="6" fill="#00ff88"/>
                
                <!-- Connection lines -->
                <line x1="50" y1="50" x2="25" y2="25" stroke="#00d9ff" stroke-width="2" opacity="0.6"/>
                <line x1="50" y1="50" x2="75" y2="25" stroke="#00d9ff" stroke-width="2" opacity="0.6"/>
                <line x1="50" y1="50" x2="25" y2="75" stroke="#00d9ff" stroke-width="2" opacity="0.6"/>
                <line x1="50" y1="50" x2="75" y2="75" stroke="#00d9ff" stroke-width="2" opacity="0.6"/>
                
                <!-- Ping waves (animated in concept) -->
                <circle cx="50" cy="50" r="20" stroke="#00d9ff" stroke-width="1.5" fill="none" opacity="0.4"/>
                <circle cx="50" cy="50" r="30" stroke="#00d9ff" stroke-width="1" fill="none" opacity="0.2"/>
                <circle cx="50" cy="50" r="40" stroke="#00d9ff" stroke-width="0.5" fill="none" opacity="0.1"/>
            </svg>
            ManyPing
        </h1>
        <p class="subtitle">Concurrent IP Monitoring Tool - Ping multiple IPs simultaneously</p>
        
        <div class="input-section">
            <div class="form-group">
                <label for="ipInput">Enter IPs, IP Ranges, or CIDR Notations</label>
                <textarea id="ipInput" placeholder="Examples:
192.168.1.1 Home Router
192.168.1.10-20 DHCP Range
192.168.1.0/24 Local Network
8.8.8.8 Google DNS
1.1.1.1 Cloudflare DNS

Format: IP_or_Range FriendlyName (optional, one per line)"></textarea>
                <div class="help-text">
                    Supports: Single IPs (192.168.1.1), Ranges (192.168.1.1-10), CIDR (192.168.1.0/24)
                </div>
            </div>
            
            <div class="rate-limit-info">
                ⚠️ Rate Limiting: Max 50 IPs per scan, 5-second minimum interval between scans to prevent network abuse
            </div>
            
            <div class="form-group">
                <label>Scan Mode</label>
                <div class="scan-mode">
                    <label>
                        <input type="radio" name="scanMode" value="once" checked>
                        One-time Scan
                    </label>
                    <label>
                        <input type="radio" name="scanMode" value="repeat">
                        Repeat Every
                    </label>
                    <div class="interval-control">
                        <input type="number" id="scanInterval" value="10" min="5" max="300">
                        <span>seconds</span>
                    </div>
                </div>
                
                <div style="margin-top:10px;">
                    <label style="font-size: 14px; font-weight: normal;">Stagger Interval (delay between each ping)</label>
                    <div class="scan-mode">
                        <div class="interval-control">
                            <input type="number" id="staggerInterval" value="0" min="0" max="10" step="0.1">
                            <span>seconds</span>
                        </div>
                    </div>
                    <div class="help-text">Set to 0 for simultaneous pings, or a delay (e.g., 0.5) to stagger pings</div>
                </div>
                
                <div id="repeatOptions" style="display:none; margin-top:10px;">
                    <label style="font-size: 14px; font-weight: normal;">Number of Scans</label>
                    <div class="scan-mode">
                        <label>
                            <input type="radio" name="scanCount" value="continuous" checked>
                            Continuous
                        </label>
                        <label>
                            <input type="radio" name="scanCount" value="limited">
                            Limited to
                        </label>
                        <div class="interval-control">
                            <input type="number" id="scanCountInput" value="10" min="1" max="1000" disabled>
                            <span>scans</span>
                        </div>
                    </div>
                    <div id="etaDisplay" style="margin-top:5px; font-size:12px; color:#667eea;"></div>
                </div>
            </div>
            
            <div class="button-group">
                <button id="toggleBtn" class="btn-toggle" onclick="toggleScan()">[ >_ EXECUTE SCAN ]</button>
                <button class="btn-danger" onclick="clearResults()">[ ✕ CLEAR ]</button>
                <div id="statusIndicator" style="display: none;"></div>
            </div>
            
            <div id="logLinkContainer" style="display:none; margin-top:10px; padding:10px; background:rgba(0,217,255,0.1); border-radius:6px; border:1px solid rgba(0,217,255,0.3);">
                <span style="color:#00d9ff; font-weight:600;">📋 Session Log:</span>
                <a id="logLink" href="#" target="_blank" style="color:#00ff88; margin-left:10px;">View Results</a>
            </div>
        </div>
        
        <div class="results-section" id="resultsSection" style="display:none;">
            <h2>Results</h2>
            
            <div class="stats-bar" id="statsBar">
                <div class="stat-item">
                    <div class="stat-value" id="totalCount">0</div>
                    <div class="stat-label">Total IPs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="onlineCount" style="color: #28a745;">0</div>
                    <div class="stat-label">Online</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="offlineCount" style="color: #dc3545;">0</div>
                    <div class="stat-label">Offline</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="avgResponse">--</div>
                    <div class="stat-label">Avg Response</div>
                </div>
            </div>
            
            <div class="status-grid" id="statusGrid"></div>
            
            <div class="chart-container" id="chartContainer">
                <h3>Response Time History</h3>
                <canvas id="responseChart"></canvas>
                <div id="chartUnavailable" style="display:none; padding: 20px; text-align: center; color: #666;">
                    📊 Chart visualization unavailable (Chart.js library not loaded)
                </div>
            </div>
        </div>
        

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Global state variables
        let scanInterval = null;
        let countdownInterval = null;
        let responseChart = null;
        let historyData = {};
        let miniCharts = {};
        let lastScanTime = 0;
        let isFirstScan = true;
        let nextScanTime = null;
        let completedScans = 0;
        let totalScans = 0;
        let scanStartTime = null;
        let progressInterval = null;
        const RATE_LIMIT_MS = 5000;
        let scanningTiles = {};
        const SCAN_TIMEOUT_MS = 10000;
        let currentSessionId = null;
        let pingTimings = []; // Track ping durations for ETA calculation
        let timedOutIPs = new Set(); // Track IPs that timed out
        let isScanning = false;
        
        // Favicon management
        function updateFavicon(status) {
            const favicon = document.getElementById('favicon');
            if (status === 'running') {
                // Red favicon for running
                favicon.href = "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='40' fill='%23dc3545'/></svg>";
            } else if (status === 'complete') {
                // Green favicon for complete
                favicon.href = "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='40' fill='%2328a745'/></svg>";
            } else {
                // Blue favicon for idle
                favicon.href = "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='40' fill='%2300d9ff'/></svg>";
            }
        }
        
        // Generate unique session ID
        function generateSessionId() {
            return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }
        
        // Show log link
        function showLogLink(sessionId) {
            const container = document.getElementById('logLinkContainer');
            const link = document.getElementById('logLink');
            link.href = 'view_log.php?session=' + sessionId;
            container.style.display = 'block';
        }

        // Initialize on load
        window.addEventListener('load', function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not available');
            }
            
            // Set up event listeners
            document.querySelectorAll('input[name="scanMode"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const repeatOptions = document.getElementById('repeatOptions');
                    if (this.value === 'repeat') {
                        repeatOptions.style.display = 'block';
                        updateETA();
                    } else {
                        repeatOptions.style.display = 'none';
                    }
                });
            });
            
            document.querySelectorAll('input[name="scanCount"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const countInput = document.getElementById('scanCountInput');
                    if (this.value === 'limited') {
                        countInput.disabled = false;
                    } else {
                        countInput.disabled = true;
                    }
                    updateETA();
                });
            });
            
            document.getElementById('scanInterval').addEventListener('input', updateETA);
            document.getElementById('scanCountInput').addEventListener('input', updateETA);
            document.getElementById('staggerInterval').addEventListener('input', updateETA);
        });
        
        function updateETA() {
            const scanMode = document.querySelector('input[name="scanMode"]:checked').value;
            if (scanMode !== 'repeat') return;
            
            const scanCountMode = document.querySelector('input[name="scanCount"]:checked').value;
            const etaDisplay = document.getElementById('etaDisplay');
            
            if (scanCountMode === 'continuous') {
                etaDisplay.textContent = '⏱️ ETA: Continuous scanning (no end time)';
            } else {
                const scans = parseInt(document.getElementById('scanCountInput').value);
                const interval = Math.max(5, parseInt(document.getElementById('scanInterval').value));
                const staggerInterval = parseFloat(document.getElementById('staggerInterval').value) || 0;
                
                // Calculate average ping time
                let avgPingTime = 2; // Default estimate
                if (pingTimings.length > 0) {
                    avgPingTime = pingTimings.reduce((a, b) => a + b, 0) / pingTimings.length;
                }
                
                // Estimate time per scan based on number of IPs
                const ipInput = document.getElementById('ipInput').value.trim();
                const ips = parseIPInput(ipInput);
                const timePerScan = (ips.length * (avgPingTime + staggerInterval));
                
                if (completedScans > 0 && totalScans > 0) {
                    const remaining = totalScans - completedScans;
                    const remainingSeconds = Math.ceil(remaining * (timePerScan + interval));
                    const remMinutes = Math.floor(remainingSeconds / 60);
                    const remSeconds = remainingSeconds % 60;
                    etaDisplay.textContent = `⏱️ ETA: ${remMinutes}m ${remSeconds}s remaining (${completedScans}/${totalScans} scans, avg ${avgPingTime.toFixed(2)}s/ping)`;
                } else {
                    const totalSeconds = Math.ceil(scans * (timePerScan + interval));
                    const minutes = Math.floor(totalSeconds / 60);
                    const seconds = totalSeconds % 60;
                    etaDisplay.textContent = `⏱️ ETA: ~${minutes}m ${seconds}s total for ${scans} scans`;
                }
            }
        }

        function setScanningState(scanning) {
            isScanning = scanning;
            const ipInput = document.getElementById('ipInput');
            const toggleBtn = document.getElementById('toggleBtn');
            const statusIndicator = document.getElementById('statusIndicator');
            
            if (scanning) {
                ipInput.readOnly = true;
                toggleBtn.classList.add('scanning');
                toggleBtn.innerHTML = '[ ■ TERMINATE ]';
                statusIndicator.className = 'status-indicator scanning';
                statusIndicator.innerHTML = '<div class="spinner-small"></div><span>Scan in progress</span>';
                statusIndicator.style.display = 'inline-flex';
                updateFavicon('running');
            } else {
                ipInput.readOnly = false;
                toggleBtn.classList.remove('scanning');
                toggleBtn.innerHTML = '[ >_ EXECUTE SCAN ]';
                statusIndicator.style.display = 'none';
                updateFavicon('idle');
            }
        }
        
        function toggleScan() {
            if (scanInterval || isScanning) {
                stopScan();
            } else {
                startScan();
            }
        }
        
        function stopScan() {
            if (scanInterval) {
                clearInterval(scanInterval);
                scanInterval = null;
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
            
            const toggleBtn = document.getElementById('toggleBtn');
            toggleBtn.style.removeProperty('--progress');
            
            isFirstScan = true;
            completedScans = 0;
            totalScans = 0;
            updateETA();
            setScanningState(false);
        }

        function clearResults() {
            stopScan();
            document.getElementById('resultsSection').style.display = 'none';
            document.getElementById('statusGrid').innerHTML = '';
            document.getElementById('logLinkContainer').style.display = 'none';
            historyData = {};
            scanningTiles = {};
            timedOutIPs.clear();
            pingTimings = [];
            if (responseChart) {
                responseChart.destroy();
                responseChart = null;
            }
        }

        function parseIPInput(input) {
            const lines = input.split('\n');
            const ips = [];
            const MAX_IPS = 50;
            
            for (let line of lines) {
                line = line.trim();
                if (!line || line.startsWith('#')) continue;
                
                const parts = line.split(/\s+/);
                const ipPart = parts[0];
                const name = parts.slice(1).join(' ');
                
                // Handle CIDR notation (simplified - just show as single entry)
                if (ipPart.includes('/')) {
                    ips.push({ ip: ipPart, name: name });
                }
                // Handle range
                else if (ipPart.match(/^(\d+\.\d+\.\d+\.)(\d+)-(\d+)$/)) {
                    const match = ipPart.match(/^(\d+\.\d+\.\d+\.)(\d+)-(\d+)$/);
                    const base = match[1];
                    const start = parseInt(match[2]);
                    const end = parseInt(match[3]);
                    for (let i = start; i <= end && ips.length < MAX_IPS; i++) {
                        ips.push({ ip: base + i, name: name });
                    }
                }
                // Single IP
                else {
                    ips.push({ ip: ipPart, name: name });
                }
                
                if (ips.length >= MAX_IPS) break;
            }
            
            return ips;
        }

        function showCompletionStatus() {
            const statusIndicator = document.getElementById('statusIndicator');
            statusIndicator.className = 'status-indicator complete';
            statusIndicator.innerHTML = '✓ Scan completed';
            statusIndicator.style.display = 'inline-flex';
            updateFavicon('complete');
            
            setTimeout(() => {
                statusIndicator.style.display = 'none';
                updateFavicon('idle');
            }, 3000);
        }
        
        function showErrorStatus(message) {
            const statusIndicator = document.getElementById('statusIndicator');
            statusIndicator.className = 'status-indicator error';
            statusIndicator.textContent = '⚠ ' + message;
            statusIndicator.style.display = 'inline-flex';
            
            setTimeout(() => {
                statusIndicator.style.display = 'none';
            }, 5000);
        }

        function startScan() {
            const ipInput = document.getElementById('ipInput').value.trim();
            if (!ipInput) {
                showErrorStatus('Please enter at least one IP address');
                return;
            }

            const now = Date.now();
            if (now - lastScanTime < RATE_LIMIT_MS) {
                const waitTime = Math.ceil((RATE_LIMIT_MS - (now - lastScanTime)) / 1000);
                console.log(`Rate limit: waiting ${waitTime} seconds...`);
                setTimeout(() => startScan(), RATE_LIMIT_MS - (now - lastScanTime));
                return;
            }

            const scanMode = document.querySelector('input[name="scanMode"]:checked').value;
            
            if (scanInterval) {
                clearInterval(scanInterval);
                scanInterval = null;
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }

            isFirstScan = true;
            completedScans = 0;
            scanStartTime = Date.now();
            currentSessionId = generateSessionId();
            timedOutIPs.clear();
            pingTimings = [];
            
            setScanningState(true);
            performScan();

            if (scanMode === 'repeat') {
                const scanCountMode = document.querySelector('input[name="scanCount"]:checked').value;
                if (scanCountMode === 'limited') {
                    totalScans = parseInt(document.getElementById('scanCountInput').value);
                } else {
                    totalScans = 0;
                }
                
                const interval = Math.max(5, parseInt(document.getElementById('scanInterval').value));
                scanInterval = setInterval(() => {
                    if (totalScans > 0 && completedScans >= totalScans) {
                        stopScan();
                        showCompletionStatus();
                        return;
                    }
                    performScan();
                }, interval * 1000);
            } else {
                totalScans = 1;
            }
            
            showLogLink(currentSessionId);
        }

        async function performScan() {
            const now = Date.now();
            if (now - lastScanTime < RATE_LIMIT_MS) {
                return;
            }
            lastScanTime = now;

            const ipInput = document.getElementById('ipInput').value.trim();
            const ips = parseIPInput(ipInput);
            
            if (ips.length === 0) {
                showErrorStatus('No valid IPs to scan');
                return;
            }
            
            displayScanningTiles(ips);
            
            const staggerInterval = parseFloat(document.getElementById('staggerInterval').value) || 0;
            const staggerMs = staggerInterval * 1000;
            
            // Ping each IP with staggered timing
            for (let i = 0; i < ips.length; i++) {
                const target = ips[i];
                
                // Skip if timed out previously
                if (timedOutIPs.has(target.ip)) {
                    updateTileSkipped(target.ip, target.name);
                    continue;
                }
                
                // Delay for staggered pings
                if (i > 0 && staggerMs > 0) {
                    await new Promise(resolve => setTimeout(resolve, staggerMs));
                }
                
                // Ping asynchronously without waiting for completion
                pingSingleIP(target.ip, target.name);
            }
            
            // Update scan counter
            completedScans++;
            updateETA();
            
            // Check if this was a one-time scan
            if (!scanInterval && completedScans >= 1) {
                // Wait a bit for all pings to complete before showing completion
                setTimeout(() => {
                    setScanningState(false);
                    showCompletionStatus();
                }, SCAN_TIMEOUT_MS + 1000);
            }
        }

        async function pingSingleIP(ip, name) {
            const startTime = Date.now();
            
            try {
                const response = await fetch('ping_single.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ip=${encodeURIComponent(ip)}&name=${encodeURIComponent(name)}&session_id=${encodeURIComponent(currentSessionId)}`
                });
                
                const data = await response.json();
                const endTime = Date.now();
                const pingDuration = (endTime - startTime) / 1000;
                
                if (data.success && data.result) {
                    const result = data.result;
                    
                    // Track ping timing for ETA calculation
                    pingTimings.push(pingDuration);
                    if (pingTimings.length > 50) {
                        pingTimings.shift(); // Keep last 50 timings
                    }
                    
                    // Check if skipped
                    if (result.skipped) {
                        updateTileSkipped(result.ip, result.name);
                    } else {
                        // Update tile with result
                        updateTileWithResult(result);
                        
                        // Track timeout
                        if (!result.online) {
                            timedOutIPs.add(result.ip);
                        }
                        
                        // Update chart
                        if (result.online && result.response_time) {
                            updateChartWithResult(result);
                        }
                    }
                    
                    // Update statistics
                    updateStatistics();
                } else {
                    console.error('Ping failed for', ip, data.error);
                    updateTileError(ip, name, data.error || 'Unknown error');
                }
            } catch (error) {
                console.error('Error pinging', ip, error);
                updateTileError(ip, name, error.message);
            }
        }

        function displayScanningTiles(ips) {
            document.getElementById('resultsSection').style.display = 'block';
            const statusGrid = document.getElementById('statusGrid');
            
            // Clear on first scan
            if (isFirstScan) {
                statusGrid.innerHTML = '';
                historyData = {};
                isFirstScan = false;
                
                // Reset statistics
                document.getElementById('totalCount').textContent = '0';
                document.getElementById('onlineCount').textContent = '0';
                document.getElementById('offlineCount').textContent = '0';
                document.getElementById('avgResponse').textContent = '--';
            }
            
            ips.forEach(target => {
                let card = statusGrid.querySelector(`[data-ip="${target.ip}"]`);
                if (!card) {
                    card = document.createElement('div');
                    card.className = 'status-card scanning';
                    card.setAttribute('data-ip', target.ip);
                    card.innerHTML = `
                        <div class="status-header">
                            <span class="status-icon">🔄</span>
                            <span style="color: #00d9ff; font-weight: 600;">Scanning...</span>
                        </div>
                        ${target.name ? `<div class="friendly-name">${escapeHtml(target.name)}</div>` : ''}
                        <div class="ip-address">${escapeHtml(target.ip)}</div>
                        <div class="status-info">Waiting for response...</div>
                        <div class="timestamp">Started: ${new Date().toLocaleTimeString()}</div>
                    `;
                    statusGrid.appendChild(card);
                } else {
                    // Update existing card to scanning state
                    card.className = 'status-card scanning';
                    card.innerHTML = `
                        <div class="status-header">
                            <span class="status-icon">🔄</span>
                            <span style="color: #00d9ff; font-weight: 600;">Scanning...</span>
                        </div>
                        ${target.name ? `<div class="friendly-name">${escapeHtml(target.name)}</div>` : ''}
                        <div class="ip-address">${escapeHtml(target.ip)}</div>
                        <div class="status-info">Waiting for response...</div>
                        <div class="timestamp">Started: ${new Date().toLocaleTimeString()}</div>
                    `;
                }
                
                scanningTiles[target.ip] = Date.now();
            });
        }

        function updateTileWithResult(result) {
            const statusGrid = document.getElementById('statusGrid');
            const card = statusGrid.querySelector(`[data-ip="${result.ip}"]`);
            if (!card) return;
            
            delete scanningTiles[result.ip];
            
            if (result.online) {
                card.className = 'status-card online';
                card.innerHTML = `
                    <div class="status-header">
                        <span class="status-icon">✅</span>
                        <span style="color: #28a745; font-weight: 600;">Online</span>
                    </div>
                    ${result.name ? `<div class="friendly-name">${escapeHtml(result.name)}</div>` : ''}
                    <div class="ip-address">${escapeHtml(result.ip)}</div>
                    ${result.host_info ? `<div class="status-info">${escapeHtml(result.host_info)}</div>` : ''}
                    <div class="response-time">${result.response_time} ms</div>
                    <div class="timestamp">Last check: ${result.timestamp}</div>
                `;
            } else {
                card.className = 'status-card offline';
                card.innerHTML = `
                    <div class="status-header">
                        <span class="status-icon">❌</span>
                        <span style="color: #dc3545; font-weight: 600;">Offline</span>
                    </div>
                    ${result.name ? `<div class="friendly-name">${escapeHtml(result.name)}</div>` : ''}
                    <div class="ip-address">${escapeHtml(result.ip)}</div>
                    <div class="status-info">No response received</div>
                    <div class="timestamp">Last check: ${result.timestamp}</div>
                `;
            }
        }

        function updateTileSkipped(ip, name) {
            const statusGrid = document.getElementById('statusGrid');
            const card = statusGrid.querySelector(`[data-ip="${ip}"]`);
            if (!card) return;
            
            delete scanningTiles[ip];
            
            card.className = 'status-card timeout';
            card.innerHTML = `
                <div class="status-header">
                    <span class="status-icon">⏩</span>
                    <span style="color: #ff9500; font-weight: 600;">Skipped</span>
                </div>
                ${name ? `<div class="friendly-name">${escapeHtml(name)}</div>` : ''}
                <div class="ip-address">${escapeHtml(ip)}</div>
                <div class="status-info">Previously timed out - skipped in this round</div>
                <div class="timestamp">${new Date().toLocaleTimeString()}</div>
            `;
        }

        function updateTileError(ip, name, error) {
            const statusGrid = document.getElementById('statusGrid');
            const card = statusGrid.querySelector(`[data-ip="${ip}"]`);
            if (!card) return;
            
            delete scanningTiles[ip];
            
            card.className = 'status-card offline';
            card.innerHTML = `
                <div class="status-header">
                    <span class="status-icon">⚠️</span>
                    <span style="color: #dc3545; font-weight: 600;">Error</span>
                </div>
                ${name ? `<div class="friendly-name">${escapeHtml(name)}</div>` : ''}
                <div class="ip-address">${escapeHtml(ip)}</div>
                <div class="status-info">${escapeHtml(error)}</div>
                <div class="timestamp">${new Date().toLocaleTimeString()}</div>
            `;
        }

        function updateStatistics() {
            const statusGrid = document.getElementById('statusGrid');
            const cards = statusGrid.querySelectorAll('.status-card');
            
            let onlineCount = 0;
            let offlineCount = 0;
            let totalResponse = 0;
            let responseCount = 0;
            
            cards.forEach(card => {
                if (card.classList.contains('online')) {
                    onlineCount++;
                    const responseTimeEl = card.querySelector('.response-time');
                    if (responseTimeEl) {
                        const time = parseFloat(responseTimeEl.textContent);
                        if (!isNaN(time)) {
                            totalResponse += time;
                            responseCount++;
                        }
                    }
                } else if (card.classList.contains('offline') || card.classList.contains('timeout')) {
                    offlineCount++;
                }
            });
            
            document.getElementById('totalCount').textContent = cards.length;
            document.getElementById('onlineCount').textContent = onlineCount;
            document.getElementById('offlineCount').textContent = offlineCount;
            
            if (responseCount > 0) {
                const avg = (totalResponse / responseCount).toFixed(2);
                document.getElementById('avgResponse').textContent = avg + ' ms';
            } else {
                document.getElementById('avgResponse').textContent = '--';
            }
        }

        function updateChartWithResult(result) {
            if (typeof Chart === 'undefined') return;
            
            const timestamp = new Date().toLocaleTimeString();
            
            if (!historyData[result.ip]) {
                historyData[result.ip] = {
                    name: result.name || result.ip,
                    data: []
                };
            }
            
            historyData[result.ip].data.push({
                time: timestamp,
                value: parseFloat(result.response_time)
            });
            
            // Keep only last 10 data points
            if (historyData[result.ip].data.length > 10) {
                historyData[result.ip].data.shift();
            }
            
            updateChart();
        }

        function updateChart() {
            if (typeof Chart === 'undefined') return;
            
            const canvas = document.getElementById('responseChart');
            const ctx = canvas.getContext('2d');
            
            // Prepare datasets
            const datasets = [];
            const colors = ['#00d9ff', '#00ff88', '#ff9500', '#dc3545', '#667eea', '#f093fb'];
            let colorIndex = 0;
            
            for (const ip in historyData) {
                const data = historyData[ip];
                if (data.data.length === 0) continue;
                
                datasets.push({
                    label: data.name,
                    data: data.data.map(d => d.value),
                    borderColor: colors[colorIndex % colors.length],
                    backgroundColor: colors[colorIndex % colors.length] + '33',
                    tension: 0.4,
                    fill: false
                });
                colorIndex++;
            }
            
            // Get labels from first dataset
            let labels = [];
            if (datasets.length > 0) {
                const firstIp = Object.keys(historyData)[0];
                labels = historyData[firstIp].data.map(d => d.time);
            }
            
            if (responseChart) {
                responseChart.data.labels = labels;
                responseChart.data.datasets = datasets;
                responseChart.update();
            } else {
                responseChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#e4e7eb'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#9ca3af'
                                },
                                grid: {
                                    color: 'rgba(0, 217, 255, 0.1)'
                                },
                                title: {
                                    display: true,
                                    text: 'Response Time (ms)',
                                    color: '#e4e7eb'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#9ca3af'
                                },
                                grid: {
                                    color: 'rgba(0, 217, 255, 0.1)'
                                }
                            }
                        }
                    }
                });
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

    </script>
</body>
</html>
