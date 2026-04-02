<?php
// src/Core/LlmInterface.php

namespace EasyLocalAI\Core;

/**
 * Interface universelle pour les modèles de langage.
 * Permet d'unifier l'utilisation d'Ollama et des services Cloud.
 */
interface LlmInterface
{
    /**
     * Envoie une requête au modèle et retourne la réponse complète.
     */
    public function ask(string $prompt, array $history = [], array $images = []): string;

    /**
     * Envoie une requête et gère le streaming de la réponse (SSE).
     */
    public function stream(string $prompt, array $history = [], array $images = []): void;

    /**
     * Récupère le system prompt actuel.
     */
    public function getSystemPrompt(): string;

    /**
     * Définit ou surcharge le system prompt.
     */
    public function setSystemPrompt(string $prompt): void;
}
