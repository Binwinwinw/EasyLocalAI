<?php
/**
 * EasyLocalAI - Router Central (DI Refactor)
 * Point d'entrée après authentification.
 */

session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

// L'utilisateur est déjà protégé par Auth::protect() dans bootstrap.php

// Demande de l'utilisateur : Toujours atterrir sur la page de config (setup) 
// comme point d'entrée après le mot de passe.
header("Location: setup.php");
exit;
