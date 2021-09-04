<?php

namespace LabelMaker\Media\Loader;

use LabelMaker\Media\MediaFile;

class Mp3Loader extends FallbackLoader
{
    public function supportsType(string $type): bool
    {
        return $type === "mp3";
    }
}
