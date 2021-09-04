<?php
declare(strict_types=1);

namespace LabelMaker\Streams;

use MintWare\Streams\MemoryStream;

class TypedMemoryStream extends MemoryStream
{
    /** @var string */
    protected string $type;

    public function __construct($data = null, string $type = "")
    {
        parent::__construct($data);
        $this->type = $type;
    }
}
