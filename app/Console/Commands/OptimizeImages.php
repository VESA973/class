<?php

namespace App\Console\Commands;

use App\Models\Prestation;
use App\Models\SiteSetting;
use App\Models\Vehicle;
use App\Services\ImageOptimizer;
use Illuminate\Console\Command;

/** Optimise les photos deja envoyees avant la mise en place de la compression automatique. */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize';

    protected $description = 'Redimensionne et compresse les photos deja envoyees (et cree leur version WebP)';

    public function handle(ImageOptimizer $optimizer): int
    {
        $paths = array_filter(array_merge(
            Vehicle::query()->pluck('image_path')->all(),
            Prestation::query()->pluck('image_path')->all(),
            [SiteSetting::current()->hero_image_path],
        ));

        foreach ($paths as $path) {
            $optimizer->optimize($path, str_starts_with($path, 'site/') ? 2400 : 1600);
            $this->line("Optimisée : {$path}");
        }

        $this->info(count($paths).' image(s) traitée(s).');

        return self::SUCCESS;
    }
}
