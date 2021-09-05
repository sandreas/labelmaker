<?php

namespace LabelMaker\Themes\Loaders;

use Closure;
use LabelMaker\Themes\Theme;

abstract class AbstractThemeFileLoader implements ThemeLoaderInterface
{
    const DOCUMENT_TEMPLATE_FILE = "document.twig";
    const DOCUMENT_CSS_FILE = "document.css";
    const DATA_HOOK_FILE = "hook.php";
    const PAGE_TEMPLATE_FILE = "page.twig";
    const PAGE_TEMPLATE_FILE_INDEXED = "page-%d.twig";
    const MAX_PAGE_TEMPLATE_COUNT = 10000;

    abstract public function load(?Theme $theme): ?Theme;

    protected function normalizePath($path): string
    {
        return rtrim($path, "\\/") . DIRECTORY_SEPARATOR;
    }

    // https://www.php.net/manual/de/wrappers.compression.php
    protected function loadFromPath($path, Theme $theme): Theme
    {
        $theme->documentTemplate = $this->loadFileTemplate($path . static::DOCUMENT_TEMPLATE_FILE, $theme->documentTemplate);
        $theme->documentCss = $this->loadFileTemplate($path . static::DOCUMENT_CSS_FILE, $theme->documentCss);

        $theme->dataHook = $this->loadDataHook($path . static::DATA_HOOK_FILE, $theme->dataHook);


        $pageTemplateFiles = $this->findThemePageTemplateFiles($path);

        $theme->pageTemplates = $this->loadPageTemplateFiles($pageTemplateFiles, $theme->pageTemplates);

        return $theme;
    }

    protected function loadFileTemplate(?string $filePath, ?string $defaultValue = null): ?string
    {
        if($filePath === null || !file_exists($filePath)) {
            return $defaultValue;
        }

        $contents = @file_get_contents($filePath);
        if ($contents === false) {
            return $defaultValue;
        }
        return $contents;
    }

    protected function loadDataHook(?string $filePath, ?Closure $default = null): ?Closure
    {
        if($filePath === null || !file_exists($filePath)) {
            return $default;
        }
        $dataHook = @include($filePath);
        if (is_callable($dataHook)) {
            return Closure::fromCallable($dataHook);
        }
        return $default;
    }

    protected function loadPageTemplateFiles(array $pageTemplateFiles, array $default = []): array
    {
        $pageTemplates = [];
        foreach ($pageTemplateFiles as $pageTemplateFile) {
            $pageTemplate = $this->loadFileTemplate($pageTemplateFile);
            if ($pageTemplate) {
                $pageTemplates[] = $pageTemplate;
            }
        }

        if (count($pageTemplates) === 0) {
            return $default;
        }
        return $pageTemplates;
    }

    /**
     * @param $path
     * @return array
     */
    private function findThemePageTemplateFiles($path): array
    {
        $pageTemplateFiles = [];
        $defaultPageTemplateFile = $path . static::PAGE_TEMPLATE_FILE;
        if (file_exists($defaultPageTemplateFile)) {
            $pageTemplateFiles[] = $defaultPageTemplateFile;
        }

        $pageTemplateIndex = 1;
        do {
            $currentPageTemplateFile = $path . sprintf(static::PAGE_TEMPLATE_FILE_INDEXED, $pageTemplateIndex);
            $pageTemplateFileExists = file_exists($currentPageTemplateFile);
            if ($pageTemplateFileExists) {
                $pageTemplateFiles[] = $currentPageTemplateFile;
            }

            $pageTemplateIndex++;
            if ($pageTemplateIndex > static::MAX_PAGE_TEMPLATE_COUNT) {
                break;
            }

        } while ($pageTemplateFileExists);
        return $pageTemplateFiles;
    }


}
