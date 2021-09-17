<?php

namespace LabelMaker\Media\Loader;

use LabelMaker\Media\MediaFile;
use LabelMaker\Streams\ImageMemoryStream;
use LabelMaker\Streams\TypedMemoryStream;

class Mp3Loader extends FallbackLoader
{
    public function supportsType(string $type): bool
    {
        return $type === "mp3";
    }

    public function enrichMediaFile(MediaFile $file, ?array $metaDataContainer = null): MediaFile
    {
        if($metaDataContainer !== null) {
            parent::enrichMediaFile($file, $metaDataContainer);

            $file->cover = $this->searchCover($metaDataContainer);
        }
        return $file;
    }

    private function searchCover($metadataArray): ?TypedMemoryStream {
        // alternate cover: $metaDataContainer["idv2"]["APIC"][0]
        $coverAtom = $metadataArray['comments']['picture'][0] ?? null;
        if($coverAtom === null) {
            return null;
        }
        return $coverAtom["data"] ? new ImageMemoryStream($coverAtom["data"], $coverAtom["image_mime"] ?? "application/octet-stream") : null;
    }
}
