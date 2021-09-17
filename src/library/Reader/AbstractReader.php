<?php

namespace LabelMaker\Reader;

use GuzzleHttp\Psr7\Uri;

use SplFileInfo;

abstract class AbstractReader implements ReaderInterface
{

    protected function uriToFilePath(Uri $uri): SplFileInfo
    {
        return new SplFileInfo(rawurldecode($uri->getHost()). rawurldecode($uri->getPath()));
    }

    protected function uriToOptions(Uri $uri):array{
        parse_str($uri->getQuery(), $params);
        return $params;
    }

    abstract public function prepare(): bool;

    abstract public function read(): ?array;

    abstract public function finish(): bool;
}
