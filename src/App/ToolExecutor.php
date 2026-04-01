<?php

namespace EasyLocalAI\App;

/**
 * ToolExecutor - Expert Code Execution Engine
 * Permet à l'Agent d'exécuter des scripts Python localement.
 */
class ToolExecutor
{
    private string $pyPath;
    private float $timeout;

    public function __construct(string $pyPath = 'python', float $timeout = 30.0)
    {
        $this->pyPath  = $pyPath;
        $this->timeout = $timeout;
    }

    /**
     * Exécute un bloc de code Python et retourne le résultat (stdout/stderr).
     */
    public function executePython(string $code): array
    {
        // Nettoyage du code (retrait des délimiteurs Markdown si présents)
        $code = preg_replace('/^```python\s*/i', '', $code);
        $code = preg_replace('/```$/', '', $code);
        $code = trim($code);

        if (empty($code)) {
            return ['success' => false, 'output' => 'Erreur : Code vide.'];
        }

        // Création d'un fichier temporaire sécurisé
        $tmpDir = __DIR__ . '/../../tmp/tools';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        
        $tmpFile = $tmpDir . '/script_' . uniqid() . '.py';
        file_put_contents($tmpFile, $code);

        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"]  // stderr
        ];

        $process = proc_open("{$this->pyPath} \"$tmpFile\"", $descriptorspec, $pipes);

        if (!is_resource($process)) {
            return ['success' => false, 'output' => 'Impossible de lancer l\'interpréteur Python.'];
        }

        // Lecture asynchrone avec timeout
        $start   = microtime(true);
        $stdout  = "";
        $stderr  = "";
        $timeout = false;

        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        while (true) {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            $status = proc_get_status($process);
            if (!$status['running']) break;

            if (microtime(true) - $start > $this->timeout) {
                proc_terminate($process);
                $timeout = true;
                break;
            }
            usleep(10000); // 10ms
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        // Nettoyage
        if (file_exists($tmpFile)) unlink($tmpFile);

        if ($timeout) {
            return ['success' => false, 'output' => "TIMEOUT : L'exécution a dépassé {$this->timeout}s."];
        }

        $output = trim($stdout . "\n" . $stderr);
        return [
            'success' => empty($stderr),
            'output'  => !empty($output) ? $output : "[Exécution terminée sans sortie]"
        ];
    }
}
