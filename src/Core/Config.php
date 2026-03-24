<?php
// src/Core/Config.php

namespace EasyLocalAI\Core;

class Config
{
    private array $settings = [];
    private string $configPath;

    public function __construct(string $configPath = __DIR__ . '/../../config/settings.json')
    {
        $this->configPath = $configPath;
        $this->load();
    }

    private function load(): void
    {
        $default = [
            'app_name' => 'EasyLocalAI',
            'model_name' => 'llama3.2',
            'api_base_url' => 'http://ollama:11434/v1/chat/completions',
            'system_prompt' => 'Tu es un assistant IA polyvalent.',
            'setup_completed' => false,
        ];

        if (file_exists($this->configPath)) {
            $json = file_get_contents($this->configPath);
            $this->settings = array_merge($default, json_decode($json, true) ?: []);
        } else {
            $this->settings = $default;
        }
    }

    public function save(): bool
    {
        $json = json_encode($this->settings, JSON_PRETTY_PRINT);
        return file_put_contents($this->configPath, $json) !== false;
    }

    public function get(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->settings[$key] = $value;
    }

    public function getSystemPrompt(): string
    {
        return $this->get('system_prompt', 'Tu es un assistant IA.');
    }

    public function getAppName(): string
    {
        return $this->get('app_name', 'EasyLocalAI');
    }
}
