<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ManyPing - Concurrent IP Monitor</title>
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
        let scanInterval = null;
        let countdownInterval = null;
        let responseChart = null;
        let historyData = {};
        let miniCharts = {}; // Store mini chart instances
        let lastScanTime = 0;
        let isFirstScan = true; // Track if this is the first scan
        let nextScanTime = null; // Track when next scan will occur
        let completedScans = 0; // Track number of completed scans
        let totalScans = 0; // Total number of scans to perform (0 = continuous)
        let scanStartTime = null; // Track when scanning started
        let progressInterval = null; // Track progress bar update interval
        const RATE_LIMIT_MS = 5000; // 5 seconds between scans
        let scanningTiles = {}; // Track when each tile starts scanning
        const SCAN_TIMEOUT_MS = 10000; // 10 seconds timeout for individual tile scans

        // Check if Chart.js loaded successfully
        window.addEventListener('load', function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not available');
            }
            
            // Set up event listeners for scan mode and count options
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
                const totalSeconds = scans * interval;
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;
                
                if (completedScans > 0) {
                    // During scanning
                    const remaining = totalScans - completedScans;
                    const remainingSeconds = remaining * interval;
                    const remMinutes = Math.floor(remainingSeconds / 60);
                    const remSeconds = remainingSeconds % 60;
                    etaDisplay.textContent = `⏱️ ETA: ${remMinutes}m ${remSeconds}s remaining (${completedScans}/${totalScans} scans completed)`;
                } else {
                    // Before scanning
                    etaDisplay.textContent = `⏱️ ETA: ${minutes}m ${seconds}s total for ${scans} scans`;
                }
            }
        }
        
        function setScanningState(scanning) {
            const ipInput = document.getElementById('ipInput');
            const toggleBtn = document.getElementById('toggleBtn');
            const statusIndicator = document.getElementById('statusIndicator');
            
            if (scanning) {
                // Set readonly and update button to stop mode
                ipInput.readOnly = true;
                toggleBtn.classList.add('scanning');
                toggleBtn.innerHTML = '[ ■ TERMINATE ]';
                
                // Show scanning status
                statusIndicator.className = 'status-indicator scanning';
                statusIndicator.innerHTML = '<div class="spinner-small"></div><span>Scan in progress</span>';
                statusIndicator.style.display = 'inline-flex';
            } else {
                // Remove readonly and reset button to scan mode
                ipInput.readOnly = false;
                toggleBtn.classList.remove('scanning');
                toggleBtn.innerHTML = '[ >_ EXECUTE SCAN ]';
                
                // Hide status indicator
                statusIndicator.style.display = 'none';
            }
        }
        
        function toggleScan() {
            // If currently scanning, stop it
            if (scanInterval || document.getElementById('toggleBtn').classList.contains('scanning')) {
                stopScan();
            } else {
                // Otherwise start scanning
                startScan();
            }
        }
        
        function showCompletionStatus() {
            const statusIndicator = document.getElementById('statusIndicator');
            statusIndicator.className = 'status-indicator complete';
            statusIndicator.innerHTML = '✓ Scan completed';
            statusIndicator.style.display = 'inline-flex';
            
            // Hide after 3 seconds
            setTimeout(() => {
                statusIndicator.style.display = 'none';
            }, 3000);
        }
        
        function showErrorStatus(message) {
            const statusIndicator = document.getElementById('statusIndicator');
            statusIndicator.className = 'status-indicator error';
            // Use textContent to prevent XSS
            statusIndicator.textContent = '⚠ ' + message;
            statusIndicator.style.display = 'inline-flex';
            
            // Hide after 5 seconds
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
                // Calculate wait time and automatically wait instead of alerting
                const waitTime = Math.ceil((RATE_LIMIT_MS - (now - lastScanTime)) / 1000);
                
                console.log(`Rate limit: waiting ${waitTime} seconds...`);
                
                // Automatically retry after waiting
                setTimeout(() => {
                    startScan();
                }, (RATE_LIMIT_MS - (now - lastScanTime)));
                return;
            }

            const scanMode = document.querySelector('input[name="scanMode"]:checked').value;
            
            // Stop any existing scan and countdown
            if (scanInterval) {
                clearInterval(scanInterval);
                scanInterval = null;
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }

            // Mark as first scan and reset counters
            isFirstScan = true;
            completedScans = 0;
            scanStartTime = Date.now();
            
            // Set scanning state
            setScanningState(true);
            
            // Perform initial scan
            performScan();

            // Set up repeat if needed
            if (scanMode === 'repeat') {
                const intervalSeconds = Math.max(5, parseInt(document.getElementById('scanInterval').value));
                const interval = intervalSeconds * 1000;
                
                const scanCountMode = document.querySelector('input[name="scanCount"]:checked').value;
                if (scanCountMode === 'continuous') {
                    totalScans = 0; // 0 means continuous
                } else {
                    totalScans = parseInt(document.getElementById('scanCountInput').value);
                    // Start progress bar only if not continuous
                    startProgressBar(intervalSeconds);
                }
                
                updateETA();
                
                scanInterval = setInterval(() => {
                    // Check if we've reached the scan limit
                    if (totalScans > 0 && completedScans >= totalScans) {
                        stopScan();
                        showCompletionStatus();
                        return;
                    }
                    
                    isFirstScan = false; // Mark subsequent scans as not first
                    nextScanTime = intervalSeconds;
                    performScan();
                }, interval);
            }
        }

        function startProgressBar(intervalSeconds) {
            // Clear any existing progress interval
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
            
            // Don't show progress bar for continuous scanning
            if (totalScans === 0) return;
            
            const toggleBtn = document.getElementById('toggleBtn');
            const totalTime = intervalSeconds * 1000; // Convert to ms
            const updateFrequency = 100; // Update every 100ms
            let elapsed = 0;
            
            progressInterval = setInterval(() => {
                elapsed += updateFrequency;
                const progress = Math.min((elapsed / totalTime) * 100, 100);
                toggleBtn.style.setProperty('--progress', progress + '%');
                
                // Reset when cycle completes
                if (elapsed >= totalTime) {
                    elapsed = 0;
                }
            }, updateFrequency);
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
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
            
            const toggleBtn = document.getElementById('toggleBtn');
            toggleBtn.style.removeProperty('--progress');
            
            isFirstScan = true; // Reset for next start
            completedScans = 0; // Reset counter
            totalScans = 0;
            updateETA();
            
            // Reset UI state
            setScanningState(false);
        }

        function clearResults() {
            stopScan();
            document.getElementById('resultsSection').style.display = 'none';
            document.getElementById('statusGrid').innerHTML = '';
            historyData = {};
            scanningTiles = {}; // Clear scanning tiles tracking
            if (responseChart) {
                responseChart.destroy();
                responseChart = null;
            }
        }

        // Parse IP input to extract individual IPs for display
        function parseIPInput(input) {
            const lines = input.split('\n');
            const ips = [];
            const MAX_IPS = 50; // Match backend limit
            
            for (let line of lines) {
                line = line.trim();
                if (!line || line.startsWith('#')) continue;
                
                // Stop if we've reached the max
                if (ips.length >= MAX_IPS) break;
                
                const parts = line.split(/\s+/);
                const ipPart = parts[0];
                const name = parts.slice(1).join(' ') || '';
                
                // Check if it's a CIDR notation
                if (ipPart.includes('/')) {
                    const cidrIPs = expandCIDR(ipPart);
                    cidrIPs.forEach(ip => {
                        if (ips.length < MAX_IPS) {
                            ips.push({ ip, name });
                        }
                    });
                }
                // Check if it's a range
                else if (/^(\d+\.\d+\.\d+\.)(\d+)-(\d+)$/.test(ipPart)) {
                    const match = ipPart.match(/^(\d+\.\d+\.\d+\.)(\d+)-(\d+)$/);
                    const base = match[1];
                    const start = parseInt(match[2]);
                    const end = parseInt(match[3]);
                    
                    for (let i = start; i <= end && ips.length < MAX_IPS; i++) {
                        ips.push({ ip: base + i, name });
                    }
                }
                // Single IP
                else {
                    if (ips.length < MAX_IPS) {
                        ips.push({ ip: ipPart, name });
                    }
                }
            }
            
            return ips;
        }

        // Simple CIDR expansion for frontend display (limited to prevent abuse)
        function expandCIDR(cidr) {
            const [ip, prefix] = cidr.split('/');
            const prefixNum = parseInt(prefix);
            
            // Limit to /24 or smaller
            if (prefixNum < 24) {
                return [ip]; // Just show the base IP if too large
            }
            
            const parts = ip.split('.').map(p => parseInt(p));
            const ips = [];
            
            // Simple expansion for /24 to /32
            const hostBits = 32 - prefixNum;
            const numHosts = Math.min(Math.pow(2, hostBits) - 2, 50); // Limit to 50
            
            for (let i = 1; i <= numHosts; i++) {
                const newParts = [...parts];
                newParts[3] = (parts[3] + i) % 256;
                ips.push(newParts.join('.'));
            }
            
            return ips.length > 0 ? ips : [ip];
        }

        // Display scanning tiles immediately when scan starts (only on first scan)
        function displayScanningTiles(ips) {
            document.getElementById('resultsSection').style.display = 'block';
            const statusGrid = document.getElementById('statusGrid');
            const currentScanStartTime = Date.now();
            
            // Track all IPs being scanned with their start time
            ips.forEach(ipObj => {
                scanningTiles[ipObj.ip] = currentScanStartTime;
            });
            
            // Only clear and show blue tiles on first scan
            if (isFirstScan) {
                statusGrid.innerHTML = '';
                
                ips.forEach(ipObj => {
                    const card = document.createElement('div');
                    card.className = 'status-card scanning';
                    card.setAttribute('data-ip', ipObj.ip);
                    
                    card.innerHTML = `
                        <div class="status-header">
                            <span class="status-icon">⏳</span>
                            <span style="color: #667eea; font-weight: 600;">Scanning...</span>
                        </div>
                        ${ipObj.name ? `<div class="friendly-name">${ipObj.name}</div>` : ''}
                        <div class="ip-address">${ipObj.ip}</div>
                        <div class="status-info">Waiting for response...</div>
                    `;
                    
                    statusGrid.appendChild(card);
                });
                
                // Update stats to show scanning state
                document.getElementById('totalCount').textContent = ips.length;
                document.getElementById('onlineCount').textContent = '0';
                document.getElementById('offlineCount').textContent = '0';
                document.getElementById('avgResponse').textContent = '--';
            }
            // On subsequent scans, tiles already exist - just leave them as is
        }
        
        // Start countdown on individual tiles

        async function performScan() {
            const now = Date.now();
            if (now - lastScanTime < RATE_LIMIT_MS) {
                return;
            }
            lastScanTime = now;

            const ipInput = document.getElementById('ipInput').value.trim();
            
            // Parse IPs and display scanning tiles immediately
            const ips = parseIPInput(ipInput);
            if (ips.length === 0) {
                showErrorStatus('No valid IPs to scan');
                return;
            }
            
            displayScanningTiles(ips);

            try {
                const response = await fetch('ping.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ips=' + encodeURIComponent(ipInput)
                });

                // Check if response is ok
                if (!response.ok) {
                    showErrorStatus(`Server error: ${response.status} ${response.statusText}`);
                    if (!scanInterval) {
                        setScanningState(false);
                    }
                    return;
                }
                
                // Read response text once
                const responseText = await response.text();
                
                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('JSON parse error:', jsonError);
                    console.error('Response text:', responseText.substring(0, 200));
                    showErrorStatus('Server returned invalid JSON. Check console for details.');
                    if (!scanInterval) {
                        setScanningState(false);
                    }
                    return;
                }
                
                if (data.error) {
                    // Check if it's a rate limit error
                    if (data.error.includes('Rate limit:')) {
                        // Extract wait time from error message
                        const match = data.error.match(/wait (\d+) seconds/);
                        if (match) {
                            const waitTime = parseInt(match[1]);
                            console.log(`Rate limit hit, automatically waiting ${waitTime} seconds...`);
                            
                            // Retry after waiting
                            setTimeout(() => {
                                performScan();
                            }, waitTime * 1000);
                            return;
                        }
                    }
                    
                    // For non-rate-limit errors, show error status
                    showErrorStatus(data.error);
                    return;
                }

                displayResults(data.results);
                updateChart(data.results);
                
                // Schedule a timeout check after SCAN_TIMEOUT_MS plus a small buffer to catch any stuck tiles
                // All tiles in a scan batch share the same start time, so a single check is sufficient
                setTimeout(() => {
                    checkForTimedOutTiles();
                }, SCAN_TIMEOUT_MS + 500); // Add 500ms buffer to ensure timeout threshold is exceeded
                
                // If one-time scan, reset UI state after completion
                if (!scanInterval) {
                    setScanningState(false);
                    showCompletionStatus();
                }
                
            } catch (error) {
                console.error('Error:', error);
                showErrorStatus('Failed to perform scan: ' + error.message);
                // Reset UI state on error
                if (!scanInterval) {
                    setScanningState(false);
                }
            }
        }

        function displayResults(results) {
            document.getElementById('resultsSection').style.display = 'block';
            
            const statusGrid = document.getElementById('statusGrid');
            
            let onlineCount = 0;
            let offlineCount = 0;
            let totalResponse = 0;
            let responseCount = 0;

            results.forEach(result => {
                // Try to find existing card for this IP
                let card = statusGrid.querySelector(`[data-ip="${result.ip}"]`);
                const isNewCard = !card;
                
                if (!card) {
                    card = document.createElement('div');
                    card.setAttribute('data-ip', result.ip);
                }
                
                // Remove from scanning tiles tracking since we got a result
                delete scanningTiles[result.ip];
                
                card.className = `status-card ${result.online ? 'online' : 'offline'}`;
                
                const icon = result.online ? '✅' : '❌';
                const statusText = result.online ? 'Online' : 'Offline';
                
                if (result.online) {
                    onlineCount++;
                    if (result.response_time) {
                        totalResponse += parseFloat(result.response_time);
                        responseCount++;
                    }
                } else {
                    offlineCount++;
                }

                let infoHTML = '';
                if (result.online && result.host_info) {
                    infoHTML = `<div class="status-info">Host: ${result.host_info}</div>`;
                }
                if (result.online && result.response_time) {
                    infoHTML += `<div class="status-info">Response: <span class="response-time">${result.response_time}ms</span></div>`;
                    
                    // Calculate and display min/max/avg if we have history
                    if (historyData[result.ip] && historyData[result.ip].data.length > 0) {
                        const values = historyData[result.ip].data.map(d => d.value);
                        const min = Math.min(...values).toFixed(2);
                        const max = Math.max(...values).toFixed(2);
                        const avg = (values.reduce((a, b) => a + b, 0) / values.length).toFixed(2);
                        infoHTML += `<div class="status-info" style="font-size:11px; color:#888;">Min: ${min}ms | Avg: ${avg}ms | Max: ${max}ms</div>`;
                    }
                }
                
                // Add mini chart container for online IPs
                const chartHTML = result.online ? `<div class="mini-chart-container"><canvas class="mini-chart-canvas" id="chart-${result.ip.replace(/\./g, '-')}"></canvas></div>` : '';
                
                card.innerHTML = `
                    <div class="status-header">
                        <span class="status-icon">${icon}</span>
                        <span style="color: ${result.online ? '#28a745' : '#dc3545'}; font-weight: 600;">${statusText}</span>
                    </div>
                    ${result.name ? `<div class="friendly-name">${result.name}</div>` : ''}
                    <div class="ip-address">${result.ip}</div>
                    ${infoHTML}
                    <div class="timestamp">Last checked: ${result.timestamp}</div>
                    ${chartHTML}
                `;
                
                if (isNewCard) {
                    statusGrid.appendChild(card);
                }
                
                // Update mini chart if online
                if (result.online && result.response_time) {
                    updateMiniChart(result.ip, result.response_time);
                }
            });

            // Update stats
            document.getElementById('totalCount').textContent = results.length;
            document.getElementById('onlineCount').textContent = onlineCount;
            document.getElementById('offlineCount').textContent = offlineCount;
            document.getElementById('avgResponse').textContent = 
                responseCount > 0 ? Math.round(totalResponse / responseCount) + 'ms' : '--';
            
            // Increment completed scans counter and update ETA
            if (scanInterval) {
                completedScans++;
                updateETA();
            }
        }

        function checkForTimedOutTiles() {
            const now = Date.now();
            const statusGrid = document.getElementById('statusGrid');
            
            // Check all tiles still in scanningTiles tracking
            for (const ip in scanningTiles) {
                const scanStartTime = scanningTiles[ip];
                const elapsed = now - scanStartTime;
                
                // If tile has been scanning longer than timeout, mark as timed out
                if (elapsed > SCAN_TIMEOUT_MS) {
                    const card = statusGrid.querySelector(`[data-ip="${ip}"]`);
                    if (card && card.classList.contains('scanning')) {
                        // Update tile to show timed out state
                        card.className = 'status-card timeout';
                        
                        // Get friendly name if it exists
                        const friendlyNameDiv = card.querySelector('.friendly-name');
                        const friendlyName = friendlyNameDiv ? friendlyNameDiv.textContent : '';
                        
                        card.innerHTML = `
                            <div class="status-header">
                                <span class="status-icon">⏱️</span>
                                <span style="color: #ff9500; font-weight: 600;">Timed Out</span>
                            </div>
                            ${friendlyName ? `<div class="friendly-name">${friendlyName}</div>` : ''}
                            <div class="ip-address">${ip}</div>
                            <div class="status-info">No response received within timeout period</div>
                            <div class="timestamp">Timeout: ${new Date().toLocaleTimeString()}</div>
                        `;
                        
                        // Remove from tracking
                        delete scanningTiles[ip];
                    }
                }
            }
        }

        function updateChart(results) {
            // Update history data for all results
            const timestamp = new Date().toLocaleTimeString();
            
            results.forEach(result => {
                if (result.online && result.response_time) {
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
                    
                    // Keep only last 10 data points for mini charts
                    if (historyData[result.ip].data.length > 10) {
                        historyData[result.ip].data.shift();
                    }
                }
            });
        }

        function updateMiniChart(ip, responseTime) {
            // Check if Chart.js is available
            if (typeof Chart === 'undefined') {
                return;
            }
            
            const canvasId = 'chart-' + ip.replace(/\./g, '-');
            const canvas = document.getElementById(canvasId);
            
            if (!canvas) {
                return;
            }
            
            // Get history data for this IP
            const ipData = historyData[ip];
            if (!ipData || ipData.data.length === 0) {
                return;
            }
            
            // Prepare data for mini chart
            const values = ipData.data.map(d => d.value);
            const labels = ipData.data.map((d, i) => ''); // No labels for mini chart
            
            // Destroy existing chart if it exists
            if (miniCharts[ip]) {
                miniCharts[ip].destroy();
            }
            
            // Create mini chart
            const ctx = canvas.getContext('2d');
            
            // Create gradient for background
            const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
            gradient.addColorStop(0, 'rgba(34, 197, 94, 0)'); // Transparent at top
            gradient.addColorStop(1, 'rgba(34, 197, 94, 0.3)'); // Darker green at bottom
            
            miniCharts[ip] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        borderColor: 'rgb(34, 197, 94)', // Green line
                        backgroundColor: gradient,
                        borderWidth: 2,
                        pointRadius: 0, // No data points
                        pointHoverRadius: 0, // No hover points
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
                                    return context.parsed.y.toFixed(2) + 'ms';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: false,
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        },
                        y: {
                            display: false,
                            beginAtZero: true,
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    elements: {
                        line: {
                            borderWidth: 2
                        }
                    }
                }
            });
        }

    </script>
</body>
</html>
