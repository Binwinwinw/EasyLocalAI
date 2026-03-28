<?php
/**
 * EasyLocalAI - Router Central (DI Refactor)
 * Point d'entrée après authentification.
 */

require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

// L'utilisateur est déjà protégé par Auth::protect() dans bootstrap.php

// L'utilisateur est déjà protégé par Auth::protect() dans bootstrap.php

// Une fois le système configuré et fonctionnel, l'entrée naturelle est le Chat.
header("Location: chat.php");
exit;
