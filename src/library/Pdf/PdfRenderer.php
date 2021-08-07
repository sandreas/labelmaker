<?php

namespace LabelMaker\Pdf;

use Exception;
use LabelMaker\Reader\ReaderInterface;
use LabelMaker\Options\CreateOptions;
use MintWare\Streams\MemoryStream;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class PdfRenderer
{
    private EngineInterface $engine;
    private ReaderInterface $reader;
    private CreateOptions $options;

    public function __construct(EngineInterface $pdfEngine, ReaderInterface $reader, CreateOptions $options)
    {
        $this->engine = $pdfEngine;
        $this->reader = $reader;
        $this->options = $options;
    }

    /**
     * @throws Exception
     */
    public function render(): MemoryStream
    {
        $dataGroups = $this->buildDataGroups();
        $htmlPages = [];
        if(count($dataGroups) === 0)  {
            foreach($this->options->pageTemplates as $index => $pageTemplate) {
                $htmlPages[] = $this->renderPageTemplate($pageTemplate, $index, []);
            }
        } else  {
            foreach ($dataGroups as $index => $data) {
                $pageTemplate = current($this->options->pageTemplates);
                $htmlPages[] = $this->renderPageTemplate($pageTemplate, $index, $data);

                if (!next($this->options->pageTemplates)) {
                    reset($this->options->pageTemplates);
                }
            }
        }

        $documentTemplate = $this->options->documentTemplate ? file_get_contents($this->options->documentTemplate) : CreateOptions::DEFAULT_DOCUMENT_TEMPLATE;
        $documentCss = $this->options->documentCss ? file_get_contents($this->options->documentCss) : CreateOptions::DEFAULT_DOCUMENT_CSS;


        $loader = new ArrayLoader([
            'document' => $documentTemplate,
        ]);
        $twig = new Environment($loader);
        $html = $twig->render('document', [
            'css' => $documentCss,
            'html' => implode("", $htmlPages)
        ]);

        return $this->engine->htmlToPdf($html);
    }

    /**
     * @throws Exception
     */
    private function buildDataGroups(): array
    {
        if (!$this->reader->prepare()) {
            throw new Exception("reader prepare failed");
        }
        $dataGroups = [];
        while($group = $this->reader->read()) {
            $dataGroups[] = $group;
        }
        return $dataGroups;

        // maybe this should already return the group?
        // $this->reader->read()

//        $dataGroups = [];
//        $currentGroup = [];
//        while ($record = $this->reader->read()) {
//            $currentGroup[] = $record;
//
//            if ($this->options->dataRecordsPerPage === count($currentGroup)) {
//                $dataGroups[] = $currentGroup;
//                $currentGroup = [];
//            }
//        }
//        if (count($currentGroup) > 0) {
//            $dataGroups[] = $currentGroup;
//        }
//
//        return $dataGroups;
    }

    private function renderPageTemplate(string $pageTemplate, int $index, array $data):string
    {

        if(!file_exists($pageTemplate) && isset($data["pageTemplate"]) && file_exists($data["pageTemplate"])) {

        }

        $loader = new ArrayLoader([
            // todo: store cache for page templates
            'page' => file_get_contents($pageTemplate),
        ]);
        $twig = new Environment($loader);
        return $twig->render('page', [
            "data" => $data,
            "page" => $index
        ]);

    }
}
