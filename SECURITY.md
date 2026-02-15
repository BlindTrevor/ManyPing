# Security Documentation

## Overview

This document outlines the security measures implemented in ManyPing to protect against common web vulnerabilities and ensure stable operation under concurrent user load.

## Security Features

### 1. CSRF (Cross-Site Request Forgery) Protection

**Implementation:**
- Token-based CSRF protection on all POST endpoints
- Tokens expire after 1 hour
- Uses cryptographically secure random token generation
- Constant-time comparison to prevent timing attacks

**Files Modified:**
- `security_config.php` - Token generation and validation functions
- `ping.php`, `ping_single.php` - Server-side validation
- `index.php` - Client-side token inclusion in requests

**How it works:**
1. Session starts and generates a unique CSRF token
2. Token is embedded in the HTML page
3. JavaScript includes token in all POST requests
4. Server validates token before processing request

### 2. Rate Limiting

**Two-layer approach:**

#### Session-based Rate Limiting
- Minimum 5 seconds between scans per session
- Prevents rapid successive scans from same user
- Stored in PHP session

#### IP-based Rate Limiting
- Maximum 20 requests per minute per IP address
- Prevents abuse from multiple sessions
- Uses temporary files for tracking
- Automatically cleans old entries

**Configuration:**
```php
MAX_REQUESTS_PER_IP = 20        // Max requests per IP per minute
RATE_LIMIT_WINDOW = 60          // Window in seconds
MIN_SCAN_INTERVAL = 5           // Min seconds between scans
```

### 3. Secure Session Management

**Features:**
- HTTPOnly cookies (prevents JavaScript access)
- SameSite=Strict (prevents CSRF via cookies)
- Secure flag when HTTPS is used
- Session fixation protection via regeneration
- Session hijacking protection via IP validation
- Periodic session ID regeneration (every 30 minutes)
- Session timeout after 60 minutes of inactivity

**Implementation:**
```php
initSecureSession() in security_config.php
```

### 4. Security Headers

**Headers Set:**
- `X-Content-Type-Options: nosniff` - Prevents MIME-type sniffing
- `X-Frame-Options: DENY` - Prevents clickjacking
- `X-XSS-Protection: 1; mode=block` - XSS filter
- `Content-Security-Policy` - Restricts resource loading
- `Referrer-Policy: strict-origin-when-cross-origin` - Privacy protection
- `Cache-Control: no-store` - Prevents sensitive data caching

### 5. Input Validation and Sanitization

**IP Address Validation:**
- Uses PHP's `filter_var()` with `FILTER_VALIDATE_IP`
- Additional sanitization via `sanitizeIP()` function
- Rejects malformed IP addresses
- Optional: Can block private/reserved IP ranges (commented out by default)

**Session ID Validation:**
- Alphanumeric, dash, and underscore only
- Maximum length of 128 characters
- Regex pattern: `/^[a-zA-Z0-9_-]{1,128}$/`

**Input Size Limits:**
- Maximum 10KB for IP input list
- Maximum 50 IPs per scan
- CIDR ranges limited to /24 or smaller (max 256 IPs)

### 6. Path Traversal Prevention

**Log File Access Protection:**
- Session ID sanitization before file operations
- Real path validation using `realpath()`
- Ensures requested file is within logs directory
- .htaccess file in logs directory denies direct web access

**Implementation in view_log.php:**
```php
$logsDir = realpath(__DIR__ . '/logs');
$requestedFile = realpath($logFile);
if ($requestedFile === false || strpos($requestedFile, $logsDir) !== 0) {
    die('Error: Invalid log file path');
}
```

### 7. Security Event Logging

**Logged Events:**
- CSRF validation failures
- Rate limit violations (both IP and session)
- Invalid request methods
- Origin mismatches
- Path traversal attempts
- Invalid IP addresses
- Input size violations

**Log Format:**
```
[Timestamp] IP: xxx.xxx.xxx.xxx | Event: EVENT_TYPE | Details: ... | User-Agent: ...
```

**Log Location:** `/logs/security.log`

### 8. File and Directory Permissions

**Secure Defaults:**
- Log directory: `0750` (rwxr-x---)
- Log files: `0640` (rw-r-----)
- .htaccess in logs: `0640`

### 9. Command Injection Prevention

