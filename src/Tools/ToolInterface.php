<?php

namespace EasyLocalAI\Tools;

/**
 * Interface pour tous les outils utilisables par l'IA.
 */
interface ToolInterface {
    /**
     * Retourne le nom de l'outil (ex: get_weather).
     */
    public function getName(): string;

    /**
     * Retourne une description claire pour l'IA.
     */
    public function getDescription(): string;

    /**
     * Définit les paramètres attendus (format JSON schema ou simple texte).
     */
    public function getParameters(): array;

    /**
     * Exécute l'outil avec les arguments fournis.
     */
    public function execute(array $args): string;
}
