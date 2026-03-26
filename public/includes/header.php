<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name ?? 'EasyLocalAI') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <script>
        function toggleTheme() {
            document.body.classList.toggle('light-mode');
            localStorage.setItem('theme', document.body.classList.contains('light-mode') ? 'light' : 'dark');
        }
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'light') document.body.classList.add('light-mode');
        });
    </script>
</head>
<body>
    <div class="container">
        <nav class="nav-tabs">
            <a href="setup.php" class="<?= basename($_SERVER['PHP_SELF']) == 'setup.php' ? 'active' : '' ?>">⚙️ Config</a>
            <a href="skills.php" class="<?= basename($_SERVER['PHP_SELF']) == 'skills.php' ? 'active' : '' ?>">➕ Compétences</a>
            <a href="models.php" class="<?= basename($_SERVER['PHP_SELF']) == 'models.php' ? 'active' : '' ?>">🌐 Modèles</a>
            <a href="chat.php" class="<?= basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : '' ?>">💬 Chat</a>
            
            <div class="theme-toggle" onclick="toggleTheme()" title="Changer de thème">🌗</div>
            <a href="logout.php" style="color: #f87171; text-decoration: none; font-size: 0.9rem; margin-left: 15px;" title="Déconnexion">🚪</a>
        </nav>
