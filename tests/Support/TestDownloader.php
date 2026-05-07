<?php

declare(strict_types=1);

namespace Tests\Support;

use Spatie\MediaLibrary\Downloaders\Downloader;

class TestDownloader implements Downloader
{
    public function getTempFile(string $url): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'media-library');

        $image = imagecreatetruecolor(1, 1);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        imagepng($image, $temporaryFile);
        imagedestroy($image);

        return $temporaryFile;
    }
}
