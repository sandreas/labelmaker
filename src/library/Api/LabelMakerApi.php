<?php

namespace LabelMaker\Api;

use FilesystemIterator;
use SplFileInfo;

class LabelMakerApi
{
    public function call($name, ...$args): string
    {
        return call_user_func_array($name, $args);
    }

    public function props($variable): string {
        $props = [];
        if(is_iterable($variable)) {
            foreach($variable as $prop => $value) {
                $props[] = $prop;
            }
        } elseif(is_object($variable)) {
            $props = array_keys(get_object_vars($variable));
        }
        return implode(", ", $props);
    }

    public function printr($variable):string {
        return print_r($variable, true);
    }

    public function varexport($variable):?string {
        return var_export($variable, true);
    }

    public function findImage($path, $preferredFileName="cover", $extensions=["jpg", "jpeg", "png", "svg"]) {
        if(!is_dir($path)) {
            return null;
        }
        $it = new FilesystemIterator($path);
        $preferredExtension = current($extensions);

        $preferredFileNameWithExtension = $preferredFileName. ($preferredExtension ? ".".$preferredExtension : "");
        $files = [];
        /** @var SplFileInfo $file */
        foreach ($it as $file) {
            $fileExt = $file->getExtension();
            $fileName = $file->getBasename($fileExt ? ".".$fileExt : null);

            if($file->getBasename() === $preferredFileNameWithExtension) {
                return $file;
            }

            if(!in_array($fileExt, $extensions)) {
                continue;
            }

            if($fileName === $preferredFileName) {
                array_unshift($files, $file);
                continue;
            }

            $files[] = $file;
        }

        return count($files) === 0 ? null : $files[0];
    }

    public function getPathPart($path, $index, $dirSeparator = DIRECTORY_SEPARATOR): string
    {
        $parts = explode($dirSeparator, $path);

        if ($index < 0) {
            $index = count($parts) - abs($index);
        }

        if (isset($parts[$index])) {
            return $parts[$index];
        }

        return "";
    }

    public function jsonLoadFile($file, ?bool $associative = false, int $depth = 512, int $flags = 0): mixed
    {
        if (!file_exists($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), $associative, $depth, $flags);
    }


}
