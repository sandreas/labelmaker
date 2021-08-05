<?php

namespace LabelMaker;

use getID3;
use LabelMaker\Media\MediaFile;
use LabelMaker\Media\MediaFileReader;
use MintWare\Streams\MemoryStream;
use SplFileInfo;

class Api
{
    private getID3 $meta;
    private MediaFileReader $mediaFileReader;


    public function __construct(getID3 $meta, MediaFileReader $mediaFileReader) {
        $this->meta = $meta;
        $this->mediaFileReader = $mediaFileReader;
    }

    public function loadMediaFile(SplFileInfo|string $file): ?MediaFile {
        if(!($file instanceof SplFileInfo)) {
            $file = new SplFileInfo($file);
        }
        $metadataArray = $this->meta->analyze($file);
        return $this->mediaFileReader->readMediaFile($metadataArray);
    }

    // findFirstImageInPath


}
