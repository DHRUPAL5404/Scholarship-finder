<?php
/**
 * CSRF Protection Helper
 * ─────────────────────
 * Usage:
 *   In forms  : <?php csrf_token(); ?>
 *   On submit : csrf_verify();
 */

/**
 * Generate a CSRF token (once per session) and echo a hidden form field.
 */
function csrf_token(): void {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    echo '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Verify the CSRF token submitted with a POST request.
 * Dies immediately with HTTP 403 if the token is missing or invalid.
 */
function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (
        empty($submitted)
        || empty($expected)
        || !hash_equals($expected, $submitted)
    ) {
        http_response_code(403);
        die('CSRF token validation failed. Please go back and try again.');
    }
}
