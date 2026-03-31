<?php

namespace EasyLocalAI\Core;

class Auth {
    private $config;
    private $session_key = 'auth_user';

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     */
    public function isLoggedIn() {
        return isset($_SESSION[$this->session_key]);
    }

    /**
     * Tente de connecter l'utilisateur.
     */
    public function login($password) {
        $stored_password = $this->config->get('admin_password');
        
        // Par défaut, si aucun mot de passe n'est configuré, on utilise "admin"
        if (!$stored_password) {
            $stored_password = "admin";
        }

        if ($password === $stored_password) {
            $_SESSION[$this->session_key] = true;
            return true;
        }
        return false;
    }

    /**
     * Déconnecte l'utilisateur.
     */
    public function logout() {
        unset($_SESSION[$this->session_key]);
        session_destroy();
    }

    /**
     * Protège une page.
     */
    public function protect() {
        if (PHP_SAPI === 'cli') return; // Skip in CLI

        $current_page = basename($_SERVER['PHP_SELF']);
        $whitelist = ['login.php', 'index.php']; 
        
        if (!$this->isLoggedIn() && !in_array($current_page, $whitelist)) {
            header("Location: index.php");
            exit;
        }
    }
}
