<?php

namespace LabelMaker\Api;

class LabelMakerApi
{
    public function call($name, ...$args): string
    {
        return call_user_func_array($name, $args);
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

    public function loadJsonFile($file, ?bool $associative = false, int $depth = 512, int $flags = 0): mixed
    {
        if (!file_exists($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), $associative, $depth, $flags);
    }
}
