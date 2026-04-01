<?php
// /tmp/test_security.php
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$explorer = Container::get('explorer');

echo "--- TEST PATH TRAVERSAL ---\n";
$maliciousPath = '....//etc/passwd';
try {
    $result = $explorer->listDirectories($maliciousPath);
    echo "Résultat pour $maliciousPath : " . json_encode($result) . "\n";
} catch (\Exception $e) {
    echo "DÉTECTION RÉUSSIE : " . $e->getMessage() . "\n";
}

$maliciousPath2 = '/../../config/settings.json';
try {
    $result = $explorer->listDirectories($maliciousPath2);
    echo "Résultat pour $maliciousPath2 : " . json_encode($result) . "\n";
} catch (\Exception $e) {
    echo "DÉTECTION RÉUSSIE : " . $e->getMessage() . "\n";
}

echo "\n--- TEST AUTH HASH ---\n";
$auth = Container::get('auth');
$pass = "admin";
$res = $auth->login($pass);
echo "Tentative avec '$pass' : " . ($res === true ? "SUCCÈS" : "ÉCHEC") . "\n";

$pass2 = "wrong";
for($i=0; $i<6; $i++) {
    $res = $auth->login($pass2);
    echo "Tentative $i avec '$pass2' : " . (is_string($res) ? $res : "ÉCHEC") . "\n";
}
