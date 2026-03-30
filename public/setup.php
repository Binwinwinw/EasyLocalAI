<?php
// public/setup.php - Unified Control Center (Expert Edition V4 - Hybrid AI)
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\Core\Security;

$ollama = Container::get('ollama');
$config = Container::get('config');
$setup  = Container::get('setup');
$env    = Container::get('env');

// Handle Model Actions (Local Ollama)
if (isset($_GET['set_default'])) {
    $modelName = Security::sanitize($_GET['set_default']);
    $config->set('model_name', $modelName);
    $config->set('active_provider', 'ollama'); // On repasse en ollama si on choisit un modèle local
    $config->save();
    header("Location: setup.php?updated=1");
    exit;
}

// Handle Cloud Provider Change
if (isset($_POST['action']) && $_POST['action'] === 'set_provider') {
    $provider = Security::sanitize($_POST['active_provider']);
    $config->set('active_provider', $provider);
    $config->save();
    
    // On met en session la clé API reçue (si présente) pour le flux stream.php actuel
    if (isset($_POST['api_key'])) {
        $_SESSION["cloud_{$provider}_api_key"] = $_POST['api_key'];
    }
    
    header("Location: setup.php?updated_provider=1");
    exit;
}

if (isset($_GET['delete_model'])) {
    $modelToDelete = Security::sanitize($_GET['delete_model']);
    $currentModel = $config->get('model_name', 'llama3.2');
    if ($modelToDelete !== $currentModel && $modelToDelete !== $currentModel.":latest") {
        $ollama->deleteModel($modelToDelete);
        header("Location: setup.php?deleted=" . urlencode($modelToDelete));
        exit;
    }
}

// Action : Réinitialiser la base vectorielle
if (isset($_POST['action']) && $_POST['action'] === 'clear_vectors') {
    Container::get('vectorStore')->clear();
    header("Location: setup.php?vectors_cleared=1");
    exit;
}

// Action : Supprimer un document physique
if (isset($_GET['delete_doc'])) {
    $docName = basename($_GET['delete_doc']);
    $docPath = __DIR__ . '/../knowledge/' . $docName;
    if (file_exists($docPath)) unlink($docPath);
    header("Location: setup.php?doc_deleted=1");
    exit;
}

if ($setup->handleForm()) {
    header("Location: chat.php");
    exit;
}

// Handle Infrastructure Change (Ollama Models Path)
if (isset($_POST['action']) && $_POST['action'] === 'set_infra') {
    $path = Security::sanitize($_POST['ollama_models_path']);
    $success = $env->set('OLLAMA_MODELS_PATH', $path);
    header("Location: setup.php?updated_infra=" . ($success ? "1" : "0"));
    exit;
}

$models = $ollama->listModels();
$currentModelName = $config->get('model_name', 'llama3.2');
$activeProvider = $config->get('active_provider', 'ollama');

// Ensure current model is in list
$foundCurrent = false;
foreach ($models as $m) {
    if ($m['name'] === $currentModelName || $m['name'] === $currentModelName.":latest") {
        $foundCurrent = true;
        break;
    }
}
if (!$foundCurrent) {
    array_unshift($models, [
        'name' => $currentModelName,
        'size' => 0,
        'details' => ['format' => 'N/A'],
        'not_pulled' => true
    ]);
}

$app_name = $config->getAppName();
include __DIR__ . '/includes/header.php';
?>

<header style="margin-bottom: 50px;">
    <h1 style="display:flex; align-items:center; gap:15px; font-weight: 300; letter-spacing: 2px;">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary); opacity: 0.8;"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.78 1.35a2 2 0 0 0 .73 2.73l.15.08a2 2 0 0 1 1 1.73v.56a2 2 0 0 1-1 1.73l-.15.08a2 2 0 0 0-.73 2.73l.78 1.35a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 1-1.73v.18a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.78-1.35a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.73v-.56a2 2 0 0 1 1-1.73l.15-.08a2 2 0 0 0 .73-2.73l-.78-1.35a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        SYSTEM CONTROL CENTER <span style="font-size: 0.5rem; background: var(--primary); color:white; padding: 2px 8px; border-radius: 20px; vertical-align: middle; margin-left: 10px;">HYBRID V4</span>
    </h1>
    <p class="subtitle" style="opacity: 0.5;">Pilotage expert de votre infrastructure souveraine et distribuée.</p>
