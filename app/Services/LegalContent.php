<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Pages legales : informations de l'entreprise (variables), nettoyage du HTML saisi
 * et rendu public (variables remplacees, champs manquants signales « À compléter »).
 */
class LegalContent
{
    /** Variable => [libelle, cle de reglage]. Les champs partages avec les devis viennent de « quotes.company ». */
    public const VARIABLES = [
        'raison_sociale' => ['Raison sociale', 'company.name'],
        'forme_juridique' => ['Forme juridique', 'company.legal_form'],
        'capital' => ['Capital social', 'legal.capital'],
        'rcs' => ['Ville du RCS', 'legal.rcs'],
        'siret' => ['SIRET', 'company.siret'],
        'tva_intracom' => ['N° de TVA intracommunautaire', 'company.vat_number'],
        'adresse_siege' => ['Adresse du siège', 'company.address'],
        'telephone' => ['Téléphone', 'company.phone'],
        'email' => ['Email de contact', 'company.email'],
        'directeur_publication' => ['Directeur de la publication', 'legal.publication_director'],
        'hebergeur_nom' => ['Nom de l’hébergeur', 'legal.host_name'],
        'hebergeur_adresse' => ['Adresse de l’hébergeur', 'legal.host_address'],
        'hebergeur_telephone' => ['Téléphone de l’hébergeur', 'legal.host_phone'],
        'contact_rgpd' => ['Contact données personnelles (DPO)', 'legal.dpo_contact'],
        'mediateur_nom' => ['Médiateur de la consommation', 'legal.mediator_name'],
        'mediateur_site' => ['Site du médiateur', 'legal.mediator_url'],
        'duree_conservation_demandes' => ['Conservation des demandes', 'legal.retention_requests'],
        'duree_conservation_devis' => ['Conservation des devis', 'legal.retention_quotes'],
        'duree_conservation_emails' => ['Conservation de l’historique des emails', 'legal.retention_emails'],
        'duree_conservation_consentements' => ['Conservation des preuves de consentement', 'legal.retention_consents'],
    ];

    /** Durees de conservation proposees par defaut (modifiables). */
    public const RETENTION_DEFAULTS = [
        'retention_requests' => '3 ans à compter du dernier contact',
        'retention_quotes' => '3 ans pour un devis non accepté ; 10 ans pour les pièces comptables liées à une location',
        'retention_emails' => '1 an',
        'retention_consents' => '13 mois',
    ];

    public function __construct(private readonly Settings $settings)
    {
    }

    /** @return array<string, string> valeur de chaque variable ('' si non renseignee) */
    public function values(): array
    {
        $company = (array) $this->settings->get('quotes.company', []);
        $legal = (array) $this->settings->get('legal.info', []) + self::RETENTION_DEFAULTS;
        $values = [];

        foreach (self::VARIABLES as $variable => [, $source]) {
            [$group, $key] = explode('.', $source, 2);
            $values[$variable] = trim((string) ($group === 'company' ? ($company[$key] ?? '') : ($legal[$key] ?? '')));
        }

        $values['telephone'] = $values['telephone'] ?: config('home.contact.phone');
        $values['email'] = $values['email'] ?: config('home.contact.email');

        return $values;
    }

    /** @return list<string> libelles des informations manquantes */
    public function missing(): array
    {
        $missing = [];
        foreach ($this->values() as $variable => $value) {
            if ($value === '') {
                $missing[] = self::VARIABLES[$variable][0];
            }
        }

        return $missing;
    }

    /** HTML autorise : titres, paragraphes, listes, gras/italique, liens (http, https, mailto, tel). */
    public function sanitize(string $html): string
    {
        $config = (new HtmlSanitizerConfig())
            ->allowElement('h2')->allowElement('h3')->allowElement('h4')->allowElement('p')->allowElement('br')
            ->allowElement('strong')->allowElement('em')->allowElement('u')->allowElement('s')
            ->allowElement('ul')->allowElement('ol')->allowElement('li')->allowElement('blockquote')->allowElement('hr')
            ->allowElement('a', ['href', 'title'])
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->withMaxInputLength(200000);

        return (new HtmlSanitizer($config))->sanitize($html);
    }

    /** Rendu public : variables remplacees (echappees), manquants et « [À compléter …] » surlignes. */
    public function render(string $html): string
    {
        $values = $this->values() + ['site_url' => url('/')];

        $html = preg_replace_callback('/\{([a-z_]+)\}/', function (array $match) use ($values) {
            if (! array_key_exists($match[1], $values)) {
                return $match[0];
            }

            return $values[$match[1]] !== ''
                ? e($values[$match[1]])
                : '[À compléter : '.e(self::VARIABLES[$match[1]][0] ?? $match[1]).']';
        }, $html);

        return preg_replace('/\[À compléter[^\]]*\]/u', '<mark class="legal-todo">$0</mark>', $html);
    }
}
