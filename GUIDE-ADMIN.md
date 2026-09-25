# Guide de l’administration — CLASS’AFFAIRE

Adresse : **/admin** (connexion par email et mot de passe). Premier compte : `php artisan admin:create-user`.

## Tableau de bord
Chiffres clés (véhicules, demandes en attente, devis en attente, départs sous 7 jours, mode maintenance),
demandes récentes et prochains départs. Chaque carte est cliquable.

## Véhicules
- **Liste à gauche** : recherche, filtres (catégorie, visible/masqué, libre/en location), tri. **Fiche à droite** :
  cliquez sur un véhicule pour le modifier sans quitter la page, puis « Enregistrer ».
- « Visible et réservable » décoché = véhicule masqué du site (préférable à la suppression, qui efface aussi ses réservations).
- Photo : envoyée, elle est automatiquement redimensionnée et convertie en WebP. Modèle 3D : fichier `.glb` (50 Mo max).
- Prestations : menu « Prestations ».

## Demandes & devis
- **Demandes** : chaque réservation faite sur le site. Deux statuts :
  - *Réservation* (En attente / Confirmée / Annulée / Terminée) : pilote le planning et les disponibilités ;
  - *Suivi* (Nouvelle / En cours / Devis envoyé / Accepté / Refusé / Archivée) : avance tout seul avec les devis.
- **Créer un devis** : ouvrez la demande › « + Créer un devis » (pré-rempli). Ajustez lignes, TVA, remise, validité,
  conditions › « Enregistrer » › « Aperçu PDF » › « Envoyer le devis » (PDF joint) › « Marquer accepté / refusé ».
- **Devis › Réglages** : TVA et validité par défaut, ligne type, conditions, informations de l’entreprise (PDF).
  L’**envoi automatique** des devis est désactivé : ne l’activez qu’après avoir vérifié tarifs et conditions.
- **Planning** : calendrier mois/semaine/jour, filtre par véhicule ; cliquez sur une réservation pour la confirmer ou l’annuler.
- L’historique de chaque demande (création, devis, envois, statuts) est visible sur sa fiche.

## Emails
- **Configuration** : réglages du serveur (`.env`, par défaut) ou serveur SMTP saisi dans l’admin (mot de passe chiffré).
  Renseignez l’adresse d’expédition et l’adresse qui reçoit les nouvelles demandes, puis « Envoyer un email de test ».
- **Modèles** : accusé de réception client, notification admin, envoi de devis. Variables cliquables (`{nom_client}`…), aperçu en direct.
- **Historique** : chaque email envoyé, avec le message d’erreur en cas d’échec.

## Site
- **Photo d’accueil**, **Logo du site** : envoi d’image.
- **SEO** : titre, description, image de partage, canonical, noindex et adresse de chaque page, véhicule et page légale
  (aperçu Google). Réglages : valeurs par défaut, données de l’entreprise pour Google, `robots.txt`.
  **Redirections** : une ancienne adresse vers une nouvelle (celles des véhicules et pages légales sont créées automatiquement).
  `sitemap.xml` est généré automatiquement.
- **Pages légales** : mentions légales, CGU, confidentialité, CGL. Remplissez d’abord l’onglet
  **« Informations de l’entreprise »** (les champs manquants sont surlignés « [À compléter] » sur le site).
  Éditeur de texte riche, historique des versions (voir / restaurer), liens automatiques en pied de page.
- **Cookies** : textes, couleurs, catégories ; collez vos codes de suivi (Google Analytics, Meta…) dans la catégorie
  correspondante : ils ne se chargent qu’après accord du visiteur. « Redemander le consentement à tous » si votre
  politique change. Registre des choix et statistiques.

## Paramètres
- **Coordonnées & WhatsApp** : pays / indicatif (Guyane +594 par défaut), téléphone, email et adresse affichés partout (en-tête, pied de page, Contact,
  réservation, emails, devis, pages légales, SEO). Bouton WhatsApp : cocher « Afficher », saisir le numéro
  (06 94… ou +594 694…, l’indicatif est ajouté automatiquement), message pré-rempli ; lien « Tester » après enregistrement. Carte « Pied de page » : titre et phrase de
  présentation en bas de toutes les pages.
- **Favicon & maintenance** : favicon (PNG 512×512, toutes les tailles générées) ; mode maintenance (page 503,
  message, date de retour, IP autorisées, aperçu). Vous continuez à voir le site une fois connecté.
- **Utilisateurs** : comptes d’accès à l’administration (activer / désactiver).

---

## Mise en production (serveur final)

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

- `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `APP_NAME="CLASS'AFFAIRE"`, `SESSION_SECURE_COOKIE=true`.
- Tâche planifiée (nettoyage des preuves de consentement de plus de 13 mois) — dans la crontab de l’utilisateur du serveur web :
  `* * * * * cd /chemin/du/site && php artisan schedule:run >> /dev/null 2>&1`
- Si l’option « Envoyer via la file d’attente » est activée : un worker `php artisan queue:work` doit tourner en permanence.
- Photos déjà envoyées avant la compression automatique : `sudo -u www-data php artisan images:optimize`
- PHP (`php.ini` fpm et apache2) : `upload_max_filesize = 64M`, `post_max_size = 70M` (modèles 3D), puis redémarrer PHP-FPM et Apache.
- Favicon SVG : `sudo apt install php8.4-imagick librsvg2-bin` (optionnel).
- Serveur nginx : supprimer `public/robots.txt` (sous Apache, `.htaccess` le fait déjà passer par l’application).
- ⚠️ Ne lancez jamais `php artisan test` avec la configuration en cache (`config:cache`) : utilisez `php artisan config:clear` avant.

## À compléter par vous
1. **Pages légales › Informations de l’entreprise** : raison sociale, forme juridique, capital, RCS, SIRET, TVA,
   siège, directeur de publication, hébergeur (nom, adresse, téléphone), contact RGPD, médiateur de la consommation,
   durées de conservation. Puis les passages « [À compléter] » des CGL (caution, conducteur, assurance, annulation…)
   et le prestataire d’emails dans la politique de confidentialité. Faites relire les pages par un professionnel.
2. **Emails › Configuration** : adresse et nom d’expédition, adresse qui reçoit les demandes, test d’envoi.
3. **Devis › Réglages** : vérifier TVA, validité, conditions, IBAN.
4. **SEO** : image de partage par défaut, réseaux sociaux, vérifier les données de l’entreprise.
5. **Véhicules** : corriger les places si besoin, remplacer les photos cassées (Lamborghini Urus), ajouter les modèles 3D.
6. **Coordonnées & WhatsApp** : vérifier le téléphone, saisir le numéro WhatsApp et activer le bouton.
   Politique de confidentialité : mentionner que les échanges WhatsApp passent par WhatsApp (Meta).
7. **Favicon** : envoyer votre logo carré (PNG 512×512).
8. Si vous ajoutez des outils de mesure d’audience ou de publicité : les coller dans **Cookies**, jamais directement dans le code.
