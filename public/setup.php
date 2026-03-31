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
    header("Location: setup.php?tab=engines&updated=1");
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
    
    header("Location: setup.php?tab=engines&updated_provider=1");
    exit;
}

if (isset($_GET['delete_model'])) {
    $modelToDelete = Security::sanitize($_GET['delete_model']);
    $currentModel = $config->get('model_name', 'llama3.2');
    if ($modelToDelete !== $currentModel && $modelToDelete !== $currentModel.":latest") {
        $ollama->deleteModel($modelToDelete);
        header("Location: setup.php?tab=engines&deleted=" . urlencode($modelToDelete));
        exit;
    }
}

// Action : Réinitialiser la base vectorielle
if (isset($_POST['action']) && $_POST['action'] === 'clear_vectors') {
    Container::get('vectorStore')->clear();
    header("Location: setup.php?tab=memory&vectors_cleared=1");
    exit;
}

// Action : Supprimer un document physique
if (isset($_GET['delete_doc'])) {
    $docName = basename($_GET['delete_doc']);
    $docPath = __DIR__ . '/../knowledge/' . $docName;
    if (file_exists($docPath)) unlink($docPath);
    header("Location: setup.php?tab=memory&doc_deleted=1");
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
    
    if ($success) {
        // Pilotage automatique de l'infrastructure
        try {
            $docker = Container::get('docker');
            $docker->restartInfrastructure();
        } catch (\Exception $e) {
            // Log error but continue
        }
    }
    
    header("Location: setup.php?tab=system&updated_infra=" . ($success ? "1" : "0"));
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

<?php
$currentTab = $_GET['tab'] ?? 'general';
?>

<div class="config-layout">
    <div class="config-main">
        <!-- --- 🚀 ONGLET GÉNÉRAL --- -->
        <?php if ($currentTab === 'general'): ?>
        <div id="tab-general" class="tab-content active">
            <div class="split-left">
                <form method="post" style="display: flex; flex-direction: column; gap: 30px;">
                    <input type="hidden" name="action" value="setup">
                    <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
                    
                    <div>
                        <h3 style="margin:0 0 20px 0; font-size: 1rem; color: var(--primary);">IDENTITÉ DE L'IA</h3>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-size: 0.75rem; color: var(--text-dim);">Dénomination publique</label>
                            <input type="text" name="app_name" value="<?= htmlspecialchars($app_name) ?>" style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; border: 1px solid var(--border);">
                        </div>
                    </div>

                    <div>
                        <h3 style="margin:0 0 20px 0; font-size: 1rem; color: var(--primary);">EXPERTISE NEURONALE (JSON)</h3>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-size: 0.75rem; color: var(--text-dim);">Identité structurée (Persona)</label>
                            <textarea name="persona_json" style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; height: 180px; font-size: 0.8rem; font-family: monospace; line-height:1.4; border: 1px solid var(--border);"><?= json_encode($config->get('persona'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></textarea>
                        </div>
                    </div>

                    <div style="padding-top: 20px; border-top: 1px solid var(--border);">
                        <h3 style="margin:0 0 20px 0; font-size: 1rem; color: var(--primary);">MÉMOIRE VIVE (Live Context)</h3>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="new_fact_input" placeholder="L'utilisateur s'appelle Alice et adore le café" style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; border: 1px solid var(--border); flex:1;">
                                <button type="button" onclick="addLiveFact()" style="background: var(--primary); color:white; padding:0 20px;">+ RETENIR</button>
                            </div>
                            
                            <div id="live_facts_list" style="display: flex; flex-direction: column; gap: 10px;">
                                <?php 
                                    $memory = Container::get('memory');
                                    foreach ($memory->getFacts() as $idx => $fact): 
                                ?>
                                    <div class="picker-item" style="padding:10px 15px; justify-content: space-between; background: rgba(255,255,255,0.02); border-radius: 10px;">
                                        <span style="font-size: 0.8rem; opacity:0.8;"><?= htmlspecialchars($fact) ?></span>
                                        <button type="button" onclick="deleteLiveFact(<?= $idx ?>)" style="background: none; border:none; color:#f87171; cursor:pointer;">&times;</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <button type="submit" style="width: 100%; justify-content: center; padding: 15px; margin-top:10px;">SAUVEGARDER L'EXPERTISE</button>
                </form>
            </div>
            <div class="split-right">
                <div class="guide-card">
                    <h4 style="margin:0 0 15px 0; font-size:0.8rem; color: var(--primary);">GUIDE D'IDENTITÉ</h4>
                    <div class="guide-step">
                        <div class="step-num">1</div>
                        <div><span class="action-verb">Nommez</span> votre instance. Ce nom apparaîtra sur l'interface et dans les en-têtes de conversation.</div>
                    </div>
                    <div class="guide-step">
                        <div class="step-num">2</div>
                        <div><span class="action-verb">Définissez</span> l'Agent. Utilisez le format JSON pour structurer ses règles, son rôle et son ton de manière rigide.</div>
                    </div>
                    <div class="guide-step">
                        <div class="step-num">3</div>
                        <div><span class="action-verb">Gérez</span> la mémoire. Ajoutez ou supprimez des faits persistants sur l'utilisateur pour personnaliser l'échange.</div>
                    </div>
                    <p style="font-size:0.7rem; opacity:0.5; margin-top:20px; line-height:1.4;">
                        💡 Une bonne expertise permet à l'Agent de mieux utiliser ses outils et de limiter les hallucinations.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- --- 🧠 ONGLET MOTEURS IA --- -->
        <?php if ($currentTab === 'engines'): ?>
        <div id="tab-engines" class="tab-content active">
            <div class="split-left">
                <!-- Switch Provider -->
                <form method="post" id="providerForm" onsubmit="saveApiKeyBeforeSubmit(event)" style="margin-bottom: 40px;">
                    <input type="hidden" name="action" value="set_provider">
                    <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
                    <input type="hidden" name="api_key" id="api_key_hidden">
                    
                    <h3 style="margin:0 0 20px 0; font-size: 1rem; color: var(--primary);">SOURCE DE PUISSANCE</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <select name="active_provider" id="active_provider" onchange="updateProviderUI()" style="background: rgba(0,0,0,0.3); border-radius: 12px; padding: 15px; border: 1px solid var(--border);">
                            <option value="ollama" <?= $activeProvider === 'ollama' ? 'selected' : '' ?>>Ollama (100% Local & Souverain)</option>
                            <option value="cortex" <?= $activeProvider === 'cortex' ? 'selected' : '' ?>>Cortex Gateway (Failover Intelligent)</option>
                            <option value="groq" <?= $activeProvider === 'groq' ? 'selected' : '' ?>>Groq (Ultra-Rapide - API)</option>
                            <option value="openai" <?= $activeProvider === 'openai' ? 'selected' : '' ?>>OpenAI (Expertise Max - API)</option>
                        </select>

                        <div id="api_key_section" style="display: <?= $activeProvider === 'ollama' ? 'none' : 'flex' ?>; flex-direction: column; gap: 8px;">
                            <label style="font-size: 0.7rem; color: var(--text-dim);">Clé API Secrète</label>
                            <input type="password" id="api_key_input" placeholder="sk-..." style="background: rgba(0,0,0,0.2); border-radius: 10px; padding: 12px; border: 1px solid var(--border);">
                        </div>

                        <button type="submit" style="background: var(--primary); color: white;">ACTIVER CE MOTEUR</button>
                    </div>
                </form>

                <!-- Local Library -->
                <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 20px; border: 1px solid var(--border);">
                    <h3 style="margin:0 0 20px 0; font-size: 0.9rem; color: var(--primary);">MODÈLES LOCAUX INSTALLÉS</h3>
                    <div id="modelList" style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($models as $m): ?>
                            <?php $isDefault = ($m['name'] === $currentModelName || $m['name'] === $currentModelName.":latest"); ?>
                            <div class="picker-item" style="border: 1px solid <?= ($isDefault && $activeProvider === 'ollama') ? 'var(--primary)' : 'rgba(255,255,255,0.05)' ?>; padding: 12px 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight:700; font-size:0.85rem;"><?= htmlspecialchars($m['name']) ?></div>
                                    <div style="font-size:0.6rem; opacity:0.5;"><?= isset($m['not_pulled']) ? 'Non installé' : (round($m['size'] / (1024*1024*1024), 2) . ' Go') ?></div>
                                </div>
                                <?php if ($isDefault && $activeProvider === 'ollama'): ?>
                                    <span style="font-size: 0.55rem; background: var(--primary); color: white; padding: 2px 7px; border-radius: 8px; font-weight:900;">ACTIF</span>
                                <?php elseif (!isset($m['not_pulled'])): ?>
                                    <a href="?tab=engines&set_default=<?= urlencode($m['name']) ?>" style="font-size:0.6rem; color:var(--primary); text-decoration:none; font-weight:800;">ACTIVER</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:20px; display:flex; gap:10px;">
                        <button type="button" onclick="openNativeDiscovery()" style="flex:1; background:rgba(124, 58, 237, 0.1); border:1px solid var(--primary); color:var(--primary-light); font-size:0.75rem; padding: 12px; border-radius: 12px; cursor:pointer; border:1px solid var(--primary);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px; vertical-align:middle; opacity:0.8;"><path d="m12 14 4-4-4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg>
                            DÉTECTER MES MODÈLES PC (BOOST GPU)
                        </button>
                    </div>
                </div>
            </div>
            <div class="split-right">
                <div class="guide-card">
                    <h4 style="margin:0 0 15px 0; font-size:0.8rem; color: var(--primary);">GUIDE MOTEURS</h4>
                    <div class="guide-step">
                        <div class="step-num">1</div>
                        <div><span class="action-verb">Choisissez</span> votre mode. "Ollama" est 100% local, les autres nécessitent une connexion internet.</div>
                    </div>
                    <div class="guide-step">
                        <div class="step-num">2</div>
                        <div><span class="action-verb">Vérifiez</span> vos clés. Si vous utilisez Groq ou OpenAI, votre clé API reste stockée localement dans votre navigateur uniquement.</div>
                    </div>
                    <div class="guide-step">
                        <div class="step-num">3</div>
                        <div><span class="action-verb">Optimisez</span>. Pour plus de vitesse sur Windows, utilisez le bouton "DÉTECTER MES MODÈLES PC" pour activer l'accélération GPU native.</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- --- 📚 ONGLET MÉMOIRE RAG --- -->
        <?php if ($currentTab === 'memory'): ?>
        <div id="tab-memory" class="tab-content active">
            <div class="split-left">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
                    <h3 style="margin:0; font-size: 1rem; color: var(--primary);">DOCUMENTS INDEXÉS</h3>
                    <form method="post" onsubmit="return confirm('Réinitialiser tout l\'index ?')">
                        <input type="hidden" name="action" value="clear_vectors">
                        <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
                        <button type="submit" style="background:rgba(239, 68, 68, 0.1); color:#f87171; border:1px solid rgba(239, 68, 68, 0.2); font-size:0.6rem; padding:8px 15px; cursor:pointer; border-radius:8px;">VIDER L'INDEX (RESET)</button>
                    </form>
                </div>

                <div style="display:grid; grid-template-columns: 1fr; gap:12px; margin-bottom:30px;">
                    <?php 
                        $knowledgeDir = __DIR__ . '/../knowledge/';
                        $files = array_diff(scandir($knowledgeDir), array('..', '.', 'vectors.json', '.gitkeep'));
                        if (empty($files)):
                    ?>
                        <div style="text-align:center; padding:40px; border:2px dashed var(--border); border-radius:20px; opacity:0.5; font-size:0.8rem;">
                            Votre bibliothèque est vide. Téléchargez des fichiers via le Chat.
                        </div>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                            <div class="picker-item" style="padding:15px 20px; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02); border-radius:12px; border: 1px solid var(--border);">
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <span style="font-size:1.4rem;"><?= pathinfo($file, PATHINFO_EXTENSION) === 'pdf' ? '📕' : '📄' ?></span>
                                    <div>
                                        <div style="font-size:0.85rem; font-weight:700;"><?= htmlspecialchars($file) ?></div>
                                        <div style="font-size:0.6rem; color:var(--text-dim);"><?= strtoupper(pathinfo($file, PATHINFO_EXTENSION)) ?> - Segmenté & Vectorisé</div>
                                    </div>
                                </div>
                                <a href="?tab=memory&delete_doc=<?= urlencode($file) ?>" style="color:#f87171; text-decoration:none; font-size:1.2rem; font-weight:100; opacity:0.6;" onclick="return confirm('Supprimer ce document ?')">&times;</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="split-right">
                <div class="guide-card">
                    <h4 style="margin:0 0 15px 0; font-size:0.8rem; color: var(--primary);">CONNAISSANCES (RAG)</h4>
                    <div class="guide-step">
                        <div class="step-num">1</div>
                        <div><span class="action-verb">Importez</span> vos documents via l'interface de discussion (trombone). Ils sont automatiquement découpés en morceaux sémantiques.</div>
                    </div>
                    <div class="guide-step">
                        <div class="step-num">2</div>
                        <div><span class="action-verb">Lisez</span>. L'IA consultera ces documents en priorité pour répondre à vos questions, citant ses sources.</div>
                    </div>
                    <div class="guide-step">
                        <div class="step-num">3</div>
                        <div><span class="action-verb">Nettoyez</span>. Si la base devient incohérente, utilisez le bouton "RESET" pour forcer une réindexation propre.</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- --- ⚙️ ONGLET SYSTÈME --- -->
        <?php if ($currentTab === 'system'): ?>
        <div id="tab-system" class="tab-content active">
            <div class="split-left">
                <form method="post" style="display: flex; flex-direction: column; gap: 30px;">
                    <input type="hidden" name="action" value="set_infra">
                    <input type="hidden" name="csrf_token" value="<?= Security::getCsrfToken() ?>">
                    
                    <div>
                        <h3 style="margin:0 0 20px 0; font-size: 1rem; color: var(--primary);">INFRASTRUCTURE DE STOCKAGE</h3>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <label style="font-size: 0.75rem; color: var(--text-dim);">Chemin Racine Ollama (Hôte)</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="ollama_models_path" id="ollama_models_path" value="<?= htmlspecialchars($env->get('OLLAMA_MODELS_PATH', 'F:/.ollama')) ?>" style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; border: 1px solid var(--border); flex: 1; font-family: monospace; font-size: 0.8rem;">
                                <button type="button" onclick="openFolderPicker()" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: white; min-width: 50px; justify-content: center;">📂</button>
                            </div>
                        </div>
                    </div>

                    <div style="padding: 20px; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px;">
                        <h4 style="margin:0 0 10px 0; font-size:0.75rem; color:#f59e0b;">PILOTAGE DOCKER</h4>
                        <p style="font-size:0.7rem; color:var(--text-dim); margin-bottom:15px; line-height:1.4;">
                            Le redémarrage est nécessaire pour appliquer les nouveaux chemins de stockage. 
                        </p>
                        <button type="submit" style="background:#3b82f6; color:white; width: 100%; justify-content: center;">APPLIQUER & ACTUALISER L'HÔTE</button>
                    </div>

                    <?php if (isset($_GET['updated_infra'])): ?>
                        <div style="font-size: 0.75rem; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 12px; text-align:center; border: 1px solid rgba(16, 185, 129, 0.2);">
                            ✅ Changements enregistrés. Redémarrage en cours...
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="split-right">
                <div class="guide-card">
                    <h4 style="margin:0 0 15px 0; font-size:0.8rem; color: var(--primary);">SYSTÈME & STOCKAGE</h4>
                    <div class="guide-step">
                        <div class="step-num">1</div>
                        <div><span class="action-verb">Spécifiez</span> où sont stockés vos To de modèles. Utilisez l'explorateur pour pointer vers votre disque SSD.</div>
                    </div>
                    <div class="guide-step">
                        <div class="step-num">2</div>
                        <div><span class="action-verb">Appliquez</span>. Le bouton bleu déclenche un signal Docker pour recharger vos containers avec les nouveaux réglages.</div>
                    </div>
                    <div class="guide-step">
                        <div class="step-num">3</div>
                        <div><span class="action-verb">Patientez</span>. Un redémarrage prend environ 5 à 10 secondes. La page s'actualisera d'elle-même.</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL FOLDER PICKER -->
<div id="folderPickerModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); backdrop-filter:blur(10px); z-index:9999; align-items:center; justify-content:center;">
    <div class="bento-item" style="width:90%; max-width:700px; height:80vh; display:flex; flex-direction:column; padding:0; overflow:hidden; border:1px solid var(--primary);">
        <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02);">
            <h3 style="margin:0; font-size:0.9rem; letter-spacing:1px; color:var(--primary);">EXPLORATEUR SYSTÈME</h3>
            <button onclick="closeFolderPicker()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer; opacity:0.5;">&times;</button>
        </div>

        <script>
        function addLiveFact() {
            const input = document.getElementById('new_fact_input');
            const fact = input.value.trim();
            if(!fact) return;
            
            fetch('memory.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=add&fact=${encodeURIComponent(fact)}`
            }).then(() => location.reload());
        }

        function deleteLiveFact(index) {
            if(!confirm('Oublier cette information ?')) return;
            fetch('memory.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete&index=${index}`
            }).then(() => location.reload());
        }
        </script>
        
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

