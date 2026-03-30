<?php
// src/Core/EnvManager.php

namespace EasyLocalAI\Core;

class EnvManager
{
    private string $envPath;

    public function __construct(string $envPath = __DIR__ . '/../../.env')
    {
        $this->envPath = $envPath;
    }

    /**
     * Lit une variable du fichier .env
     */
    public function get(string $key, $default = null): ?string
    {
        if (!file_exists($this->envPath)) return $default;

        $content = file_get_contents($this->envPath);
        $pattern = "/^{$key}=(.*)$/m";
        
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }

        return $default;
    }

    /**
     * Met à jour ou ajoute une variable dans le fichier .env
     */
    public function set(string $key, string $value): bool
    {
        // On s'assure que les anti-slashs Windows sont convertis en slashs Linux pour Docker
        $value = str_replace('\\', '/', $value);
        
        if (!file_exists($this->envPath)) {
            return file_put_contents($this->envPath, "{$key}={$value}\n") !== false;
        }

        $content = file_get_contents($this->envPath);
        $pattern = "/^{$key}=(.*)$/m";

        if (preg_match($pattern, $content)) {
            $newContent = preg_replace($pattern, "{$key}={$value}", $content);
        } else {
            $newContent = rtrim($content) . "\n" . "{$key}={$value}\n";
        }

        return file_put_contents($this->envPath, $newContent) !== false;
    }

    /**
     * Retourne tout le contenu pour vérification (debug)
     */
    public function getAll(): array
    {
        if (!file_exists($this->envPath)) return [];
        
        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env = [];
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $env[trim($name)] = trim($value);
        }
        return $env;
    }
}
