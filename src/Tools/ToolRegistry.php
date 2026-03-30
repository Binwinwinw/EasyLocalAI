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

    public function getPromptDescription(): string {
        if (empty($this->tools)) return "";

        $desc = "--- CATALOGUE D'OUTILS DISPONIBLES ---\n";
        $desc .= "Pour utiliser un outil, écris EXCLUSIVEMENT : <action>nom_outil(clé=\"valeur\")</action>.\n\n";

        foreach ($this->tools as $tool) {
            $desc .= "🛠️ " . $tool->getName() . " : " . $tool->getDescription() . "\n";
            $params = [];
            foreach ($tool->getParameters() as $pName => $pDesc) {
                $params[] = "$pName (ex: \"...\")";
            }
            $desc .= "   Usage: <action>" . $tool->getName() . "(" . implode(", ", $params) . ")</action>\n\n";
        }

        return $desc;
    }

    public function executeTool(string $name, array $args): string {
        if (!isset($this->tools[$name])) {
            return "Erreur: Outil '$name' non trouvé.";
        }
        return $this->tools[$name]->execute($args);
    }
}
