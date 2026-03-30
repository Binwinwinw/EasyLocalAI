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
     * Sécurise le chemin pour s'assurer qu'il reste dans /host
     */
    private function securePath(string $subPath): string
    {
        // Nettoyage des antislashs et doubles points
        $subPath = str_replace('\\', '/', $subPath);
        $subPath = str_replace('..', '', $subPath);
        $subPath = ltrim($subPath, '/');

        return $this->hostRoot . ($subPath ? '/' . $subPath : '');
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
