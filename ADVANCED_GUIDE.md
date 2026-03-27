# EasyLocalAI - Guide Utilisateur Avancé 🚀

Ce guide vous explique comment tirer le meilleur parti de votre nouvelle plateforme IA évolutive.

## 1. Moteur d'Outils (AI Agency) 🛠️
L'IA peut maintenant utiliser des scripts PHP pour agir. Les outils sont définis dans `src/Tools/`.
- **Heure locale** : L'IA utilise `ClockTool` pour vous répondre précisément.
- **Recherche Mémoire** : L'IA peut fouiller dans vos souvenirs enregistrés via `MemoryTool`.

**Comment ajouter un outil ?**
Créez une classe dans `src/Tools/Implementations/` qui implémente `ToolInterface` et enregistrez-la dans `config/bootstrap.php`.

## 2. RAG Vectoriel (Recherche Sémantique) 🧠
Contrairement à la V1, la recherche ne se base plus sur des mots-clés exacts, mais sur le **sens**.
- Modèle utilisé : `nomic-embed-text`.
- Stockage : `knowledge/vectors.json`.

**Astuce** : Pour réinitialiser la connaissance, supprimez simplement le fichier `vectors.json`.

## 3. Gestion des Modèles 🌐
Vous pouvez maintenant "Pull" des modèles directement depuis l'interface "Modèles".
- Les modèles sont installés sur votre instance Ollama locale.
- Vous pouvez changer le modèle par défaut d'un simple clic.

## 4. Tests et Qualité 🧪
Le projet est prêt pour le développement professionnel.
- **Tests** : Exécutez `vendor/bin/phpunit` pour vérifier que tout fonctionne.
- **CI/CD** : Chaque push vers GitHub déclenche automatiquement les tests via GitHub Actions.

---
*EasyLocalAI v2 - Développé avec excellence.*
