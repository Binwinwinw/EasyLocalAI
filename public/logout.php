<?php
// public/logout.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Config;
use EasyLocalAI\Core\Auth;

$config = new Config();
$auth = new Auth($config);
$auth->logout();

header("Location: login.php");
exit;
