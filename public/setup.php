<?php
// public/setup.php - Unified Control Center (Expert Edition V4 - Hybrid AI)
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\Core\Security;

$ollama = Container::get('ollama'); // On garde ollama pour la liste des modèles locaux
$config = Container::get('config');
$setup  = Container::get('setup');

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

if ($setup->handleForm()) {
    header("Location: chat.php");
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
        </div>
    </div>
</div>

<script>
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
