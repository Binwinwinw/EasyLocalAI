<?php
// public/skills.php - DI Refactor
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$setup  = Container::get('setup');
$config = Container::get('config');

if ($setup->handleForm()) {
    header("Location: setup.php");
    exit;
}

$app_name = $config->getAppName();
include __DIR__ . '/includes/header.php';
?>

<header>
    <h1>Compétences IA</h1>
    <p class="subtitle">Créez de nouveaux profils d'experts pour votre assistant.</p>
</header>

<section class="response-box">
    <form method="post" style="display: flex; flex-direction: column; gap: 15px;">
        <input type="hidden" name="action" value="skill_add">
        <input type="hidden" name="csrf_token" value="<?= EasyLocalAI\Core\Security::getCsrfToken() ?>">
        <strong style="font-size: 0.8rem; color: var(--primary); letter-spacing: 1px;">➕ CRÉER UNE NOUVELLE COMPÉTENCE</strong>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 0.7rem; color: var(--text-dim);">IDENTIFIANT (ex: seo)</label>
                <input type="text" name="skill_name" placeholder="id_unique" required maxlength="20">
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 0.7rem; color: var(--text-dim);">NOM AFFICHÉ (ex: Expert SEO)</label>
                <input type="text" name="skill_label" placeholder="Nom du profil" required maxlength="50">
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 5px;">
            <label style="font-size: 0.7rem; color: var(--text-dim);">PROMPT SYSTÈME</label>
            <p style="font-size: 0.7rem; color: var(--text-dim); margin-bottom: 5px;"><strong>Astuce :</strong> Décrivez précisément son rôle : "Tu es un expert en..."</p>
            <textarea name="skill_prompt" placeholder="Définissez sa personnalité et ses règles..." style="min-height: 150px; resize: vertical;" required></textarea>
        </div>

        <button type="submit">Ajouter cet Expert</button>
    </form>
</section>

<section style="margin-top: 30px;">
    <h3 style="font-size: 1rem; color: var(--primary);">Profils Actuels</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
        <?php foreach ($setup->getProfiles() as $key => $p): ?>
            <div style="background: var(--glass); padding: 15px; border-radius: 12px; border: 1px solid var(--border);">
                <strong style="display: block; font-size: 0.9rem; color: var(--text);"><?= htmlspecialchars($p['label']) ?></strong>
                <code style="font-size: 0.7rem; color: var(--text-dim);">ID: <?= htmlspecialchars($key) ?></code>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
