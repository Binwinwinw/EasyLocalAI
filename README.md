# LocaLAI – Module IA local, sans frais d’API

**LocaLAI** est un module IA local, embarqué dans votre application web, qui fonctionne avec Docker + Ollama et un modèle léger (ex. `qwen3:4b`).  
Il est conçu pour n’importe quel site (école, mairie, entreprise) qui veut un assistant IA local, sans dépendre d’une API cloud payante.

---

## 🚀 Fonctionnalités

- IA locale grâce à Ollama et un modèle léger.
- Backend PHP simple pour intégrer l’IA dans n’importe quel site.
- Docker & Docker Compose pour dev et prod.
- Prompt système en français (assistant pédagogique) et mémoire courte côté PHP.

---

## 🧱 Architecture (dev)

Navigateur
↓
[Site / App] → [Backend PHP] → [Docker : Ollama + modèle]

text

- Le backend PHP appelle l’API Ollama locale (`http://ollama:11434/api/generate`).
- Ollama tourne dans un container Docker (`ollama_upstream`).
- Peut être réutilisé pour n’importe quel projet.

---

## 🛠️ Installation rapide (dev)

```bash
git clone https://github.com/Binwinwinw/LocaLAI.git
cd LocaLAI

# Démarre les services
docker-compose up -d

# Dans le container Ollama, télécharge un modèle léger
docker exec -it ollama_upstream ollama pull qwen3:4b
Accès au chat IA :
http://localhost:8000

🚀 Exemple de chat
Une fois lancé, tu peux tester :
http://localhost:8000/?q=Bonjour%20comment%20vas%20tu%3F

🌐 Licence
Licence MIT – tu peux utiliser, modifier et redistribuer ce module, même à des fins commerciales.
```
