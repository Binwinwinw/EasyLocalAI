<?php

namespace EasyLocalAI\Tools\Implementations;

use EasyLocalAI\Tools\ToolInterface;

class CodeRunnerTool implements ToolInterface {
    private $tmpFile;

    public function __construct() {
        $this->tmpFile = '/tmp/agent_sandbox.php';
        $dir = dirname($this->tmpFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public function getName(): string {
        return "run_code";
    }

    public function getDescription(): string {
        return "Exécute un snippet de code PHP et retourne le résultat de STDOUT. Utile pour vérifier un algorithme ou tester une logique avant de la valider.";
    }

    public function getParameters(): array {
        return [
            "code" => "Le code PHP complet à exécuter (incluant <?php )."
        ];
    }

    public function execute(array $args): string {
        if (!isset($args['code'])) {
            return "Erreur: Le paramètre 'code' est requis.";
        }

        $code = $args['code'];
        
        // --- GARDE-FOU : Liste Noire de Fonctions Dangereuses ---
        $forbidden = [
            'system', 'shell_exec', 'exec', 'passthru', 'popen', 'proc_open', 'pcntl_exec',
            'curl_init', 'file_get_contents', 'file_put_contents', 'unlink', 'rmdir',
            'mysql_', 'mysqli_', 'pdo', 'eval', 'assert', 'create_function'
        ];

        foreach ($forbidden as $func) {
            if (stripos($code, $func) !== false) {
                return "Sécurité : La fonction '$func' est interdite dans le bac à sable de l'Agent.";
            }
        }

        // Vérification des backticks (exécution de commande invisible)
        if (strpos($code, '`') !== false) {
             return "Sécurité : L'utilisation des backticks pour l'exécution système est interdite.";
        }

        file_put_contents($this->tmpFile, $code);

        // Exécution via PHP CLI avec limitations strictes :
        // - max_execution_time=2 : Empêche les boucles infinies
        // - open_basedir : Limite l'accès fichiers au dossier tmp
        $tmpDir = dirname($this->tmpFile);
        $cmd = "php -d max_execution_time=2 -d open_basedir=" . escapeshellarg($tmpDir) . " " . escapeshellarg($this->tmpFile) . " 2>&1";
        
        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        $result = implode("\n", $output);
        
        return "--- Résultat de l'exécution ---\n" . ($result ?: "(Aucune sortie)") . "\n--- Statut: " . ($returnVar === 0 ? "Succès" : "Erreur/Timeout ($returnVar)") . " ---";
    }
}
