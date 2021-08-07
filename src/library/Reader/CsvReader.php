<?php

namespace LabelMaker\Reader;

use Exception;
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
    private int $recordsPerPage;
    private bool $hasHeader;

    public function __construct(Uri $uri, int $recordsPerPage)
    {
        $this->uri = $uri;
        $this->recordsPerPage = $recordsPerPage;

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

    public function read(): ?array
    {
        if (!$this->fp) {
            return null;
        }

        $group = [];
        for($i=0;$i<$this->recordsPerPage;$i++) {
            $line = fgetcsv($this->fp, 0, $this->separator, $this->enclosure, $this->escape);
            if ($line === false) {
                return $i === 0 ? null : $group;
            }
            $group[] = $this->mapHeaders($line);
        }

        return $group;
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
