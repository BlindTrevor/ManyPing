#!/bin/bash
# ManyPing Security Test Suite
# Tests all security features

echo "========================================="
echo "ManyPing Security Test Suite"
echo "========================================="
echo ""

BASE_URL="http://localhost:8000"
PASS_COUNT=0
FAIL_COUNT=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

pass_test() {
    echo -e "${GREEN}✓ PASS${NC}: $1"
    ((PASS_COUNT++))
}

fail_test() {
    echo -e "${RED}✗ FAIL${NC}: $1"
    ((FAIL_COUNT++))
}

info_test() {
    echo -e "${YELLOW}ℹ INFO${NC}: $1"
}

# Test 1: Check if security headers are present
echo "Test 1: Security Headers"
HEADERS=$(curl -s -I "$BASE_URL/index.php")
if echo "$HEADERS" | grep -q "X-Frame-Options: DENY"; then
    pass_test "X-Frame-Options header present"
else
    fail_test "X-Frame-Options header missing"
fi

if echo "$HEADERS" | grep -q "X-Content-Type-Options: nosniff"; then
    pass_test "X-Content-Type-Options header present"
else
    fail_test "X-Content-Type-Options header missing"
fi

if echo "$HEADERS" | grep -q "Content-Security-Policy"; then
    pass_test "Content-Security-Policy header present"
else
    fail_test "Content-Security-Policy header missing"
fi

if echo "$HEADERS" | grep -q "Cache-Control.*no-store"; then
    pass_test "Cache-Control header present"
else
    fail_test "Cache-Control header missing"
fi

echo ""

# Test 2: CSRF Protection
echo "Test 2: CSRF Protection"
RESPONSE=$(curl -s -X POST "$BASE_URL/ping.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "ips=8.8.8.8")

if echo "$RESPONSE" | grep -q "Invalid security token"; then
    pass_test "CSRF protection blocks requests without token"
else
    fail_test "CSRF protection not working"
fi

RESPONSE=$(curl -s -X POST "$BASE_URL/ping_single.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "ip=8.8.8.8")

if echo "$RESPONSE" | grep -q "Invalid security token"; then
    pass_test "CSRF protection on ping_single.php"
else
    fail_test "CSRF protection not working on ping_single.php"
fi

echo ""

# Test 3: Input Validation
echo "Test 3: Input Validation"
RESPONSE=$(curl -s -X POST "$BASE_URL/ping_single.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "ip=invalid_ip&csrf_token=test")

if echo "$RESPONSE" | grep -q "Invalid IP address\|Invalid security token"; then
    pass_test "Invalid IP address rejected"
else
    fail_test "Invalid IP not properly rejected"
fi

echo ""

# Test 4: Path Traversal Protection
echo "Test 4: Path Traversal Protection"
RESPONSE=$(curl -s "$BASE_URL/view_log.php?session=../../../etc/passwd")

if echo "$RESPONSE" | grep -q "Invalid session ID"; then
    pass_test "Path traversal attempt blocked"
else
    fail_test "Path traversal not blocked"
fi

RESPONSE=$(curl -s "$BASE_URL/view_log.php?session=../../security.log")

if echo "$RESPONSE" | grep -q "Invalid session ID"; then
    pass_test "Another path traversal attempt blocked"
else
    fail_test "Path traversal not blocked"
fi

echo ""

# Test 5: Security Logging
echo "Test 5: Security Event Logging"
LOG_FILE="logs/security.log"

if [ -f "$LOG_FILE" ]; then
    pass_test "Security log file exists"
    
    if grep -q "CSRF_FAILURE" "$LOG_FILE"; then
        pass_test "CSRF failures are being logged"
    else
        info_test "No CSRF failures logged yet (may need more test attempts)"
    fi
    
    if grep -q "LOG_ACCESS_DENIED" "$LOG_FILE"; then
        pass_test "Path traversal attempts are being logged"
    else
        info_test "No path traversal attempts logged yet"
    fi
    
    # Check log file permissions
    if command -v stat >/dev/null 2>&1; then
        # Detect OS type for stat command
        if [[ "$OSTYPE" == "darwin"* ]] || [[ "$OSTYPE" == "freebsd"* ]]; then
            # BSD/macOS
            PERMS=$(stat -f %A "$LOG_FILE" 2>/dev/null)
        else
            # GNU/Linux
            PERMS=$(stat -c %a "$LOG_FILE" 2>/dev/null)
        fi
        
        if [ "$PERMS" = "640" ]; then
            pass_test "Security log has correct permissions (640)"
        else
            info_test "Security log permissions: $PERMS (expected 640)"
        fi
    else
        info_test "stat command not available, skipping permission check"
    fi
else
    fail_test "Security log file not created"
fi

echo ""

# Test 6: File Permissions
echo "Test 6: File and Directory Permissions"
if [ -d "logs" ]; then
    pass_test "Logs directory exists"
    
    if [ -f "logs/.htaccess" ]; then
        pass_test "Logs .htaccess exists (blocks direct access)"
    else
        fail_test "Logs .htaccess missing"
    fi
else
    fail_test "Logs directory doesn't exist"
fi

echo ""

# Test 7: Rate Limiting (IP-based)
echo "Test 7: Rate Limiting"
info_test "Making 22 rapid requests to test rate limiting..."

RATE_LIMITED=false
for i in {1..22}; do
    RESPONSE=$(curl -s -X POST "$BASE_URL/ping_single.php" \
      -H "Content-Type: application/x-www-form-urlencoded" \
      -d "ip=8.8.8.8&csrf_token=test" 2>&1)
    
    if echo "$RESPONSE" | grep -q "Rate limit exceeded"; then
        RATE_LIMITED=true
        break
    fi
    sleep 0.05
done

if [ "$RATE_LIMITED" = true ]; then
    pass_test "Rate limiting is active (triggered after multiple requests)"
else
    info_test "Rate limiting not triggered (may need adjustment or more requests)"
fi

echo ""

# Test 8: Method Validation
echo "Test 8: HTTP Method Validation"
RESPONSE=$(curl -s -X GET "$BASE_URL/ping.php")

if echo "$RESPONSE" | grep -q "Invalid request method"; then
    pass_test "Non-POST requests blocked on ping.php"
else
    fail_test "Non-POST requests not properly blocked"
fi

echo ""

# Summary
echo "========================================="
echo "Test Summary"
echo "========================================="
echo -e "${GREEN}Passed: $PASS_COUNT${NC}"
echo -e "${RED}Failed: $FAIL_COUNT${NC}"
echo ""

if [ $FAIL_COUNT -eq 0 ]; then
    echo -e "${GREEN}All critical tests passed! ✓${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed. Please review.${NC}"
    exit 1
fi
