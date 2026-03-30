<?php
// src/Core/SecurityGuard.php

namespace EasyLocalAI\Core;

/**
 * EasyLocalAI - Security Guard
 * Centralise les règles de protection contre les accès non autorisés par l'Agent.
 */
class SecurityGuard
{
    private static array $forbiddenFiles = [
        '.env',
        'docker-compose.yml',
        'config/settings.json',
        'config/bootstrap.php',
        'composer.json',
        'composer.lock',
        'Dockerfile',
        'package.json',
        'package-lock.json'
    ];

    private static array $forbiddenDirs = [
        '.git',
        '.gemini',
        '.agents',
        'vendor'
    ];

    /**
     * Vérifie si l'accès à un chemin est autorisé.
     * @param string $path Chemin relatif ou absolu.
     * @param bool $writeMode Si on tente une écriture.
     * @return bool|string True si OK, sinon le message d'erreur.
     */
    public static function isPathAllowed(string $path, bool $writeMode = false)
    {
        $baseDir = realpath(__DIR__ . '/../../');
        $fullPath = realpath($path) ?: $path;

        // 1. Protection contre la sortie de racine (Directory Traversal)
        if (strpos($fullPath, $baseDir) !== 0) {
            return "Accès interdit : Vous ne pouvez pas sortir de la racine du projet.";
        }

        $relativePath = ltrim(str_replace($baseDir, '', $fullPath), DIRECTORY_SEPARATOR);
        $parts = explode(DIRECTORY_SEPARATOR, $relativePath);
        $fileName = end($parts);

        // 2. Fichiers interdits systématiquement
        foreach (self::$forbiddenFiles as $forbidden) {
            if (stripos($relativePath, $forbidden) !== false) {
                return "Protection : L'accès au fichier '$forbidden' est verrouillé pour des raisons de sécurité.";
            }
        }

        // 3. Dossiers interdits systématiquement
        foreach (self::$forbiddenDirs as $dir) {
            if (strpos($relativePath, $dir . DIRECTORY_SEPARATOR) === 0 || $relativePath === $dir) {
                return "Protection : L'accès au dossier '$dir/' est strictement interdit.";
            }
        }

        // 4. Protection spécifique à l'écriture (Hardening)
        if ($writeMode) {
            if (strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'Core') === 0) {
                return "Sécurité : Modification du coeur moteur (src/Core/) interdite à l'Agent.";
            }
            if (strpos($relativePath, 'config' . DIRECTORY_SEPARATOR) === 0) {
                return "Sécurité : Modification de la configuration système interdite.";
            }
        }

        return true;
    }
}
