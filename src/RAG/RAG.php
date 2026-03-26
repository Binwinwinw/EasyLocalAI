<?php
// src/RAG/RAG.php - Vector Search Refactor

namespace EasyLocalAI\RAG;

/**
 * EasyLocalAI - RAG Service
 * Gère l'ingestion de documents et la récupération de contexte sémantique.
 */
class RAG
{
    private string $knowledgeDir;
    private Embedder $embedder;
    private VectorStore $vectorStore;

    public function __construct(
        Embedder $embedder, 
        VectorStore $vectorStore,
        string $knowledgeDir = __DIR__ . '/../../knowledge/'
    ) {
        $this->embedder = $embedder;
        $this->vectorStore = $vectorStore;
        $this->knowledgeDir = $knowledgeDir;
        
        if (!is_dir($this->knowledgeDir)) {
            mkdir($this->knowledgeDir, 0755, true);
        }
    }

    /**
     * Gère l'upload et l'indexation sémantique complète d'un fichier.
     */
    public function handleUpload(): string
    {
        if (!isset($_FILES['knowledge_file'])) return "";
        
        $file = $_FILES['knowledge_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'txt') return "Fichiers .txt uniquement.";

        $content = file_get_contents($file['tmp_name']);
        if (!$content) return "Fichier vide.";

        // 1. Sauvegarde physique
        $targetFile = $this->knowledgeDir . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // 2. Indexation sémantique (Chunking et Embedding)
            $this->indexDocument(basename($file['name']), $content);
            return "Document indexé avec succès !";
        }
        return "Erreur upload.";
    }

    /**
     * Découpe et indexe un document dans le VectorStore.
     */
    private function indexDocument(string $filename, string $content): void {
        // Simple chunking par paragraphes ou blocs de 1000 caractères
        $chunks = str_split($content, 1000);
        foreach ($chunks as $chunk) {
            $vector = $this->embedder->embed($chunk);
            if (!empty($vector)) {
                $this->vectorStore->add($chunk, $vector, ['source' => $filename]);
            }
        }
    }

    /**
     * Récupère le contexte sémantique le plus proche.
     */
    public function getContext(string $prompt): string
    {
        // 1. Embed le prompt utilisateur
        $queryVector = $this->embedder->embed($prompt);
        if (empty($queryVector)) return "";

        // 2. Recherche sémantique
        $matches = $this->vectorStore->search($queryVector, 2);
        if (empty($matches)) return "";

        $context = "";
        foreach ($matches as $match) {
            $source = $match['metadata']['source'] ?? 'Inconnu';
            $context .= "\n--- Extrait de : $source (Score: " . round($match['similarity'], 2) . ") ---\n" . $match['text'] . "\n";
        }

        return "CONTEXTE SÉMANTIQUE DÉTECTÉ :\n" . $context . "\n(Utilise ces informations pour répondre de manière précise.)";
    }
}
