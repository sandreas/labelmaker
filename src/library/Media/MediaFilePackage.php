<?php

namespace LabelMaker\Media;

use SplFileInfo;

class MediaFilePackage
{
    public ?SplFileInfo $path=null;

    public ?SplFileInfo $pageTemplate=null;

    public MediaFile $mediaFile;

    /** @var SplFileInfo[] */
    public array $directoryFiles = [];
}
