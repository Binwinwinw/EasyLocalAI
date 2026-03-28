<?php

namespace EasyLocalAI\Tools\Implementations;

use EasyLocalAI\Tools\ToolInterface;
use EasyLocalAI\RAG\RAG;

class FileWriteTool implements ToolInterface {
    private $baseDir;
    private $rag;

    public function __construct(RAG $rag) {
        $this->baseDir = realpath(__DIR__ . '/../../../../'); // Root of the project
        $this->rag = $rag;
    }

    public function getName(): string {
        return "write_file";
    }

    public function getDescription(): string {
        return "Crée ou modifie un fichier dans le projet. Fournis le chemin relatif et le contenu complet. L'indexation RAG est automatique.";
    }

    public function getParameters(): array {
        return [
            "path" => "Chemin relatif du fichier (ex: src/Core/Agent.php, README.md)",
            "content" => "Nouveau contenu complet du fichier."
        ];
    }

    public function execute(array $args): string {
        if (!isset($args['path']) || !isset($args['content'])) {
            return "Erreur: Les paramètres 'path' et 'content' sont requis.";
        }

        $requestedPath = $args['path'];
        $fullPath = $this->baseDir . DIRECTORY_SEPARATOR . $requestedPath;

        // On vérifie si le dossier parent existe
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Vérification de sécurité (Directory Traversal)
        $realFullPath = realpath($fullPath) ?: $fullPath; // realpath peut échouer si le fichier n'existe pas encore
        if (strpos($realFullPath, $this->baseDir) !== 0) {
            return "Erreur: Accès interdit (tentative de sortie de la racine).";
        }

        // Écriture du fichier
        if (file_put_contents($fullPath, $args['content']) !== false) {
            // Auto-RAG : Indexation systématique si le fichier est de type texte
            $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
            if (in_array($ext, ['php', 'txt', 'md', 'json', 'js', 'css'])) {
                $this->rag->indexDocument(basename($requestedPath), $args['content']);
            }
            return "Fichier '$requestedPath' écrit et indexé avec succès (" . strlen($args['content']) . " octets).";
        }

        return "Erreur: Impossible d'écrire dans le fichier.";
    }
}
