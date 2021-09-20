<?php

namespace LabelMaker\Reader;

use Exception;
use Generator;
use GuzzleHttp\Psr7\Uri;


class CsvReader extends AbstractReader
{
    /** @var Uri */
    private Uri $uri;
    /** @var false|resource */
    private $fp;
    private string $separator = ",";
    private string $enclosure = '"';
    private string $escape = "\\";
    private array $headerLine = [];
    private bool $hasHeader;

    public function __construct(Uri $uri)
    {
        $this->uri = $uri;
        $options = $this->uriToOptions($this->uri);
        $this->separator = $options["separator"] ?? $this->separator;
        $this->enclosure = $options["enclosure"] ?? $this->enclosure;
        $this->escape = $options["escape"] ?? $this->escape;
        $noHeader = $options["noheader"] ?? false;
        $this->hasHeader = !$noHeader;
    }

    /**
     * @throws Exception
     */
    public function prepare(): bool
    {
        $file = $this->uriToFilePath($this->uri);
        if (!$file->isFile()) {
            throw new Exception(sprintf("file does not exist: %s (uri: %s)", $file, $this->uri));
        }
        if (!($this->fp = fopen($file, "rb"))) {
            throw new Exception(sprintf("could not open file: %s (uri: %s)", $file, $this->uri));
        }

        if ($this->hasHeader) {
            $this->headerLine = fgetcsv($this->fp, 0, $this->separator, $this->enclosure, $this->escape);
        }

        return true;
    }

    public function read(): Generator
    {
//        $file = new SplFileObject("data.csv");
//        $file->setFlags(SplFileObject::READ_CSV);
//        $file->setCsvControl(';');
//        foreach ($file as $row) {
//            [$dateStringInGermanFormat] = $row;
//            // ...
//        }

        if ($this->fp) {
            while($line = fgetcsv($this->fp, 0, $this->separator, $this->enclosure, $this->escape)) {
                yield $this->mapHeaders($line);
            }
        }
    }

    private function mapHeaders(array $line): array
    {
        if (count($this->headerLine) === count($line)) {
            return array_combine($this->headerLine, $line);
        }
        return $line;
    }

    public function finish(): bool
    {
        if ($this->fp) {
            fclose($this->fp);
        }
        return true;
    }
}