</header>

<div class="bento-grid">
    <!-- Section Identité -->
    <div class="bento-item bento-span-2">
        <form method="post" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="action" value="setup">
            <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
            <h3 style="margin:0 0 15px 0; font-size: 0.8rem; letter-spacing: 0.1em; color: var(--primary);">IDENTITÉ</h3>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 0.75rem; color: var(--text-dim);">Nom de l'IA</label>
                <input type="text" name="app_name" value="<?= htmlspecialchars($app_name) ?>" style="background: rgba(0,0,0,0.1); border-radius: 12px; padding: 12px;">
            </div>
            <button type="submit" style="width: 100%; justify-content: center; margin-top: 10px;">Mettre à jour l'identité</button>
        </form>
    </div>

    <!-- Section Expertise -->
    <div class="bento-item bento-span-2">
        <form method="post" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="action" value="setup">
            <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
            <h3 style="margin:0 0 15px 0; font-size: 0.8rem; letter-spacing: 0.1em; color: var(--primary);">EXPERTISE NEURONALE</h3>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 0.75rem; color: var(--text-dim);">System Prompt (Instructions)</label>
                <textarea name="system_prompt" style="background: rgba(0,0,0,0.1); border-radius: 12px; padding: 12px; height: 100px; font-size: 0.85rem; line-height:1.4;"><?= htmlspecialchars($config->get('system_prompt', '')) ?></textarea>
            </div>
            <button type="submit" style="width: 100%; justify-content: center;">Mettre à jour l'expertise</button>
        </form>
    </div>

    <!-- PONT API & CLOUDS -->
    <div class="bento-item bento-span-2" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.05) 0%, rgba(109, 40, 217, 0.05) 100%); border: 1px solid rgba(139, 92, 246, 0.2);">
        <h3 style="margin:0 0 15px 0; font-size: 0.8rem; letter-spacing: 0.1em; color: #a78bfa;">PONT API & CLOUDS</h3>
        <form method="post" id="providerForm" onsubmit="saveApiKeyBeforeSubmit(event)">
            <input type="hidden" name="action" value="set_provider">
            <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
            <input type="hidden" name="api_key" id="api_key_hidden">
            
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 0.7rem; color: var(--text-dim);">Source de puissance active</label>
                    <select name="active_provider" id="active_provider" onchange="updateProviderUI()" style="background: rgba(0,0,0,0.2); border-radius: 10px; padding: 8px;">
                        <option value="ollama" <?= $activeProvider === 'ollama' ? 'selected' : '' ?>>Ollama (Local - Gratuit)</option>
                        <option value="cortex" <?= $activeProvider === 'cortex' ? 'selected' : '' ?>>Cortex Gateway (Béton Armé - Failover)</option>
                        <option value="groq" <?= $activeProvider === 'groq' ? 'selected' : '' ?>>Groq (Turbo - API)</option>
                        <option value="openai" <?= $activeProvider === 'openai' ? 'selected' : '' ?>>OpenAI (Expert - API)</option>
                        <option value="minimax" <?= $activeProvider === 'minimax' ? 'selected' : '' ?>>MiniMax (Asie - API)</option>
                    </select>
                </div>

                <div id="api_key_section" style="display: <?= $activeProvider === 'ollama' ? 'none' : 'flex' ?>; flex-direction: column; gap: 5px;">
                    <label style="font-size: 0.7rem; color: var(--text-dim);">Clé API <span style="opacity:0.5;">(Session locale uniquement)</span></label>
                    <input type="password" id="api_key_input" placeholder="Saisissez votre clé..." style="background: rgba(0,0,0,0.2); border-radius: 10px; padding: 8px;">
                    <p style="font-size: 0.6rem; color: #a78bfa; margin-top: 5px; line-height: 1.2;">
                        🔒 Sécurité Zero-Knowledge : Votre clé reste dans ce navigateur et n'est jamais stockée sur le serveur.
                    </p>
                </div>

                <button type="submit" style="background: #7c3aed; color: white;">Activer le Moteur</button>
            </div>
        </form>
    </div>

    <!-- Stratégie de coût et performance -->
    <div class="bento-item bento-span-2" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(5, 150, 105, 0.05) 100%); border: 1px solid rgba(16, 185, 129, 0.1);">
        <h3 style="margin:0 0 10px 0; font-size: 0.8rem; letter-spacing: 0.1em; color: #10b981;">STRATÉGIE & COÛTS</h3>
        <p style="font-size: 0.75rem; line-height: 1.5; opacity: 0.8;">
            <span style="color:#10b981;">●</span> <strong>Cloud</strong> : Payant au token. Vitesse Max.<br>
            <span style="color:#10b981;">●</span> <strong>MoE (Experts)</strong> : Gratuit. Ratio IQ/Vitesse optimal.<br>
            <span style="color:#10b981;">●</span> <strong>Hybrid</strong> : Utilisez le Local pour le RAG confidentiel et le Cloud pour le code complexe.
        </p>
    </div>

    <!-- POWER BOOST (GPU NATIVE) -->
    <div class="bento-item bento-span-2" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(217, 119, 6, 0.05) 100%); border: 1px solid rgba(245, 158, 11, 0.2);">
        <h3 style="margin:0 0 10px 0; font-size: 0.8rem; letter-spacing: 0.1em; color: #f59e0b;">POWER BOOST (GPU HÔTE)</h3>
        <p style="font-size: 0.75rem; line-height: 1.4; opacity: 0.8; margin-bottom: 15px;">
            Utilisez la **puissance GPU** de votre Windows (Ollama Natif) comme accélérateur principal.
        </p>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <button onclick="openNativeDiscovery()" style="background: #f59e0b; color: white; width: 100%; justify-content: center;">DÉTECTER MES MODÈLES PC</button>
            <div id="nativeStatus" style="font-size: 0.65rem; color: var(--text-dim); text-align: center;">Vérifiez votre Ollama Windows</div>
        </div>
    </div>

    <!-- Section Téléchargement Modèles (Local) -->
    <div class="bento-item bento-span-4" style="background: rgba(3, 105, 161, 0.05); <?= $activeProvider !== 'ollama' ? 'opacity: 0.5; pointer-events: none;' : '' ?>">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="margin:0; font-size: 0.8rem; letter-spacing: 0.1em; color: var(--primary);">GESTION LOCALE (OLLAMA)</h3>
            <?php if ($activeProvider !== 'ollama'): ?>
                <span style="font-size: 0.65rem; color: #f59e0b; font-weight: 800;">DÉSACTIVÉ (MODE CLOUD ACTIF)</span>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 15px;">
            <input type="text" id="manualModelName" placeholder="ex: mistral, llama3, qwen2:3b..." style="background: rgba(0,0,0,0.2); border-radius: 12px; flex: 1;">
            <button onclick="startPull(document.getElementById('manualModelName').value)" style="min-width: 150px; justify-content: center;">PULL MODEL</button>
        </div>
    </div>

    <!-- Liste des Modèles (Bibliothèque Locale) -->
    <div class="bento-item bento-span-4" style="<?= $activeProvider !== 'ollama' ? 'opacity: 0.7;' : '' ?>">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin:0; font-size: 0.8rem; letter-spacing: 0.05em; color: var(--text);">BIBLIOTHÈQUE LOCALE</h3>
            <button onclick="location.reload()" style="background:none; border:none; color:var(--text-dim); font-size:0.7rem; cursor:pointer; opacity:0.6;">ACTUALISER</button>
        </div>
        <div id="modelList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($models as $m): ?>
                <?php 
                    $isDefault = ($m['name'] === $currentModelName || $m['name'] === $currentModelName.":latest");
                    $notPulled = isset($m['not_pulled']);
                    $sizeGB = round($m['size'] / (1024*1024*1024), 2);
                    $isHeavy = ($sizeGB > 8.0);
                    $safeName = str_replace([':', '.'], '-', $m['name']);
                ?>
                <div id="card-<?= $safeName ?>" class="card-glass" style="padding: 20px; background: rgba(255,255,255,0.02); border: 1px solid <?= ($isDefault && $activeProvider === 'ollama') ? 'var(--primary)' : 'var(--border)' ?>; border-radius: 18px; position:relative;">
                    
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                        <span style="font-weight:700; font-size:1rem;"><?= htmlspecialchars($m['name']) ?></span>
                        <div style="display:flex; gap:5px;">
                            <?php if ($isDefault && $activeProvider === 'ollama'): ?>
                                <span style="font-size: 0.55rem; background: var(--primary); color: white; padding: 2px 7px; border-radius: 8px; font-weight:900;">ACTIF</span>
                            <?php elseif (!$notPulled): ?>
                                <span style="font-size: 0.55rem; background: rgba(255,255,255,0.1); color: var(--text-dim); padding: 2px 7px; border-radius: 8px; font-weight:800;">INSTALLÉ</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p style="font-size: 0.75rem; color: var(--text-dim); margin-bottom: 20px;">
                        Taille : <span style="color:<?= $isHeavy ? '#fca5a5' : 'var(--text)' ?>"><?= $notPulled ? 'Inconnue' : ($sizeGB . ' Go') ?></span>
                    </p>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <?php if ($notPulled): ?>
                            <button onclick="startPull('<?= $m['name'] ?>')" style="padding: 10px 15px; font-size: 0.75rem; width: 100%;">TÉLÉCHARGER</button>
                        <?php elseif ($activeProvider !== 'ollama' || !$isDefault): ?>
                            <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
                                <a href="?set_default=<?= urlencode($m['name']) ?>" style="font-size: 0.7rem; color: var(--primary); text-decoration: none; font-weight:800; letter-spacing:0.05em;">ACTIVER LOCAL</a>
                                <button onclick="confirmDelete('<?= $m['name'] ?>')" style="background:none; border:none; color: #f87171; cursor:pointer; font-size: 0.65rem; padding:0; opacity:0.6;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">SUPPRIMER</button>
                            </div>
                        <?php else: ?>
                            <span style="font-size: 0.7rem; color: var(--primary); font-weight:800; display:flex; align-items:center; gap:5px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                CHARGÉ
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($models)): ?>
                <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; background: rgba(255,255,255,0.02); border: 2px dashed rgba(255,255,255,0.05); border-radius: 24px;">
                    <div style="font-size: 3rem; margin-bottom: 20px;">📦</div>
                    <h3 style="margin: 0 0 10px 0; font-size: 1.2rem; color: var(--primary);">Bibliothèque Vide</h3>
                    <p style="opacity: 0.6; font-size: 0.85rem; max-width: 400px; margin: 0 auto 25px auto; line-height: 1.5;">
                        Pour commencer à utiliser l'IA gratuitement et hors-ligne, vous devez installer votre premier modèle de langage.
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <button onclick="startPull('llama3.2')" style="background: var(--primary); color: white; padding: 12px 25px; font-weight: 700;">
                            🚀 INSTALLER LLAMA 3.2 (2GB)
                        </button>
                        <button onclick="startPull('qwen2.5:3b')" style="background: rgba(255,255,255,0.05); color: white; padding: 12px 25px;">
                            🏮 QWEN 2.5 (3B)
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- STOCKAGE & INFRASTRUCTURE -->
    <div class="bento-item bento-span-4" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(37, 99, 235, 0.05) 100%); border: 1px solid rgba(59, 130, 246, 0.2);">
        <h3 style="margin:0 0 15px 0; font-size: 0.8rem; letter-spacing: 0.1em; color: #3b82f6;">STOCKAGE & INFRASTRUCTURE</h3>
        <form method="post" style="display: flex; flex-direction: column; gap: 15px;">
            <input type="hidden" name="action" value="set_infra">
            <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-size: 0.75rem; color: var(--text-dim);">Chemin Racine des Modèles (Sur l'Hôte)</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="ollama_models_path" id="ollama_models_path" value="<?= htmlspecialchars($env->get('OLLAMA_MODELS_PATH', 'F:/.ollama')) ?>" placeholder="ex: F:/.ollama ou /home/user/.ollama" style="background: rgba(0,0,0,0.2); border-radius: 10px; padding: 12px; flex: 1; font-family: monospace; font-size: 0.8rem;">
                    <button type="button" onclick="openFolderPicker()" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; min-width: 120px; display: flex; align-items: center; gap: 8px; justify-content: center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        PARCOURIR
                    </button>
                    <button type="submit" style="background: #3b82f6; color: white; min-width: 120px;">SAUVEGARDER</button>
                </div>
                <p style="font-size: 0.65rem; color: var(--text-dim); margin: 5px 0 0 0; line-height: 1.4;">
                    💡 <strong>Note</strong> : Pour Windows, utilisez des slashs <code>/</code>. <br>
                    ⚠️ <strong>Important</strong> : Après avoir sauvegardé, vous devez redémarrer le système avec <code>docker-compose up -d</code>.
                </p>
            </div>
            <?php if (isset($_GET['updated_infra'])): ?>
                <div style="font-size: 0.75rem; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2);">
                    ✅ Configuration enregistrée dans le fichier .env. Redémarrez l'infrastructure pour appliquer.
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- GESTIONNAIRE DE CONNAISSANCES (ADVANCED RAG) -->
    <div class="bento-item bento-span-4" style="background: linear-gradient(135deg, rgba(167, 139, 250, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%); border: 1px solid rgba(139, 92, 246, 0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="margin:0; font-size: 0.8rem; letter-spacing: 0.1em; color: #a78bfa;">BIBLIOTHÈQUE DE CONNAISSANCES</h3>
            <form method="post" onsubmit="return confirm('Attention : Cela supprimera tout l\'index sémantique. Continuer ?')">
                <input type="hidden" name="action" value="clear_vectors">
                <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
                <button type="submit" style="background:rgba(239, 68, 68, 0.1); color:#f87171; border:1px solid rgba(239, 68, 68, 0.2); font-size:0.6rem; padding:5px 10px; cursor:pointer;">RÉINITIALISER L'INDEX (VECTORS.JSON)</button>
            </form>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:15px;">
            <?php 
                $knowledgeDir = __DIR__ . '/../knowledge/';
                $files = array_diff(scandir($knowledgeDir), array('..', '.', 'vectors.json', '.gitkeep'));
                if (empty($files)):
            ?>
                <p style="grid-column:1/-1; opacity:0.5; font-size:0.75rem; text-align:center; padding:20px; border:1px dashed rgba(255,255,255,0.1); border-radius:15px;">
                    Aucun document dans la base de connaissances.
                </p>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <div class="card-glass" style="padding:12px; display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.2); border-radius:12px; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="overflow:hidden; display:flex; align-items:center; gap:10px;">
                            <span style="font-size:1.2rem;"><?= pathinfo($file, PATHINFO_EXTENSION) === 'pdf' ? '📕' : '📄' ?></span>
                            <div style="overflow:hidden;">
                                <div style="font-size:0.75rem; font-weight:700; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;">
                                    <?= htmlspecialchars($file) ?>
                                </div>
                                <div style="font-size:0.6rem; opacity:0.5;"><?= strtoupper(pathinfo($file, PATHINFO_EXTENSION)) ?></div>
                            </div>
                        </div>
                        <a href="?delete_doc=<?= urlencode($file) ?>" style="color:#f87171; text-decoration:none; font-size:1.1rem; padding:0 5px; opacity:0.5;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.5" onclick="return confirm('Supprimer ce document ?')">&times;</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div style="margin-top:20px; font-size:0.65rem; color:var(--text-dim); line-height:1.4; display:flex; gap:10px; align-items:flex-start;">
            <div style="background:rgba(167, 139, 250, 0.2); padding:5px; border-radius:5px;">💡</div>
            <div>
                <strong>RAG Avancé (Hybrid V4)</strong> : Vos fichiers (PDF, TXT, MD) sont découpés en segments sémantiques avec chevauchement (Overlap) via le Gateway Python.
                Le système utilise <code>nomic-embed-text</code> pour transformer vos connaissances en vecteurs mathématiques.
            </div>
        </div>
    </div>
