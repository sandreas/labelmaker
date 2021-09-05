<?php

namespace LabelMaker\Themes\Loaders;
use LabelMaker\Themes\Theme;
class ThemeDirectoryFileLoader extends AbstractThemeFileLoader
{

    private ?string $themePath;

    public function __construct(?string $themeName) {
        $this->themePath = $this->normalizePath($themeName);
    }


    public function load(?Theme $theme): ?Theme
    {
        if(!is_dir($this->themePath)) {
            return $theme;
        }

        return parent::loadFromPath($this->themePath, $theme);
    }
}
