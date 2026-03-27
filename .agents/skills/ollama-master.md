# Skill: Ollama Master 🌐

Expertise en gestion des modèles et communication avec l'API Ollama.

## 1. Gestion des Modèles
- **Pulling** : Toujours vérifier si le modèle est présent via `listModels()`.
- **Modèle Recommandé** : `llama3.2` pour le chat, `nomic-embed-text` pour le RAG.
- **Paramètres** : Ajuster la température selon le profil (0.2 pour le code, 0.7 pour le créatif).

## 2. Prompt Engineering
- Utiliser des "System Prompts" clairs et impératifs.
- Découper les tâches complexes en plusieurs appels si nécessaire.
- Gérer le contexte de la conversation (historique JSON).