</div>

<!-- MODAL FOLDER PICKER -->
<div id="folderPickerModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); backdrop-filter:blur(10px); z-index:9999; align-items:center; justify-content:center;">
    <div class="bento-item" style="width:90%; max-width:700px; height:80vh; display:flex; flex-direction:column; padding:0; overflow:hidden; border:1px solid var(--primary);">
        <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02);">
            <h3 style="margin:0; font-size:0.9rem; letter-spacing:1px; color:var(--primary);">EXPLORATEUR SYSTÈME</h3>
            <button onclick="closeFolderPicker()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer; opacity:0.5;">&times;</button>
        </div>
        
        <div id="pickerBreadcrumb" style="padding:10px 20px; font-size:0.7rem; color:var(--text-dim); background:rgba(0,0,0,0.2); border-bottom:1px solid rgba(255,255,255,0.05); display:flex; gap:5px; align-items:center;">
            <!-- Breadcrumb JS -->
        </div>

        <div id="pickerDrives" style="padding:15px 20px; display:flex; gap:10px; background:rgba(0,0,0,0.1);">
            <button onclick="navigatePicker('/c')" class="btn-drive">C:</button>
            <button onclick="navigatePicker('/f')" class="btn-drive">F:</button>
        </div>

        <div id="pickerContent" style="flex:1; overflow-y:auto; padding:10px 0;">
            <!-- Folder List JS -->
        </div>

        <div style="padding:20px; border-top:1px solid rgba(255,255,255,0.1); background:rgba(0,0,0,0.2); display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:0.75rem; color:var(--text-dim);">
                Séléection : <span id="currentSelectionText" style="color:white; font-family:monospace;">/</span>
            </div>
            <div style="display:flex; gap:10px;">
                <button onclick="closeFolderPicker()" style="background:rgba(255,255,255,0.1); color:white;">ANNULER</button>
                <button onclick="confirmSelection()" style="background:var(--primary); color:white;">SÉLECTIONNER</button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-overlay { display: flex; animation: fadeIn 0.2s ease-out; }
