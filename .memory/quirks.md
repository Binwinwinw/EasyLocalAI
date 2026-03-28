# Quirks & Pièges du Projet ⚠️

Liste des comportements inattendus ou spécificités de l'environnement.

- **CLI Auth** : `Auth::protect()` redirige normalement vers `login.php`. En mode CLI (pour les tests PHPUnit), cette redirection est désactivée via `PHP_SAPI`.
- **Windows Case-Sensitivity** : Bien que Windows soit insensible à la casse, le renommage de dossiers (ex: `SKILLS` -> `skills`) nécessite une étape intermédiaire (`tmp`) pour être pris en compte par Git proprement.
- **CURL Timeouts** : Le délai d'attente pour les appels LLM (Ollama et Cloud) a été porté à **300s** (5 min) pour éviter les erreurs sur les gros modèles ou les longues réponses. Les embeddings (RAG) sont à **120s**.
- **SSE Streaming** : Le mode Agent (`Agent::run`) est actuellement synchrone. Le streaming est simulé par un bloc final pour éviter les conflits avec le cycle de réflexion des outils.
