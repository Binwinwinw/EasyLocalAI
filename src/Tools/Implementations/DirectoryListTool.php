<?php

namespace EasyLocalAI\Tools\Implementations;

use EasyLocalAI\Tools\ToolInterface;

class DirectoryListTool implements ToolInterface {
    private $baseDir;

    public function __construct() {
        $this->baseDir = realpath(__DIR__ . '/../../../../'); // Root of the project
    }

    public function getName(): string {
        return "list_directory";
    }

    public function getDescription(): string {
        return "Liste les fichiers et dossiers d'un répertoire donné. Par défaut, liste la racine du projet.";
    }

    public function getParameters(): array {
        return [
            "path" => "Chemin relatif du répertoire à lister (ex: src, config, .). Par défaut: '.'"
        ];
    }

    public function execute(array $args): string {
        $requestedPath = $args['path'] ?? '.';
        $fullPath = realpath($this->baseDir . DIRECTORY_SEPARATOR . $requestedPath);

        if (!$fullPath || strpos($fullPath, $this->baseDir) !== 0) {
            return "Erreur: Accès interdit ou dossier non trouvé.";
        }

        if (!is_dir($fullPath)) {
            return "Erreur: '$requestedPath' n'est pas un dossier valide.";
        }

        $items = scandir($fullPath);
        $result = "Contenu de '$requestedPath' :\n";
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
            $type = is_dir($itemPath) ? "[DIR]" : "[FILE]";
            $result .= "- $type $item\n";
        }

        return $result;
    }
}
