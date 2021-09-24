<?php
return function(Generator $data): Generator {
    foreach($data as $item) {
        $item->mediaFile->title = "datahook title";
        yield $item;
    }
};
