<?php

namespace LabelMaker\Pdf;

use MintWare\Streams\MemoryStream;

interface EngineInterface
{
    public function htmlToPdf(string $html): ?MemoryStream;
}
