<?php
// Common bootstrap for all JSON API endpoints.
// Loads config + DB + helpers, requires login, sets JSON header.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Always JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Auth required (returns 401 JSON if not)
require_ajax_login();
