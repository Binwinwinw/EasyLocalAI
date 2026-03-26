<?php

namespace EasyLocalAI\Tools;

class ToolRegistry {
    private $tools = [];

    public function register(ToolInterface $tool) {
        $this->tools[$tool->getName()] = $tool;
    }

    public function getTools(): array {
        return $this->tools;
    }

    /**
     * Génère la description des outils pour le System Prompt.
     */
    public function getPromptDescription(): string {
        if (empty($this->tools)) return "";

        $desc = "Tu as accès aux outils suivants. Pour utiliser un outil, écris EXACTEMENT : [TOOL: nom_outil(clé=\"valeur\")]\n\n";
        foreach ($this->tools as $tool) {
            $desc .= "- " . $tool->getName() . ": " . $tool->getDescription() . "\n";
            $params = json_encode($tool->getParameters());
            $desc .= "  Paramètres: $params\n";
        }
        $desc .= "\nExemple: [TOOL: get_time()]\n";
        return $desc;
    }

    public function executeTool(string $name, array $args): string {
        if (!isset($this->tools[$name])) {
            return "Erreur: Outil '$name' non trouvé.";
        }
        return $this->tools[$name]->execute($args);
    }
}
