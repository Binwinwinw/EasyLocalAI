<?php
// tmp/verify_security_direct.php
require_once __DIR__ . '/../config/bootstrap.php';
use EasyLocalAI\Core\Container;
use EasyLocalAI\Tools\Implementations\FileReadTool;
use EasyLocalAI\Tools\Implementations\CodeRunnerTool;

echo "--- VÉRIFICATION DIRECTE DES GARDES-FOUS ---\n\n";

$fr = new FileReadTool();
echo "1. Tentative lecture .env : ";
$res1 = $fr->execute(['path' => '.env']);
echo $res1 . "\n";

echo "2. Tentative lecture config/settings.json : ";
$res2 = $fr->execute(['path' => 'config/settings.json']);
echo $res2 . "\n";

$cr = new CodeRunnerTool();
echo "\n3. Tentative shell_exec : ";
$res3 = $cr->execute(['code' => '<?php echo shell_exec("ls"); ?>']);
echo $res3 . "\n";

echo "4. Tentative backticks : ";
$res4 = $cr->execute(['code' => '<?php echo `ls`; ?>']);
echo $res4 . "\n";

echo "5. Tentative boucle infinie (Timeout) : ";
$res5 = $cr->execute(['code' => '<?php while(true); ?>']);
echo $res5 . "\n";
