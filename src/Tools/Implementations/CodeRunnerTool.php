<?php

namespace EasyLocalAI\Tools\Implementations;

use EasyLocalAI\Tools\ToolInterface;

class CodeRunnerTool implements ToolInterface {
    private $tmpFile;

    public function __construct() {
        $this->tmpFile = __DIR__ . '/../../../../public/tmp/sandbox.php';
        $dir = dirname($this->tmpFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
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
        
        // Empêcher l'exécution de code malveillant évident (très basique)
        if (strpos($code, 'shell_exec') !== false || strpos($code, 'system') !== false) {
            return "Erreur: Les fonctions système ne sont pas autorisées dans le Sandbox.";
        }

        file_put_contents($this->tmpFile, $code);

        // Exécution via PHP CLI pour capturer la sortie
        $output = [];
        $returnVar = 0;
        exec("php " . escapeshellarg($this->tmpFile) . " 2>&1", $output, $returnVar);

        $result = implode("\n", $output);
        
        return "--- Résultat de l'exécution ---\n" . ($result ?: "(Aucune sortie)") . "\n--- Code de retour: $returnVar ---";
    }
}
