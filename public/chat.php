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
$upload_msg = $rag->handleUpload();

// History clearing
if (isset($_POST['action']) && $_POST['action'] === "clear") {
    Conversation::clearHistory();
    header("Location: chat.php");
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<header style="margin-bottom: 50px; text-align: center;">
    <h1 style="display:inline-flex; align-items:center; gap:15px; font-weight: 300; letter-spacing: 2px;">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary); opacity: 0.8;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        AI WORKSPACE <span style="font-size: 0.5rem; background: var(--primary); color:white; padding: 2px 8px; border-radius: 20px; vertical-align: middle; margin-left: 10px;"><?= strtoupper($activeProvider) ?></span>
    </h1>
    <p class="subtitle" style="opacity: 0.5;">Dialogue souverain et orchestration cognitive <?= $activeProvider !== 'ollama' ? 'boosté par le Cloud' : '100% Locale' ?>.</p>
</header>

<div class="main-chat-area">
    <!-- Zone de Pensée (Agent Thought Process) -->
    <div id="thoughtContainer" style="display:none; margin-bottom: 25px;">
        <div id="thoughtLog"></div>
    </div>

    <div class="response-box" style="border:none; background:none; padding:0; margin-bottom: 50px;">
        <div class="response-content" id="iaResponse" style="line-height: 1.8; font-size: 1.1rem; color: var(--text); padding: 0 40px;">Bonjour ! Posez votre question pour commencer l'analyse.</div>
    </div>

    <form class="chat-form" id="questionForm">
        <input type="text" id="qInput" placeholder="Posez votre question à l'IA..." required minlength="5" autocomplete="off">
        <button type="submit" id="submitBtn">
            <div class="loader" id="loader"></div>
            <span>Envoyer</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
    </form>

    <div style="display:flex; justify-content: space-between; align-items: center; margin-top: 25px; padding: 0 10px;">
        <div style="display:flex; gap: 20px;">
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="knowledge_file" id="kFile" style="display:none;" onchange="this.form.submit()">
                <label for="kFile" style="cursor:pointer; font-size: 0.75rem; color: var(--text-dim); display:flex; align-items:center; gap:8px; opacity:0.7; transition:0.3s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Contextualiser (.txt)
                </label>
                <?php if (!empty($upload_msg)): ?><span style="font-size: 0.7rem; color: #10b981; margin-left:10px;"><?= $upload_msg ?></span><?php endif; ?>
            </form>
        </div>
        
        <form method="post">
            <input type="hidden" name="action" value="clear">
            <button type="submit" style="background:none; border:none; color: #f87171; font-size: 0.75rem; cursor:pointer; padding:0; text-transform:none; letter-spacing:0;" onclick="return confirm('Supprimer définitivement l\'historique ?')">Réinitialiser la session</button>
        </form>
    </div>
</div>

<!-- Section Mémoire & Historique (Grid) -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 40px;">
    <div class="card-glass" style="padding: 25px; background: var(--glass); border: 1px solid var(--border); border-radius: 20px;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin:0; font-size: 0.9rem; letter-spacing: 0.05em; color: var(--primary);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px; vertical-align:middle;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                MÉMOIRE VIVE
            </h3>
            <button onclick="toggleMemory()" style="background:none; padding:5px; border-radius:8px; opacity:0.5;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.78 1.35a2 2 0 0 0 .73 2.73l.15.08a2 2 0 0 1 1 1.73v.56a2 2 0 0 1-1 1.73l-.15.08a2 2 0 0 0-.73 2.73l.78 1.35a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 1-1.73v.18a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.78-1.35a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.73v-.56a2 2 0 0 1 1-1.73l.15-.08a2 2 0 0 0 .73-2.73l-.78-1.35a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </button>
        </div>
        <div id="memoryList" style="font-size: 0.85rem; color: var(--text-dim);">
            <?php if (empty($memory->getFacts())): ?>
                <p style="font-style: italic; opacity:0.5;">Aucun souvenir persistant détecté.</p>
            <?php else: ?>
                <ul style="margin: 0; padding-left: 18px; line-height:1.7;">
                    <?php foreach ($memory->getFacts() as $idx => $fact): ?>
                        <li><?= htmlspecialchars($fact) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div id="memoryControls" style="display:none; margin-top:15px; animation: fadeIn 0.3s;">
            <div style="display:flex; gap:10px;">
                <input type="text" id="newFact" placeholder="Ajouter un fait..." style="background:rgba(0,0,0,0.2); border-radius:10px; font-size:0.8rem;">
                <button onclick="addFact()" style="padding:10px 15px;">OK</button>
            </div>
        </div>
    </div>

    <div class="card-glass" style="padding: 25px; background: var(--glass); border: 1px solid var(--border); border-radius: 20px;">
        <h3 style="margin:0 0 20px 0; font-size: 0.9rem; letter-spacing: 0.05em; color: var(--text);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px; vertical-align:middle;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            HISTORIQUE
        </h3>
        <div id="historyList" style="font-size: 0.85rem; color: var(--text-dim);">
            <?php 
            $history = array_slice(Conversation::getHistory(), -2);
            if (empty($history)): ?>
                <p style="font-style: italic; opacity:0.5;">Début de la transmission...</p>
            <?php else: ?>
                <?php foreach (array_reverse($history) as $item): ?>
                    <div style="margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                        <div style="font-weight:700; color:var(--text); font-size:0.8rem; margin-bottom:3px;">USER: <?= htmlspecialchars($item['q']) ?></div>
                        <div style="opacity:0.8; font-size:0.8rem;"><?= mb_strimwidth(htmlspecialchars($item['a']), 0, 80, "...") ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const q = input.value.trim();
        if (!q || q.length < 5) return;

        input.value = '';
        responseBox.innerText = "";
        loader.style.display = "block";
        btn.disabled = true;

        try {
            // IA Hybride : Récupération de la clé depuis le localStorage
            const activeProvider = "<?= $activeProvider ?>";
            let apiKey = "";
            if (activeProvider !== 'ollama') {
                apiKey = localStorage.getItem('api_key_' + activeProvider) || "";
            }

            const response = await fetch(`stream.php?q=${encodeURIComponent(q)}&key=${encodeURIComponent(apiKey)}`);
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let fullText = "";
            let currentEvent = "";

            document.getElementById('thoughtContainer').style.display = 'block';
            document.getElementById('thoughtLog').innerHTML = "";

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                const chunk = decoder.decode(value);
                const lines = chunk.split("\n");
                
                for (const line of lines) {
                    if (line.startsWith("event: ")) {
                        currentEvent = line.slice(7).trim();
                        continue;
                    }
                    
                    if (line.startsWith("data: ")) {
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
                                step.className = "thought-step";
                                step.innerHTML = `⚡ ${json.content}`;
                                log.appendChild(step);
                                log.scrollTop = log.scrollHeight;
                            } else {
                                const content = json.message?.content || "";
                                fullText += content;
                                responseBox.innerText = fullText;
                            }
                        } catch (e) {
                            // Parfois le streaming cloud envoie du texte brut
                            fullText += dataRaw;
                            responseBox.innerText = fullText;
                        }
                        currentEvent = "";
                    }
                }
            }
        } catch (e) {
            responseBox.innerText = "Erreur de transmission : " + e.message;
        } finally {
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

<?php include __DIR__ . '/includes/footer.php'; ?>
