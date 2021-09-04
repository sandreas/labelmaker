<?php

namespace LabelMaker\Themes;

interface ThemeLoaderInterface
{
    public function load(string $theme):void;
}
