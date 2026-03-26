<?php

namespace EasyLocalAI\Tools\Implementations;

use EasyLocalAI\Tools\ToolInterface;

class ClockTool implements ToolInterface {
    public function getName(): string {
        return "get_time";
    }

    public function getDescription(): string {
        return "Donne la date et l'heure actuelle précise.";
    }

    public function getParameters(): array {
        return [];
    }

    public function execute(array $args): string {
        return "La date et l'heure actuelle est : " . date('Y-m-d H:i:s');
    }
}
