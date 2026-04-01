<?php
// src/Setup/SetupManager.php

namespace EasyLocalAI\Setup;

use EasyLocalAI\Core\Config;
use EasyLocalAI\Core\Security;

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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            return false;
        }

        // Vérification CSRF
        if (!isset($_POST['csrf_token']) || !Security::checkCsrf($_POST['csrf_token'])) {
            die("Erreur de sécurité (CSRF)");
        }

        // Sauvegarde depuis le Control Center (Unified)
        if ($_POST['action'] === 'setup') {
            $name = Security::sanitize($_POST['app_name'] ?? 'EasyLocalAI');
            $personaJson = $_POST['persona_json'] ?? '';
            
            $this->config->set('app_name', $name);
            
            $persona = json_decode($personaJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($persona)) {
                // Sanitize all elements in the persona array
                $persona = Security::sanitize($persona);
                $this->config->set('persona', $persona);
                // On met à jour aussi le vieux system_prompt pour la compatibilité LLM directe si besoin
                $this->config->set('system_prompt', json_encode($persona));
            }
            
            $this->config->set('setup_completed', true);
            return $this->config->save();
        }

        // Sauvegarde initiale (Legacy Setup)
        if ($_POST['action'] === 'setup_save') {
            $name = Security::sanitize($_POST['app_name_input'] ?? 'EasyLocalAI');
            $profile = Security::sanitize($_POST['profile_choice'] ?? 'general');
            $currentTab = \EasyLocalAI\Core\Security::sanitize($_GET['tab'] ?? 'general');
            $custom = Security::sanitize($_POST['custom_prompt'] ?? '');
            
            $this->config->set('app_name', $name);
            $this->config->set('active_profile', $profile);
            
            if ($profile === 'custom' && !empty($custom)) {
                // Assuming updateProfile is a new method or logic to be added elsewhere
                // For now, we'll just set the system_prompt directly if custom
                $this->config->set('system_prompt', $custom);
            } else {
                // Fallback to existing profile prompt if not custom or custom is empty
                $this->config->set('system_prompt', $this->profiles[$profile]['prompt'] ?? "Tu es un assistant IA.");
            }
            $this->config->set('setup_completed', true);
            return $this->config->save();
        }

        // Ajout d'une nouvelle compétence
        if ($_POST['action'] === 'skill_add') {
            $key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', Security::sanitize($_POST['skill_name'] ?? '')));
            $label = Security::sanitize($_POST['skill_label'] ?? $_POST['skill_name'] ?? '');
            $prompt = Security::sanitize($_POST['skill_prompt'] ?? '');
            
            if ($key && !isset($this->profiles[$key])) {
                $this->addProfile($key, [
                    'label' => $label,
                    'prompt' => $prompt
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
