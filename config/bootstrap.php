<?php
// config/bootstrap.php

spl_autoload_register(function ($class) {
    $prefix = 'EasyLocalAI\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use EasyLocalAI\Core\Config;
use EasyLocalAI\Core\Auth;

// Force Error Reporting in dev
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize Security
$auth = new Auth(new Config());
$auth->protect();
