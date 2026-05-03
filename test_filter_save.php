<?php

use App\Models\Product;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

$product = Product::where('is_active', true)->first();
$uploader = SpatieMediaLibraryFileUpload::make('images')
    ->collection('images')
    ->filterMediaUsing(function (Collection $media) {
        return $media->filter(function (Media $item) {
            return empty($item->custom_properties['color_ids']);
        });
    });

// To truly test Filament's saving mechanism, it's easier to just do it via UI or look at Filament source code.
// Actually, looking at Filament v3 source for `SpatieMediaLibraryFileUpload`:
// It uses `saveUploadedFiles` which checks `$this->getState()`.
// Then it gets existing media: `$media = $this->getModel()->getMedia($this->getCollection());`
// Then it deletes media that are NOT in the state!
// Wait! If `getMedia()` returns ALL media, and the state only contains the filtered ones, the ones filtered out WILL BE DELETED!
// Let me verify this by inspecting the source of SpatieMediaLibraryFileUpload.
echo "To be inspected...\n";
