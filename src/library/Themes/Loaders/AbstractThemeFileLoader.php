<?php

namespace LabelMaker\Themes\Loaders;

use Closure;
use LabelMaker\Themes\Theme;
use Throwable;

abstract class AbstractThemeFileLoader implements ThemeLoaderInterface
{


    abstract public function load(?Theme $theme): ?Theme;

    protected function normalizePath($path): string
    {
        return rtrim($path, "\\/") . DIRECTORY_SEPARATOR;
    }

    protected function loadFileTemplate(?string $filePath, ?string $defaultValue = null): ?string
    {

        if($filePath === null || !$this->fileExistsWithWrapperSupport($filePath)) {
            return $defaultValue;
        }

        $contents = @file_get_contents($filePath);
        if ($contents === false) {
            return $defaultValue;
        }
        return $contents;
    }

    /**
     * @param string|null $filePath
     * @param Closure|null $default
     * @return Closure|null
     */
    protected function loadDataHook(?string $filePath, ?Closure $default = null): ?Closure
    {
        if($filePath === null || !$this->fileExistsWithWrapperSupport($filePath)) {
            return $default;
        }
        ob_start();
        try {
            $dataHook = @include($filePath);
            if (is_callable($dataHook)) {
                return Closure::fromCallable($dataHook);
            }
        } catch (Throwable $t) {
            return $default;
        } finally {
            ob_end_clean();
        }

        return $default;
    }

    protected function loadPageTemplateFiles(array $pageTemplateFiles, array $default = []): array
    {
        $pageTemplates = [];
        foreach ($pageTemplateFiles as $pageTemplateFile) {
            $pageTemplate = $this->loadFileTemplate($pageTemplateFile);
            if ($pageTemplate !== null) {
                $pageTemplates[] = $pageTemplate;
            }
        }

        if (count($pageTemplates) === 0) {
            return $default;
        }
        return $pageTemplates;
    }

    /**
     * file_exists does not support wrappers like zip:// (https://www.php.net/manual/de/wrappers.compression.php)
     * @param $file
     * @return bool
     */
    protected function fileExistsWithWrapperSupport($file):bool {

        $file = (string)$file;
        if($file === "") {
            return false;
        }
        try {
            $fp = @fopen($file, "r");
            if($fp) {
                fclose($fp);
                return true;
            }
            // could not open file results in an error
            // to prevent shutdown function hick up, clear the last error
            error_clear_last();
            return false;
        } catch(Throwable $t) {
            return false;
        }
    }


}
