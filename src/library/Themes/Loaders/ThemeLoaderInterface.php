<?php

namespace LabelMaker\Themes\Loaders;

use LabelMaker\Themes\Theme;

interface ThemeLoaderInterface
{
    public function load(?Theme $theme):?Theme;
}
