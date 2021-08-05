<?php

namespace LabelMaker\Media;

use MintWare\Streams\MemoryStream;

class MediaFile
{
    public string $title;
    public string $series;
    public string $part;

    public ?MemoryStream $cover;
}
