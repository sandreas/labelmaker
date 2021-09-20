<?php

namespace LabelMaker\Reader;

use Generator;

class NullReader implements ReaderInterface
{

    public function prepare(): bool
    {
        return true;
    }

    public function read(): Generator
    {
        yield null;
    }

    public function finish(): bool
    {
        return true;
    }
}
