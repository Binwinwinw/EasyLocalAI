<?php

namespace EasyLocalAI\Tools\Implementations;

use EasyLocalAI\Tools\ToolInterface;

class FileReadTool implements ToolInterface {
    private $baseDir;

    public function __construct() {
        $this->baseDir = realpath(__DIR__ . '/../../../../'); // Root of the project
    }

    public function getName(): string {
        return "read_file";
    }

    public function getDescription(): string {
        return "Lit le contenu d'un fichier texte présent dans le projet. Fournis le chemin relatif depuis la racine.";
    }

    public function getParameters(): array {
        return [
            "path" => "Chemin relatif du fichier (ex: README.md, src/Core/Config.php)"
        ];
    }

    public function execute(array $args): string {
        if (!isset($args['path'])) {
            return "Erreur: Le paramètre 'path' est requis.";
        }

        $requestedPath = $args['path'];
        $fullPath = realpath($this->baseDir . DIRECTORY_SEPARATOR . $requestedPath);

        if (!$fullPath || strpos($fullPath, $this->baseDir) !== 0) {
            return "Erreur: Accès interdit ou fichier non trouvé.";
        }

        if (!is_file($fullPath)) {
            return "Erreur: '$requestedPath' n'est pas un fichier valide.";
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            return "Erreur: Impossible de lire le fichier.";
        }

        // Limiter la taille pour éviter de saturer le contexte
        if (strlen($content) > 10000) {
            $content = substr($content, 0, 10000) . "\n... [Contenu tronqué]";
        }

        return "Contenu de $requestedPath :\n\n" . $content;
    }
}
