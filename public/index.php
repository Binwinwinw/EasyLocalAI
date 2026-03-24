<?php
// public/index.php

session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Config;
use EasyLocalAI\Core\Ollama;
use EasyLocalAI\App\Conversation;
use EasyLocalAI\Setup\SetupManager;
use EasyLocalAI\RAG\RAG;
use EasyLocalAI\App\Memory;

// Performance settings
set_time_limit(120);
ini_set('memory_limit', '256M');

// Core Components
$config = new Config();
$setup  = new SetupManager($config);
$rag    = new RAG();
$memory = new Memory();
$ollama = new Ollama($config, $memory->getContextString());

// Setup Mode Check
$is_setup_mode = $setup->isSetupRequired();

// Handle Setup Submission
if ($setup->handleForm()) {
    header("Location: index.php");
    exit;
}

// Global UI vars
$app_name = $config->getAppName();
$upload_msg = $rag->handleUpload();

// Handle Clear History
if (isset($_POST['action']) && $_POST['action'] === "clear") {
    Conversation::clearHistory();
    header("Location: index.php");
    exit;
}

// Main Request (Non-AJAX)
$q = $_POST['q'] ?? $_GET['q'] ?? "";
$reply = "Bonjour ! Comment puis-je vous aider ?";

if ($q && !isset($_GET['ajax'])) {
    $history = Conversation::getHistory();
    $rag_context = $rag->getContext($q);
    $prompt_with_rag = $rag_context ? $rag_context . "\n\n" . $q : $q;

    session_write_close();
    $reply = $ollama->ask($prompt_with_rag, $history);
    session_start();
    Conversation::addMessage($q, $reply);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --bg: #0f172a; --card-bg: rgba(30, 41, 59, 0.7); --text: #f8fafc; --text-dim: #94a3b8; --border: rgba(255, 255, 255, 0.1); --glass: rgba(255, 255, 255, 0.05); }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; min-height: 100vh; background-image: radial-gradient(circle at 50% -20%, #312e81 0%, transparent 50%); }
        .container { width: 100%; max-width: 900px; background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid var(--border); border-radius: 24px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        h1 { background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-align: center; margin: 0 0 10px; }
        .subtitle { text-align: center; color: var(--text-dim); margin-bottom: 30px; font-size: 0.9rem; }
        .response-box { background: var(--glass); border: 1px solid var(--border); border-radius: 16px; padding: 20px; min-height: 80px; margin-bottom: 20px; }
        .response-box strong { color: var(--primary); font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase; display: block; margin-bottom: 10px; }
        .response-content { line-height: 1.6; white-space: pre-wrap; }
        form.chat-form { display: flex; gap: 10px; }
        input[type="text"] { flex: 1; background: var(--glass); border: 1px solid var(--border); border-radius: 12px; padding: 12px 15px; color: white; font-size: 1rem; }
        button { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        button:hover { background: #4f46e5; transform: translateY(-1px); }
        select { background: var(--glass); border: 1px solid var(--border); border-radius: 12px; padding: 12px; color: white; font-size: 1rem; cursor: pointer; outline: none; transition: 0.3s; }
        select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2); }
        select option { background-color: #1e293b; color: white; padding: 10px; }
        .history-section { margin-top: 40px; border-top: 1px solid var(--border); padding-top: 20px; }
        .msg { background: var(--glass); padding: 15px; border-radius: 12px; margin-bottom: 10px; border-left: 4px solid var(--primary); }
        .btn-clear { background: transparent; border: 1px solid var(--border); color: var(--text-dim); font-size: 0.8rem; margin: 10px auto; display: block; }
        .btn-clear:hover { color: #f87171; border-color: #f87171; }
        .loader { display: none; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.2); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; margin-right: 10px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($is_setup_mode): ?>
            <header>
                <h1>Bienvenue sur <?= htmlspecialchars($app_name) ?></h1>
                <p class="subtitle">Configurons votre assistant personnel en quelques secondes.</p>
            </header>

            <section class="response-box" style="margin-bottom: 0;">
                <form method="post" style="display: flex; flex-direction: column; gap: 20px;">
                    <input type="hidden" name="action" value="setup_save">
                    
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="font-size: 0.8rem; color: var(--primary); font-weight: 600;">NOM DE L'ASSISTANT</label>
                        <input type="text" name="app_name_input" value="<?= htmlspecialchars($app_name) ?>" required maxlength="30">
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="font-size: 0.8rem; color: var(--primary); font-weight: 600;">EXPERTISE & PERSONNALITÉ</label>
                        <select name="profile_choice" id="profileChoice">
                            <?php foreach ($setup->getProfiles() as $key => $p): ?>
                                <option value="<?= $key ?>" <?= $key === 'general' ? 'selected' : '' ?>><?= htmlspecialchars($p['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="customPromptContainer" style="display: none; flex-direction: column; gap: 8px; margin-top: 10px;">
                            <label style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">VOTRE PROMPT SYSTÈME</label>
                            <textarea name="custom_prompt" style="width: 100%; min-width: 100%; background: var(--glass); border: 1px solid var(--border); border-radius: 12px; padding: 12px; color: white; font-family: inherit; font-size: 0.9rem; min-height: 100px; resize: vertical;" placeholder="Ex: Tu es un expert en cuisine italienne..."></textarea>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-dim);">Chaque profil inclut des règles de sécurité pour un usage responsable.</p>
                    </div>

                    <button type="submit" style="width: 100%;">Enregistrer et Commencer</button>
                    <a href="index.php" style="text-align: center; color: var(--text-dim); text-decoration: none; font-size: 0.85rem;">Passer la configuration</a>
                </form>

                <hr style="border: 0; border-top: 1px solid var(--border); margin: 30px 0;">
                
                <form method="post" style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="hidden" name="action" value="skill_add">
                    <strong style="font-size: 0.8rem; color: var(--primary); letter-spacing: 1px;">➕ CRÉER UNE NOUVELLE COMPÉTENCE</strong>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <input type="text" name="skill_name" placeholder="ID (ex: chef)" required maxlength="20">
                        <input type="text" name="skill_label" placeholder="Nom Affiché (ex: Chef Cuistot)" required maxlength="50">
                    </div>
                    <textarea name="skill_prompt" placeholder="Définissez sa personnalité et ses règles..." style="width: 100%; min-height: 80px; background: var(--glass); border: 1px solid var(--border); border-radius: 12px; padding: 10px; color: white;" required></textarea>
                    <button type="submit" style="background: var(--glass); border: 1px solid var(--primary); color: var(--primary);">Ajouter cette compétence</button>
                </form>
            </section>
        <?php else: ?>
            <h1><?= htmlspecialchars($app_name) ?></h1>
            <p class="subtitle">Assistant IA local • Streaming SSE • RAG Dynamique</p>

        <div class="response-box">
            <strong>Réponse de l'IA</strong>
            <div class="response-content" id="iaResponse"><?= nl2br(htmlspecialchars($reply)) ?></div>
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
            <a href="?setup=1" style="font-size: 0.75rem; color: var(--text-dim); text-decoration: none; margin-top: 10px;">⚙️ Reconfigurer</a>
        </div>
        <?php endif; ?>

        <!-- Section Mémoire -->
        <?php if (!$is_setup_mode): ?>
        <div class="source-toggle" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
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
                <input type="text" id="newFact" placeholder="Ajouter un fait important..." style="width:70%; margin-right:5px; font-size:0.8rem;">
                <button onclick="addFact()" style="padding:5px 10px; font-size:0.8rem;">Ajouter</button>
                <button onclick="clearMemory()" class="btn-clear" style="margin-top:5px; width:100%;">Réinitialiser la mémoire</button>
            </div>
        </div>
        <?php endif; ?>

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

    // Export functions to window since we are in a module
    window.setAIMode = async function(mode) {
        if (mode === 'browser') {
            const hasWebGpu = await webLlm.checkWebGPU();
            if (!hasWebGpu) {
                alert("Votre navigateur ne supporte pas WebGPU. Le mode Navigateur est impossible.");
                return;
            }
            document.getElementById('modeBrowser').style.background = 'var(--primary)';
            document.getElementById('modeBrowser').style.color = 'white';
            document.getElementById('modeServer').style.background = 'none';
            document.getElementById('modeServer').style.color = 'var(--text-dim)';
            currentMode = 'browser';
            
            if (!isWebLlmReady) {
                document.getElementById('webllmProgressContainer').style.display = 'block';
                try {
                    await webLlm.init((report) => {
                        const percent = Math.round(report.progress * 100);
                        document.getElementById('loadPercent').innerText = percent + '%';
                        document.getElementById('loadProgressBar').style.width = percent + '%';
                        document.getElementById('loadStatus').innerText = report.text;
                    });
                    isWebLlmReady = true;
                    document.getElementById('loadStatus').innerText = "Modèle prêt dans le navigateur !";
                    setTimeout(() => {
                        document.getElementById('webllmProgressContainer').style.opacity = '0.5';
                    }, 2000);
                } catch (e) {
                    alert("Erreur lors du chargement du modèle : " + e.message);
                }
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
        if (!confirm("Effacer tous les souvenirs de l'IA ?")) return;
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
            // Mode Serveur (Streaming original)
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
                            if (data === "[DONE]") {
                                finish(q, fullText);
                                break;
                            }
                            fullText += data;
                            responseBox.innerText = fullText;
                        }
                    }
                }
            } catch (e) {
                responseBox.innerText = "Erreur de connexion au serveur.";
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

    // Toggle custom prompt
    const profileChoice = document.getElementById('profileChoice');
    const customPromptContainer = document.getElementById('customPromptContainer');
    profileChoice?.addEventListener('change', () => {
        customPromptContainer.style.display = (profileChoice.value === 'custom') ? 'flex' : 'none';
    });
    </script>
</body>
</html>
