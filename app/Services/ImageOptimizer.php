<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Photos envoyees depuis l'admin : redimensionnees (largeur maximale), recompressees,
 * et doublees d'une version WebP plus legere. Le format d'origine (JPEG/PNG) reste en secours.
 * En cas d'echec (format non gere), l'image d'origine est conservee telle quelle.
 */
class ImageOptimizer
{
    public const JPEG_QUALITY = 82;

    public const WEBP_QUALITY = 80;

    /** Optimise un fichier du disque "public" et cree son jumeau .webp. Retourne le chemin (inchange). */
    public function optimize(string $path, int $maxWidth = 1920): string
    {
        $disk = Storage::disk('public');

        try {
            $absolute = $disk->path($path);
            $info = @getimagesize($absolute);

            if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                return $path;
            }

            $image = @imagecreatefromstring((string) file_get_contents($absolute));
            if (! $image) {
                return $path;
            }

            if ($info[2] === IMAGETYPE_JPEG) {
                $image = $this->applyExifOrientation($image, $absolute);
            }

            $image = $this->resize($image, $maxWidth);
            imagesavealpha($image, true);

            // Format d'origine recompresse (secours pour les tres vieux navigateurs).
            match ($info[2]) {
                IMAGETYPE_JPEG => imagejpeg($image, $absolute, self::JPEG_QUALITY),
                IMAGETYPE_PNG => imagepng($image, $absolute, 8),
                IMAGETYPE_WEBP => imagewebp($image, $absolute, self::WEBP_QUALITY),
            };

            if ($info[2] !== IMAGETYPE_WEBP) {
                imagewebp($image, $disk->path(self::webpPath($path)), self::WEBP_QUALITY);
            }

            imagedestroy($image);
        } catch (Throwable) {
            // Image laissee telle quelle.
        }

        return $path;
    }

    /** Supprime une image et son jumeau WebP. */
    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete([$path, self::webpPath($path)]);
        }
    }

    public static function webpPath(string $path): string
    {
        return preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    }

    /** URL de la version WebP si elle existe (sinon null : le navigateur utilise l'image d'origine). */
    public static function webpUrl(?string $path): ?string
    {
        if (! $path || str_ends_with(strtolower($path), '.webp')) {
            return null;
        }

        $webp = self::webpPath($path);

        return $webp !== $path && Storage::disk('public')->exists($webp) ? Storage::disk('public')->url($webp) : null;
    }

    /** @param \GdImage $image @return \GdImage */
    private function resize($image, int $maxWidth)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $newHeight = (int) round($height * $maxWidth / $width);
        $resized = imagecreatetruecolor($maxWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /** Photos de telephone : applique la rotation indiquee dans les donnees EXIF. @param \GdImage $image @return \GdImage */
    private function applyExifOrientation($image, string $absolute)
    {
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($absolute)['Orientation'] ?? 1) : 1;

        return match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
