<?php

namespace EasyLocalAI\App;

use EasyLocalAI\Core\LlmInterface;
use EasyLocalAI\Tools\ToolRegistry;

/**
 * EasyLocalAI - Agent Orchestrator
 * Gère le cycle de réflexion de l'IA et l'utilisation d'outils.
 */
class Agent {
    private $llm;
    private $registry;
    private $maxIterations = 5;

    public function __construct(LlmInterface $llm, ToolRegistry $registry) {
        $this->llm = $llm;
        $this->registry = $registry;
    }

    /**
     * Exécute une requête complète avec boucle de raisonnement.
     * @param callable|null $onStep Callback pour streamer la "pensée" de l'IA.
     */
    public function run(string $query, array $history = [], ?callable $onStep = null) {
        $currentHistory = $history;
        $currentQuery = $query;
        $iteration = 0;

        // Préparer les descriptions d'outils une seule fois
        $toolsDescription = $this->registry->getPromptDescription();
        $baseSystemPrompt = $this->llm->getSystemPrompt();
        $this->llm->setSystemPrompt($baseSystemPrompt . "\n\n" . $toolsDescription);

        while ($iteration < $this->maxIterations) {
            $iteration++;
            
            // 1. Demander à l'IA
            $response = $this->llm->ask($currentQuery, $currentHistory);

            // 2. Détecter l'utilisation d'un outil : [TOOL: name(args)]
            // On cherche la première occurrence
            if (preg_match('/\[TOOL:\s*(\w+)\((.*)\)\]/s', $response, $matches)) {
                $toolName = $matches[1];
                $argsRaw = $matches[2];
                $args = $this->parseArgs($argsRaw);

                if ($onStep) $onStep("Utilisation de l'outil : **$toolName** (" . json_encode($args) . ")");

                // 3. Exécuter l'outil
                $result = $this->registry->executeTool($toolName, $args);
                
                if ($onStep) $onStep("Résultat : " . (strlen($result) > 100 ? substr($result, 0, 100) . "..." : $result));

                // 4. Mettre à jour l'histoire pour le prochain tour
                $currentHistory[] = ["role" => "user", "content" => $currentQuery];
                $currentHistory[] = ["role" => "assistant", "content" => $response];
                
                $currentQuery = "Résultat de l'outil $toolName: $result\nContinue ta réflexion ou termine ta réponse.";
                continue; // On boucle pour que l'IA puisse utiliser le résultat
            }

            // Si aucun outil n'est détecté, c'est la réponse finale
            return $response;
        }

        return "Erreur : Trop d'itérations de réflexion.";
    }

    private function parseArgs($raw) {
        $args = [];
        // Parse key="val", key='val' or key=val
        preg_match_all('/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s,)]+))/', $raw, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $key = $m[1];
            $value = $m[2] ?: ($m[3] ?: $m[4]);
            $args[$key] = $value;
        }
        return $args;
    }
}
