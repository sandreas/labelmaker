<?php

namespace LabelMaker\Streams;

class ImageMemoryStream extends TypedMemoryStream
{
    public int $width;
    public int $height;

    public function __toString()
    {
        return sprintf("data:%s;base64,", $this->type).base64_encode(parent::__toString());
    }
}
