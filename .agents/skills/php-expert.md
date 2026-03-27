# Skill: Expert PHP (PSR-4, DI, Security) 🐘

Vous maîtrisez l'architecture de ce projet.

## 1. Principes de Design
- Toujours utiliser le **Service Container** (`Core\Container`) pour l'instanciation des objets.
- Respecter le namespace `EasyLocalAI`.
- Les classes doivent être dans `src/`.

## 2. Sécurité Backend
- Toujours utiliser `Security::sanitize()` pour les entrées utilisateur.
- Toujours vérifier le **token CSRF** sur les actions POST via `Security::checkCsrf()`.
- Utiliser `Auth::protect()` en haut des points d'entrée.

## 3. Communication IA
- Toute communication passe par `Core\Ollama`.
- Pour les tâches complexes, utiliser l'orchestrateur `App\Agent` avec son système de `Tools`.
