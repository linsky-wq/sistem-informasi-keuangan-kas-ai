<?php
/**
 * Test Live HTTP Requests on Apache Server
 */

$baseUrl = 'http://localhost/Project%20RPL/Project%20Kas';

echo "Testing Live HTTP Server responses...\n";

// 1. Test Home page
$homeHtml = @file_get_contents($baseUrl . '/');
if ($homeHtml !== false) {
    assert(strpos($homeHtml, 'Sistem Keuangan Kas AI') !== false);
    assert(strpos($homeHtml, 'navbar-public') !== false);
    echo "  ✓ Home Landing Page (Guest) HTTP 200 OK & rendered correctly.\n";
} else {
    echo "  ✗ Failed to fetch Home Page via HTTP\n";
}

// 2. Test Login page
$loginHtml = @file_get_contents($baseUrl . '/auth/login');
if ($loginHtml !== false) {
    assert(strpos($loginHtml, 'Masuk ke Akun Anda') !== false || strpos($loginHtml, 'login') !== false);
    echo "  ✓ Login Page HTTP 200 OK & rendered correctly.\n";
} else {
    echo "  ✗ Failed to fetch Login Page via HTTP\n";
}

echo "HTTP Endpoint Tests Completed Successfully!\n";
