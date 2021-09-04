<?php

namespace LabelMaker\Media\Loader;

use LabelMaker\Media\MediaFile;

interface MediaFileLoaderInterface
{
    public function supportsType(string $type): bool;

    public function enrichMediaFile(MediaFile $file, ?array $metaDataContainer = null): MediaFile;
}
