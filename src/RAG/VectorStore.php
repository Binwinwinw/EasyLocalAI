<?php

namespace EasyLocalAI\RAG;

/**
 * EasyLocalAI - VectorStore
 * Stocke et recherche des embeddings textuels de manière persistante (JSON).
 */
class VectorStore {
    private string $filePath;
    private array $data = [];

    public function __construct(string $filePath = __DIR__ . '/../../knowledge/vectors.json') {
        $this->filePath = $filePath;
        if (file_exists($this->filePath)) {
            $this->data = json_decode(file_get_contents($this->filePath), true) ?: [];
        }
    }

    /**
     * Ajoute un fragment de texte et son vecteur à la base.
     */
    public function add(string $text, array $vector, array $metadata = []): void {
        $this->data[] = [
            'text' => $text,
            'vector' => $vector,
            'metadata' => $metadata,
            'timestamp' => time()
        ];
        $this->save();
    }

    /**
     * Recherche les fragments les plus similaires au vecteur fourni.
     */
    public function search(array $queryVector, int $limit = 3): array {
        if (empty($this->data)) return [];

        $results = [];
        foreach ($this->data as $item) {
            $similarity = $this->cosineSimilarity($queryVector, $item['vector']);
            $results[] = [
                'text' => $item['text'],
                'similarity' => $similarity,
                'metadata' => $item['metadata']
            ];
        }

        // Tri par score de similarité décroissant
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($results, 0, $limit);
    }

    private function cosineSimilarity(array $vecA, array $vecB): float {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        $count = count($vecA);
        // On s'assure que les vecteurs ont la même taille
        if ($count !== count($vecB)) return 0;

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] ** 2;
            $normB += $vecB[$i] ** 2;
        }

        if ($normA == 0 || $normB == 0) return 0;
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    private function save(): void {
        file_put_contents($this->filePath, json_encode($this->data, JSON_UNESCAPED_UNICODE));
    }

    public function clear(): void {
        $this->data = [];
        $this->save();
    }
}
