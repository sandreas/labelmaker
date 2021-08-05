<?php

namespace LabelMaker\Media\Readers;

use LabelMaker\Media\MediaFile;
use LabelMaker\Media\ReaderInterface;
use MintWare\Streams\MemoryStream;

class Mp3Reader implements ReaderInterface
{


    public function readMediaFile(array $getID3MetaDataArray): MediaFile
    {
        $mediaFile = new MediaFile();

        return $mediaFile;
    }
}
