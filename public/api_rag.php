<?php
// public/api_rag.php - AJAX Endpoint for Knowledge Ingestion
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

header('Content-Type: application/json');

try {
    $rag = Container::get('rag');
    $result = $rag->handleUpload();
    
    if (empty($result)) {
        echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu ou erreur formulaire.']);
    } else {
        echo $result;
    }
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur système : ' . $e->getMessage()]);
}
