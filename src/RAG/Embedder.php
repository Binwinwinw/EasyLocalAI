<?php

namespace EasyLocalAI\RAG;

use EasyLocalAI\Core\Config;

/**
 * EasyLocalAI - Embedder
 * Transforme du texte en vecteurs (Embeddings) via l'API Ollama.
 */
class Embedder {
    private string $baseUrl;
    private string $model;

    public function __construct(Config $config) {
        $url = rtrim($config->get('api_base_url'), '/');
        // Si l'URL finit par /chat/completions, on remonte d'un niveau
        $this->baseUrl = str_replace('/chat/completions', '', $url) . '/api/embeddings';
        // Modèle par défaut pour les embeddings
        $this->model = $config->get('embedding_model', 'nomic-embed-text');
    }

    /**
     * Génère un vecteur pour une chaîne de caractères.
     */
    public function embed(string $text): array {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model' => $this->model,
                'prompt' => $text,
            ]),
            CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 120,
        ]);

        $response = curl_exec($curl);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error) return [];
        $data = json_decode($response, true);
        return $data['embedding'] ?? [];
    }
}
