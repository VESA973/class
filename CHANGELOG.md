# Changelog

Toutes les évolutions notables du site CLASS'AFFAIRE. Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

## [Non publié] — branche `refonte-backend`

### Préparation
- Sauvegarde de la base avant travaux : `storage/app/private/backups/class-20260925-122327.sql.gz`.
- Branche `refonte-backend` créée ; commit « état de départ » avec le travail précédent non commité.
- Filet de sécurité : `tests/Feature/ExistingFeaturesTest.php` vérifie que toutes les pages publiques,
  l'API de réservation, les pages admin et les changements de statut répondent comme avant.
- Tests : la fabrique `UserFactory` crée des comptes actifs (`is_active`), `ExampleTest` utilise une base de test.

### Module 7 — Pages légales
- 4 pages pré-remplies (tables `legal_pages`, `legal_page_versions`) : **Mentions légales** (LCEN art. 6-III),
  **CGU**, **Politique de confidentialité** (RGPD art. 13, adaptée aux traitements réels du site : réservation,
  devis, emails, services tiers IGN / Bunny Fonts / Unsplash / OpenStreetMap), **Conditions générales de location**
  (dont l'exclusion du droit de rétractation, art. L221-28 12° C. conso., et la médiation de la consommation).
  Modèles dans `database/legal/*.html`, à faire relire par un professionnel.
- **Informations de l'entreprise** (Pages légales › Informations) : raison sociale, forme, capital, RCS, SIRET,
  TVA, siège, directeur de publication, hébergeur, contact RGPD, médiateur, durées de conservation. Insérées
  automatiquement via des variables (`{raison_sociale}`…) ; partagées avec les devis. Les informations manquantes
  et les passages « [À compléter …] » sont surlignés sur le site et signalés dans l'admin.
- **Éditeur de texte riche** (Tiptap) : titres, gras, italique, listes, citation, liens, annuler/rétablir,
  insertion des variables. HTML nettoyé côté serveur (`symfony/html-sanitizer` : scripts, événements, liens
  `javascript:` et images supprimés).
- **Historique des versions** : une version par modification (auteur, date, note), aperçu et restauration.
- Pages publiques `/mentions-legales`, `/cgu`, `/politique-de-confidentialite`, `/conditions-generales-de-location`
  au design du site (sommaire automatique, date de dernière mise à jour, liens vers les autres pages) ;
  **liens automatiques dans le pied de page** ; SEO, adresse modifiable (redirection 301), sitemap.
- Formulaire de réservation : mention d'information RGPD avec lien vers la politique de confidentialité.
- Dépendances : `symfony/html-sanitizer` ^8.1, `@tiptap/react`, `@tiptap/starter-kit`, `@tiptap/pm`, `@tiptap/extension-link`.
- Tests : `LegalModuleTest` (6 tests). 65 tests au total.

### Module 6 — SEO
- Balises sur toutes les pages publiques (`partials/seo-head.blade.php`) : `title`, `meta description`, `robots`
  (index / noindex), `canonical`, Open Graph et Twitter (image de partage). Valeurs par défaut automatiques :
  titres et descriptions des pages centralisés dans `App\Services\Seo::PAGES`, fiche véhicule générée à partir
  du véhicule, image = image de partage par défaut, sinon photo d'accueil.
- **Admin › SEO** : personnalisation par page (Accueil, Véhicules, Prestations, Contact, Réservation) et par
  véhicule — titre, description (compteurs de caractères), image de partage, canonical, noindex — avec **aperçu
  Google en direct**. Adresse (slug) des véhicules modifiable : l'ancienne redirige automatiquement en 301.
- **Données structurées schema.org** : entreprise (`AutoRental` par défaut, adresse, horaires, zones desservies,
  réseaux sociaux) sur toutes les pages ; `Car` (offre au prix par jour) et `BreadcrumbList` sur les fiches
  véhicules. JSON-LD protégé contre l'injection.
- **`/sitemap.xml`** généré automatiquement (pages et véhicules indexables, date de mise à jour) ;
  **`/robots.txt`** éditable dans l'admin (ligne `Sitemap:` ajoutée automatiquement). `public/.htaccess` : une ligne
  ajoutée pour que `robots.txt` passe par l'application ; le fichier `public/robots.txt` d'origine est conservé.
- **Redirections 301/302** (table `redirects`) appliquées avant le routage (middleware `HandleRedirects`,
  lecture en cache), compteur de visites, paramètres conservés ; boucles et redirection de l'admin refusées.
- Tables `seo_metas`, `redirects`. Tests : `SeoModuleTest` (6 tests). 59 tests au total.

### Module 5 — Demandes et devis
- Une **demande** = une réservation faite sur le site. Nouveau champ `reservations.request_status` (suivi commercial :
  Nouvelle, En cours, Devis envoyé, Accepté, Refusé, Archivée), **distinct** du statut de réservation qui continue
  de piloter le planning et les disponibilités. Menu « Demandes » (ex-Réservations) avec filtre par suivi.
- **Devis** (tables `quotes`, `quote_lines`) créés depuis une demande, pré-remplis (client, véhicule, dates, tarif
  du véhicule × jours, TTC converti en HT) : lignes (désignation, quantité, prix HT, TVA 0 / 2,1 / 5,5 / 10 / 20 %),
  remise en % ou en montant, date de validité, conditions, notes internes. Totaux calculés **en centimes** côté
  serveur (remise répartie sur chaque taux de TVA) et affichés en direct pendant la saisie.
- **Numérotation automatique** `DEV-AAAA-NNNN` (verrou anti-doublon).
- **PDF** (barryvdh/laravel-dompdf) : bandeau noir aux couleurs du site, émetteur, client, trajet, lignes, TVA par
  taux, total TTC, conditions, zone « Bon pour accord » ; champs d'entreprise manquants signalés « à compléter ».
  Polices réduites aux caractères utilisés (~33 Ko). Stocké dans `storage/app/private/quotes` (non public).
- **Envoi par email** avec le PDF joint (modèle « Envoi de devis ») ; échec signalé et tracé ; « Marquer accepté /
  refusé » met à jour le suivi. Seuls les brouillons peuvent être supprimés.
- **Historique par demande** (table `reservation_events`) : demande reçue, devis créé / modifié / envoyé / accepté /
  refusé / supprimé, changements de statut (fiche, planning) et de suivi, avec l'auteur.
- **Réglages** (Devis › Réglages) : TVA et validité par défaut, ligne par défaut, prix TTC ou HT, conditions,
  informations de l'entreprise pour le PDF (raison sociale, forme, SIRET, TVA intracom., adresse, IBAN).
- **Envoi automatique préparé mais DÉSACTIVÉ** : événement `ReservationCreated` → `HandleReservationCreated` →
  `QuoteService::handleNewReservation()`, qui ne fait rien tant que l'option (avertissement + confirmation) est
  désactivée. Activée : `QuoteService::generate()` puis `send()`.
- Tableau de bord : carte « Devis en attente » (nombre et montant TTC).
- Dépendance ajoutée : `barryvdh/laravel-dompdf` ^3.1.
- Tests : `QuoteModuleTest` (11 tests, stockage fictif). 53 tests au total.

### Module 4 — Emails
- **Configuration** (Admin › Emails) : choix entre les réglages du `.env` (par défaut, rien ne change) et un
  **serveur SMTP personnalisé** (hôte, port, chiffrement STARTTLS / SSL / aucun, identifiant, mot de passe) ;
  adresse et nom d'expédition ; adresse qui reçoit les nouvelles demandes ; option « Envoyer via la file
  d'attente ». Le mot de passe est **chiffré** (`APP_KEY`) et jamais réaffiché. Bouton **« Envoyer un email de test »**.
- **Modèles éditables** (table `email_templates`) : accusé de réception de demande (client), notification admin
  de nouvelle demande, envoi de devis (prêt pour le module Devis). Objet + contenu en Markdown simple, variables
  cliquables (`{nom_client}`, `{vehicule}`, `{numero_devis}`…), **aperçu en direct**, activation/désactivation.
  Les valeurs des variables sont échappées (aucune injection HTML possible).
- **Mise en page HTML sombre** aux couleurs du site, styles inlinés pour Gmail/Outlook, version texte incluse.
- **Historique** (table `email_logs`) : date, destinataire, objet, type, statut (envoyé / échec / en file
  d'attente), message d'erreur, lien vers la réservation ; recherche et filtre par statut.
- Service unique `App\Services\EmailService` ; les emails de réservation (`ReservationMailer`) utilisent
  désormais les modèles. Un échec d'envoi est enregistré dans l'historique et ne bloque jamais la réservation.
- File d'attente optionnelle : job `SendLoggedEmail` (3 tentatives).
- Anciennes classes `NewReservationAdminMail` / `ReservationReceivedMail` conservées (non supprimées), plus utilisées.
- Tests : `EmailModuleTest` (8 tests). 43 tests au total.

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
