<?php

namespace LabelMaker\Media;

use SplFileInfo;

class MediaFilePackage
{
    /** @var MediaFile[] */
    public array $files;
    public array $pageTemplates;
    public ?SplFileInfo $path;
}