<!-- MODAL NATIVE DISCOVERY -->
<div id="nativeDiscoveryModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); backdrop-filter:blur(10px); z-index:9999; align-items:center; justify-content:center;">
    <div class="bento-item" style="width:90%; max-width:500px; padding:0; overflow:hidden; border:1px solid #f59e0b; background: var(--bg); position:relative;">
        <div style="padding:20px; border-bottom:1px solid rgba(245, 158, 11, 0.2); display:flex; justify-content:space-between; align-items:center; background:rgba(245, 158, 11, 0.05);">
            <h3 style="margin:0; font-size:0.8rem; letter-spacing:1px; color:#f59e0b;">ACCÉLÉRATION GPU NATIVE</h3>
            <button onclick="closeNativeDiscovery()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer; opacity:0.5;">&times;</button>
        </div>
        <div style="padding:25px;">
            <p style="font-size:0.75rem; color:var(--text-dim); margin-bottom:20px; line-height:1.6;">
                Cette fonction détecte **Ollama pour Windows** tournant directement sur votre machine hôte. Cela permet d'utiliser votre carte graphique (NVIDIA/AMD) à 100% de sa puissance.
            </p>
            <div id="nativeModelsList" style="max-height:300px; overflow-y:auto; margin-bottom:25px;">
                <!-- Modèles détectés -->
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:20px; border-top:1px solid var(--border);">
                <span id="nativeStatus" style="font-size:0.65rem; opacity:0.5; color:#f59e0b;">En attente...</span>
                <button onclick="closeNativeDiscovery()" style="background:rgba(255,255,255,0.05); color:white; border:1px solid var(--border); padding:8px 20px;">FERMER</button>
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
