# SimulPing

Ping many IP's simultaneously - A PHP web application for concurrent IP monitoring

## Features

- **Concurrent IP Pinging**: Monitor multiple IPs simultaneously
- **Flexible Input Formats**:
  - Single IPs: `192.168.1.1`
  - IP Ranges: `192.168.1.1-10`
  - CIDR Notation: `192.168.1.0/24` (auto-expanded)
- **Scan Modes**:
  - One-off scans for quick checks
  - Repeated scans with configurable intervals (5-300 seconds)
- **Rate Limiting**: Built-in protection to prevent DDOS behavior
  - Maximum 50 IPs per scan
  - Minimum 5-second interval between scans
- **Visual Response Time Graphing**: Real-time Chart.js graphs showing response times per IP
- **Friendly Names**: Assign custom names to IPs for easier identification
- **Status Indicators**: Clear visual indicators (✅/❌) with timestamps
- **Host Information**: Display hostname and response codes on success
- **Statistics Dashboard**: Real-time stats showing total IPs, online/offline counts, and average response times

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/BlindTrevor/SimulPing.git
   cd SimulPing
   ```

2. Ensure you have PHP installed (PHP 7.0 or higher recommended)

3. Start a local PHP server:
   ```bash
   php -S localhost:8000
   ```

4. Open your browser and navigate to:
   ```
   http://localhost:8000
   ```

## Usage

### Input Format

Enter IPs one per line in the text area. Each line can have:
- An IP address, range, or CIDR notation
- An optional friendly name (separated by space)

**Examples:**
```
192.168.1.1 Home Router
192.168.1.10-20 DHCP Range
192.168.1.0/24 Local Network
8.8.8.8 Google DNS
1.1.1.1 Cloudflare DNS
```

### Scan Modes

1. **One-time Scan**: Performs a single scan of all IPs
2. **Repeat Scan**: Continuously scans at specified intervals (5-300 seconds)

### Understanding Results

Each IP is displayed in a card showing:
- Status icon (✅ online / ❌ offline)
- Friendly name (if provided)
- IP address
- Host information (resolved hostname)
- Response time in milliseconds
- Last check timestamp

### Response Time Graph

The graph displays historical response times for all online IPs, updating with each scan. Useful for monitoring network stability and latency trends.

## Rate Limiting

To prevent network abuse and DDOS behavior:
- Maximum of **50 IPs** can be scanned at once
- Minimum **5-second interval** between scans (enforced server-side)
- CIDR ranges are limited to /24 or smaller (max 256 IPs)
- Input is automatically truncated if limits are exceeded

## Session Management

- Uses PHP sessions for rate limiting (no persistent storage on server)
- Sessions automatically expire after **60 minutes** of inactivity
- All tracking is temporary and session-based only
- No user data is permanently stored on the server

## Requirements

- PHP 7.0 or higher
- PHP `exec()` function must be enabled
- Operating system with `ping` command available (Linux, Mac, Windows)
- Modern web browser with JavaScript enabled

## Security Considerations

- The application is designed for internal network monitoring
- Rate limiting prevents abuse
- Input validation sanitizes all IP addresses
- Commands are properly escaped to prevent injection attacks
- Recommend running behind authentication in production environments

## Browser Compatibility

- Chrome/Edge (recommended)
- Firefox
- Safari
- Any modern browser with JavaScript and Canvas support

## Troubleshooting

**Ping command not found:**
- Ensure `ping` is available in your system PATH
- On some systems, ping may require elevated permissions

**No results showing:**
- Check browser console for errors
- Ensure PHP `exec()` function is not disabled
- Verify firewall rules allow ICMP packets

**Slow performance:**
- Reduce the number of IPs being scanned
- Increase scan interval for repeated scans
- Check network connectivity

## License

MIT License - Feel free to use and modify for your needs

## Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues for bugs and feature requests.
