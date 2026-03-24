<?php
// src/App/Conversation.php

namespace EasyLocalAI\App;

class Conversation
{
    public static function getHistory(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['conversation_history'] ?? [];
    }

    public static function addMessage(string $question, string $answer): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['conversation_history'][] = ['q' => $question, 'a' => $answer];
    }

    public static function clearHistory(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['conversation_history'] = [];
    }

    public static function limit(int $count = 5): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['conversation_history'])) {
            $_SESSION['conversation_history'] = array_slice($_SESSION['conversation_history'], -$count);
        }
    }
}