.btn-drive { background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: #60a5fa; padding: 5px 15px; border-radius: 6px; font-size: 0.7rem; cursor: pointer; transition: 0.2s; }
.btn-drive:hover { background: rgba(59, 130, 246, 0.4); }
.picker-item { display: flex; align-items: center; gap: 12px; padding: 10px 20px; cursor: pointer; transition: 0.1s; border-bottom: 1px solid rgba(255,255,255,0.02); }
.picker-item:hover { background: rgba(255,255,255,0.05); }
.picker-item svg { opacity: 0.5; color: var(--primary); }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<script>
// -- Gestion de l'Explorateur de Dossiers --
let currentPickerPath = '';

function openFolderPicker() {
    document.getElementById('folderPickerModal').style.display = 'flex';
    navigatePicker('');
}

function closeFolderPicker() {
    document.getElementById('folderPickerModal').style.display = 'none';
}

function navigatePicker(path) {
    currentPickerPath = path;
    const content = document.getElementById('pickerContent');
    const breadcrumb = document.getElementById('pickerBreadcrumb');
    const selection = document.getElementById('currentSelectionText');
    
    content.innerHTML = '<div style="padding:20px; opacity:0.5; font-size:0.8rem;">Chargement...</div>';
    breadcrumb.innerHTML = '📂 ' + (path || '/');
    selection.innerText = path || '/';

    fetch('api_explorer.php?path=' + encodeURIComponent(path))
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                content.innerHTML = `<div style="padding:20px; color:#f87171; font-size:0.8rem;">Erreur : ${data.error}</div>`;
                return;
            }

            let html = '';
            
            // Bouton Retour si on n'est pas à la racine
            if (data.parent_path !== null) {
                html += `
                    <div class="picker-item" onclick="navigatePicker('${data.parent_path}')" style="opacity:0.6;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        <span style="font-size:0.85rem;">... (Dossier parent)</span>
                    </div>
                `;
            }

            data.items.forEach(item => {
                const subPath = (path === '' ? '' : path) + '/' + item.name;
                html += `
                    <div class="picker-item" ondblclick="navigatePicker('${subPath}')" onclick="selectPath('${subPath}')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        <span style="font-size:0.85rem;">${item.name}</span>
                    </div>
                `;
            });

            if (data.items.length === 0 && data.parent_path === null) {
                html = '<div style="padding:20px; opacity:0.4; font-size:0.8rem;">Aucun disque détecté. Vérifiez votre montage Docker.</div>';
            }

            content.innerHTML = html;
        });
}

