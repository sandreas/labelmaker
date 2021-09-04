<?php

namespace LabelMaker\Pdf;

use Exception;
use LabelMaker\Api\LabelMakerApi;
use LabelMaker\Reader\ReaderInterface;
use LabelMaker\Options\CreateOptions;
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
    public function render(EngineInterface $engine): MemoryStream
    {
        $pageRecordSets = $this->buildPageRecordSets();
        $htmlPages = [];

        // todo: integrate ThemeLoaders

        if (count($pageRecordSets) === 0) {
            foreach ($this->options->pageTemplates as $index => $pageTemplate) {
                $htmlPages[] = $this->renderPageTemplate($pageTemplate, $index, []);
            }
        } else {
            foreach ($pageRecordSets as $index => $data) {
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

        return $engine->htmlToPdf($html);
    }

    /**
     * @throws Exception
     */
    private function buildPageRecordSets(): array
    {
        if (!$this->reader->prepare()) {
            throw new Exception("reader prepare failed");
        }
        $pageRecordSets = [];
        while ($pageRecordSet = $this->reader->read()) {
            $pageRecordSets[] = $pageRecordSet;
        }
        return $pageRecordSets;
    }

    /**
     * @throws Exception
     */
    private function renderPageTemplate(string $pageTemplate, int $pageIndex, array $pageRecords): string
    {
        if (!is_file($pageTemplate)) {
            // seek first page template in data

            foreach ($pageRecords as $record) {
                if (is_array($record) && isset($record["pageTemplate"])) {
                    $pageTemplate = $record["pageTemplate"];
                    break;
                }

                if (is_object($record) && property_exists($record, "pageTemplate")) {
                    $pageTemplate = $record->pageTemplate;
                    break;
                }
            }

            if (!is_file($pageTemplate)) {
                throw new Exception(sprintf("Could not find page template: %s", $pageRecords["pageTemplate"] ?? " - "));
            }
        }

        $loader = new ArrayLoader([
            'page' => file_get_contents($pageTemplate),
        ]);
        $twig = new Environment($loader);
        return $twig->render('page', [
            "data" => $pageRecords,
            "api" => $this->api,
            "page" => $pageIndex
        ]);

    }
}
