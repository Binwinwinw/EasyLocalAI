<?php
// RAG basique : cherche un contexte pertinent dans data/ selon le domaine scolaire mentionné
function get_rag_context($question) {
    $domains = [
        'collège' => 'college.txt',
        'lycée'   => 'lycee.md',
        'bac'     => 'bac.txt',
    ];
    $context = [];
    $question_lc = mb_strtolower($question, 'UTF-8');
    foreach ($domains as $motcle => $file) {
        if (mb_strpos($question_lc, $motcle) !== false) {
            $path = __DIR__ . '/../data/' . $file;
            if (file_exists($path)) {
                $txt = file_get_contents($path);
                $context[] = "[Contexte $motcle] :\n" . trim($txt);
            }
        }
    }
    return $context ? implode("\n\n", $context) : '';
}
