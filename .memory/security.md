# Règles de Sécurité Critiques 🛡️

Ces règles ne doivent JAMAIS être enfreintes.

- **CSRF Obligatoire** : Toute action modifiant l'état (POST) doit vérifier un token CSRF via `Security::checkCsrf()`.
- **Assainissement (XSS)** : Aucune variable utilisateur ne doit être affichée sans passer par `Security::sanitize()`.
- **Accès Protégé** : Toutes les pages de `public/` (sauf `login.php`) doivent appeler l'initialisation de sécurité via le conteneur.
- **Secrets** : Ne jamais committer `config/settings.json` ou des clés API. Ils sont exclus via `.gitignore`.
- **Validation** : Les identifiants de compétences (skills) sont nettoyés pour ne contenir que `a-z0-9_`.
