<?php
/**
 * ManyPing - Security Configuration
 * Centralized security settings and functions
 */

// Prevent direct access
if (!defined('MANYPING_SECURITY')) {
    die('Direct access not permitted');
}

// Security Constants
define('CSRF_TOKEN_NAME', 'manyping_csrf_token');
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('MAX_REQUESTS_PER_IP', 20); // Maximum requests per IP per minute
define('RATE_LIMIT_WINDOW', 60); // Rate limit window in seconds
define('LOG_DIRECTORY_PERMISSIONS', 0750);
define('LOG_FILE_PERMISSIONS', 0640);

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !isset($_SESSION[CSRF_TOKEN_NAME . '_time'])) {
        return false;
    }
    
    // Check token expiry
    if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION[CSRF_TOKEN_NAME]);
        unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
        return false;
    }
    
    // Constant-time comparison to prevent timing attacks
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Set security headers
 */
function setSecurityHeaders() {
    // Prevent XSS attacks
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'");
    
    // Prevent MIME-type sniffing
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Disable caching for sensitive pages
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

/**
 * Initialize secure session
 */
function initSecureSession() {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.sid_length', 48);
    ini_set('session.sid_bits_per_character', 6);
    
    // Configure session lifetime
    ini_set('session.gc_maxlifetime', 3600); // 60 minutes
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_start();
    
    // Session fixation protection
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['created'] = time();
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    }
    
    // Check session age and IP
    if (isset($_SESSION['created']) && (time() - $_SESSION['created'] > 3600)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['initiated'] = true;
        $_SESSION['created'] = time();
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    }
    
    // Session hijacking protection - verify IP hasn't changed
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['initiated'] = true;
        $_SESSION['created'] = time();
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    }
    
    // Regenerate session ID periodically (every 30 minutes)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * IP-based rate limiting
 */
function checkRateLimit($ip) {
    $rateLimitFile = sys_get_temp_dir() . '/manyping_rate_limit_' . md5($ip) . '.dat';
    
    $currentTime = time();
    $requests = [];
    
    // Read existing rate limit data
    if (file_exists($rateLimitFile)) {
        $data = @file_get_contents($rateLimitFile);
        if ($data !== false) {
            $requests = json_decode($data, true) ?: [];
        }
    }
    
    // Remove old requests outside the window
    $requests = array_filter($requests, function($timestamp) use ($currentTime) {
        return ($currentTime - $timestamp) < RATE_LIMIT_WINDOW;
    });
    
    // Check if rate limit exceeded
    if (count($requests) >= MAX_REQUESTS_PER_IP) {
        return false;
    }
    
    // Add current request
    $requests[] = $currentTime;
    
    // Save updated rate limit data
    @file_put_contents($rateLimitFile, json_encode($requests), LOCK_EX);
    
    return true;
}

/**
 * Sanitize and validate IP address
 */
function sanitizeIP($ip) {
    $ip = trim($ip);
    
    // Validate IP format
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    
    // Block private and reserved ranges for security (optional - uncomment if needed)
    // This prevents scanning internal networks
    /*
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return false;
    }
    */
    
    return $ip;
}

/**
 * Sanitize session ID for file operations
 */
function sanitizeSessionId($sessionId) {
    // Only allow alphanumeric, dash, and underscore
    if (!preg_match('/^[a-zA-Z0-9_-]{1,128}$/', $sessionId)) {
        return false;
    }
    return $sessionId;
}

/**
 * Secure log file creation
 */
function createSecureLogDirectory($directory) {
    if (!file_exists($directory)) {
        if (!mkdir($directory, LOG_DIRECTORY_PERMISSIONS, true)) {
            return false;
        }
        
        // Create .htaccess to prevent direct access
        $htaccess = $directory . '/.htaccess';
        file_put_contents($htaccess, "Deny from all\n", LOCK_EX);
        chmod($htaccess, 0640);
    }
    return true;
}

/**
 * Log security events
 */
function logSecurityEvent($event, $details = '') {
    $logDir = __DIR__ . '/logs';
    createSecureLogDirectory($logDir);
    
    $logFile = $logDir . '/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
    
    $logEntry = sprintf(
        "[%s] IP: %s | Event: %s | Details: %s | User-Agent: %s\n",
        $timestamp,
        $ip,
        $event,
        $details,
        $userAgent
    );
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    @chmod($logFile, LOG_FILE_PERMISSIONS);
}

/**
 * Validate origin for CSRF protection
 */
function validateOrigin() {
    if (!isset($_SERVER['HTTP_REFERER'])) {
        return true; // Allow if no referer (some browsers/privacy tools strip this)
    }
    
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $host = $_SERVER['HTTP_HOST'];
    
    if (!isset($referer['host']) || $referer['host'] !== $host) {
        return false;
    }
    
    return true;
}
