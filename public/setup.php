<?php
// public/setup.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Config;
use EasyLocalAI\Setup\SetupManager;

$config = new Config();
$setup  = new SetupManager($config);

if ($setup->handleForm()) {
    header("Location: chat.php");
    exit;
}

$app_name = $config->getAppName();
include __DIR__ . '/includes/header.php';
?>

<header>
    <h1>Configuration Assistant</h1>
    <p class="subtitle">Personnalisez le nom et l'expertise par défaut de votre IA.</p>
</header>

<section class="response-box">
    <form method="post" style="display: flex; flex-direction: column; gap: 20px;">
        <input type="hidden" name="action" value="setup_save">
        
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <label style="font-size: 0.8rem; color: var(--primary); font-weight: 600;">NOM DE L'ASSISTANT</label>
            <input type="text" name="app_name_input" value="<?= htmlspecialchars($app_name) ?>" required maxlength="30">
        </div>

        <div style="display: flex; flex-direction: column; gap: 8px;">
            <p style="font-size: 0.75rem; color: var(--text-dim); margin-bottom: 5px;">Choisissez un profil pré-configuré ou créez le vôtre.</p>
            <select name="profile_choice" id="profileChoice">
                <?php foreach ($setup->getProfiles() as $key => $p): ?>
                    <option value="<?= $key ?>" <?= $key === 'general' ? 'selected' : '' ?>><?= htmlspecialchars($p['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <div id="customPromptContainer" style="display: none; flex-direction: column; gap: 8px; margin-top: 15px; padding: 15px; background: rgba(99, 102, 241, 0.1); border-radius: 12px; border: 1px solid var(--primary);">
                <label style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">🛠️ PERSONNALISER LE PROFIL</label>
                <p style="font-size: 0.7rem; color: var(--text-dim); margin: 0;"><strong>Astuce :</strong> Commencez par "Tu es un expert en [Domaine]..." pour de meilleurs résultats.</p>
                <textarea name="custom_prompt" style="min-height: 120px; resize: vertical;" placeholder="Ex: Tu es un expert en développement PHP..."></textarea>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-dim);">Chaque profil définit le comportement et les limites de votre assistant.</p>
        </div>

        <button type="submit" style="width: 100%;">Enregistrer et Activer</button>
        <a href="chat.php" style="text-align: center; color: var(--text-dim); text-decoration: none; font-size: 0.85rem;">Annuler et retourner au Chat</a>
    </form>
</section>

<script>
    const profileChoice = document.getElementById('profileChoice');
    const customPromptContainer = document.getElementById('customPromptContainer');
    profileChoice?.addEventListener('change', () => {
        customPromptContainer.style.display = (profileChoice.value === 'custom') ? 'flex' : 'none';
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
