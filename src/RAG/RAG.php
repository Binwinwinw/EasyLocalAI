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

        return json_encode([
            'success' => true,
            'message' => 'Document indexé avec succès.',
            'count' => count($data['chunks']),
            'filename' => $file['name']
        ]);
    }

    /**
     * Récupère le contexte sémantique le plus proche avec système de citation.
     */
    public function getContext(string $prompt): string
    {
        // 1. Embed le prompt utilisateur
        $queryVector = $this->embedder->embed($prompt);
        if (empty($queryVector)) return "";

        // 2. Recherche sémantique (Top 5 segments)
        $matches = $this->vectorStore->search($queryVector, 5);
        if (empty($matches)) return "";

        $context = "";
        $sourcesUsed = [];
        
        foreach ($matches as $match) {
            // Seuil de similarité (Expert: 0.45 pour éviter le bruit)
            if ($match['similarity'] < 0.45) continue;

            $source = $match['metadata']['source'] ?? 'Document inconnu';
            $sourcesUsed[$source] = true;
            
            $context .= "\n[SOURCE: $source | PERTINENCE: " . round($match['similarity'] * 100, 1) . "%]\n";
            $context .= $match['text'] . "\n";
        }

        if (empty($context)) return "";

        $citationList = implode(", ", array_keys($sourcesUsed));

        return "--- CONNAISSANCES EXTRAITES (Sources: $citationList) ---\n" . 
               $context . 
               "\n--- FIN DU CONTEXTE ---\n" .
               "RÈGLE : Utilise UNIQUEMENT les sources ci-dessus. Si l'information n'y est pas, dis que tu ne sais pas selon tes documents.";
    }
}
