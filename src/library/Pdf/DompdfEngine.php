<?php

namespace LabelMaker\Pdf;

use Dompdf\Dompdf;
use MintWare\Streams\MemoryStream;

class DompdfEngine implements EngineInterface
{

    private Dompdf $dompdf;

    public function __construct(Dompdf $dompdf)
    {
        $this->dompdf = $dompdf;
    }

    public function htmlToPdf(string $html): ?MemoryStream
    {
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();
        return new MemoryStream($this->dompdf->output());
    }
}
