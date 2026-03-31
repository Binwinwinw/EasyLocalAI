<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name ?? 'EasyLocalAI') ?> | Intelligence Locale</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css?v=4.2">
    <script>
        // Le thème doit être appliqué le plus tôt possible pour éviter le flash blanc (FOUC)
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                document.documentElement.classList.add('light-mode');
            }
        })();

        function toggleTheme() {
            const html = document.documentElement;
            const isLight = html.classList.toggle('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            
            // Toggle icons
            document.getElementById('moonIcon').style.display = isLight ? 'none' : 'block';
            document.getElementById('sunIcon').style.display = isLight ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-header" style="padding-bottom: 30px; border-bottom: 1px solid var(--border);">
                <h2 style="font-family: 'Poppins', sans-serif; font-size: 1.2rem; display:flex; align-items:center; gap:10px; margin:0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary);"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    EASY LOCAL AI
                </h2>
            </div>

            <nav class="side-nav">
                <a href="chat.php" class="<?= basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Chat Agent
                </a>

                <div class="nav-group <?= basename($_SERVER['PHP_SELF']) == 'setup.php' ? 'open active' : '' ?>">
                    <a href="setup.php" onclick="toggleAccordion(event)" class="nav-toggle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>Configuration</span>
                        <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left: auto; transition: 0.3s;"><polyline points="6 9 12 15 18 9"/></svg>
                    </a>
                    <div class="sub-nav">
                        <?php 
                            $currentTab = $_GET['tab'] ?? 'general';
                        ?>
                        <a href="setup.php?tab=general" class="<?= ($currentTab === 'general') ? 'active' : '' ?>">Général</a>
                        <a href="setup.php?tab=engines" class="<?= ($currentTab === 'engines') ? 'active' : '' ?>">Moteurs IA</a>
                        <a href="setup.php?tab=memory" class="<?= ($currentTab === 'memory') ? 'active' : '' ?>">Mémoire RAG</a>
                        <a href="setup.php?tab=system" class="<?= ($currentTab === 'system') ? 'active' : '' ?>">Système</a>
                    </div>
                </div>
            </nav>

            <script>
            function toggleAccordion(e) {
                // Si on clique sur le lien principal mais qu'on veut juste ouvrir l'accordéon sans naviguer ?
                // Non, on veut naviguer vers setup.php (général) mais aussi ouvrir.
                const group = e.currentTarget.parentElement;
                const isOpen = group.classList.contains('open');
                
                // Si on est déjà sur setup.php, on bascule juste l'ouverture
                if (window.location.pathname.includes('setup.php')) {
                    // e.preventDefault(); // On ne prevent pas pour allow setup.php?tab=general
                    group.classList.toggle('open');
                    localStorage.setItem('sidebar_config_open', !isOpen);
                }
            }
            
            // Persistance de l'état
            document.addEventListener('DOMContentLoaded', () => {
                if (window.location.pathname.includes('setup.php')) {
                    const group = document.querySelector('.nav-group');
                    if (group) group.classList.add('open');
                }
            });
            </script>

            <div class="sidebar-footer" style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border);">
                <div class="system-monitor" style="background: var(--glass); border-radius: 12px; padding: 15px; font-size: 0.75rem;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; color:var(--text-dim);">
                        <span>Ollama Status</span>
                        <span style="color:#10b981; font-weight:700;">ONLINE</span>
                    </div>
                    <div style="margin-bottom:8px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:4px; color:var(--text-dim);">
                            <span>RAM Usage</span>
                            <span><?= round(memory_get_usage(true) / 1024 / 1024, 0) ?> MB</span>
                        </div>
                        <div style="height:4px; background:rgba(255,255,255,0.05); border-radius:2px; overflow:hidden;">
                            <div style="width:45%; height:100%; background:var(--primary); box-shadow:0 0 10px var(--primary-glow);"></div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:10px; margin-top:20px;">
                    <button class="theme-toggle" id="themeIdx" onclick="toggleTheme()" title="Mode Sombre/Clair" style="background:none; border:none; color: var(--text-dim); cursor:pointer; padding: 0;">
                        <svg id="moonIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: <?= !isset($_COOKIE['theme']) || $_COOKIE['theme'] === 'dark' ? 'block' : 'none' ?>;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                        <svg id="sunIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: <?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'light' ? 'block' : 'none' ?>;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    </button>
                    <a href="logout.php" style="color: #f87171; text-decoration: none; font-size:0.8rem; font-weight:600; opacity:0.6;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">
                        Déconnexion
                    </a>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="content-wrapper">
