<?php
// public/index.php

session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Config;
use EasyLocalAI\Core\Ollama;
use EasyLocalAI\App\Conversation;
use EasyLocalAI\Setup\SetupManager;
use EasyLocalAI\RAG\RAG;

// Performance settings
set_time_limit(120);
ini_set('memory_limit', '256M');

// Core Components
$config = new Config();
$setup  = new SetupManager($config);
$rag    = new RAG();
$ollama = new Ollama($config);

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

    <script>
    const form = document.getElementById('questionForm');
    const input = document.getElementById('qInput');
    const responseBox = document.getElementById('iaResponse');
    const loader = document.getElementById('loader');
    const btn = document.getElementById('submitBtn');

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const q = input.value.trim();
        if (q.length < 5) return;

        responseBox.innerText = "";
        loader.style.display = "block";
        btn.disabled = true;

        let fullText = "";
        try {
            const response = await fetch(`stream.php?q=${encodeURIComponent(q)}`);
            const reader = response.body.getReader();
            const decoder = new TextDecoder();

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                const chunk = decoder.decode(value);
                const lines = chunk.split("\n");
                for (const line of lines) {
                    if (line.startsWith("data: ")) {
                        const data = line.slice(6).trim();
                        if (data === "[DONE]") { finish(q, fullText); break; }
                        try {
                            const json = JSON.parse(data);
                            if (json.choices && json.choices[0].delta) {
                                fullText += json.choices[0].delta.content || "";
                                responseBox.innerText = fullText;
                            }
                        } catch(e) {}
                    }
                }
            }
        } catch(e) { responseBox.innerText = "Erreur : " + e; }
        loader.style.display = "none";
        btn.disabled = false;
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