function selectPath(path) {
    currentPickerPath = path;
    document.getElementById('currentSelectionText').innerText = path;
}

function confirmSelection() {
    // Conversion du chemin Docker (/c/users...) vers Windows (C:/users...)
    let winPath = currentPickerPath;
    
    // Pattern /c/something -> C:/something
    const driveMatch = winPath.match(/^\/([a-z])\/(.*)$/i);
    if (driveMatch) {
        winPath = driveMatch[1].toUpperCase() + ':/' + driveMatch[2];
    } else {
        const singleDriveMatch = winPath.match(/^\/([a-z])$/i);
        if (singleDriveMatch) {
            winPath = singleDriveMatch[1].toUpperCase() + ':/';
        }
    }

    document.getElementById('ollama_models_path').value = winPath;
    closeFolderPicker();
}

// -- Gestion du Boost GPU (Ollama Natif Hôte) --
function openNativeDiscovery() {
    const modal = document.getElementById('nativeDiscoveryModal');
    const list = document.getElementById('nativeModelsList');
    const status = document.getElementById('nativeStatus');
    
    modal.style.display = 'flex';
    list.innerHTML = '<div style="opacity:0.5; padding:20px; text-align:center;">Recherche de votre Ollama Windows...</div>';

    // On passe par le Gateway (Proxy) pour éviter les erreurs CORS navigateur vers localhost:11434
    fetch('http://' + window.location.hostname + ':8003/v1/native/models') // Port du Gateway
        .then(res => res.json())
        .then(data => {
            if (!data.models || data.models.length === 0) {
                list.innerHTML = `
                    <div style="padding:20px; text-align:center;">
                        <p style="color:#f87171;">Ollama non détecté sur votre PC.</p>
                        <p style="font-size:0.7rem; opacity:0.6;">Vérifiez qu'Ollama pour Windows est lancé et réglé sur '0.0.0.0' si nécessaire.</p>
                    </div>`;
                status.innerText = "Non détecté";
                return;
            }

            let html = '<div style="display:flex; flex-direction:column; gap:10px;">';
            data.models.forEach(m => {
                html += `
                    <div class="picker-item" style="border: 1px solid rgba(245, 158, 11, 0.2); border-radius:10px;">
                        <div style="flex:1;">
                            <div style="font-weight:700; font-size:0.8rem;">${m.name}</div>
                            <div style="font-size:0.6rem; opacity:0.5;">${m.details.parameter_size || 'N/A'} - GPU Opti</div>
                        </div>
                        <span style="font-size:0.55rem; background:#f59e0b; color:black; padding:2px 5px; border-radius:4px; font-weight:900;">PRÊT</span>
                    </div>
                `;
            });
            html += '</div>';
            list.innerHTML = html;
            status.innerText = data.models.length + " modèles détectés";
        })
        .catch(err => {
            list.innerHTML = '<div style="padding:20px; color:#f87171;">Erreur de connexion au Gateway.</div>';
        });
}

