<?php

namespace LabelMaker\Media;

interface ReaderInterface
{
    public function readMediaFile(array $getID3MetaDataArray): MediaFile;
}
