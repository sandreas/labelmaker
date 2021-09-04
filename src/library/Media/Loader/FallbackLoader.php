<?php

namespace LabelMaker\Media\Loader;

use LabelMaker\Media\MediaFile;

class FallbackLoader implements MediaFileLoaderInterface
{

    public function supportsType(string $type): bool
    {
        return true;
    }

    public function enrichMediaFile(MediaFile $file, ?array $metaDataContainer = null): MediaFile
    {
        if($metaDataContainer === null){
            return $file;
        }
        $tag = current($metaDataContainer["tags"] ?? []); // title, track_number, encoder_settings
        if($tag !== false){
            if(isset($tag["playtime_seconds"])) {
                $file->durationMs = ($tag["playtime_seconds"] > 0 ) ? (int)($tag["playtime_seconds"] * 1000) : 0;
            }

//          $tag["chapters"] // only chapter names
            // $metaDataContainer["quicktime"]["chapters"][] => ["timestamp" => 0, "title" => "Intro"]
            // $metaDataContainer["quicktime"]["bookmarks"][] => ["start_seconds" => 0, "duration_seconds" => 6.332, "title" => "Intro"]
            // $metaDataContainer["quicktime"]["moov"] => all mp4 atoms

            $file->title = $tag["title"][0] ?? null;
            $file->artist = $tag["artist"][0] ?? null;
            $file->album = $tag["album"][0] ?? null;
            $file->composer = $tag["composer"][0] ?? null;
            $file->genre = $tag["genre"][0] ?? null;
            // $file->creationDate = $tag["creation_date"][0] ?? null; // releaseDate?
            $file->sortTitle = $tag["sort_title"][0] ?? null;
            $file->sortAlbum = $tag["sort_album"][0] ?? null;
            $file->description = $tag["description"][0] ?? null;
            $file->longDescription = $tag["description_long"][0] ?? null;
            $file->copyright = $tag["copyright"][0] ?? null;
            $file->encodingTool = $tag["encoding_tool"][0] ?? null;
            // $file->purchaseDate = $tag["purchase_date"][0] ?? null;
            $file->mediaTypeName = $tag["stik"][0] ?? null;
            $file->trackNumber = $tag["track_number"][0] ?? null;


            if($file->sortTitle && $file->sortTitle !== $file->title) {
                $parts = explode(" - ", $file->sortTitle);
                $seriesAndNumber = array_shift($parts);

                if(preg_match("/\b[0-9.]+$/isU", $seriesAndNumber)) {
                    $seriesParts = explode(" ", $seriesAndNumber);
                    $file->part = array_pop($seriesParts);
                    $file->series = implode(" ", $seriesParts);
                } else {
                    $file->series = $seriesAndNumber;
                }
                unset($seriesAndNumber);
            }

        }
        return $file;
    }
}
