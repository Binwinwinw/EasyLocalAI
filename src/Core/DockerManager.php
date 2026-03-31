<?php
// src/Core/DockerManager.php

namespace EasyLocalAI\Core;

/**
 * EasyLocalAI - Docker Manager
 * Permet de piloter l'infrastructure Docker via le socket /var/run/docker.sock.
 */
class DockerManager
{
    private string $socketPath = '/var/run/docker.sock';

    /**
     * Redémarre un container par son nom.
     * @param string $containerName Nom du container (ex: ollama_upstream)
     * @return bool Succès de l'opération
     */
    public function restartContainer(string $containerName): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $this->socketPath);
        curl_setopt($ch, CURLOPT_URL, "http://localhost/containers/$containerName/restart");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Docker retourne 204 No Content en cas de succès de restart
        return ($httpCode === 204);
    }

    /**
     * Redémarre toute l'infrastructure (Gateway + Ollama).
     * @return array Résultats [container => success]
     */
    public function restartInfrastructure(): array
    {
        return [
            'cortex_gateway_v4' => $this->restartContainer('cortex_gateway_v4'),
            'ollama_upstream'   => $this->restartContainer('ollama_upstream')
        ];
    }
}
