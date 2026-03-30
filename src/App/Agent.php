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
     * Exécute une requête complète avec boucle de raisonnement avancée (XML Thought-Action-Observation).
     * @param string $query La question de l'utilisateur.
     * @param array $history L'historique des messages.
     * @param callable|null $onStep Callback pour streamer la pensée en temps réel.
     */
    public function run(string $query, array $history = [], ?callable $onStep = null): string {
        $messages = $this->prepareInitialMessages($history);
        $messages[] = ["role" => "user", "content" => $query];
        
        $iteration = 0;
        $fullResponse = "";

        while ($iteration < $this->maxIterations) {
            $iteration++;
            
            // 1. Demander à l'IA (en bloquant pour le raisonnement interne)
            $response = $this->llm->ask($query, $history); // Note: ask() gère l'historique en interne avec prepareMessages()
            
            // Extraction de la pensée (<thought>...</thought>)
            if (preg_match('/<thought>(.*?)<\/thought>/s', $response, $thoughtMatch)) {
                $thought = trim($thoughtMatch[1]);
                if ($onStep) $onStep("RÉFLEXION : " . $thought);
            }

            // Extraction de l'action (<action>outil(args)</action>)
            if (preg_match('/<action>\s*(\w+)\((.*)\)\s*<\/action>/s', $response, $actionMatch)) {
                $toolName = $actionMatch[1];
                $argsRaw = $actionMatch[2];
                $args = $this->parseArgs($argsRaw);

                if ($onStep) $onStep("ACTION : **$toolName** (" . json_encode($args) . ")");

                // 2. Exécuter l'outil
                $observation = $this->registry->executeTool($toolName, $args);
                
                // Garde-fou de contexte : on tronque les sorties trop longues pour le LLM
                $cleanObservation = $observation;
                if (mb_strlen($observation) > 5000) {
                    $cleanObservation = mb_substr($observation, 0, 5000) . "\n... [Sortie tronquée par le système pour préserver le contexte]";
                }

                if ($onStep) $onStep("OBSERVATION : " . (mb_strlen($observation) > 150 ? mb_substr($observation, 0, 150) . "..." : $observation));

                // 3. Réinjecter l'observation dans le contexte
                // On simule une suite de conversation
                $history[] = ["q" => $query, "a" => $response];
                $query = "OBSERVATION DE L'OUTIL $toolName :\n$cleanObservation\n\nAnalyse ce résultat et continue ton raisonnement ou donne la réponse finale si tu as terminé.";
                
                // On met à jour la réponse accumulée si besoin (non nécessaire en mode agent pur)
                continue; 
            }

            // Si aucune action n'est détectée ou si <answer> est présent
            if (preg_match('/<answer>(.*?)<\/answer>/s', $response, $answerMatch)) {
                return trim($answerMatch[1]);
            }

            // Fail-safe : Si pas de balises mais du texte, on considère que c'est la réponse
            return $response;
        }

        return "Désolé, j'ai atteint ma limite de réflexion (5 cycles) sans trouver de solution définitive.";
    }

    /**
     * Prépare le prompt système enrichi avec les outils.
     */
    private function prepareInitialMessages(array $history): array {
        $toolsDescription = $this->registry->getPromptDescription();
        $baseSystemPrompt = $this->llm->getSystemPrompt();
        
        $agentInstructions = "\n\n--- INSTRUCTIONS AGENT (MODE RÉFLEXION) ---\n" .
        "Tu es un Agent Expert capable d'utiliser des outils pour répondre précisément.\n" .
        "Pour chaque étape, tu DOIS structurer ta réponse ainsi :\n" .
        "1. <thought>Ta réflexion interne sur ce que tu vas faire.</thought>\n" .
        "2. <action>nom_outil(args)</action> (si tu as besoin d'une information externe).\n" .
        "3. <answer>Ta réponse finale une fois que tu as toutes les informations.</answer>\n\n" .
        "Si tu utilises un outil, attends 'OBSERVATION' avant de conclure.\n";

        $this->llm->setSystemPrompt($baseSystemPrompt . $agentInstructions . $toolsDescription);
        return $history;
    }

    private function parseArgs($raw) {
        $raw = trim($raw);
        if (empty($raw)) return [];

        // Format JSON
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }

        // Format key="val"
        $args = [];
        preg_match_all('/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s,)]+))/', $raw, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $key = $m[1];
            $value = $m[2] ?: ($m[3] ?: $m[4]);
            $args[$key] = $value;
        }
        return $args;
    }
}
