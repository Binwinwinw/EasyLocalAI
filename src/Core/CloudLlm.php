<?php
// src/Core/CloudLlm.php

namespace EasyLocalAI\Core;

/**
 * Service LLM pour les fournisseurs Cloud (OpenAI compatible).
 * Utilisé pour Groq, OpenAI, MiniMax, etc.
 */
class CloudLlm implements LlmInterface
{
    private string $baseUrl;
    private string $apiKey;
    private string $model;
    private string $systemPrompt;

    public function __construct(string $baseUrl, string $apiKey, string $model, string $systemPrompt = "")
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey  = $apiKey;
        $this->model   = $model;
        $this->systemPrompt = $systemPrompt;
    }

    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
    }

    public function setSystemPrompt(string $prompt): void
    {
        $this->systemPrompt = $prompt;
    }

    public function ask(string $prompt, array $history = [], array $images = []): string
    {
        $messages = $this->prepareMessages($prompt, $history, $images);

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
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->apiKey
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 300,
        ]);

        $response = curl_exec($curl);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error) return "Erreur Cloud: " . $error;
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? "Erreur Cloud (Réponse vide)";
    }

    public function stream(string $prompt, array $history = [], array $images = []): void
    {
        $messages = $this->prepareMessages($prompt, $history, $images);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'    => $this->model,
                'messages' => $messages,
                'stream'   => true,
            ]),
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->apiKey
            ],
            CURLOPT_WRITEFUNCTION  => function($ch, $data) {
                echo "data: " . $data . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                return strlen($data);
            },
        ]);

        curl_exec($curl);
        curl_close($curl);
    }

    private function prepareMessages(string $prompt, array $history, array $images = []): array
    {
        $messages = [
            ["role" => "system", "content" => $this->systemPrompt]
        ];

        foreach ($history as $item) {
            if (isset($item['q'], $item['a'])) {
                $messages[] = ["role" => "user", "content" => $item['q']];
                $messages[] = ["role" => "assistant", "content" => $item['a']];
            }
        }

        if (empty($images)) {
            $messages[] = ["role" => "user", "content" => $prompt];
        } else {
            $content = [["type" => "text", "text" => $prompt]];
            foreach ($images as $img) {
                $content[] = [
                    "type"      => "image_url",
                    "image_url" => ["url" => $img]
                ];
            }
            $messages[] = ["role" => "user", "content" => $content];
        }

        return $messages;
    }
}
