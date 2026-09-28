<?php

namespace App\Console\Commands;

use App\Services\CustomerDirectory;
use Illuminate\Console\Command;

/** Reprise de l'existant : cree les fiches clients des anciennes demandes et des devis (sans risque de doublon, relancable). */
class SyncCustomers extends Command
{
    protected $signature = 'customers:sync';

    protected $description = 'Crée les fiches clients des demandes et devis qui n’en ont pas encore';

    public function handle(CustomerDirectory $directory): int
    {
        $counts = $directory->syncExisting();

        $this->info("Demandes rattachées : {$counts['reservations']} · Devis rattachés : {$counts['quotes']}");
        $this->line('Vérifiez ensuite Admin > Clients > Doublons.');

        return self::SUCCESS;
    }
}