function closeNativeDiscovery() {
    document.getElementById('nativeDiscoveryModal').style.display = 'none';
}

// -- Gestion des Clés API (localStorage) --
function updateProviderUI() {
    const provider = document.getElementById('active_provider').value;
    const keySection = document.getElementById('api_key_section');
    const keyInput = document.getElementById('api_key_input');
    
    if (provider === 'ollama') {
        keySection.style.display = 'none';
    } else {
        keySection.style.display = 'flex';
        // Charger la clé depuis le localStorage
        keyInput.value = localStorage.getItem('api_key_' + provider) || '';
    }
}

function saveApiKeyBeforeSubmit(e) {
    const provider = document.getElementById('active_provider').value;
    const key = document.getElementById('api_key_input').value;
    
    if (provider !== 'ollama') {
        // Sauvegarder dans le navigateur
        localStorage.setItem('api_key_' + provider, key);
        // Injecter dans le formulaire pour le PHP (temporaire pour la session)
        document.getElementById('api_key_hidden').value = key;
    }
}

// Initialisation au chargement
window.onload = function() {
    updateProviderUI();
    
    // Si on vient de mettre à jour le provider, s'assurer que le PHP a la clé
    <?php if (isset($_GET['updated_provider'])): ?>
        console.log("Provider mis à jour avec succès.");
    <?php endif; ?>
};

