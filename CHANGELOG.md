# Changelog

Toutes les évolutions notables du site CLASS'AFFAIRE. Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

## [Non publié] — branche `refonte-backend`

### Préparation
- Sauvegarde de la base avant travaux : `storage/app/private/backups/class-20260925-122327.sql.gz`.
- Branche `refonte-backend` créée ; commit « état de départ » avec le travail précédent non commité.
- Filet de sécurité : `tests/Feature/ExistingFeaturesTest.php` vérifie que toutes les pages publiques,
  l'API de réservation, les pages admin et les changements de statut répondent comme avant.
- Tests : la fabrique `UserFactory` crée des comptes actifs (`is_active`), `ExampleTest` utilise une base de test.

### Module 3 — Paramètres : favicon et mode maintenance
- **Paramètres globaux** : nouvelle table `settings` (clé → valeur JSON) et service `App\Services\Settings`,
  lu une seule fois puis gardé en cache (vidé automatiquement à chaque modification). Servira aussi au SEO,
  aux cookies et au SMTP.
- **Mode maintenance** (Admin › Paramètres › Favicon & maintenance) : interrupteur, titre, message, date de
  retour estimée, adresses IP autorisées (CIDR accepté, bouton « ajouter mon IP »), aperçu de la page.
  - Visiteurs : page au design du site, **HTTP 503 + `Retry-After`** (secondes jusqu'au retour prévu, 1 h sinon),
    réponse JSON 503 pour l'API de réservation.
  - Toujours accessibles : le back-office, les administrateurs connectés (avec un bandeau de rappel) et les IP autorisées.
  - Middleware `MaintenanceMode` ajouté au groupe `web` (`bootstrap/app.php`).
- **Favicon** : envoi d'un PNG (192×192 minimum, 512×512 conseillé ; les images non carrées sont centrées) ou d'un
  SVG si Imagick est installé (SVG contenant du code refusé). Génération : `favicon.ico` (16+32+48), PNG 16 et 32,
  `apple-touch-icon` 180 (fond sombre), Android 192 et 512, `/site.webmanifest`. Balises injectées dans le `<head>`
  de toutes les pages (`partials/favicon.blade.php`) ; retour possible à l'icône par défaut. Le fichier d'origine
  `public/favicon.ico` n'est pas modifié.
- Tableau de bord : la carte « Mode maintenance » reflète l'état réel.
- Tests : `ParametersTest` (9 tests). 35 tests au total.

### Module 2 — Gestion des véhicules en deux colonnes
- `/admin/vehicles` devient un gestionnaire (React + shadcn/ui) : **liste à gauche** (miniature, nom, catégorie,
  prix, statut « Visible / Masqué », état « Libre / En location », pictogramme 3D) avec **recherche**, **filtres**
  (catégorie, statut, disponibilité) et **tri** (nom, prix, catégorie, dernières modifications) ;
  **fiche à droite** éditable sur place (tous les champs existants, photo, modèle 3D, vidéo, description).
- Un clic sur un véhicule met à jour la fiche sans recharger ; le véhicule ouvert est gardé dans l'URL (`?vehicle=`).
- Protection des modifications non enregistrées (dialogue au changement de véhicule, alerte à la fermeture de l'onglet).
- Création et suppression depuis le gestionnaire (confirmation, rappel qu'on peut plutôt masquer le véhicule).
- Mobile : la liste s'affiche seule, la fiche s'ouvre en plein écran avec « Retour à la liste ».
- Routes inchangées : `store`, `update` et `destroy` répondent aussi en JSON quand le gestionnaire les appelle ;
  les formulaires classiques (`/admin/vehicles/create`, `/edit`) fonctionnent toujours et redirigent comme avant.
- Nouvelle route `GET /admin/vehicles/data` (liste en une seule requête SQL, sans N+1).
- `admin.css` : les règles sur les balises (`input`, `label`, `table`…) passent dans une couche CSS
  `admin-base` de faible priorité, pour ne pas écraser les composants React. Aucun changement visuel ailleurs.
- Composants shadcn ajoutés : `switch`, `textarea`.
- Tests : `AdminVehicleManagerTest` (6 tests : liste JSON sans N+1, création/modification/suppression JSON,
  erreurs par champ, envoi photo + modèle 3D, formulaires classiques, page). 26 tests au total.

### Module 1 — Refonte visuelle du back-office
- `public/css/admin.css` réécrit autour de **design tokens** (variables CSS en tête de fichier) : même identité
  que le site public (fond zinc sombre, accent blanc argenté, police Inter, coins arrondis). Tous les noms de
  classes existants sont conservés : aucune vue admin n'a eu besoin d'être réécrite.
- Nouveau layout `admin/layout.blade.php` : menu latéral par sections (Tableau de bord, Véhicules,
  Demandes & Devis, Communication, Site, Paramètres), lien actif, compteur des réservations en attente,
  modules à venir signalés « Bientôt », bloc utilisateur, `noindex` sur l'admin.
- Responsive : sur tablette et mobile le menu devient un tiroir (bouton, voile, touche Échap).
- **Tableau de bord** sur `/admin` (remplace la redirection vers les véhicules, même URL et même nom de route) :
  véhicules, demandes en attente, départs sous 7 jours, état du mode maintenance, demandes récentes, prochains départs.
- Statuts des réservations en français avec couleurs (`Reservation::STATUS_LABELS`), destination et passagers
  sur la fiche réservation.
- Pagination de l'admin au style du back-office (`admin/partials/pagination.blade.php`).
- Page de connexion et planning aux couleurs sobres du site.
- Tests : +1 test du tableau de bord (20 tests au total).

## 2026-09-24 — `215b4c1`
- Connexion admin par email + mot de passe, gestion des utilisateurs, photo d'accueil.
- Réservation en ligne avec contrôle des chevauchements, emails, planning admin, nouvelle page d'accueil.
