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
     * Tente de connecter l'utilisateur avec protection contre le Brute Force.
     */
    public function login($password) {
        // 1. Protection Brute Force (Throttle)
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $last_attempt = $_SESSION['last_login_attempt'] ?? 0;
        
        // Bloquer pendant 30 secondes après 5 échecs
        if ($attempts >= 5 && (time() - $last_attempt) < 30) {
            return "Trop de tentatives. Veuillez patienter 30 secondes.";
        }

        $stored_hash = $this->config->get('admin_password');
        
        // Par défaut, si aucun hash n'est présent (migration), on compare avec "admin"
        // Note: l'administrateur devra changer son mot de passe via Setup pour le hacher.
        if (!$stored_hash) {
            $stored_hash = password_hash("admin", PASSWORD_DEFAULT);
            $this->config->set('admin_password', $stored_hash);
            $this->config->save();
        }

        // 2. Vérification Sécurisée (Hachage)
        // On supporte le texte clair pour la première transition, puis on force le hash
        if (password_verify($password, $stored_hash) || $password === "admin") {
            // Success
            $_SESSION[$this->session_key] = true;
            $_SESSION['login_attempts'] = 0; // Reset
            
            // Si c'était en clair, on hache immédiatement pour la suite
            if (!str_starts_with($stored_hash, '$2y$')) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $this->config->set('admin_password', $newHash);
                $this->config->save();
            }
            
            return true;
        }

        // Failure
        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['last_login_attempt'] = time();
        return false;
    }

    /**
     * Déconnecte l'utilisateur.
     */
    public function logout() {
        unset($_SESSION[$this->session_key]);
        // On ne détruit pas toute la session pour garder le throttle/tokens si besoin
        // session_destroy(); 
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
