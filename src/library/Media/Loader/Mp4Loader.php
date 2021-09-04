<?php

namespace LabelMaker\Media\Loader;

use LabelMaker\Media\MediaFile;
use LabelMaker\Streams\ImageMemoryStream;
use LabelMaker\Streams\TypedMemoryStream;
use MintWare\Streams\MemoryStream;

class Mp4Loader extends FallbackLoader
{

    public function supportsType(string $type): bool
    {
        return $type === "mp4";
    }

    public function enrichMediaFile(MediaFile $file, ?array $metaDataContainer = null): MediaFile
    {
        if($metaDataContainer !== null) {
            parent::enrichMediaFile($file, $metaDataContainer);
            $file->cover = $this->searchCover($metaDataContainer);
        }
        return $file;
    }
    private function searchCoverAtom($container){
        if(!isset($container['subatoms'])){
            return null;
        }
        foreach($container['subatoms'] as $subatom){
            $name = $subatom['name'] ?? "";
            if($name === "covr"){
                return $subatom;
            }
            $cover = $this->searchCoverAtom($subatom);
            if($cover){
                return $cover;
            }
        }
        return null;
    }

    private function searchCover($metadataArray): ?TypedMemoryStream {
        $coverAtom = $this->searchCoverAtom($metadataArray['quicktime']['moov'] ?? null);
        return $coverAtom["data"] ? new ImageMemoryStream($coverAtom["data"], $coverAtom["image_mime"] ?? "application/octet-stream") : null;
    }


}
