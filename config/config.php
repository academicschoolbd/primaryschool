<?php
// Application configuration
session_start();

// Enable output buffering globally so redirect()/header() works even if
// admin page chrome has already started rendering. Prevents
// "Cannot modify header information - headers already sent" errors.
if (ob_get_level() === 0) {
    ob_start();
}

define('APP_NAME', 'EduSaaS');
define('APP_TAGLINE', 'Primary School Management System');
define('BASE_URL', '/school-saas');
define('ADMIN_URL', BASE_URL . '/admin');
define('ASSETS_URL', BASE_URL . '/assets');

date_default_timezone_set('UTC');
