# Skill: RAG Specialist 🧠

Expertise en recherche sémantique et gestion des embeddings.

## 1. Pipeline Vectoriel
- **Embeddings** : Utiliser l'API `/api/embeddings` d'Ollama.
- **Chunking** : Découper les fichiers `.txt` en blocs de ~1000 caractères pour un meilleur focus.
- **Similitude** : Utiliser la similarité cosinus (implémentée dans `VectorStore.php`).

## 2. Maintenance du Savoir
- Nettoyer les vecteurs obsolètes si le fichier source est supprimé.
- Gérer les collisions de noms de fichiers dans la `VectorStore`.