// -- Fonctions de Pull (Inchangées) --
function confirmDelete(name) {
    if (confirm("Supprimer définitivement " + name + " ? L'espace disque sera libéré.")) {
        window.location.href = "setup.php?delete_model=" + encodeURIComponent(name);
    }
}

function createProgressCard(modelName, safeName) {
    const list = document.getElementById('modelList');
    const card = document.createElement('div');
    card.id = 'card-' + safeName;
    card.className = 'card-glass';
    card.style = 'padding: 20px; background: rgba(255,255,255,0.05); border: 1px solid var(--primary); border-radius: 18px; position:relative;';
    card.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
            <span style="font-weight:700; font-size:1rem;">${modelName}</span>
            <span style="font-size: 0.55rem; background: var(--primary); color: white; padding: 2px 7px; border-radius: 8px; font-weight:900;">TÉLÉCHARGEMENT...</span>
        </div>
        <div id="progress-container-${safeName}" style="display:block; margin-bottom: 12px;">
            <div style="height:4px; background:rgba(255,255,255,0.1); border-radius:2px; overflow:hidden;">
                <div id="bar-${safeName}" style="height:100%; width:0%; background:var(--primary); transition: width 0.3s;"></div>
            </div>
        </div>
        <p id="status-${safeName}" style="font-size: 0.7rem; color: var(--text-dim); margin: 0;">Initialisation...</p>
    `;
    list.prepend(card);
}

function startPull(modelName) {
    if (!modelName) return;
    const safeName = modelName.replace(/[:.]/g, '-').replace(/\//g, '-');
    let container = document.getElementById('progress-container-' + safeName);
    if (!container) {
        createProgressCard(modelName, safeName);
        container = document.getElementById('progress-container-' + safeName);
    }
    const bar = document.getElementById('bar-' + safeName);
    const statusTxt = document.getElementById('status-' + safeName);

    const eventSource = new EventSource('pull_stream.php?model=' + encodeURIComponent(modelName));
    eventSource.onmessage = function(e) {
        if (e.data === '[DONE]') {
            eventSource.close();
            statusTxt.innerText = "Terminé !";
            setTimeout(() => location.reload(), 1000);
            return;
        }
        try {
            const data = JSON.parse(e.data);
            if (bar) bar.style.width = (data.progress || 0) + '%';
            if (statusTxt) statusTxt.innerText = data.status || "Téléchargement...";
        } catch (err) {}
    };
    eventSource.onerror = function() { 
        eventSource.close(); 
        statusTxt.innerText = "Erreur.";
    };
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
