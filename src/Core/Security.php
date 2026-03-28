<?php

namespace EasyLocalAI\Core;

/**
 * EasyLocalAI - Security Utilities
 * Gère les tokens CSRF et la validation des entrées.
 */
class Security {
    /**
     * Génère ou récupère un jeton CSRF pour l'inclusion dans un formulaire.
     */
    public static function getCsrfToken() {
        // Note: La session est déjà démarrée dans bootstrap.php
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Vérifie si le jeton fourni correspond à celui en session.
     * Cette méthode NE génère PAS de nouveau jeton s'il est absent.
     */
    public static function checkCsrf($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Nettoie une chaîne de caractères (XSS).
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Génère un jeton unique par page (Optionnel, non utilisé ici).
     */
    public static function getCsrfTokenForPage($pageId) {
        return hash_hmac('sha256', $pageId, self::getCsrfToken());
    }
}
