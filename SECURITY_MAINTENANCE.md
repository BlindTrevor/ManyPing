# Security Maintenance Guide

## Regular Security Tasks

### Daily
- [ ] Monitor security logs for unusual activity
- [ ] Check for rate limit violations
- [ ] Review access patterns

### Weekly  
- [ ] Review full security.log for anomalies
- [ ] Check disk usage for log files
- [ ] Verify all security headers are being sent

### Monthly
- [ ] Update PHP to latest patch version
- [ ] Review and update dependencies
- [ ] Test security controls
- [ ] Rotate log files if needed

### Quarterly
- [ ] Full security audit
- [ ] Review and update security policies
- [ ] Test incident response procedures
- [ ] Update documentation

## Security Log Monitoring

### Important Events to Watch For

**High Priority:**
- Multiple CSRF failures from same IP
- Rapid rate limit violations
- Path traversal attempts
- Origin mismatch errors

**Medium Priority:**
- Occasional rate limits (could be legitimate)
- Invalid IP address submissions
- Session timeout patterns

### Log Analysis Commands

```bash
# Count security events by type
grep -o 'Event: [A-Z_]*' logs/security.log | sort | uniq -c | sort -nr

# Find IPs with most security violations
grep -oP 'IP: \K[\d.]+' logs/security.log | sort | uniq -c | sort -nr

# Recent CSRF failures
grep 'CSRF_FAILURE' logs/security.log | tail -20

# Recent rate limit violations
grep 'RATE_LIMIT' logs/security.log | tail -20
```

## Updating Security Configuration

### Adjusting Rate Limits

Edit `security_config.php`:

```php
// To make more restrictive
define('MAX_REQUESTS_PER_IP', 10);      // Reduce from 20
define('RATE_LIMIT_WINDOW', 60);        // Keep at 60 seconds

// To make less restrictive
define('MAX_REQUESTS_PER_IP', 30);      // Increase from 20
define('RATE_LIMIT_WINDOW', 120);       // Increase to 2 minutes
```

### Adjusting Session Timeout

Edit `security_config.php`:

```php
// In initSecureSession() function
ini_set('session.gc_maxlifetime', 7200); // 2 hours instead of 1
```

### Blocking Private IP Scanning

Uncomment in `security_config.php` → `sanitizeIP()` function:

```php
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
    return false;
}
```

## Testing Security Controls

### 1. Test CSRF Protection

```bash
# This should fail without valid token
curl -X POST http://yourserver/ping.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "ips=8.8.8.8"
```

Expected: Error about invalid security token

### 2. Test Rate Limiting

Make 21 requests rapidly from same IP:

```bash
for i in {1..21}; do
  curl -X POST http://yourserver/ping_single.php \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d "ip=8.8.8.8&csrf_token=TOKEN"
  sleep 0.1
done
```

Expected: Last request should fail with rate limit error

### 3. Test Path Traversal Protection

```bash
curl "http://yourserver/view_log.php?session=../../../etc/passwd"
```

Expected: Error about invalid session ID or path

### 4. Test Security Headers

```bash
curl -I http://yourserver/index.php | grep -E "X-Frame-Options|X-Content-Type-Options|Content-Security-Policy"
```

Expected: All three headers should be present

## Incident Response Procedures

### 1. Suspected Attack in Progress

```bash
# Check recent security events
tail -100 logs/security.log

# Find attacking IP
grep 'RATE_LIMIT\|CSRF' logs/security.log | tail -20

# Block IP at firewall (example for iptables)
sudo iptables -A INPUT -s ATTACKING_IP -j DROP

# Or in Apache .htaccess
echo "Deny from ATTACKING_IP" >> .htaccess
```

### 2. Suspected Compromise

```bash
# Check for unauthorized file modifications
find . -type f -name "*.php" -mtime -1

# Review all security logs
less logs/security.log

# Check web server logs
less /var/log/apache2/access.log  # or nginx logs

# Verify file integrity
md5sum *.php > checksums_now.txt
diff checksums_original.txt checksums_now.txt
```

### 3. After Incident

1. Document what happened
2. Update security measures
3. Notify affected users if needed
4. Review and improve monitoring
5. Update incident response procedures

## Performance Monitoring

### Check Rate Limit File Cleanup

```bash
# Check temp directory for rate limit files
ls -lah /tmp/manyping_rate_limit_*

# Clean old files (older than 2 hours)
find /tmp -name "manyping_rate_limit_*" -mmin +120 -delete
```

### Monitor Log Size

```bash
# Check security log size
du -h logs/security.log

# Rotate if over 10MB
if [ $(stat -f%z logs/security.log) -gt 10485760 ]; then
    mv logs/security.log logs/security.log.$(date +%Y%m%d)
    touch logs/security.log
    chmod 0640 logs/security.log
fi
```

## Hardening Checklist

- [ ] Running behind HTTPS
- [ ] Authentication layer in place
- [ ] Firewall rules restrict access
- [ ] PHP configured securely
- [ ] Regular backups configured
- [ ] Monitoring and alerting active
- [ ] Log rotation configured
- [ ] Web server timeouts configured
- [ ] Security headers verified
- [ ] Rate limits appropriate for use case

## Emergency Contacts

Document your security contacts:

- **Security Team Lead:** _______________
- **System Administrator:** _______________
- **Hosting Provider Support:** _______________
- **Emergency Phone:** _______________

## Useful Resources

- Security documentation: `SECURITY.md`
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- PHP Security: https://www.php.net/manual/en/security.php
- CVE Database: https://cve.mitre.org/

## Automated Monitoring Script

Save as `monitor_security.sh`:

```bash
#!/bin/bash

LOG_FILE="logs/security.log"
ALERT_EMAIL="admin@example.com"
THRESHOLD=10

# Count recent violations (last hour)
RECENT_VIOLATIONS=$(grep "$(date -d '1 hour ago' '+%Y-%m-%d %H')" "$LOG_FILE" | \
                   grep -E "RATE_LIMIT|CSRF_FAILURE|PATH_TRAVERSAL" | wc -l)

if [ $RECENT_VIOLATIONS -gt $THRESHOLD ]; then
    echo "Alert: $RECENT_VIOLATIONS security violations in the last hour" | \
    mail -s "ManyPing Security Alert" "$ALERT_EMAIL"
fi
```

Run via cron: `0 * * * * /path/to/monitor_security.sh`

## Version History

- **1.0** - Initial security maintenance guide
