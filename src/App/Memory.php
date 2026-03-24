<?php
// src/App/Memory.php

namespace EasyLocalAI\App;

class Memory
{
    private string $memoryPath;
    private array $facts = [];

    public function __construct(string $memoryPath = __DIR__ . '/../../config/memory.json')
    {
        $this->memoryPath = $memoryPath;
        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->memoryPath)) {
            $json = file_get_contents($this->memoryPath);
            $this->facts = json_decode($json, true) ?: [];
        }
    }

    public function save(): bool
    {
        $json = json_encode($this->facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->memoryPath, $json) !== false;
    }

    public function addFact(string $fact): void
    {
        $fact = trim($fact);
        if ($fact && !in_array($fact, $this->facts)) {
            $this->facts[] = $fact;
            $this->save();
        }
    }

    public function removeFact(int $index): void
    {
        if (isset($this->facts[$index])) {
            unset($this->facts[$index]);
            $this->facts = array_values($this->facts);
            $this->save();
        }
    }

    public function getFacts(): array
    {
        return $this->facts;
    }

    public function getContextString(): string
    {
        if (empty($this->facts)) {
            return "";
        }

        $context = "\n\n🧠 MÉMOIRE PERSISTANTE (Informations retenues sur l'utilisateur/projet) :\n";
        foreach ($this->facts as $fact) {
            $context .= "- " . $fact . "\n";
        }
        return $context;
    }

    public function clear(): void
    {
        $this->facts = [];
        $this->save();
    }
}
