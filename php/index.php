<?php

session_start();
require_once __DIR__ . '/rag.php';

// Configuration du prompt système (IA pédagogique en français)
$system_prompt = "Tu es un assistant pédagogique francophone, très clair, concis et structuré. "
    . "Tu réponds toujours en français, en évitant le chinois et les formulations confuses. "
    . "Tu peux expliquer simplement des concepts pour un public scolaire ou professionnel.";

// Initialisation de l'historique si besoin
if (!isset($_SESSION['conversation_history'])) {
    $_SESSION['conversation_history'] = [];
}

// Fonction pour appeler LocalAI (OpenAI compatible)
function askLocalAI($prompt, $history = []) {
    global $system_prompt;
    // Ajout RAG : cherche un contexte si la question mentionne un domaine scolaire
    $rag_context = get_rag_context($prompt);
    $curl = curl_init();

    // Construire les messages pour l'API Chat Completions
    $messages = [
        ["role" => "system", "content" => $system_prompt]
    ];

    foreach ($history as $item) {
        $messages[] = ["role" => "user", "content" => $item['q']];
        $messages[] = ["role" => "assistant", "content" => $item['a']];
    }

    $full_prompt = $prompt;
    if ($rag_context) {
        $full_prompt = $rag_context . "\n\n" . $prompt;
    }

    $messages[] = ["role" => "user", "content" => $full_prompt];

    curl_setopt_array($curl, [
        CURLOPT_URL            => "http://local-ai:8080/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "model"  => "ollama-llama3",
            "messages" => $messages,
            "stream" => false,
        ]),
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        return "Erreur Curl: " . $error;
    }

    $data = json_decode($response, true);
    if (isset($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }

    // Si on arrive ici, il y a une erreur ou un format inattendu
    if (isset($data['error'])) {
        return "Erreur API: " . ($data['error']['message'] ?? json_encode($data['error']));
    }
    
    return "Erreur IA (Réponse vide ou invalide). Réponse brute: " . substr($response, 0, 200);
}

// Gérer la requête GET
$q = $_GET['q'] ?? "";

$reply = null;
if ($q) {
    $reply = askLocalAI($q, $_SESSION['conversation_history']);
    $_SESSION['conversation_history'][] = ['q' => $q, 'a' => $reply];
} else {
    $reply = "Entre une question avec ?q=Votre+question+ici";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>LocaLAI – IA locale intégrée</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        form input[type="text"] {
            width: 70%;
            padding: 8px;
        }
        .msg {
            margin: 10px 0;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>LocaLAI – Assistant IA local</h1>


    <form method="get" id="questionForm" autocomplete="off" novalidate>
        <label>
            Question :
            <input type="text" name="q" id="qInput" value="<?= htmlspecialchars($q) ?>"
                   required minlength="5" maxlength="200"
                   pattern="^[^<>\n\r\t]+$"
                   title="5 à 200 caractères, sans balises HTML ni caractères spéciaux" autocomplete="off">
        </label>
        <button type="submit">Envoyer</button>
    </form>
    <script>
    // Validation JS stricte côté client
    document.getElementById('questionForm').addEventListener('submit', function(e) {
        const input = document.getElementById('qInput');
        const val = input.value.trim();
        const minLen = 5, maxLen = 200;
        const forbidden = /[<>\n\r\t]/;
        let error = '';
        if (val.length < minLen) error = 'La question est trop courte (min 5 caractères).';
        else if (val.length > maxLen) error = 'La question est trop longue (max 200 caractères).';
        else if (forbidden.test(val)) error = 'Caractères interdits (<, >, retour ligne, tabulation).';
        if (error) {
            alert(error);
            input.focus();
            e.preventDefault();
        }
    });
    </script>

    <div>
        <p><strong>Question : </strong> <?= htmlspecialchars($q ?: "Aucune question") ?> </p>
        <p><strong>IA : </strong><br> <?= nl2br(htmlspecialchars($reply)) ?> </p>
    </div>

    <hr>

    <h3>Histoire courte (3 derniers échanges)</h3>
    <ul>
        <?php
        $history = array_slice($_SESSION['conversation_history'], -3);
        foreach ($history as $item): ?>
            <li class="msg">
                <strong>Q</strong> : <?= htmlspecialchars($item['q']) ?> 
                <br>
                <strong>R</strong> : <?= nl2br(htmlspecialchars($item['a'])) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
