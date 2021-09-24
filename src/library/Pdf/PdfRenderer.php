<?php

namespace LabelMaker\Pdf;

use ArrayIterator;
use Exception;
use LabelMaker\Api\LabelMakerApi;
use LabelMaker\Reader\ReaderInterface;
use LabelMaker\Options\CreateOptions;
use LabelMaker\Themes\Theme;
use MintWare\Streams\MemoryStream;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class PdfRenderer
{
    private ReaderInterface $reader;
    private LabelMakerApi $api;
    private CreateOptions $options;

    public function __construct(ReaderInterface $reader, LabelMakerApi $api, CreateOptions $options)
    {
        $this->reader = $reader;
        $this->api = $api;
        $this->options = $options;
    }


    /**
     * @throws Exception
     */
    public function render(EngineInterface $engine, Theme $theme): MemoryStream
    {
        // $pageRecordSets = $this->buildPageRecordSets();
        if (!$this->reader->prepare()) {
            throw new Exception("reader prepare failed");
        }
//        $pageRecordSets = [];
//        while ($pageRecordSet = $this->reader->read()) {
//            $pageRecordSets[] = $pageRecordSet;
//        }
//        return $pageRecordSets;

        $htmlPages = [];

        $dataRecords = $this->reader->read();
        if ($theme->dataHook) {
            $dataRecords = ($theme->dataHook)($dataRecords);
        }

        if(is_array($dataRecords)) {
            $dataRecords = new ArrayIterator($dataRecords);
        }

        if (!$dataRecords->valid() || $this->options->dataRecordsPerPage <= 0) {
            $records = $dataRecords->valid() ? iterator_to_array($dataRecords) : [];
            foreach ($theme->pageTemplates as $index => $pageTemplate) {
                $htmlPages[] = $this->renderPageTemplate($pageTemplate, $index, $records);
            }
        } else {
            $recordGroup = [];
            $index = 0;
            foreach($dataRecords as $record) {
                if(count($recordGroup) === $this->options->dataRecordsPerPage) {
                    $pageTemplate = current($theme->pageTemplates);
                    if (!next($theme->pageTemplates)) {
                        reset($theme->pageTemplates);
                    }
                    $htmlPages[] = $this->renderPageTemplate($pageTemplate, $index, $recordGroup);
                    $recordGroup = [$record];
                    $index++;
                } else {
                    $recordGroup[] = $record;
                }
            }

            if(count($recordGroup) > 0) {
                $pageTemplate = current($theme->pageTemplates);
                $htmlPages[] = $this->renderPageTemplate($pageTemplate, $index, $recordGroup);
            }
        }

        $loader = new ArrayLoader([
            'document' => $theme->documentTemplate,
        ]);
        $twig = new Environment($loader);
        $html = $twig->render('document', [
            'css' => $theme->documentCss,
            'html' => implode("", $htmlPages)
        ]);

        return $engine->htmlToPdf($html);
    }

    /**
     * @throws Exception
     */
    private function renderPageTemplate(string $pageTemplate, int $pageIndex, array $pageRecords): string
    {
        $loader = new ArrayLoader([
            'page' => $pageTemplate,
        ]);
        $twig = new Environment($loader);
        return $twig->render('page', [
            "data" => $pageRecords,
            "api" => $this->api,
            "page" => $pageIndex
        ]);

    }
}
