# Décisions Architecturales (V2 Evolution) 🏛️

Historique des choix majeurs faits pour transformer EasyLocalAI en plateforme professionnelle.

- **Modularité (PSR-4)** : Passage d'un fichier monolithique `index.php` vers une structure de classes namespacées dans `src/`.
- **Injection de Dépendances** : Création de `Core\Container` pour centraliser la gestion des services et éviter les dépendances hardcodées.
- **Moteur d'Agents (Tools)** : Implémentation de `App\Agent` capable de détecter des balises `[TOOL: ...]` et d'exécuter des scripts PHP dynamiquement.
- **RAG Vectoriel** : Abandon de la recherche par mots-clés pour un système d'embeddings avec Ollama (`nomic-embed-text`) et `VectorStore` JSON.
- **Router Neural** : Mise en place d'un système de routage de contexte via `.github/copilot-instructions.md` limité à 150 lignes.
- **Thémage Dynamique** : Support natif du mode Sombre/Clair via variables CSS et localStorage.
