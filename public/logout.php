<?php
/**
 * Logout Handler
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/functions/auth.php';

session_start();
logout_user();

redirect('index.php');
