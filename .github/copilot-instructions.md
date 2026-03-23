<!-- hacklm-memory:start -->

## Memory-Augmented Context

Read memory files on-demand — not all at once.

| File                                               | When to read                        |
| -------------------------------------------------- | ----------------------------------- |
| [.memory/instructions.md](.memory/instructions.md) | How to behave                       |
| [.memory/quirks.md](.memory/quirks.md)             | When something breaks unexpectedly  |
| [.memory/preferences.md](.memory/preferences.md)   | Style/design/naming choices         |
| [.memory/decisions.md](.memory/decisions.md)       | Architectural changes               |
| [.memory/security.md](.memory/security.md)         | **ALWAYS — before any code change** |

### Memory Tools

Call `queryMemory` before answering anything about architecture, conventions, or style.

Call `storeMemory` (with a kebab-case `slug`) when:

1. User states a preference or rule → store as Instruction or Preference **before** acting
2. User corrects you → store the correction
3. A command or build fails → store root cause and fix
4. After completing any implementation task → store each architectural decision, convention, or pattern applied that is not already in memory. Do this **before ending the turn**.

Same slug = update, not duplicate.

### Writing Style for Memory Entries

Hemingway style. Short sentences. No jargon. No filler. Be blunt.
Bad: "The system employs an asynchronous locking mechanism to serialise concurrent write operations."
Good: "Use a lock before writing. One write at a time."

### Categories

| Category    | Use for                         |
| ----------- | ------------------------------- |
| Instruction | How to behave                   |
| Quirk       | Project-specific weirdness      |
| Preference  | Style/design/naming             |
| Decision    | Architectural commitments       |
| Security    | Rules that must NEVER be broken |

<!-- hacklm-memory:end -->

## Project-Specific Instructions

### Build & Run

- Utilise Docker Compose pour tout : `docker-compose up -d` démarre l’IA locale et le backend PHP.
- Pour télécharger un modèle IA : `docker exec -it ollama_upstream ollama pull qwen3:4b`
- Accès au chat IA : http://localhost:8000

### Conventions

- Backend en PHP, tout accès à l’IA passe par l’API locale Ollama (`http://ollama:11434/api/generate`).
- Les services sont isolés : PHP et Ollama dans des containers séparés.
- Les variables d’environnement sont définies dans docker-compose.yml (ex : `OLLAMA_HOST`).

### Pièges & Spécificités

- Ollama doit être prêt avant de lancer le backend PHP (voir `depends_on`).
- Le port 11434 doit être libre pour Ollama, 8000 pour PHP.
- Si le modèle n’est pas téléchargé, l’IA ne répondra pas.

### Documentation

- Voir le [README.md](../README.md) pour : architecture détaillée, installation, exemples d’utilisation, licence.
- Pour le dépannage : voir [TROUBLESHOOTING.md](../TROUBLESHOOTING.md) (erreurs Ollama, Docker, PHP, solutions rapides).

### À retenir

- Toujours vérifier la disponibilité des containers Docker avant de tester.
- Pour toute modification majeure, documenter la décision dans `.memory/decisions.md`.
