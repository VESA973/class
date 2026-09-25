# Changelog

Toutes les évolutions notables du site CLASS'AFFAIRE. Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

## [Non publié] — branche `refonte-backend`

### Préparation
- Sauvegarde de la base avant travaux : `storage/app/private/backups/class-20260925-122327.sql.gz`.
- Branche `refonte-backend` créée ; commit « état de départ » avec le travail précédent non commité.
- Filet de sécurité : `tests/Feature/ExistingFeaturesTest.php` vérifie que toutes les pages publiques,
  l'API de réservation, les pages admin et les changements de statut répondent comme avant.
- Tests : la fabrique `UserFactory` crée des comptes actifs (`is_active`), `ExampleTest` utilise une base de test.

## 2026-09-24 — `215b4c1`
- Connexion admin par email + mot de passe, gestion des utilisateurs, photo d'accueil.
- Réservation en ligne avec contrôle des chevauchements, emails, planning admin, nouvelle page d'accueil.
