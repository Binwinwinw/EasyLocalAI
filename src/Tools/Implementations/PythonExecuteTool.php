<?php

namespace EasyLocalAI\Tools\Implementations;

use EasyLocalAI\Tools\ToolInterface;
use EasyLocalAI\App\ToolExecutor;

/**
 * PythonExecuteTool - Outil permettant à l'IA d'exécuter du code Python 3.13.
 */
class PythonExecuteTool implements ToolInterface {
    private ToolExecutor $executor;

    public function __construct(ToolExecutor $executor) {
        $this->executor = $executor;
    }

    public function getName(): string {
        return "python_execute";
    }

    public function getDescription(): string {
        return "Exécute du code Python 3.13 pour faire des calculs, traiter des données ou manipuler des fichiers. Retourne la sortie (stdout/stderr).";
    }

    public function getParameters(): array {
        return [
            "code" => [
                "type" => "string",
                "description" => "Le code Python complet à exécuter."
            ]
        ];
    }

    public function execute(array $args): string {
        $code = $args['code'] ?? '';
        if (empty($code)) {
            return "ERREUR : Aucun code fourni.";
        }

        $result = $this->executor->executePython($code);
        
        if ($result['success']) {
            return "RÉSULTAT DE L'EXÉCUTION :\n" . $result['output'];
        } else {
            return "ERREUR D'EXÉCUTION :\n" . $result['output'];
        }
    }
}
