<?php
// src/Setup/SetupManager.php

namespace EasyLocalAI\Setup;

use EasyLocalAI\Core\Config;

class SetupManager
{
    private Config $config;
    private array $profiles = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->loadProfiles();
    }

    private function loadProfiles(): void
    {
        $path = __DIR__ . '/../../config/profiles.json';
        if (file_exists($path)) {
            $this->profiles = json_decode(file_get_contents($path), true) ?: [];
        }
    }

    public function isSetupRequired(): bool
    {
        return isset($_GET['setup']) || !($this->config->get('setup_completed', false));
    }

    public function addProfile(string $key, array $data): bool
    {
        $path = __DIR__ . '/../../config/profiles.json';
        $this->profiles[$key] = $data;
        $json = json_encode($this->profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($path, $json) !== false;
    }

    public function handleForm(): bool
    {
        if (isset($_POST['action']) && $_POST['action'] === "setup_save") {
            $new_name = $_POST['app_name_input'] ?? "EasyLocalAI";
            $profile_key = $_POST['profile_choice'] ?? "general";
            
            if ($profile_key === 'custom' && !empty($_POST['custom_prompt'])) {
                $new_prompt = $_POST['custom_prompt'];
            } else {
                $new_prompt = $this->profiles[$profile_key]['prompt'] ?? "Tu es un assistant IA.";
            }
            
            $this->config->set('app_name', $new_name);
            $this->config->set('system_prompt', $new_prompt);
            $this->config->set('setup_completed', true);
            return $this->config->save();
        }

        if (isset($_POST['action']) && $_POST['action'] === "skill_add") {
            $key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['skill_name']));
            if ($key && !isset($this->profiles[$key])) {
                $this->addProfile($key, [
                    'label' => $_POST['skill_label'] ?: $_POST['skill_name'],
                    'prompt' => $_POST['skill_prompt']
                ]);
                return true;
            }
        }
        return false;
    }

    public function getProfiles(): array
    {
        return $this->profiles;
    }
}
