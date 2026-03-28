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

        $desc = "--- MODE AGENT ACTIF ---\n";
        $desc .= "Tu as accès aux outils suivants pour interagir avec le système.\n";
        $desc .= "RÈGLE D'OR : Utilise EXCLUSIVEMENT le format [TOOL: nom_outil(clé=\"valeur\")] ou [TOOL: nom_outil({\"clé\":\"valeur\"})].\n";
        $desc .= "ATTENTION : Ne jamais utiliser 'list_directory' pour écrire un fichier. Utilise 'write_file' pour toute création/modification.\n";
        $desc .= "CONTEXTE PROJET : Le coeur de l'application est dans 'src/App/'. Ne pas inventer de dossiers 'src/Core/' pour les classes applicatives.\n\n";

        foreach ($this->tools as $tool) {
            $desc .= "🔧 Outil: " . $tool->getName() . "\n";
            $desc .= "   Description: " . $tool->getDescription() . "\n";
            $params = json_encode($tool->getParameters());
            $desc .= "   Paramètres: $params\n\n";
        }

        $desc .= "Exemple correct: [TOOL: write_file(path=\"src/App/Test.php\", content=\"<?php ...\")]\n";
        return $desc;
    }

    public function executeTool(string $name, array $args): string {
        if (!isset($this->tools[$name])) {
            return "Erreur: Outil '$name' non trouvé.";
        }
        return $this->tools[$name]->execute($args);
    }
}
