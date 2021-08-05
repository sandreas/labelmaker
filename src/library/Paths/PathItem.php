<?php

namespace LabelMaker\Paths;

use SplFileInfo;

class PathItem
{
    /** @var SplFileInfo[] */
    public array $files;
    public ?SplFileInfo $path;
    public ?SplFileInfo $pageTemplate;
    public ?SplFileInfo $pageItemTemplate;
}
