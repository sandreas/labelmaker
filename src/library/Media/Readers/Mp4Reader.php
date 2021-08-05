<?php

namespace LabelMaker\Media\Readers;

use LabelMaker\Media\MediaFile;
use LabelMaker\Media\ReaderInterface;
use MintWare\Streams\MemoryStream;

class Mp4Reader implements ReaderInterface
{
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

    private function searchTitle($metadataArray) {
        return $metadataArray["tags"]["quicktime"]["title"][0] ?? "";
    }

    private function searchCover($metadataArray): ?MemoryStream {
        $coverAtom = $this->searchCoverAtom($metadataArray['quicktime']['moov'] ?? null);
        return $coverAtom["data"] ? new MemoryStream($coverAtom["data"]) : null;
    }

    public function readMediaFile(array $getID3MetaDataArray): MediaFile
    {
        $mediaFile = new MediaFile();
        $mediaFile->title = $this->searchTitle($getID3MetaDataArray);
        $mediaFile->cover = $this->searchCover($getID3MetaDataArray);
        return $mediaFile;
    }
}
