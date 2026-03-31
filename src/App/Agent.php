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
    private $config;
    private $memory;
    private $maxIterations = 5;

    public function __construct(LlmInterface $llm, ToolRegistry $registry, \EasyLocalAI\Core\Config $config, \EasyLocalAI\App\Memory $memory) {
        $this->llm = $llm;
        $this->registry = $registry;
        $this->config = $config;
        $this->memory = $memory;
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

            // 4. Détection de la réponse finale <answer>
            if (preg_match('/<answer>(.*?)<\/answer>/s', $response, $answerMatch)) {
                return trim($answerMatch[1]);
            }

            // 5. Fail-safe : Si l'IA n'a plus d'action à proposer et qu'elle n'a pas utilisé <answer>, 
            // on vérifie si elle a quand même donné une réponse textuelle hors balises.
            $cleanResponse = preg_replace('/<(thought|action|observation)>.*?<\/\1>/s', '', $response);
            $cleanResponse = trim(strip_tags($cleanResponse));

            if (!empty($cleanResponse) && !str_contains($response, '<action>')) {
                return $cleanResponse;
            }
        }

        return "Désolé, j'ai atteint ma limite de réflexion (5 cycles). Mon analyse s'arrête ici : " . ($cleanResponse ?? "Pas de réponse.");
    }

    /**
     * Prépare le prompt système enrichi avec les outils, le Persona JSON et la Mémoire Vive.
     */
    private function prepareInitialMessages(array $history): array {
        // 1. Chargement du Persona JSON
        $persona = $this->config->get('persona');
        $expertName = $persona['name'] ?? 'EasyLocalAI';
        $expertRole = $persona['role'] ?? 'Assistant IA';
        $expertTone = $persona['tone'] ?? 'Professionnel';
        $expertInstructions = $persona['instructions'] ?? [];

        $personaPrompt = "--- IDENTITÉ DE L'AGENT ---\n" .
                         "NOM : $expertName\n" .
                         "RÔLE : $expertRole\n" .
                         "TON : $expertTone\n\n" .
                         "--- RÈGLES DE CONDUITE ---\n";
        
        foreach ($expertInstructions as $rule) {
            $personaPrompt .= "- $rule\n";
        }

        // 2. Injection de la Mémoire Vive (Facts)
        $memoryString = $this->memory->getContextString();

        // 3. Description des outils
        $toolsDescription = $this->registry->getPromptDescription();
        
        $agentInstructions = "\n\n--- PROTOCOLE DE RÉFLEXION AGENT ---\n" .
        "Tu es une IA agentique locale. Pour chaque message :\n" .
        "1. <thought>Ta réflexion courte. SI LA QUESTION EST SUR TON IDENTITÉ, RÉPONDS DIRECTEMENT SANS OUTIL.</thought>\n" .
        "2. <action>nom_outil(clé=\"valeur\")</action> (Seulement si tu as BESOIN d'une info externe).\n" .
        "3. <answer>Ta réponse finale concise.</answer>\n\n" .
        "INTERDICTION : Ne cherche pas dans les fichiers ou internet pour ton nom ou ton rôle.\n";

        $finalSystemPrompt = $personaPrompt . $memoryString . $agentInstructions . $toolsDescription;
        
        $this->llm->setSystemPrompt($finalSystemPrompt);
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
