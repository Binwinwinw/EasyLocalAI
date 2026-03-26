<?php

namespace EasyLocalAI\App;

use EasyLocalAI\Core\Ollama;
use EasyLocalAI\Tools\ToolRegistry;

/**
 * EasyLocalAI - Agent Orchestrator
 * Gère le cycle de réflexion de l'IA et l'utilisation d'outils.
 */
class Agent {
    private $ollama;
    private $registry;

    public function __construct(Ollama $ollama, ToolRegistry $registry) {
        $this->ollama = $ollama;
        $this->registry = $registry;
    }

    /**
     * Exécute une requête complète (avec outils).
     */
    public function run(string $query, array $history = []) {
        // 1. Préparer le prompt avec les outils
        $toolsDescription = $this->registry->getPromptDescription();
        $this->ollama->setSystemPrompt($this->ollama->getSystemPrompt() . "\n\n" . $toolsDescription);

        // 2. Premier appel à l'IA
        $response = $this->ollama->ask($query, $history);

        // 3. Détecter l'utilisation d'un outil : [TOOL: name(args)]
        if (preg_match('/\[TOOL:\s*(\w+)\((.*)\)\]/', $response, $matches)) {
            $toolName = $matches[1];
            $argsRaw = $matches[2];
            $args = $this->parseArgs($argsRaw);

            // 4. Exécuter l'outil
            $result = $this->registry->executeTool($toolName, $args);

            // 5. Renvoyer le résultat à l'IA pour la réponse finale
            $newQuery = "Résultat de l'outil $toolName: $result\nUtilise ce résultat pour répondre à l'utilisateur.";
            return $this->ollama->ask($newQuery, array_merge($history, [["role" => "user", "content" => $query], ["role" => "assistant", "content" => $response]]));
        }

        return $response;
    }

    private function parseArgs($raw) {
        $args = [];
        // Parse key="val", key2="val2"
        preg_match_all('/(\w+)="([^"]*)"/', $raw, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $args[$m[1]] = $m[2];
        }
        return $args;
    }
}
