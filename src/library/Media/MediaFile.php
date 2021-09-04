<?php

namespace LabelMaker\Media;

use DateTime;
use LabelMaker\Streams\TypedMemoryStream;
use SplFileInfo;

class MediaFile extends SplFileInfo
{
    public ?string $mimeType;
    public ?string $title;
    public ?string $artist;
    public ?string $album;
    public ?string $composer;
    public ?string $genre;
    public ?string $sortTitle;
    public ?string $sortAlbum;
    public ?string $description;
    public ?string $longDescription;
    public ?string $copyright;
    public ?string $encodingTool;
    public ?string $mediaTypeName;
    public ?int $durationMs;

    public ?string $trackNumber;

    public ?DateTime $creationDate;
    public ?DateTime $purchaseDate;
    public ?TypedMemoryStream $cover;


    public ?string $series;
    public ?string $part;
}
