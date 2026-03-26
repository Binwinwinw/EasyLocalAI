<?php
// public/logout.php - DI Refactor
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$auth = Container::get('auth');
$auth->logout();

header("Location: login.php");
exit;
