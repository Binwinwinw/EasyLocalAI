# Décisions Architecturales (V4 Hybrid Cinematic) 🏛️

Historique des choix majeurs faits pour transformer EasyLocalAI en plateforme de niveau professionnel.

- **Modularité (PSR-4)** : Passage d'un fichier monolithique `index.php` vers une structure de classes namespacées dans `src/`.
- **Injection de Dépendances** : Création de `Core\Container` pour centraliser la gestion des services.
- **Moteur d'Agents (Tools)** : Implémentation de `App\Agent` capable de raisonner via des outils (Clock, Memory).
- **RAG Vectoriel** : Utilisation d'embeddings Ollama (`nomic-embed-text`) et stockage vectoriel JSON.
- **Unified Control Center (V4)** : Fusion de la configuration, des modèles et de l'expertise neuronale dans un seul fichier `setup.php` avec une interface **Bento Grid**.
- **Cinematic UI (V3/V4)** : Layout avec **Sidebar fixe**, scanlines HUD, et thémage dynamique sombre/clair.
- **Pont Multi-Providers** : Support hybride de **Ollama (local)** et des APIs Cloud (**Groq, OpenAI, MiniMax**) avec gestion sécurisée des clés via `localStorage`.
- **Streaming de Pull** : Implémentation de `pull_stream.php` pour suivre en temps réel le téléchargement des modèles Ollama.
