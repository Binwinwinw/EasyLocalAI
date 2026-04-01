# Conventions de Développement EasyLocalAI (Expert Edition)

Ce document établit les standards techniques pour maintenir la qualité "Béton Armé" de l'application.

## 📁 Architecture & Autoloading
- **Namespace** : `EasyLocalAI\`
- **Structure** : PSR-4 (Dossier `src/`).
- **DI (Injection de Dépendances)** : Utilisation systématique du `Container` (enregistré dans `bootstrap.php`).

## 🛡️ Sécurité (Hardening Rules)
Toute nouvelle fonctionnalité doit respecter les règles suivantes :
- **Protection CSRF** : Vérifier le jeton avec `Security::checkCsrf` pour chaque action d'écriture (POST/GET destructif).
- **Sanitization** : Filtrer ABSOLUMENT toutes les entrées utilisateur (`$_POST`, `$_GET`) avec `Security::sanitize()`.
- **Prepared Statements** : Ne jamais concaténer de variables dans des requêtes SQL ou des commandes système.
- **Uploads** : Utiliser `Security::validateUpload` (type MIME, taille, nom de fichier assaini).

## 🧩 Composants & UI Kinetic
- **Vanilla CSS** : Pas de frameworks lourds. Utilisation de variables CSS (`--primary`, `--glass-bg`, etc.).
- **Micro-animations** : Toute interaction doit être fluide (transitions CSS, Lucide icons animés).
- **Responsive** : Design mobile-first (Drawer Sidebar).

## 🚀 Performance & IA
- **Ollama Proxy** : Toujours utiliser le **Cortex Gateway** (port 8003) pour les appels LLM internes.
- **127.0.0.1** : Préférer l'adresse IP à `localhost` pour éviter les lenteurs de résolution DNS sur Windows.
- **Streaming SSE** : Préférer le streaming temps réel pour les réponses de l'Assistant.

## 📝 Documentation
- Chaque classe doit être documentée.
- Le [Journal de bord](file:///d:/Hostinger/public_html/EasyLocalAI_V2/docs/Journal_de_bord.md) doit être mis à jour à chaque phase majeure.
