<?php

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

$methods = get_class_methods(SpatieMediaLibraryFileUpload::class);
foreach ($methods as $m) {
    if (stripos($m, 'filter') !== false || stripos($m, 'query') !== false || stripos($m, 'media') !== false || stripos($m, 'load') !== false) {
        echo $m."\n";
    }
}
