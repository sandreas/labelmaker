<?php

namespace LabelMaker\Reader;

use CallbackFilterIterator;
use GuzzleHttp\Psr7\Uri;
use LabelMaker\Media\MediaFilePackage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class MediaDirReader extends AbstractReader
{
    private Uri $uri;
    private int $recordsPerPage;
    private array $pageTemplates = [];
    private array $extensions = ["mp3", "m4b"];
    private array $groups = [];

    public function __construct(Uri $uri, int $recordsPerPage, array $pageTemplates)
    {
        $this->uri = $uri;
        $this->recordsPerPage = $recordsPerPage;
        $this->pageTemplates = [];
    }


    public function prepare(): bool
    {
        $mediaPath = $this->uriToFilePath($this->uri);
        if (!is_dir($mediaPath)) {
            return false;
        }

        $paths = $this->loadMediaPackages($mediaPath);
        $pathItemGroups = [];
        foreach ($paths as $pathItem) {
            if ($pathItem->pageTemplates === null) {
                continue;
            }
            $key = (string)$pathItem->pageTemplates;
            $pathItemGroups[$key] ??= [];
            $pathItemGroups[$key][] = $pathItem;
        }

        $this->groups = $pathItemGroups;
    }

    /**
     * @param $inputPath
     * @return MediaFilePackage[]
     */
    protected function loadMediaPackages($inputPath): array
    {
        $baseIterator = new RecursiveDirectoryIterator($inputPath);
        $innerIterator = new RecursiveIteratorIterator($baseIterator);
        $callbackIterator = new CallbackFilterIterator($innerIterator, function (SplFileInfo $file) {
            return in_array($file->getExtension(), $this->extensions, true);
        });

        $paths = [];
        foreach ($callbackIterator as $file) {
            $path = $file->getPath();
            $paths[$path] ??= [];
            $paths[$path][] = $file;
        }

        $mediaPackages = [];
        foreach ($paths as $path => $files) {
            $mediaPackages[] = $this->buildMediaPackage($inputPath, $path, $files);
        }

        return $mediaPackages;
    }


    protected function buildMediaPackage($inputRootPath, $path, $files): MediaFilePackage
    {
        $pathItem = new MediaFilePackage();
        $pathItem->files = $files;
        $pathItem->path = new SplFileInfo($path);
        $pathItem->pageTemplates = $this->searchPageTemplates($inputRootPath, $path);
        return $pathItem;
    }

    private function searchPageTemplates($inputPath, $path): array
    {
        $splPath = new SplFileInfo($path);
        $splInputPath = new SplFileInfo($inputPath);
        $inputPathLen = strlen($splInputPath);
        $pageTemplates = [];
        $len = strlen($splPath);
        do {
            foreach($this->pageTemplates as $pageTemplate){
                $pageTemplateFile = new SplFileInfo($splPath . "/" . $pageTemplate);
                if ($pageTemplateFile->isFile()) {
                    $pageTemplates[] = $pageTemplateFile;
                }
            }
            $splPath = new SplFileInfo($splPath->getPath());
            $oldLen = $len;
            $len = strlen($splPath);

        } while ($len >= $inputPathLen && $len < $oldLen && count($pageTemplates) === 0);
        return $pageTemplates;
    }

    public function read(): ?array
    {
        $currentItem = current($this->groups);
        if($currentItem === false){
            return null;
        }
        next($this->groups);
        return $currentItem;
    }

    public function finish(): bool
    {
        // todo: WIP
    }
}
