<?php
// src/Core/Ollama.php

namespace EasyLocalAI\Core;

class Ollama implements LlmInterface
{
    private string $baseUrl;
    private string $ollamaBase; // Racine pure d'Ollama (ex: http://ollama:11434)
    private string $model;
    private string $systemPrompt;
    private string $memoryContext = "";

    public function __construct(Config $config, string $memoryContext = "")
    {
        $url = rtrim($config->get('api_base_url'), '/');
        
        // On sépare la racine Ollama de l'endpoint API
        $parsedUrl = parse_url($url);
        $this->ollamaBase = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? 'localhost') . ($parsedUrl['port'] ? ':' . $parsedUrl['port'] : '');
        
        // Si l'URL contient déjà 'chat/completions', on ne l'ajoute pas
        if (strpos($url, 'chat/completions') !== false) {
            $this->baseUrl = $url;
        } else {
            // Si /v1 est dans l'URL mais pas chat/completions
            $this->baseUrl = $this->ollamaBase . '/v1/chat/completions';
        }

        $this->model   = $config->get('model_name', 'llama3.2');
        $this->systemPrompt = $config->getSystemPrompt();
        $this->memoryContext = $memoryContext;
    }

    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
    }

    public function setSystemPrompt(string $prompt): void
    {
        $this->systemPrompt = $prompt;
    }

    /**
     * Appelle l'API Ollama (compatible OpenAI)
     */
    public function ask(string $prompt, array $history = []): string
    {
        $messages = $this->prepareMessages($prompt, $history);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'    => $this->model,
                'messages' => $messages,
                'stream'   => false,
            ]),
            CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 300,
        ]);

        $response = curl_exec($curl);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error) return "Erreur Curl: " . $error;
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? "Erreur IA (Réponse vide)";
    }

    /**
     * Mode Streaming réel via SSE
     */
    public function stream(string $prompt, array $history = []): void
    {
        $messages = $this->prepareMessages($prompt, $history);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => false, // On flush directement
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'    => $this->model,
                'messages' => $messages,
                'stream'   => true,
            ]),
            CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
            CURLOPT_WRITEFUNCTION  => function($ch, $data) {
                // Ollama peut envoyer plusieurs lignes JSON dans un seul chunk
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $cleanLine = trim($line);
                    if ($cleanLine) {
                        echo "data: " . $cleanLine . "\n\n";
                        if (ob_get_level() > 0) ob_flush();
                        flush();
                    }
                }
                return strlen($data);
            },
        ]);

        curl_exec($curl);
        curl_close($curl);
    }

    /**
     * Liste les modèles installés localement.
     */
    public function listModels(): array {
        $url = $this->ollamaBase . '/api/tags';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        
        $data = json_decode($response, true);
        return $data['models'] ?? [];
    }

    /**
     * Télécharge un modèle en mode streaming pour suivre la progression.
     */
    public function pullStream(string $name, callable $onProgress): bool {
        $url = $this->ollamaBase . '/api/pull';
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['name' => $name, 'stream' => true]),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_WRITEFUNCTION => function($ch, $data) use ($onProgress) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $json = json_decode(trim($line), true);
                    if ($json) {
                        $onProgress($json);
                    }
                }
                return strlen($data);
            },
            CURLOPT_TIMEOUT => 3600
        ]);
        
        $success = curl_exec($curl);
        curl_close($curl);
        return $success !== false;
    }

    /**
     * Supprime un modèle localement pour libérer de l'espace.
     */
    public function deleteModel(string $name): bool {
        $url = $this->ollamaBase . '/api/delete';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_POSTFIELDS => json_encode(['name' => $name]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        ]);
        curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $status === 200;
    }

    private function prepareMessages(string $prompt, array $history): array
    {
        $messages = [
            ["role" => "system", "content" => $this->systemPrompt . $this->memoryContext]
        ];

        foreach ($history as $item) {
            if (isset($item['q'], $item['a'])) {
                $messages[] = ["role" => "user", "content" => $item['q']];
                $messages[] = ["role" => "assistant", "content" => $item['a']];
            }
        }

        $messages[] = ["role" => "user", "content" => $prompt];
        return $messages;
    }
}
