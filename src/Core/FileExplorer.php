<?php
// src/Core/FileExplorer.php

namespace EasyLocalAI\Core;

class FileExplorer
{
    private string $hostRoot = '/host';

    public function __construct(string $hostRoot = '/host')
    {
        $this->hostRoot = $hostRoot;
    }

    /**
     * Liste les répertoires d'un chemin donné
     */
    public function listDirectories(string $subPath = ''): array
    {
        $target = $this->securePath($subPath);
        
        if (!$target || !is_dir($target)) {
            return [
                'success' => false, 
                'error' => "Chemin invalide ou inaccessible : $subPath",
                'current_path' => $subPath
            ];
        }

        $items = [];
        $files = @scandir($target);
        
        if ($files === false) {
            return ['success' => false, 'error' => "Impossible de lire le dossier."];
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $fullPath = $target . DIRECTORY_SEPARATOR . $file;
            
            // On ne remonte que les dossiers pour le sélecteur de chemin Ollama
            if (is_dir($fullPath)) {
                $items[] = [
                    'name' => $file,
                    'type' => 'dir'
                ];
            }
        }

        // Trier par nom
        usort($items, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return [
            'success' => true,
            'current_path' => $this->toVirtualPath($target),
            'parent_path' => $this->getParentPath($subPath),
            'items' => $items
        ];
    }

    /**
     * Sécurise le chemin pour s'assurer qu'il reste dans /host (Jailing).
     */
    private function securePath(string $subPath): string
    {
        // 1. Détermination du chemin cible absolu
        $subPath = str_replace(['\\', '..'], ['/', ''], $subPath);
        $absoluteTarget = $this->hostRoot . DIRECTORY_SEPARATOR . ltrim($subPath, '/');
        
        // 2. Validation par realpath()
        // Note: Realpath retourne false si le dossier n'existe pas encore
        $realHostRoot = realpath($this->hostRoot);
        
        if ($realHostRoot === false) {
            // Environnement Docker mal configuré ou dossier /host absent
            return $this->hostRoot;
        }

        // Si le chemin existe, on vérifie qu'il commence bien par realHostRoot
        if (file_exists($absoluteTarget)) {
            $realTarget = realpath($absoluteTarget);
            if (strpos($realTarget, $realHostRoot) !== 0) {
                // Tentative d'évasion détectée !
                throw new \Exception("Violation de sécurité : Tentative d'évasion de répertoire détectée.");
            }
            return $realTarget;
        }

        // Si le chemin n'existe pas encore (création de dossier), on vérifie la chaîne
        // En s'assurant qu'il n'y a plus de '..'
        return $absoluteTarget;
    }

    /**
     * Convertit le chemin physique /host/c/users en chemin virtuel /c/users
     */
    private function toVirtualPath(string $fullPath): string
    {
        return str_replace($this->hostRoot, '', str_replace('\\', '/', $fullPath)) ?: '/';
    }

    private function getParentPath(string $path): ?string
    {
        $path = trim($path, '/');
        if (!$path) return null;
        
        $parts = explode('/', $path);
        array_pop($parts);
        
        return count($parts) > 0 ? implode('/', $parts) : '';
    }
}
