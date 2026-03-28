<?php
// config/bootstrap.php - Agent & Tools Refactor (Hybrid Edition V4.3)

// Centralisation Sécurisée de la Session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

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
use EasyLocalAI\Core\CloudLlm;
use EasyLocalAI\App\Memory;
use EasyLocalAI\RAG\RAG;
use EasyLocalAI\Setup\SetupManager;
use EasyLocalAI\Tools\ToolRegistry;
use EasyLocalAI\Tools\Implementations\ClockTool;
use EasyLocalAI\Tools\Implementations\MemoryTool;
use EasyLocalAI\Tools\Implementations\FileReadTool;
use EasyLocalAI\Tools\Implementations\DirectoryListTool;
use EasyLocalAI\Tools\Implementations\SearchKnowledgeTool;
use EasyLocalAI\Tools\Implementations\FileWriteTool;
use EasyLocalAI\Tools\Implementations\CodeRunnerTool;
use EasyLocalAI\App\Agent;

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

/**
 * Service Ollama (Gestion Locale)
 */
Container::register('ollama', function() {
    return new Ollama(Container::get('config'), Container::get('memory')->getContextString());
});

/**
 * Injection Dynamique du service LLM (IA Hybride).
 */
Container::register('llm', function() {
    $config = Container::get('config');
    $provider = $config->get('active_provider', 'ollama');
    $memoryContext = Container::get('memory')->getContextString();

    if ($provider === 'ollama') {
        return Container::get('ollama');
    } else {
        $defaults = [
            'cortex'  => ['url' => 'http://cortex_gateway:8000/v1/chat/completions', 'model' => 'cortex'],
            'groq'    => ['url' => 'https://api.groq.com/openai/v1/chat/completions', 'model' => 'llama-3.3-70b-versatile'],
            'openai'  => ['url' => 'https://api.openai.com/v1/chat/completions', 'model' => 'gpt-4o'],
            'minimax' => ['url' => 'https://api.minimax.io/v1/chat/completions', 'model' => 'minimax-m2.7']
        ];

        $baseUrl = $config->get("cloud_{$provider}_base_url", $defaults[$provider]['url'] ?? "");
        $apiKey  = $_SESSION["cloud_{$provider}_api_key"] ?? $_GET['key'] ?? ""; 
        $model   = $config->get("cloud_{$provider}_model", $defaults[$provider]['model'] ?? "gpt-3.5-turbo");
        $sysPrompt = $config->getSystemPrompt();

        return new CloudLlm($baseUrl, $apiKey, $model, $sysPrompt);
    }
});

Container::register('embedder', function() {
    return new \EasyLocalAI\RAG\Embedder(Container::get('config'));
});

Container::register('vector_store', function() {
    return new \EasyLocalAI\RAG\VectorStore();
});

Container::register('rag', function() {
    return new RAG(
        Container::get('embedder'),
        Container::get('vector_store')
    );
});

Container::register('setup', function() {
    return new SetupManager(Container::get('config'));
});

// --- Phase 2: AI Agency ---

Container::register('tool_registry', function() {
    $registry = new ToolRegistry();
    $registry->register(new ClockTool());
    $registry->register(new MemoryTool(Container::get('memory')));
    $registry->register(new FileReadTool());
    $registry->register(new DirectoryListTool());
    $registry->register(new SearchKnowledgeTool());
    $registry->register(new FileWriteTool(Container::get('rag')));
    $registry->register(new CodeRunnerTool());
    return $registry;
});

Container::register('agent', function() {
    return new Agent(Container::get('llm'), Container::get('tool_registry'));
});

// --- Initialisation de la Sécurité ---
$auth = Container::get('auth');
$auth->protect();
