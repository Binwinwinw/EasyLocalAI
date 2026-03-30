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
     * Valide un fichier uploadé (Taille et Type Mime réel).
     * @return string|bool True si OK, sinon un message d'erreur.
     */
    public static function validateUpload($file, $maxSizeMB = 5) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return "Fichier invalide.";
        }

        // 1. Taille
        $maxBytes = $maxSizeMB * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return "Fichier trop volumineux (max {$maxSizeMB} Mo).";
        }

        // 2. Type MIME réel (via finfo)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        $allowed = [
            'text/plain',
            'application/pdf',
            'text/markdown',
            'application/octet-stream' // Parfois les .md sont vus comme ça
        ];

        if (!in_array($mime, $allowed)) {
            return "Format de fichier non autorisé ({$mime}). .txt, .pdf, .md uniquement.";
        }

        return true;
    }
}
