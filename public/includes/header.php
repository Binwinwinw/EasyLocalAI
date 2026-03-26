<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name ?? 'EasyLocalAI') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --bg: #0f172a; --card-bg: rgba(30, 41, 59, 0.7); --text: #f8fafc; --text-dim: #94a3b8; --border: rgba(255, 255, 255, 0.1); --glass: rgba(255, 255, 255, 0.05); }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; min-height: 100vh; background-image: radial-gradient(circle at 50% -20%, #312e81 0%, transparent 50%); }
        .container { width: 100%; max-width: 900px; background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid var(--border); border-radius: 24px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        h1 { background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-align: center; margin: 0 0 10px; }
        .subtitle { text-align: center; color: var(--text-dim); margin-bottom: 30px; font-size: 0.9rem; }
        .response-box { background: var(--glass); border: 1px solid var(--border); border-radius: 16px; padding: 20px; min-height: 80px; margin-bottom: 20px; }
        .response-box strong { color: var(--primary); font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase; display: block; margin-bottom: 10px; }
        .response-content { line-height: 1.6; white-space: pre-wrap; }
        form.chat-form { display: flex; gap: 10px; }
        input[type="text"], input[type="file"], select, textarea { width: 100%; background: var(--glass); border: 1px solid var(--border); border-radius: 12px; padding: 12px 15px; color: white; font-size: 1rem; font-family: inherit; box-sizing: border-box; }
        button { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        button:hover { background: #4f46e5; transform: translateY(-1px); }
        .history-section { margin-top: 40px; border-top: 1px solid var(--border); padding-top: 20px; }
        .msg { background: var(--glass); padding: 15px; border-radius: 12px; margin-bottom: 10px; border-left: 4px solid var(--primary); }
        .btn-clear { background: transparent; border: 1px solid var(--border); color: var(--text-dim); font-size: 0.8rem; margin: 10px auto; display: block; }
        .btn-clear:hover { color: #f87171; border-color: #f87171; }
        .loader { display: none; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.2); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; margin-right: 10px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Navigation Tabs */
        .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        .nav-tabs a { text-decoration: none; color: var(--text-dim); font-size: 0.9rem; padding: 8px 15px; border-radius: 8px; transition: 0.3s; }
        .nav-tabs a.active { background: var(--primary); color: white; }
        .nav-tabs a:hover:not(.active) { background: var(--glass); color: var(--text); }
    </style>
</head>
<body>
    <div class="container">
        <nav class="nav-tabs">
            <a href="setup.php" class="<?= basename($_SERVER['PHP_SELF']) == 'setup.php' ? 'active' : '' ?>">⚙️ Configuration</a>
            <a href="skills.php" class="<?= basename($_SERVER['PHP_SELF']) == 'skills.php' ? 'active' : '' ?>">➕ Compétences</a>
            <a href="chat.php" class="<?= basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : '' ?>">💬 Chat</a>
            <a href="logout.php" style="margin-left: auto; color: #f87171;">🚪 Déconnexion</a>
        </nav>
