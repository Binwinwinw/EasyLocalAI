<?php
/**
 * EasyLocalAI - Router Central (DI Refactor)
 */

session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$setup = Container::get('setup');

// Si le setup est requis (premier lancement), rediriger vers la configuration
if ($setup->isSetupRequired()) {
    header("Location: setup.php");
    exit;
}

// Par défaut : Vers l'interface de discussion
header("Location: chat.php");
exit;
