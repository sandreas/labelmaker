<?php

namespace LabelMaker\Pdf;

use MintWare\Streams\MemoryStream;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;

class MpdfEngine implements EngineInterface
{
    private Mpdf $mpdf;

    public function __construct(Mpdf $mpdf)
    {
        $this->mpdf = $mpdf;
    }

    /**
     * @param string $html
     * @return MemoryStream|null
     * @throws MpdfException
     */
    public function htmlToPdf(string $html): ?MemoryStream
    {
        $this->mpdf->WriteHTML($html);
        return new MemoryStream($this->mpdf->Output('', Destination::STRING_RETURN));
    }
}
