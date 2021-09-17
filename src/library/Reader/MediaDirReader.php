<?php

namespace LabelMaker\Reader;

use CallbackFilterIterator;
use Collator;
use FilesystemIterator;
use GuzzleHttp\Psr7\Uri;
use LabelMaker\Media\Loader\MediaFileTagLoaderComposite;
use LabelMaker\Media\MediaFile;
use LabelMaker\Media\MediaFilePackage;
use Locale;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class MediaDirReader extends AbstractReader
{
    private MediaFileTagLoaderComposite $tagLoader;
    private Uri $uri;
    private int $recordsPerPage;
    private array $pageTemplates = [];
    private array $mediaFileExtensions = ["mp3", "m4b"];
    private array $noStackMediaFileExtensions = ["m4b"];
    private array $chunkedPackageGroups = [];
    private ?SplFileInfo $baseMediaPath;



    public function __construct(MediaFileTagLoaderComposite $tagLoader, Uri $uri, int $recordsPerPage, array $pageTemplates)
    {
        $this->tagLoader = $tagLoader;
        $this->uri = $uri;
        $this->recordsPerPage = $recordsPerPage;
        $this->pageTemplates = $pageTemplates;
    }


    public function prepare(): bool
    {
        $this->baseMediaPath = $this->uriToFilePath($this->uri);
        if (!is_dir($this->baseMediaPath)) {
            return false;
        }

        $paths = $this->loadMediaFilesGroupedByPath($this->baseMediaPath);
        $mediaFilePackages = $this->buildMediaFilePackages($paths);

        $packageGroups = array_map(function(array $mediaFilePackage) {
            return array_chunk($mediaFilePackage, $this->recordsPerPage);
        }, $this->groupMediaFilePackages(...$mediaFilePackages));

        foreach($packageGroups as $packageGroup) {
            foreach($packageGroup as $package) {
                $this->chunkedPackageGroups[] = $package;
            }
        }
        return true;
    }

    protected function loadMediaFilesGroupedByPath($inputPath): array
    {
        $flags = FilesystemIterator::UNIX_PATHS |
                FilesystemIterator::SKIP_DOTS;
        $baseIterator = new RecursiveDirectoryIterator($inputPath,$flags);
        $innerIterator = new RecursiveIteratorIterator($baseIterator);
        $callbackIterator = new CallbackFilterIterator($innerIterator, function (SplFileInfo $file) {
            return in_array($file->getExtension(), $this->mediaFileExtensions, true);
        });

        $paths = [];
        foreach ($callbackIterator as $file) {
            $path = $file->getPath();
            $paths[$path] ??= [];
            $paths[$path][] = new MediaFile($file);
        }


        $sortedPaths = [];
        $sortedKeys = $this->sortSplFiles(array_map(function($p){
            return new SplFileInfo($p);
        }, array_keys($paths)));

        foreach($sortedKeys as $sortedKey) {
            $key = (string)$sortedKey;
            $sortedPaths[$key] = $this->sortSplFiles($paths[$key]);
        }

        return $sortedPaths;
    }


    protected function sortSplFiles($files)   {
        $compareFunc = "strnatcmp";
        if(class_exists("\\Collator")) {
            $defaultLocale = Locale::getDefault();
            $collator = new Collator($defaultLocale);
            $compareFunc = [$collator, "compare"];
        }


        usort($files, function (SplFileInfo $a, SplFileInfo $b) use($compareFunc) {
            // normalize filenames for sorting
            $a = new SplFileInfo($a->getRealPath());
            $b = new SplFileInfo($b->getRealPath());


            // if path is equal, compare filenames
            if ($a->getPath() === $b->getPath()) {
                return $compareFunc($a->getBasename(), $b->getBasename());
            }

            // sort by path
            $aParts = explode(DIRECTORY_SEPARATOR, $a);
            $bParts = explode(DIRECTORY_SEPARATOR, $b);
            foreach ($aParts as $index => $part) {
                if(!isset($bParts[$index])) {
                    break;
                }

                if ($part !== $bParts[$index]) {
                    return $compareFunc($part, $bParts[$index]);
                }
            }

            $aCount = count($aParts);
            $bCount = count($bParts);
            // count is not equal, but parts are, so return shortest path
            if ($aCount != $bCount) {
                return $aCount - $bCount;
            }

            // compare full path if nothing else matched
            return $compareFunc($a, $b);
        });
        return $files;
    }

    public function read(): ?array
    {
        $currentItem = current($this->chunkedPackageGroups);
        if($currentItem === false) {
            return null;
        }
        next($this->chunkedPackageGroups);

        return $this->loadMediaFileTagsForGroup(...$currentItem);
    }

    private function loadMediaFileTagsForGroup(MediaFilePackage ...$mediaFilePackages): array {
        foreach($mediaFilePackages as $package) {
            $this->tagLoader->enrichMediaFile($package->mediaFile);
        }
        return $mediaFilePackages;
    }

    public function finish(): bool
    {
        return true;
    }

    private function buildMediaFilePackages(array $paths): array
    {
        $mediaGroups = [];
        foreach($paths as $path => $mediaFilesInPath) {
            $mediaFileGroup = new MediaFilePackage();
            $mediaFileGroup->path = new SplFileInfo($path);
            $mediaFileGroup->mediaFile = array_shift($mediaFilesInPath);

            /**
             *
             */
            foreach($mediaFilesInPath as $mediaFile) {
                if(in_array($mediaFile->getExtension(), $this->noStackMediaFileExtensions)){
                    $mediaGroups[] = clone $mediaFileGroup;
                    $mediaFileGroup->mediaFile = $mediaFile;
                    continue;
                }
                $mediaFileGroup->directoryFiles[] = $mediaFile;
            }
            $mediaGroups[] = $mediaFileGroup;
        }
        return $mediaGroups;
    }



    private function groupMediaFilePackages(MediaFilePackage... $mediaFilePackages): array
    {
        $packageGroups = [];
        foreach($mediaFilePackages as $package) {
            $packageGroups[(string)$package->pageTemplate] ??= [];
            $packageGroups[(string)$package->pageTemplate][] = $package;
        }
        return array_values($packageGroups);
    }
}
