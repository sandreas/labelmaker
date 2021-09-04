<?php

namespace LabelMaker\Themes;

class Theme
{
    public string $documentTemplate;
    public string $documentCss;
    public array $pageTemplates = [];
    public string $dataHook;
}
