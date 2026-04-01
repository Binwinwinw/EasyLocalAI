<?php
/**
 * EasyLocalAI - Landing Immersive 3D V4.5
 * Vitrine technologique avec moteur de bulles 'Kinetic'.
 */
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$app_name = Container::get('config')->get('app_name', 'EasyLocalAI');
$auth = Container::get('auth');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?> | Votre Assistant Intelligent Souverain</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="landing.css?v=4.5">
    
    <!-- Scripts Moteur 3D -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="immersive-landing">

<!-- CANAVE 3D FONDS -->
<div id="canvas-container"></div>

<!-- Navigation Fixe -->
<nav style="position:fixed; top:0; width:100%; z-index:100; padding:20px 40px; display:flex; justify-content:space-between; align-items:center; backdrop-filter:blur(10px);">
    <div style="font-weight:900; font-size:1.5rem; letter-spacing:-1px;">EasyLocal<span class="gradient-text">AI</span></div>
        <div style="display:flex; gap:20px; align-items:center;">
            <?php if ($auth->isLoggedIn()): ?>
                <a href="chat.php" class="btn-elite" style="padding:10px 20px; font-size:0.75rem;">TABLEAU DE BORD</a>
            <?php else: ?>
                <a href="login.php" class="btn-elite" style="padding:10px 20px; font-size:0.75rem;">SE CONNECTER</a>
            <?php endif; ?>
        </div>
</nav>

<div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <!-- HERO SECTION -->
    <section style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding-top: 80px;">
        <div class="reveal" style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); padding: 8px 16px; border-radius: 99px; font-size: 0.7rem; font-weight: 700; color: var(--primary-light); margin-bottom: 30px; letter-spacing: 2px;">
            KINETIC ENGINE V4.5 EXPERT
        </div>
        <h1 class="massive-title reveal">L'Assistant qui vous <br><span class="gradient-text">appartient</span>.</h1>
        <p class="reveal" style="max-width: 700px; font-size: 1.4rem; color: var(--text-dim); margin-bottom: 40px; font-weight: 400; font-style: italic; opacity: 0.9;">
            "Un assistant local de votre intelligence."
        </p>
        <div class="reveal" style="display: flex; gap: 15px;">
            <a href="chat.php" class="btn-elite">DÉMARRER L'ASSISTANT</a>
            <a href="#features" class="btn-elite" style="background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border);">DÉCOUVRIR</a>
        </div>
    </section>

    <!-- BENTO GRID -->
    <section id="features" style="padding: 100px 0;">
        <h2 class="reveal" style="font-size: 2.5rem; text-align: center; margin-bottom: 60px;">Capacités <span class="gradient-text">Élite</span></h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <!-- Agentic -->
            <div class="glass-panel reveal" style="padding: 40px; grid-column: span 2;">
                <div style="width: 48px; height: 48px; background: rgba(168, 85, 247, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i data-lucide="brain" style="color: var(--secondary);"></i>
                </div>
                <h3 style="font-size: 1.5rem; margin-bottom: 15px;">Cognition Agentique</h3>
                <p style="color: var(--text-dim);">Des modèles capables de réflexion itérative, d'auto-correction et d'utilisation d'outils complexes pour résoudre vos tâches les plus ardues.</p>
            </div>

            <!-- RAG -->
            <div class="glass-panel reveal" style="padding: 40px;">
                <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i data-lucide="database" style="color: var(--emerald-glow);"></i>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 10px;">Mémoire Vectorielle</h3>
                <p style="color: var(--text-dim); font-size: 0.9rem;">Discutez avec vos documents PDF/TXT en toute sécurité. Vos données restent locales, indexées et protégées.</p>
            </div>

            <!-- GPU -->
            <div class="glass-panel reveal" style="padding: 40px;">
                <div style="width: 48px; height: 48px; background: rgba(99, 102, 241, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i data-lucide="zap" style="color: var(--primary);"></i>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 10px;">Accélération Native</h3>
                <p style="color: var(--text-dim); font-size: 0.9rem;">Exploitez 100% de votre GPU (NVIDIA/AMD) grâce à notre passerelle Docker optimisée et ultra-basse latence.</p>
            </div>
        </div>
    </section>
</div>

<!-- FOOTER -->
<footer style="padding: 60px 0; border-top: 1px solid var(--glass-border); background: rgba(0,0,0,0.2); backdrop-filter: blur(10px); margin-top: 100px;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-weight: 800; opacity: 0.5;">EasyLocalAI</div>
        <div style="font-size: 0.8rem; color: var(--text-dim);">
            Souveraineté par le Code &copy; 2026
        </div>
        <div style="display: flex; gap: 20px;">
             <i data-lucide="shield-check" style="width: 18px; color: var(--emerald-glow);"></i>
             <i data-lucide="github" style="width: 18px; opacity: 0.5;"></i>
        </div>
    </div>
</footer>

<script src="kinetic-bg.js"></script>
<script>
    lucide.createIcons();
    
    // Intersection Observer pour les révélations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>

</body>
</html>
