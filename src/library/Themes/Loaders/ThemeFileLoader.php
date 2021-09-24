<?php

namespace LabelMaker\Themes\Loaders;

use Exception;
use LabelMaker\Themes\Theme;

class ThemeFileLoader extends AbstractThemeFileLoader
{
    private ?string $documentTemplateFile;
    private ?string $cssFile;
    private array $pageTemplateFiles;
    private ?string $dataHookFile;


    public function __construct(?string $documentFile, ?string $cssFile, array $pageTemplateFiles, ?string $dataHookFile)
    {
        $this->documentTemplateFile = $documentFile;
        $this->cssFile = $cssFile;
        $this->pageTemplateFiles = $pageTemplateFiles;
        $this->dataHookFile = $dataHookFile;
    }

    /**
     * @throws Exception
     */
    public function load(?Theme $theme): ?Theme
    {
        if ($theme === null) {
            return null;
        }

        $theme->documentTemplate = $this->loadFileTemplate($this->documentTemplateFile, $theme->documentTemplate);
        $theme->documentCss = $this->loadFileTemplate($this->cssFile, $theme->documentCss);
        $theme->pageTemplates = $this->loadPageTemplateFiles($this->pageTemplateFiles, $theme->pageTemplates);
        $theme->dataHook = $this->loadDataHook($this->dataHookFile, $theme->dataHook);
        return $theme;
    }
}
