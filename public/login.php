<?php
/**
 * EasyLocalAI - Login Immersif Kinetic 3D
 * Interface de connexion ultra-premium avec moteur de particules.
 */
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\Core\Security;

$auth = Container::get('auth');
$error = null;

if (isset($_POST['password'])) {
    $csrf = Security::sanitize($_POST['csrf_token'] ?? '');
    if (!$csrf || !Security::checkCsrf($csrf)) {
        die("Erreur de sécurité (CSRF)");
    }
    
    if ($auth->login($_POST['password'])) {
        header("Location: chat.php");
        exit;
    } else {
        $error = "Accès refusé. Mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S'authentifier | EasyLocalAI Kinetic</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="landing.css?v=4.5">
    
    <!-- Scripts Moteur 3D -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="immersive-login">

<!-- MOTEUR DE BULLES 3D -->
<div id="canvas-container"></div>

<div class="login-sphere-container">
    <div class="login-glass-card glass-panel reveal visible">
        <div style="margin-bottom: 30px;">
            <div style="display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; background:rgba(99, 102, 241, 0.1); border-radius:18px; border:1px solid rgba(99, 102, 241, 0.2); margin-bottom: 20px;">
                <i data-lucide="shield-check" style="color:var(--primary); width:32px; height:32px;"></i>
            </div>
            <h1 class="massive-title" style="font-size: 2.2rem; letter-spacing: -1px;">EasyLocal<span class="gradient-text">AI</span></h1>
            <p style="color:var(--text-dim); font-size:0.9rem; font-weight:300;">Identifiez-vous pour déverrouiller la puissance locale.</p>
        </div>

        <?php if ($error): ?>
            <div style="background:rgba(239, 68, 68, 0.1); color:#f87171; border:1px solid rgba(239, 68, 68, 0.2); padding:12px; border-radius:12px; font-size:0.8rem; margin-bottom: 25px; display:flex; align-items:center; gap:10px;">
                <i data-lucide="alert-circle" style="width:16px; height:16px;"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
            
            <div class="input-group-kinetic">
                <input type="password" name="password" placeholder="Mot de passe Maître" required autofocus>
                <i data-lucide="key" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); width:18px; color:var(--text-dim); opacity:0.5;"></i>
            </div>

            <button type="submit" class="btn-elite" style="width: 100%; height: 54px; font-size: 0.9rem;">
                SE CONNECTER
            </button>
        </form>

        <div style="margin-top: 35px; padding-top: 25px; border-top: 1px solid var(--glass-border); display:flex; justify-content:space-between; align-items:center;">
             <span style="font-size:0.7rem; color:var(--text-dim); opacity:0.6;">v4.5 Expert Edition</span>
             <a href="index.php" style="font-size:0.7rem; color:var(--primary); text-decoration:none; font-weight:600;">RETOUR ACCUEIL</a>
        </div>
    </div>
</div>

<!-- Initialisation -->
<script src="kinetic-bg.js"></script>
<script>
    lucide.createIcons();
    
    // Effet d'entrée en fondu
    document.querySelector('.login-glass-card').style.opacity = '0';
    document.querySelector('.login-glass-card').style.transform = 'translateY(20px) scale(0.98)';
    
    setTimeout(() => {
        document.querySelector('.login-glass-card').style.transition = 'all 1s cubic-bezier(0.16, 1, 0.3, 1)';
        document.querySelector('.login-glass-card').style.opacity = '1';
        document.querySelector('.login-glass-card').style.transform = 'translateY(0) scale(1)';
    }, 100);
</script>

</body>
</html>
