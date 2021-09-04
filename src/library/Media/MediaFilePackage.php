<?php

namespace LabelMaker\Media;

use SplFileInfo;

class MediaFilePackage
{
    public ?SplFileInfo $path;

    public ?SplFileInfo $pageTemplate;

    public MediaFile $mediaFile;

    /** @var SplFileInfo[] */
    public array $directoryFiles = [];

    public array $pageTemplates = [];
}
