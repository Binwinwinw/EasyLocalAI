# EasyLocalAI 🚀

EasyLocalAI : un module IA local, embarqué dans votre application web, sans frais d’API cloud. Fait pour n’importe quel site, fonctionne avec Docker + Ollama et un modèle léger (Llama 3.2, Qwen, Mistral, etc.)

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

### ☁️ Lancement en un clic (Codespaces)

Si vous ne pouvez pas installer Docker localement, vous pouvez lancer **EasyLocalAI** directement dans votre navigateur :
1. Cliquez sur le bouton **"Code"** en haut de ce dépôt.
2. Allez dans l'onglet **"Codespaces"** et cliquez sur **"Create codespace on main"**.
3. Attendez que l'environnement se construise. L'interface s'ouvrira automatiquement une fois prête.

## 🌍 Déploiement en Production

Pour un serveur de production (VPS), utilisez le fichier de configuration dédié :
```bash
docker compose -f docker-compose.prod.yml up -d
```
*Note : Pensez à configurer un reverse-proxy (Nginx/Traefik) pour la gestion SSL.*

## 📂 Structure du Projet (Modulaire)
- **`public/`** : Point d'entrée web (Interface, Streaming SSE).
- **`src/`** : Logique métier (Config, Ollama, RAG, Session).
- **`config/`** : Paramètres JSON (`settings`, `profiles`) et Autoloader.
- **`knowledge/`** : Documents locaux pour le RAG.
- **`ollama_data/`** : Volume pour la persistance des modèles.

## ⚖️ Licence
Distribué sous licence MIT. Voir le fichier `LICENSE` pour plus d'informations.
