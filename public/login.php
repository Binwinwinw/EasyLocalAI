<?php
// public/login.php - DI Refactor
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$auth = Container::get('auth');

$error = null;
if (isset($_POST['password'])) {
    if (!isset($_POST['csrf_token']) || !EasyLocalAI\Core\Security::checkCsrf($_POST['csrf_token'])) {
        die("Erreur de sécurité (CSRF)");
    }
    
    if ($auth->login($_POST['password'])) {
        header("Location: index.php");
        exit;
    } else {
        $error = "Mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EasyLocalAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --bg: #0f172a; --card-bg: rgba(30, 41, 59, 0.7); --text: #f8fafc; --text-dim: #94a3b8; --border: rgba(255, 255, 255, 0.1); --glass: rgba(255, 255, 255, 0.05); }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; background-image: radial-gradient(circle at 50% -20%, #312e81 0%, transparent 50%); }
        .login-card { width: 100%; max-width: 400px; background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid var(--border); border-radius: 24px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); text-align: center; }
        h1 { margin: 0 0 10px; font-size: 1.5rem; background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        p { color: var(--text-dim); font-size: 0.9rem; margin-bottom: 30px; }
        input[type="password"] { width: 100%; background: var(--glass); border: 1px solid var(--border); border-radius: 12px; padding: 12px 15px; color: white; font-size: 1rem; margin-bottom: 20px; box-sizing: border-box; text-align: center; }
        button { width: 100%; background: var(--primary); color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        button:hover { background: #4f46e5; transform: translateY(-1px); }
        .error { color: #f87171; font-size: 0.8rem; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>EasyLocalAI 🚀</h1>
        <p>Veuillez entrer le mot de passe administrateur pour accéder à l'IA.</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= EasyLocalAI\Core\Security::getCsrfToken() ?>">
            <input type="password" name="password" placeholder="Mot de passe" required autofocus>
            <button type="submit">Se connecter</button>
        </form>
        
        <div style="margin-top: 20px; font-size: 0.75rem; color: var(--text-dim);">
            Par défaut: <code>admin</code>
        </div>
    </div>
</body>
</html>
