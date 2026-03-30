<?php
// src/RAG/RAG.php - Vector Search Refactor

namespace EasyLocalAI\RAG;

/**
 * EasyLocalAI - RAG Service
 * Gère l'ingestion de documents et la récupération de contexte sémantique.
 */
class RAG
{
    private string $knowledgeDir;
    private Embedder $embedder;
    private VectorStore $vectorStore;

    public function __construct(
        Embedder $embedder, 
        VectorStore $vectorStore,
        string $knowledgeDir = __DIR__ . '/../../knowledge/'
    ) {
        $this->embedder = $embedder;
        $this->vectorStore = $vectorStore;
        $this->knowledgeDir = $knowledgeDir;
        
        if (!is_dir($this->knowledgeDir)) {
            mkdir($this->knowledgeDir, 0755, true);
        }
    }

    /**
     * Gère l'upload et l'indexation sémantique complète d'un fichier (PDF/TXT/MD).
     */
    public function handleUpload(): string
    {
        if (!isset($_FILES['knowledge_file'])) return "";
        
        // 1. Protection CSRF (Vérifie le jeton envoyé en POST)
        if (!Security::checkCsrf($_POST['csrf_token'] ?? '')) {
            return "Session de sécurité expirée (CSRF).";
        }

        $file = $_FILES['knowledge_file'];
        
        // 2. Validation Stricte (Taille / Mime-Type réel)
        $valid = Security::validateUpload($file, 10); // Limite à 10 Mo pour l'Expert
        if ($valid !== true) return $valid;

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // 3. Appel au Gateway avec Jeton Secret
        $curl = curl_init("http://cortex_gateway_v4:8000/v1/rag/ingest");
        $cFile = new \CURLFile($file['tmp_name'], $file['type'], $file['name']);
        
        $token = Container::get('env')->get('CORTEX_API_KEY');

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $cFile],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "X-Cortex-Token: $token"
            ],
        ]);

        $response = curl_exec($curl);
        $data = json_decode($response, true);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($status === 401) return "Erreur d'authentification Gateway (Token invalide).";
        if (!isset($data['chunks'])) return "Erreur lors du parsing du document : " . ($data['detail'] ?? 'Inconnue');

        // 4. Indexation des chunks
        foreach ($data['chunks'] as $chunk) {
            $vector = $this->embedder->embed($chunk);
            if (!empty($vector)) {
                $this->vectorStore->add($chunk, $vector, ['source' => $file['name']]);
            }
        }

        // 3. Sauvegarde physique du fichier original
        $targetFile = $this->knowledgeDir . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $targetFile);

        return "Doc indexé : " . count($data['chunks']) . " segments créés.";
    }

    /**
     * Récupère le contexte sémantique le plus proche.
     */
    public function getContext(string $prompt): string
    {
        // 1. Embed le prompt utilisateur
        $queryVector = $this->embedder->embed($prompt);
        if (empty($queryVector)) return "";

        // 2. Recherche sémantique
        $matches = $this->vectorStore->search($queryVector, 4);
        if (empty($matches)) return "";

        $context = "";
        foreach ($matches as $match) {
            // Seuil de similarité pour éviter le bruit (0.4 est un bon compromis)
            if ($match['similarity'] < 0.4) continue;

            $source = $match['metadata']['source'] ?? 'Inconnu';
            $context .= "\n--- Extrait de : $source (Pertinence: " . round($match['similarity'] * 100, 1) . "%) ---\n" . $match['text'] . "\n";
        }

        if (empty($context)) return "";

        return "CONTEXTE SÉMANTIQUE DÉTECTÉ :\n" . $context . "\n(Utilise ces informations pour répondre de manière précise.)";
    }
}
