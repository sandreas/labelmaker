<?php

namespace LabelMaker\Media;

use LabelMaker\Media\Readers\Mp3Reader;
use LabelMaker\Media\Readers\Mp4Reader;

class MediaFileReader implements ReaderInterface
{
    public function readMediaFile(array $getID3MetaDataArray): MediaFile
    {
        return match ($getID3MetaDataArray["fileformat"] ?? null) {
            "mp4" => (new Mp4Reader())->readMediaFile($getID3MetaDataArray),
            "mp3" => (new Mp3Reader())->readMediaFile($getID3MetaDataArray),
            default => new MediaFile(),
        };
    }
}
