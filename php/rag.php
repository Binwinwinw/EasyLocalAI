<?php

/**
 * Minimal RAG context provider for LocaLAI
 */
function get_rag_context($prompt) {
    // Basic implementation: search for keywords and provide relevant context
    $context = "";
    
    $keywords = [
        'école' => "L'école est un lieu d'apprentissage et de socialisation.",
        'travail' => "Le travail est une activité humaine visant à produire des biens ou des services.",
        'informatique' => "L'informatique est la science du traitement automatique de l'information.",
        'ia' => "L'intelligence artificielle est un domaine de l'informatique visant à simuler l'intelligence humaine."
    ];

    foreach ($keywords as $key => $val) {
        if (stripos($prompt, $key) !== false) {
            $context .= $val . "\n";
        }
    }

    return $context ? "Contexte additionnel :\n" . $context : "";
}
