<!-- hacklm-memory:start -->
# EasyLocalAI - Neural Router (Hybrid V4) 🧠

Vous êtes sur un projet **Hybrid V4** (Souveraineté locale + Puissance Cloud). Votre contexte est distribué.

## 1. La Boucle de Pensée
1. **Sécurité** : Consultez `../.memory/security.md` avant toute action POST.
2. **Architecture** : Le projet utilise un `Core\Container`. Vérifiez `../config/bootstrap.php`.
3. **Pilotage** : Toute la config se passe dans `setup.php` (Unified Control Center).
4. **Mémoriser** : Résumez les changements dans `../.memory/history.md`.

## 2. Cartographie du Cerveau

| Neurone | Usage |
| :--- | :--- |
| **[../.memory/](../.memory/)** | Mémoire long terme (Décisions, Quirks, Sécurité). |
| **[prompts/](prompts/)** | Instructions pour tâches spécifiques (Refactor, etc.). |
| **[../.agents/skills/](../.agents/skills/)** | Expertise technique (PHP-Expert, RAG, Ollama). |
| **[../knowledge/](../knowledge/)** | Base de connaissances pour le RAG. |

## 3. Taxonomie du Projet (Anchoring)
- **Application** : `src/App/` (Agent, Conversation, Memory).
- **Core** : `src/Core/` (Container, Security, Ollama). Config dans `config/`.
- **UI** : `public/` (V4 Hybrid Cinematic).
- **RAG** : `src/RAG/` (Embedder, VectorStore). Données dans `knowledge/`.

## 4. Règles d'Honneur (Code)
- Respectez le style **Cinematic v3** (Sidebar, Bento, Scanlines).
- Les clés API sont gérées via `localStorage` (Zero-Knowledge).
- Utilisez systématiquement `write_file` pour le code, JAMAIS `list_directory`.
- Gardez ce fichier < 150 lignes.

<!-- hacklm-memory:end -->
