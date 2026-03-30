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
        $this->baseUrl = "http://cortex_gateway_v4:8000/v1/rag/embed";
        $this->model = $config->get('embedding_model', 'nomic-embed-text');
    }

    /**
     * Génère un vecteur via le Gateway.
     */
    public function embed(string $text): array {
        $curl = curl_init();
        $token = \EasyLocalAI\Core\Container::get('env')->get('CORTEX_API_KEY');
        
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model' => $this->model,
                'text' => $text,
            ]),
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/json",
                "X-Cortex-Token: $token"
            ],
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
