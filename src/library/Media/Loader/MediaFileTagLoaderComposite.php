<?php

namespace LabelMaker\Media\Loader;

use getID3;
use LabelMaker\Media\MediaFile;

class MediaFileTagLoaderComposite implements MediaFileLoaderInterface
{
    private getID3 $metaReader;
    /** @var MediaFileLoaderInterface[] */
    private array $loaders = [];


    public function __construct(getID3 $metaReader, array $loaders)
    {
        $this->metaReader = $metaReader;
        $this->loaders = $loaders;
    }


    public function enrichMediaFile(MediaFile $file, ?array $metaDataContainer = null): MediaFile
    {
        $metaDataContainer ??= $this->metaReader->analyze($file);
        $type = $metaDataContainer["fileformat"] ?? null;
        foreach($this->loaders as $loader) {
            if($loader->supportsType($type)) {
                return $loader->enrichMediaFile($file, $metaDataContainer);
            }
        }

        return $file;
    }

    public function supportsType(string $type): bool
    {
        return true;
    }

}
