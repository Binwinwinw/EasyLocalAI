# EasyLocalAI 🚀

Une solution d'IA locale autonome, légère et facile à déployer, utilisant Docker, Ollama et PHP.

## 🌟 Points Forts
- **100% Privé** : Tout tourne sur votre machine, aucune donnée ne sort.
- **Streaming SSE** : Réponses de l'IA affichées mot par mot en temps réel.
- **RAG Dynamique** : Ajoutez vos propres documents `.txt` pour enrichir les connaissances de l'IA.
- **Personnalisation Totale** : Modifiez le nom et la personnalité de l'IA via l'interface de configuration.
- **Zéro Dépendance Cloud** : Pas besoin d'API OpenAI ou de frais mensuels.

## 🚀 Installation Rapide

1. **Prérequis** : Avoir Docker et Docker Compose installés.
2. **Lancer le projet** :
   ```bash
   docker compose up -d
   ```
3. **Télécharger le modèle** (la première fois) :
   ```bash
   docker exec -it ollama_upstream ollama pull llama3.2
   ```
4. **Accès** : L'interface est disponible sur [http://localhost:8002](http://localhost:8002).

## 🌍 Déploiement en Production

Pour un serveur de production (VPS), utilisez le fichier de configuration dédié :
```bash
docker compose -f docker-compose.prod.yml up -d
```
*Note : Pensez à configurer un reverse-proxy (Nginx/Traefik) pour la gestion SSL.*

## 📂 Structure du Projet (Modulaire)
- `public/` : Point d'entrée Web (DocumentRoot).
- `src/` : Coeur logique du projet (Namespace `EasyLocalAI`).
- `config/` : Paramètres de l'IA et autoloader.
- `knowledge/` : Dossier pour vos documents `.txt` (RAG).
- `models/` : Données des modèles Ollama (volume local).

## ⚖️ Licence
Distribué sous licence MIT. Voir le fichier `LICENSE` pour plus d'informations.
