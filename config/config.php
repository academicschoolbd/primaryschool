<?php
// Application configuration
session_start();

define('APP_NAME', 'EduSaaS');
define('APP_TAGLINE', 'Primary School Management System');
define('BASE_URL', '/school-saas');
define('ADMIN_URL', BASE_URL . '/admin');
define('ASSETS_URL', BASE_URL . '/assets');

date_default_timezone_set('UTC');
