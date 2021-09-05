<?php

namespace LabelMaker\Themes;

use Closure;

class Theme
{
    public string $documentTemplate = "";
    public string $documentCss = "";
    // https://wiki.php.net/rfc/typed_properties_v2#supported_types
    public ?Closure $dataHook = null;
    public array $pageTemplates = [];

}
