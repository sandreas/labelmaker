<?php

namespace LabelMaker\Themes\Loaders;
use LabelMaker\Themes\Theme;
class ThemeCompositeThemeLoader implements ThemeLoaderInterface
{

    private array $loaders = [];

    public function add(ThemeLoaderInterface $loader) {
        $this->loaders[] = $loader;
    }

    public function load(?Theme $theme): ?Theme
    {
        foreach($this->loaders as $loader) {
            $theme = $loader->load($theme);
        }
        return $theme;
    }
}
