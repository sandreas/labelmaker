<?php

namespace LabelMaker\Reader;

interface ReaderInterface
{
    public function prepare(): bool;
    public function read(): ?array;
    public function finish(): bool;

}
