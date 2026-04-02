<?php
// public/chat.php - Cinematic v3 Overhaul (Hybrid Edition)
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\App\Conversation;

$config = Container::get('config');
$rag    = Container::get('rag');
$memory = Container::get('memory');
$ollama = Container::get('ollama');

$app_name = $config->getAppName();
$activeProvider = $config->get('active_provider', 'ollama');
// $upload_msg est désormais géré en AJAX pour l'Expert Edition

// History clearing
if (isset($_POST['action'])) {
    $action = \EasyLocalAI\Core\Security::sanitize($_POST['action']);
    if ($action === "clear") {
        if (!isset($_POST['csrf_token']) || !\EasyLocalAI\Core\Security::checkCsrf($_POST['csrf_token'])) {
            die("Erreur de sécurité : Jeton CSRF invalide (Action: Clear History)");
        }
        Conversation::clearHistory();
        header("Location: chat.php");
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<header style="margin-bottom: 50px; text-align: center;">
    <h1 style="display:inline-flex; flex-direction:column; align-items:center; gap:5px; font-weight: 800; letter-spacing: -1px; text-transform: uppercase;">
        <div style="display:flex; align-items:center; gap:15px;">
            <svg width="45" height="45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary); filter: drop-shadow(0 0 10px var(--primary-glow));"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            EASY LOCAL AI
        </div>
        <span style="font-size: 0.9rem; font-weight: 300; letter-spacing: 4px; color: var(--text-dim); opacity: 0.8; margin-top: -5px;">WORKSPACE</span>
    </h1>
    <div style="margin-top: 15px;">
        <span style="font-size: 0.55rem; background: var(--primary); color:white; padding: 3px 10px; border-radius: 20px; vertical-align: middle;"><?= strtoupper($activeProvider) ?> MODE</span>
        <span style="font-size: 0.55rem; background: rgba(255,255,255,0.05); color:var(--text-dim); padding: 3px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); margin-left: 5px;"><?= $config->get('model_name', 'llama3.2') ?></span>
    </div>
    <p class="subtitle" style="opacity: 0.6; font-size: 1rem; margin-top: 20px;">Le système souverain à pleine puissance, augmenté par le RAG et votre infrastructure locale.</p>

    <!-- Expert Hub (Nexus Integration) -->
    <div class="expert-hub fadeIn" style="margin-top: 30px; max-width: 900px; margin-left: auto; margin-right: auto;">
        <!-- Division Tabs -->
        <div class="division-tabs" id="divisionTabs" style="display:flex; justify-content:center; gap:10px; margin-bottom:20px; overflow-x:auto; padding-bottom:10px;">
            <button class="division-tab active" onclick="loadDivision('engineering')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg> Engineering</button>
            <button class="division-tab" onclick="loadDivision('design')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg> Design</button>
            <button class="division-tab" onclick="loadDivision('testing')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Testing</button>
            <div class="division-tab disabled" style="opacity:0.3; cursor:not-allowed;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg> Global</div>
        </div>

        <!-- Expert Grid -->
        <div class="expert-grid" id="expertGrid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:15px; perspective: 1000px;">
            <!-- Les cartes d'experts chargés dynamiquement via loadDivision() -->
            <div class="expert-card active" onclick="window.selectExpertCard(this, null, 'NONE')">
                <div class="expert-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                <div class="expert-card-content">
                    <div class="expert-card-name">Standard</div>
                    <div class="expert-card-desc">Assistant généraliste réactif.</div>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="main-chat-area">
    <!-- Zone de Pensée (Agent Thought Process) - Cinematic Timeline -->
    <div id="thoughtContainer" style="display:none; margin-bottom: 40px; padding: 0 40px;">
        <div style="font-size: 0.65rem; color: var(--primary); font-weight: 700; letter-spacing: 0.2em; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
            <div class="pulsing-brain" style="width: 12px; height: 12px; background: var(--primary); border-radius: 50%; box-shadow: 0 0 15px var(--primary);"></div>
            COGNITION LOCALE - ANALYSE EN COURS
        </div>
        <div id="thoughtLog" class="thought-timeline" style="border-left: 1px dashed rgba(255,255,255,0.1); margin-left: 6px; padding-left: 20px; display: flex; flex-direction: column; gap: 15px;">
            <!-- Les étapes de pensée apparaissent ici dynamiquement -->
        </div>
    </div>

    <div class="response-box" style="border:none; background:none; padding:0; margin-bottom: 50px;">
        <div id="statusLabel" style="display:none; color:var(--primary); font-size: 0.8rem; font-weight: 700; margin-bottom: 10px; margin-left: 40px; text-transform: uppercase; letter-spacing: 0.1em; animation: pulse-glow 2s infinite;">
            <span class="dot" style="display:inline-block; margin-right:8px;"></span>
            Analyse et génération en cours...
        </div>
        <div class="response-content" id="iaResponse" style="line-height: 1.8; font-size: 1.1rem; color: var(--text); padding: 0 40px;">Bonjour ! Posez votre question pour commencer l'analyse.</div>
    </div>

    <form class="chat-form" id="questionForm">
        <div class="chat-input-wrapper" style="flex:1;">
            <div id="imagePreview" class="image-preview-container"></div>
            <div style="display:flex; align-items:center; background: rgba(255,255,255,0.05); border-radius: 15px; border: 1px solid rgba(255,255,255,0.1); padding: 5px 15px;">
                <input type="file" id="imageInput" accept="image/*" multiple style="display:none;">
                <button type="button" class="vision-btn" id="visionBtn" title="Ajouter une image">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                </button>
                <input type="text" id="qInput" placeholder="Posez votre question ou analysez une image..." required minlength="5" autocomplete="off" style="border:none; background:none; flex:1; margin-bottom:0; box-shadow:none;">
            </div>
        </div>
        <button type="submit" id="submitBtn" style="height: 50px;">
            <div class="loader" id="loader" style="display:none; width:16px; height:16px; border-radius:50%; margin-right:10px;"></div>
            <span id="btnText">Envoyer</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
    </form>

    <div style="display:flex; justify-content: space-between; align-items: center; margin-top: 25px; padding: 0 10px;">
        <div style="display:flex; gap: 20px;">
            <div id="uploadStatus" style="display:none; font-size: 0.7rem; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 5px 12px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2); align-items: center; gap: 8px;">
                <span id="uploadMsg">Document indexé !</span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; color:inherit; cursor:pointer; padding:0; font-size:1rem; line-height:1;">&times;</button>
            </div>

            <form id="uploadForm" enctype="multipart/form-data" style="display: flex; align-items: center;">
                <input type="hidden" name="csrf_token" id="upload_csrf" value="<?= \EasyLocalAI\Core\Security::getCsrfToken() ?>">
                <input type="file" name="knowledge_file" id="kFile" style="display:none;" onchange="uploadFile()">
                <label for="kFile" id="uploadLabel" style="cursor:pointer; font-size: 0.75rem; color: var(--text-dim); display:flex; align-items:center; gap:8px; opacity:0.7; transition:0.3s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">
                    <svg id="uploadIcon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span id="uploadText">Contextualiser (.pdf, .txt, .md)</span>
                </label>
            </form>
        </div>
        
        <form method="post">
            <input type="hidden" name="action" value="clear">
            <button type="submit" style="background:none; border:none; color: #f87171; font-size: 0.75rem; cursor:pointer; padding:0; text-transform:none; letter-spacing:0;" onclick="return confirm('Supprimer définitivement l\'historique ?')">Réinitialiser la session</button>
        </form>
    </div>
</div>

    </div>
</div>

<script type="module">
    const form = document.getElementById('questionForm');
    const input = document.getElementById('qInput');
    const responseBox = document.getElementById('iaResponse');
    const loader = document.getElementById('loader');
    const btn = document.getElementById('submitBtn');

    window.toggleMemory = () => {
        const ctrl = document.getElementById('memoryControls');
        ctrl.style.display = ctrl.style.display === 'none' ? 'block' : 'none';
    };

    window.addFact = async () => {
        const factInput = document.getElementById('newFact');
        const fact = factInput.value.trim();
        if (!fact) return;
        await fetch('memory.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=add&fact=${encodeURIComponent(fact)}`
        });
        location.reload();
    };

    window.uploadFile = async () => {
        const fileInput = document.getElementById('kFile');
        const label = document.getElementById('uploadLabel');
        const icon = document.getElementById('uploadIcon');
        const text = document.getElementById('uploadText');
        const status = document.getElementById('uploadStatus');
        const msg = document.getElementById('uploadMsg');
        const csrf = document.getElementById('upload_csrf').value;

        if (!fileInput.files.length) return;

        const formData = new FormData();
        formData.append('knowledge_file', fileInput.files[0]);
        formData.append('csrf_token', csrf);

        // UI Feedback
        text.innerText = "Indexation...";
        label.style.color = "var(--primary)";
        icon.style.animation = "pulse-glow 1s infinite";

        try {
            const response = await fetch('api_rag.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                status.style.display = "flex";
                msg.innerText = `Prêt ! ${data.count} segments indexés (${data.filename})`;
                text.innerText = "Ajouter un autre document";
            } else {
                alert("Erreur : " + data.message);
                text.innerText = "Réessayer";
            }
        } catch (e) {
            alert("Erreur réseau lors de l'indexation.");
            text.innerText = "Erreur";
        } finally {
            icon.style.animation = "none";
            label.style.color = "";
        }
    };

    // --- Gestion de la Vision ---
    let currentExpert = null;
    let currentImages = [];

    // --- Expert Hub Logic (Nexus) ---
    window.loadDivision = async function(division) {
        // UI Tabs update
        document.querySelectorAll('.division-tab').forEach(t => t.classList.remove('active'));
        if (event) event.currentTarget.classList.add('active');

        const grid = document.getElementById('expertGrid');
        grid.innerHTML = `<div style="grid-column:1/-1; text-align:center; padding:20px; opacity:0.5;">Initialisation de la division ${division}...</div>`;

        try {
            const resp = await fetch(`get_experts.php?division=${division}`);
            const data = await resp.json();

            grid.innerHTML = ""; // Clear loader
            
            // Re-ajouter l'option "Standard"
            const standardCard = document.createElement('div');
            standardCard.className = `expert-card ${!currentExpert ? 'active' : ''}`;
            standardCard.onclick = (e) => window.selectExpertCard(standardCard, null, 'NONE');
            standardCard.innerHTML = `
                <div class="expert-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                <div class="expert-card-content">
                    <div class="expert-card-name">Standard</div>
                    <div class="expert-card-desc">Assistant généraliste réactif.</div>
                </div>
            `;
            grid.appendChild(standardCard);

            if (data.experts) {
                data.experts.forEach(exp => {
                    const card = document.createElement('div');
                    const isSelected = currentExpert === `${division}:${exp.key}`;
                    card.className = `expert-card ${isSelected ? 'active' : ''}`;
                    card.onclick = (e) => window.selectExpertCard(card, exp.key, data.division);
                    card.innerHTML = `
                        <div class="expert-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="M8 12h8"/></svg></div>
                        <div class="expert-card-content">
                            <div class="expert-card-name">${exp.name}</div>
                            <div class="expert-card-desc">${exp.description}</div>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }
        } catch (e) {
            grid.innerHTML = `<div style="grid-column:1/-1; color:red; padding:20px;">Erreur de chargement des experts.</div>`;
        }
    }

    window.selectExpertCard = function(el, key, division) {
        document.querySelectorAll('.expert-card').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        
        if (!key) {
            currentExpert = null;
        } else {
            currentExpert = `${division.toLowerCase()}:${key}`;
        }
        console.log("Nexus Target Set:", currentExpert || "Standard");
    }

    // Init first division
    document.addEventListener('DOMContentLoaded', () => {
        // loadDivision('engineering'); // Optionnel, attendons l'interaction
    });

    const visionBtn = document.getElementById('visionBtn');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');

    visionBtn.addEventListener('click', () => imageInput.click());

    imageInput.addEventListener('change', async (e) => {
        const files = Array.from(e.target.files);
        for (const file of files) {
            if (currentImages.length >= 5) break; // Limite à 5 images
            const base64 = await toBase64(file);
            currentImages.push(base64);
            renderPreviews();
        }
        imageInput.value = '';
    });

    function toBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }

    function renderPreviews() {
        imagePreview.innerHTML = '';
        currentImages.forEach((img, index) => {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${img}">
                <button class="remove-preview" onclick="removeImage(${index})">&times;</button>
            `;
            imagePreview.appendChild(div);
        });
    }

    window.removeImage = (index) => {
        currentImages.splice(index, 1);
        renderPreviews();
    };

    // --- Gestion du Chat (POST Stream) ---
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const q = input.value.trim();
        if (!q || q.length < 3) return;

        const imagesBackup = [...currentImages];
        input.value = '';
        currentImages = [];
        renderPreviews();

        responseBox.innerText = "";
        document.getElementById('statusLabel').style.display = "block";
        document.getElementById('btnText').innerText = "Génération...";
        loader.style.display = "block";
        btn.disabled = true;

        try {
            const activeProvider = "<?= $activeProvider ?>";
            let apiKey = "";
            if (activeProvider !== 'ollama') {
                apiKey = localStorage.getItem('api_key_' + activeProvider) || "";
            }

            const response = await fetch('stream.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    q: q, 
                    key: apiKey, 
                    images: imagesBackup,
                    expert: currentExpert,
                    csrf_token: "<?= \EasyLocalAI\Core\Security::getCsrfToken() ?>"
                })
            });

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let fullText = "";
            let currentEvent = "";
            let buffer = "";

            document.getElementById('thoughtContainer').style.display = 'block';
            document.getElementById('thoughtLog').innerHTML = "";

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                
                buffer += decoder.decode(value, { stream: true });
                const parts = buffer.split("\n\n");
                buffer = parts.pop(); // On garde le dernier morceau incomplet

                for (const part of parts) {
                    const lines = part.split("\n");
                    for (const line of lines) {
                        if (line.startsWith("event: ")) {
                            currentEvent = line.slice(7).trim();
                        } else if (line.startsWith("data: ")) {
                            const dataRaw = line.slice(6).trim();
                            if (dataRaw === "[DONE]") { 
                                finish(q, fullText); 
                                break; 
                            }
                            
                            try {
                                const json = JSON.parse(dataRaw);
                                if (currentEvent === "thought") {
                                    const log = document.getElementById('thoughtLog');
                                    const step = document.createElement('div');
                                    step.className = "thought-v4 fadeIn";
                                    
                                    // Icônes Kinetic
                                    let iconPath = "<circle cx='12' cy='12' r='10'></circle><line x1='12' y1='8' x2='12' y2='12'></line><line x1='12' y1='16' x2='12.01' y2='16'></line>"; 
                                    let stateClass = "icon-thought";
                                    let label = "ANALYSE";

                                    if (json.content.startsWith("ACTION")) { 
                                        iconPath = "<path d='M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z'></path>";
                                        label = "OUTIL";
                                        stateClass = "icon-action";
                                    } else if (json.content.startsWith("OBSERVATION")) { 
                                        iconPath = "<path d='M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z'></path><circle cx='12' cy='12' r='3'></circle>";
                                        stateClass = "icon-observation";
                                        label = "RÉSULTAT";
                                    } else if (json.content.startsWith("RÉFLEXION")) { 
                                        iconPath = "<path d='M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 4.44-2.54Z'></path><path d='M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-4.44-2.54Z'></path>";
                                        stateClass = "icon-thought";
                                        label = "CORTEX";
                                    }

                                    // Construction sécurisée du DOM pour éviter XSS
                                    const iconWrapper = document.createElement('div');
                                    iconWrapper.className = `kinetic-icon-wrapper ${stateClass} ${label === 'CORTEX' ? 'spin-kinetic' : ''}`;
                                    iconWrapper.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${iconPath}</svg>`;

                                    const contentWrapper = document.createElement('div');
                                    contentWrapper.style.flex = "1";
                                    
                                    const labelDiv = document.createElement('div');
                                    labelDiv.style.fontSize = "0.6rem";
                                    labelDiv.style.opacity = "0.4";
                                    labelDiv.style.fontWeight = "700";
                                    labelDiv.style.marginBottom = "2px";
                                    labelDiv.style.letterSpacing = "1px";
                                    labelDiv.textContent = label;

                                    const textDiv = document.createElement('div');
                                    textDiv.style.fontSize = "0.85rem";
                                    textDiv.textContent = json.content; // SÉCURISÉ : textContent neutralise le HTML

                                    contentWrapper.appendChild(labelDiv);
                                    contentWrapper.appendChild(textDiv);
                                    
                                    step.appendChild(iconWrapper);
                                    step.appendChild(contentWrapper);
                                    
                                    log.appendChild(step);
                                    log.parentElement.scrollTop = log.parentElement.scrollHeight;
                                } else {
                                    const content = json.message?.content || "";
                                    fullText += content;
                                    responseBox.innerText = fullText;
                                }
                            } catch (e) {
                                // Fallback pour texte brut
                                if (line.startsWith("data: ")) {
                                    fullText += line.slice(6);
                                    responseBox.innerText = fullText;
                                }
                            }
                        }
                    }
                    currentEvent = "";
                }
            }
        } catch (e) {
            responseBox.innerText = "Erreur de transmission : " + e.message;
        } finally {
            document.getElementById('statusLabel').style.display = "none";
            document.getElementById('btnText').innerText = "Envoyer";
            loader.style.display = "none";
            btn.disabled = false;
        }
    });

    function finish(q, a) {
        fetch('save_history.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `q=${encodeURIComponent(q)}&a=${encodeURIComponent(a)}`
        });
    }
</script>


<!-- Arrière-plan Kinetic 3D (Subtil) -->
<div id="canvas-container" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1; opacity:0.3; pointer-events:none;"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="kinetic-bg.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
