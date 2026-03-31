<?php
/**
 * EasyLocalAI - Landing Immersive 3D V2
 * Point d'entrée public de l'application avec Three.js.
 */
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\Core\Auth;

$app_name = Container::get('config')->get('app_name', 'EasyLocalAI');
$auth = Container::get('auth');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?> | L'IA Souveraine Immersion 3D</title>
    
    <!-- Bibliothèques Premium -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css?v=4.3">
    <link rel="stylesheet" href="landing.css?v=2.0">
    
    <!-- Scripts Moteur -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="immersive-landing">

<!-- CANAVE 3D FONDS -->
<div id="canvas-container"></div>

<!-- Navigation Fixe -->
<nav class="main-nav">
    <div class="nav-container">
        <div class="nav-logo"><?= htmlspecialchars($app_name) ?></div>
        <div class="nav-actions">
            <?php if ($auth->isLoggedIn()): ?>
                <a href="chat.php" class="btn-nav">TABLEAU DE BORD</a>
            <?php else: ?>
                <a href="login.php" class="btn-nav">SE CONNECTER</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="content-overlay">
    <!-- Hero Section Parallaxe -->
    <section class="hero-section glass-hero">
        <div class="hero-inner">
            <div class="badge neon-badge">EXPLOREZ L'IA LOCALE V4.3</div>
            <h1 class="massive-title">L'IA qui vous <span class="gradient-text">appartient</span> vraiment.</h1>
            <p class="hero-sub">Déployez un écosystème d'IA complet, sans cloud et sans compromis. Sécurité militaire, performance native, contrôle total.</p>
            <div class="cta-group">
                <a href="chat.php" class="btn-primary">DÉCOLLER MAINTENANT</a>
                <a href="#features" class="btn-outline">VOIR LES CAPACITÉS</a>
            </div>
        </div>
    </section>

    <!-- Features Bento section -->
    <section id="features" class="features-section">
        <h3 class="section-label">CAPACITÉS ÉLITE</h3>
        <div class="bento-grid">
            <div class="bento-card large">
                <div class="card-icon"><i data-lucide="brain"></i></div>
                <h4>Agents Autonomes</h4>
                <p>Des agents capables de réfléchir, d'utiliser des outils et de corriger leurs erreurs en temps réel avec une logique agentique poussée.</p>
            </div>
            <div class="bento-card">
                <div class="card-icon"><i data-lucide="database"></i></div>
                <h4>RAG Avancé</h4>
                <p>Indexez vos documents locaux et discutez avec vos données en toute confidentialité grâce au vector store intégré.</p>
            </div>
            <div class="bento-card">
                <div class="card-icon"><i data-lucide="zap"></i></div>
                <h4>GPU Boost</h4>
                <p>Vitesse d'exécution décuplée grâce à l'optimisation native des containers Docker & Ollama.</p>
            </div>
            <div class="bento-card high">
                <div class="card-icon"><i data-lucide="shield-check"></i></div>
                <h4>Souveraineté Totale</h4>
                <p>Aucune donnée ne quitte votre machine. Le respect absolu de la vie privée par le code.</p>
            </div>
        </div>
    </section>

    <!-- Social Proof / Why -->
    <section class="why-section">
        <h3 class="section-label">POURQUOI NOUS ?</h3>
        <div class="why-list">
            <div class="why-item-glow">
                <i data-lucide="terminal"></i>
                <div>
                    <h4>Infrastructure "Béton Armé"</h4>
                    <p>Pilotage intelligent des containers via socket Unix sécurisé et redémarrage automatique.</p>
                </div>
            </div>
            <div class="why-item-glow">
                <i data-lucide="layout"></i>
                <div>
                    <h4>Interface Nouvelle Génération</h4>
                    <p>Design "Kinetic" pensé pour la performance fluide et le confort visuel expert.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- GDPR / Footer -->
    <footer class="landing-footer glass-footer">
        <div class="security-box">
            <div class="sec-header">
                <i data-lucide="lock" style="color: #10b981;"></i>
                <h4>CONFORMITÉ RGPD & SÉCURITÉ SOUVERAINE</h4>
            </div>
            <p>EasyLocalAI garantit le respect strict du RGPD. En mode local, aucune télémétrie n'est envoyée. Vos fichiers, vos prompts et l'historique restent chiffrés sur votre stockage local (Article 32).</p>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 <?= htmlspecialchars($app_name) ?>. Fait pour la liberté numérique.</p>
        </div>
    </footer>
</div>

<script src="landing.js?v=2.0"></script>
<script>
    // Initialise les icônes Lucide
    lucide.createIcons();
    
    // Intersection Observer pour les révélations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('section, .bento-card, .why-item-glow').forEach(el => {
        el.classList.add('reveal');
        observer.observe(el);
    });
</script>

</body>
</html>
