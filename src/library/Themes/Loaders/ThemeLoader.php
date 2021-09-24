<?php

namespace LabelMaker\Themes\Loaders;

class ThemeLoader extends ThemeFileLoader
{
    const WRAPPER_PREFIX_ZIP = "zip://";
    const WRAPPER_SUFFIX_ZIP = "#";
    const DOCUMENT_TEMPLATE_FILE = "document.twig";
    const DOCUMENT_CSS_FILE = "document.css";
    const DATA_HOOK_FILE = "hook.php";
    const PAGE_TEMPLATE_FILE = "page.twig";
    const PAGE_TEMPLATE_FILE_INDEXED = "page-%d.twig";
    const MAX_PAGE_TEMPLATE_COUNT = 10000;
    const ZIP_HEADER_HEX = '504b0304';

    public function __construct(?string $themeName) {

        $path = null;
        $internalPath = $this->normalizePath(__DIR__."/../../../themes/".$themeName);

        if(is_dir($themeName)) {
            $path = $this->normalizePath($themeName);
        } else if(is_file($themeName) && $this->isZipFile($themeName)) {
            $path = $this->normalizeZipFile($themeName);
        } else if(is_dir($internalPath)) {
            $path = $internalPath;
        }

        if($path !== null) {
            $documentFile = $path.static::DOCUMENT_TEMPLATE_FILE;
            $cssFile = $path.static::DOCUMENT_CSS_FILE;
            $pageTemplateFiles = $this->findThemePageTemplateFiles($path);
            $dataHookFile = $path.static::DATA_HOOK_FILE;
            parent::__construct($documentFile, $cssFile, $pageTemplateFiles, $dataHookFile);
        }
    }

    private function isZipFile($filepath):bool{
        $fh = fopen($filepath,'r');
        $bytes = fread($fh,4);
        fclose($fh);
        return (static::ZIP_HEADER_HEX === bin2hex($bytes));
    }

    private function normalizeZipFile($zipFile):string {
        if(substr($zipFile, 0, 6) !== static::WRAPPER_PREFIX_ZIP) {
            $zipFile = static::WRAPPER_PREFIX_ZIP.$zipFile;
        }

        if(substr($zipFile, -1) !== static::WRAPPER_SUFFIX_ZIP) {
            $zipFile .= static::WRAPPER_SUFFIX_ZIP;
        }

        if(DIRECTORY_SEPARATOR !== "/") {
            return str_replace(DIRECTORY_SEPARATOR, "/", $zipFile);
        }
        return $zipFile;
    }


    //
//    protected function loadFromPath($path, Theme $theme): Theme
//    {
//        $theme->documentTemplate = $this->loadFileTemplate($path . static::DOCUMENT_TEMPLATE_FILE, $theme->documentTemplate);
//        $theme->documentCss = $this->loadFileTemplate($path . static::DOCUMENT_CSS_FILE, $theme->documentCss);
//
//        $theme->dataHook = $this->loadDataHook($path . static::DATA_HOOK_FILE, $theme->dataHook);
//
//
//        $pageTemplateFiles = $this->findThemePageTemplateFiles($path);
//
//        $theme->pageTemplates = $this->loadPageTemplateFiles($pageTemplateFiles, $theme->pageTemplates);
//
//        return $theme;
//    }

    /**
     * @param $path
     * @return array
     */
    private function findThemePageTemplateFiles($path): array
    {
        $pageTemplateFiles = [];
        $defaultPageTemplateFile = $path . static::PAGE_TEMPLATE_FILE;
        if ($this->fileExistsWithWrapperSupport($defaultPageTemplateFile)) {
            $pageTemplateFiles[] = $defaultPageTemplateFile;
        }

        $pageTemplateIndex = 1;
        do {
            $currentPageTemplateFile = $path . sprintf(static::PAGE_TEMPLATE_FILE_INDEXED, $pageTemplateIndex);
            $pageTemplateFileExists = $this->fileExistsWithWrapperSupport($currentPageTemplateFile);
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