**Measures:**
- All IP addresses validated before use
- Uses `escapeshellarg()` for shell commands
- Limited to ping command only
- No user-controlled command parameters beyond IP

### 10. Origin Validation

**Implementation:**
- Validates HTTP_REFERER against server hostname
- Prevents cross-origin POST requests
- Allows missing referer (privacy tools compatibility)

## Security Best Practices for Deployment

### 1. Use HTTPS

Always deploy behind HTTPS in production:
- Enables secure session cookies
- Protects data in transit
- Prevents man-in-the-middle attacks

### 2. Add Authentication

The application has no built-in authentication. For production:

**Option A: HTTP Basic Authentication**
```apache
# In .htaccess
AuthType Basic
AuthName "ManyPing Access"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

**Option B: Reverse Proxy Authentication**
Configure your nginx/Apache reverse proxy to require authentication.

### 3. Firewall Configuration

Restrict access to trusted IPs:
```nginx
# Nginx example
location /manyping/ {
    allow 192.168.1.0/24;
    deny all;
}
```

### 4. PHP Security Configuration

Recommended php.ini settings:
```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /path/to/php-error.log
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

### 5. Regular Updates

- Keep PHP updated to latest stable version
- Monitor security advisories
- Review logs regularly for suspicious activity

### 6. Resource Limits

Consider implementing:
- Web server connection limits
- PHP process limits (php-fpm)
- System resource quotas

### 7. Monitoring and Alerting

Set up monitoring for:
- Security log anomalies
- Rate limit triggers
- Failed authentication attempts
- Unusual traffic patterns

## Known Limitations

### 1. No Built-in Authentication

**Risk:** Anyone with network access can use the application

**Mitigation:** 
- Deploy behind authentication layer
- Use firewall rules
- Restrict to internal network only

### 2. Potential for Network Abuse

**Risk:** Even with rate limiting, could be used to scan networks

**Mitigation:**
- IP-based rate limiting (20 requests/min)
- Maximum 50 IPs per scan
- Monitor usage patterns
- Optional: Uncomment private IP blocking in `sanitizeIP()`

### 3. Session Storage in Filesystem

**Risk:** On shared hosting, session data might be accessible

**Mitigation:**
- Use dedicated hosting
- Configure custom session save path with restricted permissions
- Consider Redis/Memcached for session storage

### 4. Log Files Growth

**Risk:** Log files can grow large over time

**Mitigation:**
- Implement log rotation
- Clean up old session logs
- Monitor disk usage

## Incident Response

### If you suspect a security breach:

1. **Immediate Actions:**
   - Review `/logs/security.log` for suspicious activity
   - Check web server access logs
   - Block suspicious IPs at firewall level

2. **Investigation:**
   - Identify the attack vector
   - Assess what data may have been accessed
   - Check for unauthorized file modifications

3. **Remediation:**
   - Patch any vulnerabilities discovered
   - Rotate session keys
   - Update security configurations

4. **Prevention:**
   - Strengthen security measures
   - Update monitoring and alerting
   - Document the incident and response

## Security Testing Checklist

- [ ] CSRF protection working on all forms
- [ ] Rate limiting enforces limits correctly
- [ ] Session fixation prevented
- [ ] Session hijacking protection works
- [ ] Path traversal attempts blocked
- [ ] Invalid input rejected properly
- [ ] Security headers present in all responses
- [ ] Log files created with correct permissions
- [ ] Command injection attempts fail
- [ ] Security events logged properly

## Compliance and Standards

This implementation follows security best practices from:
- OWASP Top 10 Web Application Security Risks
- PHP Security Best Practices
- CWE/SANS Top 25 Most Dangerous Software Weaknesses

## Contact and Reporting

To report security vulnerabilities:
1. **DO NOT** open a public GitHub issue
2. Contact the maintainer directly
3. Provide detailed information about the vulnerability
4. Allow reasonable time for patches before disclosure

## Changelog

### Version 1.0 (Current)
- Initial security hardening implementation
- CSRF protection
- Dual-layer rate limiting
- Secure session management
- Security headers
- Input validation and sanitization
- Path traversal prevention
- Security event logging
- Secure file permissions

## Additional Resources

- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [Session Security](https://www.php.net/manual/en/session.security.php)
