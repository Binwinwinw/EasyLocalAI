# Template : Architecture Neuronale pour IA 🧠

Ce fichier sert de modèle pour répliquer cette structure de contexte sur n'importe quel nouveau projet.

## 📂 Arborescence à Créer
```text
.
├── .agents/
│   └── skills/
│       ├── skills.md         # Index des compétences
│       └── [maitrise].md     # Fiche technique spécifique
├── .github/
│   ├── prompts/
│   │   └── [tache].md        # Prompts pour actions spécifiques
│   └── copilot-instructions.md # LE ROUTEUR (Max 150 lignes)
├── .memory/
│   ├── decisions.md          # Le "Pourquoi" (Log architectural)
│   ├── history.md            # Le "Quand" (Journal de bord)
│   ├── instructions.md       # Le "Comment" (Règles globales)
│   ├── preferences.md        # Le "Style" (Design/Naming)
│   ├── quirks.md             # Le "Attention" (Pièges/Bugs)
│   └── security.md           # Le "Sûr" (Règles critiques)
└── knowledge/
    └── .gitkeep              # Dossier pour données RAG
```

---

## 📄 Contenu Type : Le Routeur (`.github/copilot-instructions.md`)
```markdown
# [Nom du Projet] - Neural Router 🧠

## 1. Boucle de Contexte
- **Consulter** : [.memory/security.md](.memory/security.md) avant chaque changement.
- **Vérifier** : [.memory/history.md](.memory/history.md) pour reprendre le travail.
- **Appliquer** : Les fiches techniques de [.agents/skills/](.agents/skills/).

## 2. Cartographie
- Mémoire : [.memory/](.memory/)
- Compétences : [.agents/skills/](.agents/skills/)
- Prompts : [.github/prompts/](.github/prompts/)
```

## 📄 Contenu Type : Décisions (`.memory/decisions.md`)
```markdown
# Décisions Architecturales

- [AAAA-MM-JJ] : [Titre de la décision]. 
  - Contexte : [Pourquoi on change ?]
  - Choix : [Quelle techno/méthode ?]
  - Conséquence : [Ce que ça implique pour la suite].
```

## 📄 Contenu Type : Compétence (`.agents/skills/expert.md`)
```markdown
# Skill: [Domaine d'Expertise]

- **Règles d'or** : [Règle 1], [Règle 2].
- **Tech Stack** : [Version, Framework].
- **Patterns** : [Singleton, Factory, etc.].
```

---
*Générez cette structure pour transformer n'importe quel dépôt en un environnement IA-Ready.*
