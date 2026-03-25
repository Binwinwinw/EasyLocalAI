<?php
/**
 * EasyLocalAI - Router Central
 * Dirige l'utilisateur vers le Setup (si non configuré) ou vers le Chat.
 */

session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Config;
use EasyLocalAI\Setup\SetupManager;

$config = new Config();
$setup  = new SetupManager($config);

// Si le setup est requis (premier lancement), rediriger vers la configuration
if ($setup->isSetupRequired()) {
    header("Location: setup.php");
    exit;
}

// Par défaut : Vers l'interface de discussion
header("Location: chat.php");
exit;
