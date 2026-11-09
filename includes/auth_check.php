<?php
/**
 * Authentication Guard
 * Include this at the very top of every protected page.
 * Redirects to login.php if no valid session is found.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id_user'])) {
    // Remember the page they tried to visit
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}
