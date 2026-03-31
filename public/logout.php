<?php
// public/logout.php - DI Refactor
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$auth = Container::get('auth');
$auth->logout();

header("Location: index.php");
exit;
