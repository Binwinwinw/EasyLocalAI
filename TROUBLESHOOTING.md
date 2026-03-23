# Dépannage LocaLAI (TROUBLESHOOTING)

Ce guide aide à diagnostiquer les problèmes courants avec LocaLAI, notamment liés à Docker, Ollama et l’intégration PHP.

## Symptômes fréquents côté PHP

- **Erreur IA ou pas de réponse** :
  - Le backend PHP affiche « Erreur IA ou pas de réponse. »
  - Aucune réponse de l’IA dans l’interface web.

## Étapes de diagnostic

### 1. Vérifier les containers Docker

- `docker ps` : les containers `ollama_upstream` et `php_app` doivent être en état "Up".
- `docker logs ollama_upstream` : vérifier l’absence d’erreurs (modèle manquant, crash, etc).

### 2. Tester l’API Ollama directement

- Depuis l’hôte :
  ```bash
  curl http://localhost:11434/api/generate -d '{"model":"qwen3:4b","prompt":"Bonjour","stream":false}' -H "Content-Type: application/json"
  ```
- Attendre une réponse JSON. Si erreur, vérifier que le modèle est bien téléchargé (`docker exec -it ollama_upstream ollama list`).

### 3. Tester le curl depuis le container PHP

- `docker exec -it php_app bash`
- `curl http://ollama:11434/api/generate -d '{"model":"qwen3:4b","prompt":"test","stream":false}' -H "Content-Type: application/json"`
- Si erreur de connexion, vérifier le réseau Docker et le nom du service `ollama`.

### 4. Vérifier la variable d’environnement OLLAMA_HOST

- Dans `docker-compose.yml`, la variable doit être : `OLLAMA_HOST=http://ollama:11434`
- Le code PHP doit utiliser cette URL pour joindre Ollama.

### 5. Analyser les logs PHP

- `docker logs php_app` : chercher des erreurs curl, JSON ou autres anomalies.

### 6. Vérifier le modèle Ollama

- Si le modèle n’est pas téléchargé, Ollama ne répondra pas.
- Utiliser : `docker exec -it ollama_upstream ollama pull qwen3:4b`

### 7. Redémarrer les containers

- Parfois, un redémarrage synchronise les services :
  ```bash
  docker-compose restart
  ```

## Problèmes courants et solutions

- **Port déjà utilisé** :
  - Le port 11434 (Ollama) ou 8000 (PHP) est occupé. Libérer le port ou modifier le mapping dans `docker-compose.yml`.

- **Modèle non téléchargé** :
  - L’IA ne répond pas tant que le modèle n’est pas présent. Télécharger avec la commande ci-dessus.

- **Erreur réseau entre containers** :
  - Vérifier que les services sont sur le même réseau Docker (défaut avec Compose).

- **Erreur JSON côté PHP** :
  - Vérifier que la réponse d’Ollama est bien du JSON. Si besoin, afficher `$response` brut pour debug.

---

Pour toute modification majeure ou bug récurrent, documenter la cause et la solution dans `.memory/decisions.md`.
