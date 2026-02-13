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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .input-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-family: monospace;
            font-size: 13px;
            resize: vertical;
            min-height: 150px;
        }
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .scan-mode {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .scan-mode label {
            margin-bottom: 0;
            font-weight: normal;
        }
        .scan-mode input[type="radio"] {
            margin-right: 5px;
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
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }
        .status-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .status-card.online {
            border-color: #28a745;
            background: #f0fdf4;
        }
        .status-card.offline {
            border-color: #dc3545;
            background: #fef2f2;
        }
        .status-card.scanning {
            border-color: #667eea;
            background: #e8eeff;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
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
            color: #333;
            font-family: monospace;
        }
        .friendly-name {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .status-info {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .response-time {
            font-weight: 600;
            color: #667eea;
        }
        .timestamp {
            font-size: 11px;
            color: #999;
        }
        .mini-chart-container {
            margin-top: 10px;
            height: 60px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 5px;
            padding: 5px;
        }
        .mini-chart-canvas {
            width: 100% !important;
            height: 50px !important;
        }
        .chart-container {
            margin-top: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            display: none; /* Hide the main chart */
        }
        canvas {
            max-height: 400px;
        }
        .countdown-timer {
            text-align: center;
            padding: 20px;
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
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
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        .stats-bar {
            display: flex;
            gap: 20px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stat-item {
            flex: 1;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .interval-control {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .interval-control input {
            width: 80px;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span>📡</span> ManyPing
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
            </div>
            
            <div class="button-group">
                <button class="btn-primary" onclick="startScan()">🚀 Start Scan</button>
                <button class="btn-secondary" onclick="stopScan()">⏸️ Stop Scan</button>
                <button class="btn-danger" onclick="clearResults()">🗑️ Clear Results</button>
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
        
        <div id="countdownTimer" class="countdown-timer" style="display:none;">
            Next scan in: <span id="countdownValue">0</span> seconds
        </div>
        
        <div class="loading" id="loadingIndicator" style="display:none;">
            <div class="spinner"></div>
            <p>Scanning IPs...</p>
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
        const RATE_LIMIT_MS = 5000; // 5 seconds between scans

        // Check if Chart.js loaded successfully
        window.addEventListener('load', function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not available');
            }
        });

        function startScan() {
            const ipInput = document.getElementById('ipInput').value.trim();
            if (!ipInput) {
                alert('Please enter at least one IP address');
                return;
            }

            const now = Date.now();
            if (now - lastScanTime < RATE_LIMIT_MS) {
                // Calculate wait time and automatically wait instead of alerting
                const waitTime = Math.ceil((RATE_LIMIT_MS - (now - lastScanTime)) / 1000);
                
                // Show countdown for the wait
                startCountdown(waitTime);
                
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
            document.getElementById('countdownTimer').style.display = 'none';

            // Perform initial scan
            performScan();

            // Set up repeat if needed
            if (scanMode === 'repeat') {
                const intervalSeconds = Math.max(5, parseInt(document.getElementById('scanInterval').value));
                const interval = intervalSeconds * 1000;
                
                // Start countdown timer
                startCountdown(intervalSeconds);
                
                scanInterval = setInterval(() => {
                    performScan();
                    startCountdown(intervalSeconds);
                }, interval);
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
            document.getElementById('countdownTimer').style.display = 'none';
        }

        function startCountdown(seconds) {
            // Clear any existing countdown
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            
            let remaining = seconds;
            document.getElementById('countdownTimer').style.display = 'block';
            document.getElementById('countdownValue').textContent = remaining;
            
            countdownInterval = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                    document.getElementById('countdownTimer').style.display = 'none';
                } else {
                    document.getElementById('countdownValue').textContent = remaining;
                }
            }, 1000);
        }

        function clearResults() {
            stopScan();
            document.getElementById('resultsSection').style.display = 'none';
            document.getElementById('statusGrid').innerHTML = '';
            historyData = {};
            if (responseChart) {
                responseChart.destroy();
                responseChart = null;
            }
        }

        // Parse IP input to extract individual IPs for display
        function parseIPInput(input) {
            const lines = input.split('\n');
            const ips = [];
            
            for (let line of lines) {
                line = line.trim();
                if (!line || line.startsWith('#')) continue;
                
                const parts = line.split(/\s+/);
                const ipPart = parts[0];
                const name = parts.slice(1).join(' ') || '';
                
                // Check if it's a CIDR notation
                if (ipPart.includes('/')) {
                    const cidrIPs = expandCIDR(ipPart);
                    cidrIPs.forEach(ip => ips.push({ ip, name }));
                }
                // Check if it's a range
                else if (/^(\d+\.\d+\.\d+\.)(\d+)-(\d+)$/.test(ipPart)) {
                    const match = ipPart.match(/^(\d+\.\d+\.\d+\.)(\d+)-(\d+)$/);
                    const base = match[1];
                    const start = parseInt(match[2]);
                    const end = parseInt(match[3]);
                    
                    for (let i = start; i <= end && ips.length < 50; i++) {
                        ips.push({ ip: base + i, name });
                    }
                }
                // Single IP
                else {
                    ips.push({ ip: ipPart, name });
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

        // Display scanning tiles immediately when scan starts
        function displayScanningTiles(ips) {
            document.getElementById('resultsSection').style.display = 'block';
            const statusGrid = document.getElementById('statusGrid');
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
                alert('No valid IPs to scan');
                return;
            }
            
            displayScanningTiles(ips);
            document.getElementById('loadingIndicator').style.display = 'block';

            try {
                const response = await fetch('ping.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ips=' + encodeURIComponent(ipInput)
                });

                const data = await response.json();
                
                if (data.error) {
                    // Check if it's a rate limit error
                    if (data.error.includes('Rate limit:')) {
                        // Extract wait time from error message
                        const match = data.error.match(/wait (\d+) seconds/);
                        if (match) {
                            const waitTime = parseInt(match[1]);
                            console.log(`Rate limit hit, automatically waiting ${waitTime} seconds...`);
                            
                            // Show countdown and retry after waiting
                            startCountdown(waitTime);
                            
                            setTimeout(() => {
                                performScan();
                            }, waitTime * 1000);
                            return;
                        }
                    }
                    
                    // For non-rate-limit errors, show alert
                    alert('Error: ' + data.error);
                    return;
                }

                displayResults(data.results);
                updateChart(data.results);
                
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to perform scan: ' + error.message);
            } finally {
                document.getElementById('loadingIndicator').style.display = 'none';
            }
        }

        function displayResults(results) {
            document.getElementById('resultsSection').style.display = 'block';
            
            const statusGrid = document.getElementById('statusGrid');
            statusGrid.innerHTML = '';
            
            let onlineCount = 0;
            let offlineCount = 0;
            let totalResponse = 0;
            let responseCount = 0;

            results.forEach(result => {
                const card = document.createElement('div');
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
                
                statusGrid.appendChild(card);
                
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
            miniCharts[ip] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 3,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y.toFixed(2) + 'ms';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            display: false,
                            beginAtZero: true
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }

    </script>
</body>
</html>
