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

use EasyLocalAI\Core\Container;
use EasyLocalAI\Core\Config;
use EasyLocalAI\Core\Auth;
use EasyLocalAI\Core\Ollama;
use EasyLocalAI\App\Memory;
use EasyLocalAI\RAG\RAG;
use EasyLocalAI\Setup\SetupManager;

// Force Error Reporting in dev
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Enregistrement des Services (Conteneur DI) ---

Container::register('config', function() {
    return new Config();
});

Container::register('auth', function() {
    return new Auth(Container::get('config'));
});

Container::register('memory', function() {
    return new Memory();
});

Container::register('ollama', function() {
    return new Ollama(Container::get('config'), Container::get('memory')->getContextString());
});

Container::register('rag', function() {
    return new RAG();
});

Container::register('setup', function() {
    return new SetupManager(Container::get('config'));
});

// --- Initialisation de la Sécurité ---
$auth = Container::get('auth');
$auth->protect();
