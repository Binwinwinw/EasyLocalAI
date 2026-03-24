<?php
// src/RAG/RAG.php

namespace EasyLocalAI\RAG;

class RAG
{
    private string $knowledgeDir;

    public function __construct(string $knowledgeDir = __DIR__ . '/../../knowledge/')
    {
        $this->knowledgeDir = $knowledgeDir;
        if (!is_dir($this->knowledgeDir)) {
            mkdir($this->knowledgeDir, 0755, true);
        }
    }

    public function handleUpload(): string
    {
        if (!isset($_FILES['knowledge_file'])) return "";
        
        $file = $_FILES['knowledge_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'txt') return "Fichiers .txt uniquement.";

        $targetFile = $this->knowledgeDir . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return "Document ajouté !";
        }
        return "Erreur upload.";
    }

    public function getContext(string $prompt): string
    {
        $files = glob($this->knowledgeDir . '*.txt');
        if (empty($files)) return "";

        $context = "";
        
        // Extraction de mots clés simples (ignore les mots courts)
        $keywords = preg_split('/\W+/u', mb_strtolower($prompt));
        $keywords = array_filter($keywords, function($w) { return mb_strlen($w) > 3; });

        if (empty($keywords)) return "";

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $found = false;
            foreach ($keywords as $word) {
                if (mb_stripos($content, $word) !== false) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                $context .= "\n--- Extrait de : " . basename($file) . " ---\n" . mb_substr($content, 0, 800) . "...\n";
            }
        }

        if ($context) {
            return "CONTEXTE LOCAL DÉTECTÉ :\n" . $context . "\n(Utilise ces informations pour répondre si pertinent.)";
        }

        return "";
    }
}
