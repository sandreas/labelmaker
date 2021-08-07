<?php

namespace LabelMaker\Reader;

class NullReader implements ReaderInterface
{

    public function prepare(): bool
    {
        return true;
    }

    public function read(): ?array
    {
        return null;
    }

    public function finish(): bool
    {
        return true;
    }
}
