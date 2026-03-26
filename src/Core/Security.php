<?php

namespace EasyLocalAI\Core;

/**
 * EasyLocalAI - Security Utilities
 * Gère les tokens CSRF et la validation des entrées.
 */
class Security {
    /**
     * Génère ou récupère un token CSRF.
     */
    public static function getCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Vérifie le token CSRF.
     */
    public static function checkCsrf($token) {
        return hash_equals(self::getCsrfToken(), $token);
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
}
