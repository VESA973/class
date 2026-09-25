<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Genere les icones du site a partir d'une image carree (PNG, ou SVG si Imagick sait le lire) :
 * favicon-16/32, apple-touch-icon 180, android-chrome 192/512 et favicon.ico (16+32+48).
 * Fichiers ecrits dans storage/app/public/favicon (servis sous /storage/favicon).
 */
class FaviconGenerator
{
    public const DIRECTORY = 'favicon';

    /** Nom du fichier => [taille, fond opaque ?] (Apple remplit la transparence en noir : on met le fond du site). */
    public const SIZES = [
        'favicon-16x16.png' => [16, false],
        'favicon-32x32.png' => [32, false],
        'apple-touch-icon.png' => [180, true],
        'android-chrome-192x192.png' => [192, false],
        'android-chrome-512x512.png' => [512, false],
    ];

    public const BACKGROUND = [10, 10, 10]; // #0a0a0a, fond du site

    public static function svgSupported(): bool
    {
        return class_exists(\Imagick::class) && count(\Imagick::queryFormats('SVG')) > 0;
    }

    /** @return array{has_svg: bool} */
    public function generate(UploadedFile $file): array
    {
        $isSvg = strtolower($file->getClientOriginalExtension()) === 'svg' || $file->getMimeType() === 'image/svg+xml';
        $contents = (string) file_get_contents($file->getRealPath());

        if ($isSvg) {
            $this->assertSafeSvg($contents);
            $source = $this->rasterizeSvg($contents, 512);
        } else {
            $source = @imagecreatefromstring($contents);
        }

        if (! $source) {
            throw new RuntimeException("L'image n'a pas pu être lue.");
        }

        $square = $this->toSquare($source);
        $disk = Storage::disk('public');
        $disk->deleteDirectory(self::DIRECTORY);

        foreach (self::SIZES as $name => [$size, $opaque]) {
            $disk->put(self::DIRECTORY.'/'.$name, $this->png($square, $size, $opaque));
        }

        $disk->put(self::DIRECTORY.'/favicon.ico', $this->ico($square, [16, 32, 48]));

        if ($isSvg) {
            $disk->put(self::DIRECTORY.'/icon.svg', $contents);
        }

        imagedestroy($square);

        return ['has_svg' => $isSvg];
    }

    public function delete(): void
    {
        Storage::disk('public')->deleteDirectory(self::DIRECTORY);
    }

    /** Refuse les SVG contenant du code (le fichier est servi depuis le meme domaine que le site). */
    private function assertSafeSvg(string $svg): void
    {
        if (preg_match('/<script|<foreignObject|\son\w+\s*=|javascript:|<!ENTITY/i', $svg)) {
            throw new RuntimeException('Ce SVG contient du code ou des éléments interdits (script, événements).');
        }
    }

    /** @return \GdImage */
    private function rasterizeSvg(string $svg, int $size)
    {
        if (! self::svgSupported()) {
            throw new RuntimeException('Le serveur ne sait pas encore convertir le SVG (Imagick absent) : envoyez un PNG 512×512.');
        }

        $imagick = new \Imagick();
        $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
        $imagick->setResolution(384, 384);
        $imagick->readImageBlob($svg);
        $imagick->setImageFormat('png32');
        $imagick->thumbnailImage($size, $size, true, true);
        $png = $imagick->getImageBlob();
        $imagick->clear();

        return imagecreatefromstring($png);
    }

    /**
     * Centre l'image sur un carre transparent (une image non carree n'est pas deformee).
     *
     * @param  \GdImage  $source
     * @return \GdImage
     */
    private function toSquare($source)
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $side = max($width, $height);

        $square = $this->canvas($side, false);
        imagecopy($square, $source, intdiv($side - $width, 2), intdiv($side - $height, 2), 0, 0, $width, $height);
        imagedestroy($source);

        return $square;
    }

    /** @return \GdImage */
    private function canvas(int $size, bool $opaque)
    {
        $image = imagecreatetruecolor($size, $size);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $color = $opaque
            ? imagecolorallocate($image, ...self::BACKGROUND)
            : imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $color);
        imagealphablending($image, true);

        return $image;
    }

    /** @param \GdImage $square */
    private function png($square, int $size, bool $opaque): string
    {
        $target = $this->canvas($size, $opaque);
        $side = imagesx($square);
        imagecopyresampled($target, $square, 0, 0, 0, 0, $size, $size, $side, $side);
        imagealphablending($target, false);
        imagesavealpha($target, true);

        ob_start();
        imagepng($target, null, 9);
        imagedestroy($target);

        return (string) ob_get_clean();
    }

    /**
     * Fichier .ico contenant plusieurs tailles au format PNG (accepte par tous les navigateurs actuels).
     *
     * @param  \GdImage  $square
     * @param  list<int>  $sizes
     */
    private function ico($square, array $sizes): string
    {
        $images = array_map(fn (int $size) => $this->png($square, $size, false), $sizes);
        $header = pack('vvv', 0, 1, count($images));
        $directory = '';
        $offset = 6 + 16 * count($images);

        foreach ($images as $index => $data) {
            $size = $sizes[$index];
            $directory .= pack('CCCCvvVV', $size >= 256 ? 0 : $size, $size >= 256 ? 0 : $size, 0, 0, 1, 32, strlen($data), $offset);
            $offset += strlen($data);
        }

        return $header.$directory.implode('', $images);
    }
}
