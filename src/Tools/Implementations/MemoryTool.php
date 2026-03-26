<?php

namespace EasyLocalAI\Tools\Implementations;

use EasyLocalAI\Tools\ToolInterface;
use EasyLocalAI\App\Memory;

class MemoryTool implements ToolInterface {
    private $memory;

    public function __construct(Memory $memory) {
        $this->memory = $memory;
    }

    public function getName(): string {
        return "search_memory";
    }

    public function getDescription(): string {
        return "Recherche des faits importants enregistrés sur l'utilisateur dans sa mémoire persistante.";
    }

    public function getParameters(): array {
        return ["query" => "Terme de recherche (facultatif)"];
    }

    public function execute(array $args): string {
        $facts = $this->memory->getFacts();
        if (empty($facts)) return "La mémoire est vide.";
        
        $query = $args['query'] ?? null;
        if ($query) {
            $filtered = array_filter($facts, function($f) use ($query) {
                return stripos($f, $query) !== false;
            });
            return "Faits trouvés pour '$query' : " . implode(" | ", $filtered ?: ["Aucun match"]);
        }

        return "Tous les souvenirs : " . implode(" | ", $facts);
    }
}
