# Changelog

Toutes les évolutions notables du site CLASS'AFFAIRE. Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

## [Non publié] — branche `refonte-backend`

### Préparation
- Sauvegarde de la base avant travaux : `storage/app/private/backups/class-20260925-122327.sql.gz`.
- Branche `refonte-backend` créée ; commit « état de départ » avec le travail précédent non commité.
- Filet de sécurité : `tests/Feature/ExistingFeaturesTest.php` vérifie que toutes les pages publiques,
  l'API de réservation, les pages admin et les changements de statut répondent comme avant.
- Tests : la fabrique `UserFactory` crée des comptes actifs (`is_active`), `ExampleTest` utilise une base de test.

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
