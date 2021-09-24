<?php

namespace LabelMaker\Reader;

use GuzzleHttp\Psr7\Uri;

use SplFileInfo;
use Generator;

abstract class AbstractReader implements ReaderInterface
{

    protected function uriToFilePath(Uri $uri): SplFileInfo
    {
        $path = rawurldecode($uri->getHost()). rawurldecode($uri->getPath());
        $fileInfo = new SplFileInfo($path);

        if($fileInfo->isReadable()) {
            return $fileInfo;
        }
        return new SplFileInfo("/".$path);
    }

    protected function uriToOptions(Uri $uri):array{
        parse_str($uri->getQuery(), $params);
        return $params;
    }

    protected function splitExtensions(string $separator, ?string $str=null, array $defaultValue=[]):array{
        if(!isset($str) || !is_string($str)){
            return $defaultValue;
        }

        return array_filter(array_map(function($value) {
            return mb_strtolower(trim($value));
        }, explode($separator, $str)));
    }


    abstract public function prepare(): bool;

    abstract public function read(): Generator;

    abstract public function finish(): bool;
}
