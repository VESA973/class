<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\Reservation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Base clients : rattachement automatique des demandes et devis, detection et fusion des doublons.
 *
 * Regle pour le site : le client n'est JAMAIS bloque. Meme email qu'un client existant => la demande est
 * rattachee a ce client (ses champs vides sont completes, rien n'est ecrase). Sinon un nouveau client est
 * cree ; s'il ressemble a un autre (meme telephone, meme nom...), il apparait dans Admin > Clients > Doublons
 * ou l'administrateur decide de fusionner.
 */
class CustomerDirectory
{
    /** Champs recopies d'un doublon vers le client conserve lorsqu'ils y sont vides. */
    private const MERGEABLE = [
        'first_name', 'last_name', 'company_name', 'siret', 'email', 'phone_mobile', 'phone_landline',
        'address', 'postal_code', 'city', 'country', 'birth_date', 'license_number', 'license_issued_at',
    ];

    /** Numero normalise (chiffres au format international) pour comparer « 06 94… », « +594 694… »… */
    public static function phoneKey(?string $phone): ?string
    {
        if (! $phone || trim($phone) === '') {
            return null;
        }

        $key = ContactSettings::normalizeWhatsapp($phone, (string) config('home.contact.country_code', ContactSettings::DEFAULT_COUNTRY_CODE));

        return strlen($key) >= 6 ? substr($key, 0, 20) : null;
    }

    /**
     * Portable ou fixe, d'apres le numero au format international : France 6/7, Guyane 694, Guadeloupe 690-691,
     * Martinique 696-697, Reunion/Mayotte 692-693/639. Pays inconnu : considere comme portable.
     */
    public static function isMobile(?string $phone): bool
    {
        $key = self::phoneKey($phone);

        if (! $key) {
            return true;
        }

        foreach (['594' => '/^694/', '590' => '/^69[01]/', '596' => '/^69[67]/', '262' => '/^(69[23]|639)/', '33' => '/^[67]/'] as $code => $pattern) {
            if (str_starts_with($key, (string) $code)) {
                return (bool) preg_match($pattern, substr($key, strlen((string) $code)));
            }
        }

        return true;
    }

    /** « Jean-Marc Dupont » -> ['Jean-Marc', 'Dupont'] (un seul mot : nom de famille). */
    public static function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return count($parts) === 2 ? [$parts[0], $parts[1]] : [null, $parts[0] ?? null];
    }

    /**
     * Client d'une nouvelle demande du site (ou d'un devis) : rattache par email, sinon cree.
     *
     * @param  array{first_name?: ?string, last_name?: ?string, company_name?: ?string, email?: ?string, phone?: ?string, address?: ?string}  $data
     */
    public function resolve(array $data, string $source = 'site'): Customer
    {
        $email = isset($data['email']) && trim((string) $data['email']) !== '' ? mb_strtolower(trim($data['email'])) : null;
        $phone = isset($data['phone']) ? trim((string) $data['phone']) : null;
        $phoneField = $phone && ! self::isMobile($phone) ? 'phone_landline' : 'phone_mobile';

        $attributes = array_filter([
            'first_name' => $this->clean($data['first_name'] ?? null),
            'last_name' => $this->clean($data['last_name'] ?? null),
            'company_name' => $this->clean($data['company_name'] ?? null),
            'email' => $email,
            $phoneField => $phone ?: null,
            'address' => $this->clean($data['address'] ?? null),
        ], fn ($value) => $value !== null && $value !== '');

        $existing = $email ? Customer::query()->where('email', $email)->oldest('id')->first() : null;

        if ($existing) {
            // Complete seulement les champs vides : les donnees saisies par l'admin ne sont jamais ecrasees.
            $existing->fill(array_filter($attributes, fn ($value, $key) => blank($existing->{$key}), ARRAY_FILTER_USE_BOTH));

            // Autre numero que ceux connus : on le garde dans le champ libre s'il reste de la place.
            $key = self::phoneKey($phone);
            if ($key && ! in_array($key, [$existing->phone_mobile_key, $existing->phone_landline_key], true)) {
                if (blank($existing->phone_mobile)) {
                    $existing->phone_mobile = $phone;
                } elseif (blank($existing->phone_landline)) {
                    $existing->phone_landline = $phone;
                } else {
                    $existing->notes = trim(($existing->notes ?? '')."\nAutre numéro indiqué le ".now(config('app.local_timezone'))->format('d/m/Y').' : '.$phone);
                }
            }

            $existing->save();

            return $existing;
        }

        return Customer::create($attributes + [
            'type' => filled($attributes['company_name'] ?? null) ? 'professionnel' : 'particulier',
            'source' => $source,
        ]);
    }

    /** Client d'une demande du site (appele a la creation de la demande). */
    public function attachReservation(Reservation $reservation, array $name = []): Customer
    {
        [$first, $last] = isset($name['first_name']) || isset($name['last_name'])
            ? [$name['first_name'] ?? null, $name['last_name'] ?? null]
            : self::splitName((string) $reservation->customer_name);

        $customer = $this->resolve([
            'first_name' => $first,
            'last_name' => $last,
            'company_name' => $name['company_name'] ?? null,
            'email' => $reservation->customer_email,
            'phone' => $reservation->customer_phone,
        ], 'site');

        $reservation->forceFill(['customer_id' => $customer->id])->saveQuietly();

        return $customer;
    }

    /** Clients qui ressemblent a celui-ci (meme email, meme telephone, meme nom ou meme societe). @return Collection<int, Customer> */
    public function similarTo(Customer $customer): Collection
    {
        $keys = array_filter([$customer->phone_mobile_key, $customer->phone_landline_key]);

        return Customer::query()
            ->whereKeyNot($customer->getKey())
            ->where(function ($query) use ($customer, $keys) {
                $query->whereRaw('1 = 0');

                if ($customer->email) {
                    $query->orWhere('email', $customer->email);
                }

                if ($keys) {
                    $query->orWhereIn('phone_mobile_key', $keys)->orWhereIn('phone_landline_key', $keys);
                }

                if ($customer->first_name && $customer->last_name) {
                    $query->orWhere(fn ($same) => $same->whereRaw('LOWER(first_name) = ?', [mb_strtolower($customer->first_name)])
                        ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($customer->last_name)]));
                }

                if ($customer->company_name) {
                    $query->orWhereRaw('LOWER(company_name) = ?', [mb_strtolower($customer->company_name)]);
                }
            })
            ->limit(20)
            ->get();
    }

    /**
     * Groupes de doublons probables. Deux fiches sont liees si elles partagent un email, un numero de
     * telephone (portable ou fixe, quel que soit le format), le meme prenom + nom, ou la meme raison sociale.
     *
     * @return list<array{customers: Collection<int, Customer>, reasons: list<string>}>
     */
    public function duplicateGroups(): array
    {
        $customers = Customer::query()->withCount(['reservations', 'quotes'])->get()->keyBy('id');
        $parent = [];
        $reasons = [];

        $find = function (int $id) use (&$parent, &$find): int {
            if (! isset($parent[$id]) || $parent[$id] === $id) {
                return $parent[$id] = $id;
            }

            return $parent[$id] = $find($parent[$id]);
        };

        $buckets = [];
        foreach ($customers as $customer) {
            $signatures = [];
            if ($customer->email) {
                $signatures['même email'][] = $customer->email;
            }
            foreach (array_filter([$customer->phone_mobile_key, $customer->phone_landline_key]) as $key) {
                $signatures['même téléphone'][] = $key;
            }
            if ($customer->first_name && $customer->last_name) {
                $signatures['même nom'][] = Str::lower(Str::ascii($customer->first_name.' '.$customer->last_name));
            }
            if ($customer->company_name) {
                $signatures['même raison sociale'][] = Str::lower(Str::ascii($customer->company_name));
            }

            foreach ($signatures as $reason => $values) {
                foreach (array_unique($values) as $value) {
                    $buckets[$reason."\0".$value][] = $customer->id;
                }
            }
        }

        foreach ($buckets as $bucket => $ids) {
            if (count($ids) < 2) {
                continue;
            }

            $reason = strstr($bucket, "\0", true);
            foreach ($ids as $id) {
                $parent[$find($id)] = $find($ids[0]);
            }
            $reasons[$ids[0]][] = $reason;
        }

        $groups = [];
        foreach (array_keys($parent) as $id) {
            $groups[$find($id)][] = $id;
        }

        $result = [];
        foreach ($groups as $root => $ids) {
            if (count($ids) < 2) {
                continue;
            }

            $groupReasons = [];
            foreach ($ids as $id) {
                array_push($groupReasons, ...($reasons[$id] ?? []));
            }

            $result[] = [
                'customers' => collect($ids)->sort()->map(fn ($id) => $customers[$id])->values(),
                'reasons' => array_values(array_unique($groupReasons)),
            ];
        }

        return $result;
    }

    /** Nombre de fiches concernees par un doublon probable (badge du menu). */
    public function duplicateCount(): int
    {
        return array_sum(array_map(fn ($group) => $group['customers']->count(), $this->duplicateGroups()));
    }

    /**
     * Fusionne des doublons dans la fiche conservee : champs vides completes, notes regroupees, demandes et
     * devis rattaches ; les doublons sont archives (suppression douce, lien vers la fiche conservee).
     *
     * @param  iterable<Customer>  $duplicates
     */
    public function merge(Customer $primary, iterable $duplicates): Customer
    {
        return DB::transaction(function () use ($primary, $duplicates) {
            foreach ($duplicates as $duplicate) {
                if ($duplicate->is($primary)) {
                    continue;
                }

                foreach (self::MERGEABLE as $field) {
                    if (blank($primary->{$field}) && filled($duplicate->{$field})) {
                        $primary->{$field} = $duplicate->{$field};
                    }
                }

                if (filled($duplicate->notes)) {
                    $primary->notes = trim(($primary->notes ?? '')."\n".$duplicate->notes);
                }

                Reservation::query()->where('customer_id', $duplicate->id)->update(['customer_id' => $primary->id]);
                Quote::query()->where('customer_id', $duplicate->id)->update(['customer_id' => $primary->id]);

                $duplicate->forceFill(['merged_into_id' => $primary->id])->save();
                $duplicate->delete();
            }

            if ($primary->company_name && $primary->type === 'particulier') {
                $primary->type = 'professionnel';
            }

            $primary->save();

            return $primary;
        });
    }

    /**
     * Reprise de l'existant : cree les fiches des anciennes demandes et des devis sans client (idempotent).
     *
     * @return array{reservations: int, quotes: int}
     */
    public function syncExisting(): array
    {
        $counts = ['reservations' => 0, 'quotes' => 0];

        Reservation::query()->whereNull('customer_id')->orderBy('id')->each(function (Reservation $reservation) use (&$counts) {
            $customer = $this->attachReservation($reservation);
            $customer->wasRecentlyCreated && $customer->update(['source' => 'import']);
            $counts['reservations']++;
        });

        Quote::query()->whereNull('customer_id')->with('reservation')->orderBy('id')->each(function (Quote $quote) use (&$counts) {
            $customerId = $quote->reservation?->customer_id;

            if (! $customerId && ($quote->customer_email || $quote->customer_phone)) {
                [$first, $last] = self::splitName((string) $quote->customer_name);
                $customerId = $this->resolve([
                    'first_name' => $first, 'last_name' => $last, 'email' => $quote->customer_email,
                    'phone' => $quote->customer_phone, 'address' => $quote->customer_address,
                ], 'import')->id;
            }

            if ($customerId) {
                $quote->forceFill(['customer_id' => $customerId])->saveQuietly();
                $counts['quotes']++;
            }
        });

        return $counts;
    }

    private function clean(?string $value): ?string
    {
        $value = $value !== null ? trim(preg_replace('/\s+/', ' ', $value)) : null;

        return $value === '' ? null : $value;
    }
}
