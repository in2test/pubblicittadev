<?php

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Collection;

$f = SpatieMediaLibraryFileUpload::make('images')->filterMediaUsing(function (Collection $media) {
    return $media;
});
echo "OK\n";
