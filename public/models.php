<?php
// public/models.php - Model Management
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\Core\Security;

$ollama = Container::get('ollama');
$config = Container::get('config');

$message = "";
$messageType = "info";

// Handle Pull Model
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pull_model'])) {
    if (!Security::checkCsrf($_POST['csrf_token'] ?? '')) {
        die("CSRF invalid");
    }
    $modelToPull = Security::sanitize($_POST['model_name_pull']);
    if ($modelToPull) {
        $message = "Téléchargement de $modelToPull lancé... cela peut prendre quelques minutes.";
        // On lance le pull. Note: En PHP synchrone, ça va bloquer jusqu'à la fin ou timeout.
        if ($ollama->pullModel($modelToPull)) {
            $message = "Modèle $modelToPull récupéré avec succès !";
            $messageType = "success";
        } else {
            $message = "Erreur lors du téléchargement. Vérifiez votre connexion ou le nom du modèle.";
            $messageType = "error";
        }
    }
}

// Handle Set Default Model
if (isset($_GET['set_default'])) {
    $modelName = Security::sanitize($_GET['set_default']);
    $config->set('model_name', $modelName);
    $config->save();
    header("Location: models.php?updated=1");
    exit;
}

$models = $ollama->listModels();
$currentModel = $config->get('model_name', 'llama3.2');

$app_name = $config->getAppName();
include __DIR__ . '/includes/header.php';
?>

<header>
    <h1>Gestion des Modèles</h1>
    <p class="subtitle">Administrez vos modèles Ollama locaux.</p>
</header>

<?php if ($message): ?>
    <div style="padding: 15px; border-radius: 12px; margin-bottom: 20px; background: <?= $messageType === 'success' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)' ?>; border: 1px solid <?= $messageType === 'success' ? '#10b981' : '#ef4444' ?>;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<section class="response-box">
    <strong style="margin-bottom: 15px;">📥 Télécharger un nouveau modèle</strong>
    <form method="post" style="display: flex; gap: 10px;">
        <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
        <input type="text" name="model_name_pull" placeholder="ex: mistral, llama3:8b, phi3..." required>
        <button type="submit" name="pull_model">Télécharger</button>
    </form>
    <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 10px;">Retrouvez la liste des noms sur <a href="https://ollama.com/library" target="_blank" style="color: var(--primary);">ollama.com/library</a></p>
</section>

<section>
    <h3 style="font-size: 1rem; color: var(--primary);">Modèles Installés</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 15px;">
        <?php foreach ($models as $m): ?>
            <?php $isDefault = ($m['name'] === $currentModel || $m['name'] === $currentModel.":latest"); ?>
            <div style="background: var(--glass); padding: 20px; border-radius: 16px; border: 1px solid <?= $isDefault ? 'var(--primary)' : 'var(--border)' ?>; position: relative;">
                <?php if ($isDefault): ?>
                    <span style="position: absolute; top: 10px; right: 10px; font-size: 0.7rem; background: var(--primary); padding: 2px 8px; border-radius: 10px;">ACTIF</span>
                <?php endif; ?>
                <strong style="display: block; font-size: 1.1rem;"><?= htmlspecialchars($m['name']) ?></strong>
                <p style="font-size: 0.8rem; color: var(--text-dim); margin: 5px 0;">
                    Taille: <?= round($m['size'] / (1024*1024*1024), 2) ?> Go<br>
                    Format: <?= htmlspecialchars($m['details']['format'] ?? 'N/A') ?>
                </p>
                <?php if (!$isDefault): ?>
                    <a href="?set_default=<?= urlencode($m['name']) ?>" style="font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 600;">Utiliser par défaut →</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
