<?php

namespace LabelMaker\Themes\Loaders;

use LabelMaker\Themes\Theme;

class ThemeFallbackLoader implements ThemeLoaderInterface
{
    private string $document;

    public function __construct(string $document) {
        $this->document = $document;
    }

    public function load(?Theme $theme): ?Theme
    {
        if($theme === null) {
            return null;
        }

        if(!$theme->documentTemplate)  {
            $theme->documentTemplate = $this->document;
        }

        return $theme;
    }
}
