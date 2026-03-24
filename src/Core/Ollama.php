<?php
// src/Core/Ollama.php

namespace EasyLocalAI\Core;

class Ollama
{
    private string $baseUrl;
    private string $model;
    private string $systemPrompt;
    private string $memoryContext = "";

    public function __construct(Config $config, string $memoryContext = "")
    {
        $url = rtrim($config->get('api_base_url'), '/');
        // Si l'URL contient déjà 'chat/completions', on ne l'ajoute pas
        if (strpos($url, 'chat/completions') !== false) {
            $this->baseUrl = $url;
        } else {
            $this->baseUrl = $url . '/chat/completions';
        }
        $this->model   = $config->get('model_name', 'llama3.2');
        $this->systemPrompt = $config->getSystemPrompt();
        $this->memoryContext = $memoryContext;
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
            CURLOPT_TIMEOUT        => 90,
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
