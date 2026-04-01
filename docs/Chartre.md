# Chartre Fondatrice EasyLocalAI (Expert Edition)

Ce document définit les principes directeurs et le périmètre de développement de l'application EasyLocalAI. Il sert de point d'ancrage pour l'Agent de développement (Moi-même) et de garantie pour l'Utilisateur.

## 🏛️ Vision & Souveraineté
EasyLocalAI est conçu pour être un **Assistant Intelligent SOUVERAIN**. 
- **Local-First** : L'intelligence doit résider prioritairement sur la machine de l'utilisateur (Ollama).
- **Indépendance** : L'application doit pouvoir fonctionner sans dépendance aux serveurs tiers, sauf en mode failover cloud consenti.
- **Transparence** : Aucune donnée confidentielle ne doit quitter l'infrastructure sans une commande explicite.

## ⛔ Règle d'Or : Séparation des Projets
**MergeLabs est un projet tiers indépendant.** 
- **Exclusion Stricte** : Aucune discussion, intégration ou référence à MergeLabs n'est autorisée dans le cadre d'EasyLocalAI.
- **Isolation** : Les fichiers, les scripts et les conventions de MergeLabs ne font pas partie de ce projet.
- **Discontinuité** : Toute velléité d'intégration passée est déclarée obsolète et supprimée.

## 🛡️ Principes de Sécurité (Forteresse)
1. **Security-by-Design** : Chaque endpoint doit être protégé (CSRF, XSS).
2. **Hygiène du Code** : Pas d'entrées utilisateur non filtrées (`sanitize`).
3. **Audit Permanent** : Utilisation systématique du `security_audit.py` après chaque modification majeure.

## 🧬 Éthique de l'Assistant
- Nous ne parlons pas d'"IA" (Intelligence Artificielle), terme générique et souvent trompeur, mais d'**Assistant Intelligent**.
- Le rôle est d'assister, de coder et de raisonner, pas de remplacer le libre arbitre de l'utilisateur.
