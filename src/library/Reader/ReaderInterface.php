<?php

namespace LabelMaker\Reader;

use Generator;

interface ReaderInterface
{
    public function prepare(): bool;
    public function read(): Generator;
    public function finish(): bool;

}
