<?php
// src/App/Conversation.php

namespace EasyLocalAI\App;

class Conversation
{
    /**
     * Récupère l'historique de la conversation depuis la session.
     */
    public static function getHistory(): array
    {
        return $_SESSION['conversation_history'] ?? [];
    }

    /**
     * Ajoute un échange à l'historique en session.
     */
    public static function addMessage(string $question, string $answer): void
    {
        if (!isset($_SESSION['conversation_history'])) {
            $_SESSION['conversation_history'] = [];
        }
        $_SESSION['conversation_history'][] = ['q' => $question, 'a' => $answer];
    }

    /**
     * Réinitialise l'historique en session.
     */
    public static function clearHistory(): void
    {
        $_SESSION['conversation_history'] = [];
    }

    /**
     * Limite la taille de l'historique pour éviter la saturation du contexte.
     */
    public static function limit(int $count = 5): void
    {
        if (isset($_SESSION['conversation_history'])) {
            $_SESSION['conversation_history'] = array_slice($_SESSION['conversation_history'], -$count);
        }
    }
}
