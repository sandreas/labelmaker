<?php

namespace LabelMaker\Pdf;

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
        $pageRecordSets = $this->buildPageRecordSets();
        $htmlPages = [];

        if ($theme->dataHook) {
            // todo: use generators / yield
            $pageRecordSets = ($theme->dataHook)($pageRecordSets);
        }

        if (count($pageRecordSets) === 0) {
            foreach ($theme->pageTemplates as $index => $pageTemplate) {
                $htmlPages[] = $this->renderPageTemplate($pageTemplate, $index, []);
            }
        } else {
            foreach ($pageRecordSets as $index => $data) {
                $pageTemplate = current($theme->pageTemplates);

                $htmlPages[] = $this->renderPageTemplate($pageTemplate, $index, $data);
                if (!next($theme->pageTemplates)) {
                    reset($theme->pageTemplates);
                }
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
