<?php

namespace EasyLocalAI\Tools\Implementations;

use EasyLocalAI\Tools\ToolInterface;
use EasyLocalAI\Core\Container;

class SearchKnowledgeTool implements ToolInterface {
    public function getName(): string {
        return "search_knowledge";
    }

    public function getDescription(): string {
        return "Recherche des informations spécifiques dans la base de connaissance (RAG) du projet. À utiliser UNIQUEMENT pour des faits techniques ou métier présents dans les documents, et JAMAIS pour ton identité ou ton rôle.";
    }

    public function getParameters(): array {
        return [
            "query" => "La question ou les mots-clés à rechercher dans la base sémantique."
        ];
    }

    public function execute(array $args): string {
        if (!isset($args['query'])) {
            return "Erreur: Le paramètre 'query' est requis.";
        }

        $rag = Container::get('rag');
        $context = $rag->getContext($args['query']);

        if (empty($context)) {
            return "Aucune connaissance correspondante trouvée dans la base RAG.";
        }

        return $context;
    }
}
