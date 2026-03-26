<?php
// public/chat.php - DI Refactor
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\App\Conversation;

$config = Container::get('config');
$rag    = Container::get('rag');
$memory = Container::get('memory');
$ollama = Container::get('ollama');

$app_name = $config->getAppName();
$upload_msg = $rag->handleUpload();

// History clearing
if (isset($_POST['action']) && $_POST['action'] === "clear") {
    Conversation::clearHistory();
    header("Location: chat.php");
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<header>
    <h1><?= htmlspecialchars($app_name) ?></h1>
    <p class="subtitle">Assistant IA local • Streaming SSE • RAG Dynamique</p>
</header>

<div class="response-box">
    <strong>Réponse de l'IA</strong>
    <div class="response-content" id="iaResponse">Bonjour ! Comment puis-je vous aider ?</div>
</div>

<form class="chat-form" id="questionForm">
    <input type="text" id="qInput" placeholder="Posez une question..." required minlength="5">
    <button type="submit" id="submitBtn">
        <span style="display:flex; align-items:center;"><div class="loader" id="loader"></div><span>Envoyer</span></span>
    </button>
</form>

<div style="display:flex; justify-content: space-between; align-items: center; margin-top: 20px;">
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="knowledge_file" id="kFile" style="display:none;" onchange="this.form.submit()">
        <label for="kFile" style="cursor:pointer; font-size: 0.8rem; color: var(--text-dim);">📁 Ajouter un .txt</label>
        <?php if (!empty($upload_msg)): ?><span style="font-size: 0.75rem; color: #10b981; margin-left:10px;"><?= $upload_msg ?></span><?php endif; ?>
    </form>
    <form method="post"><input type="hidden" name="action" value="clear"><button type="submit" class="btn-clear" onclick="return confirm('Vraiment tout effacer ?')">Vider l'histoire</button></form>
</div>

<!-- Mode AI Toggle -->
<div class="source-toggle" style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
    <div style="flex: 1; display: flex; background: var(--glass); border: 1px solid var(--border); border-radius: 12px; padding: 5px;">
        <button id="modeServer" onclick="setAIMode('server')" style="flex: 1; padding: 8px; border-radius: 8px; font-size: 0.8rem; background: var(--primary); border: none; color: white;">🚀 Serveur Docker</button>
        <button id="modeBrowser" onclick="setAIMode('browser')" style="flex: 1; padding: 8px; border-radius: 8px; font-size: 0.8rem; background: none; border: none; color: var(--text-dim);">🌐 Navigateur (WebGPU)</button>
    </div>
</div>

<div id="webllmProgressContainer" style="display:none; margin-bottom: 20px; padding: 15px; background: rgba(0, 255, 127, 0.05); border-radius: 12px; border: 1px dashed #00ff7f;">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <strong style="font-size: 0.75rem; color: #00ff7f;">📥 CHARGEMENT DU MODÈLE (CACHÉ)</strong>
        <span id="loadPercent" style="font-size: 0.75rem; color: #00ff7f;">0%</span>
    </div>
    <div style="height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
        <div id="loadProgressBar" style="height: 100%; width: 0%; background: #00ff7f; transition: width 0.3s;"></div>
    </div>
    <p id="loadStatus" style="font-size: 0.7rem; color: var(--text-dim); margin-top: 8px; font-style: italic;">Initialisation de WebGPU...</p>
</div>

<!-- Section Mémoire -->
<div class="memory-section" style="margin-top: 20px; padding: 15px; background: rgba(99, 102, 241, 0.05); border-radius: 12px; border: 1px dashed var(--primary);">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <strong style="font-size: 0.8rem; color: var(--primary);">🧠 MÉMOIRE PERSISTANTE</strong>
        <button onclick="toggleMemory()" style="background:none; border:none; color:var(--text-dim); font-size:1.2rem; cursor:pointer;">⚙️</button>
    </div>
    <div id="memoryList" style="font-size: 0.85rem; color: var(--text-dim); line-height: 1.4;">
        <?php if (empty($memory->getFacts())): ?>
            <p style="font-style: italic; margin: 0;">L'IA n'a pas encore de souvenirs persistants.</p>
        <?php else: ?>
            <ul style="margin: 0; padding-left: 15px;">
                <?php foreach ($memory->getFacts() as $idx => $fact): ?>
                    <li><?= htmlspecialchars($fact) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div id="memoryControls" style="display:none; margin-top:10px;">
        <input type="text" id="newFact" placeholder="Ajouter un fait important..." style="width:70%; border-radius:8px;">
        <button onclick="addFact()">Ajouter</button>
        <button onclick="clearMemory()" class="btn-clear" style="margin-top:5px; width:100%;">Réinitialiser</button>
    </div>
</div>

<div class="history-section" id="historyList">
    <?php 
    $history = array_slice(Conversation::getHistory(), -3);
    foreach (array_reverse($history) as $item): ?>
        <div class="msg">
            <div style="font-weight:600; font-size:0.9rem;">Q: <?= htmlspecialchars($item['q']) ?></div>
            <div style="color:var(--text-dim); font-size:0.9rem; margin-top:5px;"><?= nl2br(htmlspecialchars($item['a'])) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<script type="module">
    import { WebLLMEngine } from './js/webllm-engine.js';
    
    const webLlm = new WebLLMEngine();
    let currentMode = 'server';
    let isWebLlmReady = false;

    const form = document.getElementById('questionForm');
    const input = document.getElementById('qInput');
    const responseBox = document.getElementById('iaResponse');
    const loader = document.getElementById('loader');
    const btn = document.getElementById('submitBtn');

    window.setAIMode = async function(mode) {
        if (mode === 'browser') {
            const hasWebGpu = await webLlm.checkWebGPU();
            if (!hasWebGpu) {
                alert("Votre navigateur ne supporte pas WebGPU.");
                return;
            }
            document.getElementById('modeBrowser').style.background = 'var(--primary)';
            document.getElementById('modeBrowser').style.color = 'white';
            document.getElementById('modeServer').style.background = 'none';
            document.getElementById('modeServer').style.color = 'var(--text-dim)';
            currentMode = 'browser';
            
            if (!isWebLlmReady) {
                document.getElementById('webllmProgressContainer').style.display = 'block';
                await webLlm.init((report) => {
                    const percent = Math.round(report.progress * 100);
                    document.getElementById('loadPercent').innerText = percent + '%';
                    document.getElementById('loadProgressBar').style.width = percent + '%';
                    document.getElementById('loadStatus').innerText = report.text;
                });
                isWebLlmReady = true;
                setTimeout(() => document.getElementById('webllmProgressContainer').style.opacity = '0.5', 2000);
            }
        } else {
            document.getElementById('modeServer').style.background = 'var(--primary)';
            document.getElementById('modeServer').style.color = 'white';
            document.getElementById('modeBrowser').style.background = 'none';
            document.getElementById('modeBrowser').style.color = 'var(--text-dim)';
            currentMode = 'server';
        }
    }

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

    window.clearMemory = async () => {
        if (!confirm("Effacer tout ?")) return;
        await fetch('memory.php?action=clear');
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

        if (currentMode === 'browser') {
            const messages = [
                { role: "system", content: "<?= addslashes($ollama->getSystemPrompt() . $memory->getContextString()) ?>" },
                { role: "user", content: q }
            ];
            let responseText = "";
            try {
                await webLlm.generate(messages, (chunk) => {
                    responseText += chunk;
                    responseBox.innerText = responseText;
                }, (final) => {
                    finish(q, final);
                });
            } catch (e) {
                responseBox.innerText = "Erreur BrowserMode: " + e.message;
            }
            loader.style.display = "none";
            btn.disabled = false;
        } else {
            try {
                const response = await fetch(`stream.php?q=${encodeURIComponent(q)}`);
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let fullText = "";

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    const chunk = decoder.decode(value);
                    const lines = chunk.split("\n");
                    for (const line of lines) {
                        if (line.startsWith("data: ")) {
                            const data = line.slice(6).trim();
                            if (data === "[DONE]") { finish(q, fullText); break; }
                            fullText += data;
                            responseBox.innerText = fullText;
                        }
                    }
                }
            } catch (e) {
                responseBox.innerText = "Erreur serveur.";
            } finally {
                loader.style.display = "none";
                btn.disabled = false;
            }
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
