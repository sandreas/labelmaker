<?php

namespace LabelMaker\Commands;

use Exception;
use GuzzleHttp\Psr7\Uri;
use LabelMaker\Options\CreateOptions;
use LabelMaker\Pdf\DompdfEngine;
use LabelMaker\Pdf\EngineInterface;
use LabelMaker\Pdf\MpdfEngine;
use LabelMaker\Pdf\PdfRenderer;
use LabelMaker\Reader\CsvReader;
use LabelMaker\Reader\NullReader;
use LabelMaker\Reader\ReaderInterface;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Dompdf\Dompdf;


class CreateCommand extends AbstractCommand
{
    const OPT_PDF_ENGINE = "pdf-engine";
    const OPT_DOCUMENT_TEMPLATE = "document-template";
    const OPT_DOCUMENT_CSS = "document-css";
    const OPT_PAGE_TEMPLATE = "page-template";
    const OPT_DATA_URI = "data-uri";
    const OPT_DATA_RECORDS_PER_PAGE = "data-records-per-page";
    const OPT_OUTPUT_FILE = "output-file";

    protected static $defaultName = 'create';
    protected array $extensions = ["txt", "jpg"];
    protected string $optPageTemplate = ".labelmaker.page.twig";


    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('create a new pdf output file based on templates in the input directory or a provided theme');

        $this->addOption(static::OPT_PDF_ENGINE, null, InputOption::VALUE_OPTIONAL, "pdf engine that should be used (mpdf, dompdf)", CreateOptions::PDF_ENGINE_MPDF);

        $this->addOption(static::OPT_DOCUMENT_TEMPLATE, null, InputOption::VALUE_OPTIONAL, "path to custom document-template (otherwise the internal default will be used)");
        $this->addOption(static::OPT_DOCUMENT_CSS, null, InputOption::VALUE_OPTIONAL, "path to custom document-css (otherwise the internal default will be used)");
        $this->addOption(static::OPT_PAGE_TEMPLATE, null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "path to page-template (if more than one is provided, the sequence will be repeated till the end of pages)");
        $this->addOption(static::OPT_DATA_URI, null, InputOption::VALUE_OPTIONAL, "tries to load data from specified uri and provide it as variables for the page template");
        $this->addOption(static::OPT_DATA_RECORDS_PER_PAGE, null, InputOption::VALUE_OPTIONAL, sprintf("splits records loaded from --%s in chunks of this size before injecting into --%s", static::OPT_DATA_URI, static::OPT_PAGE_TEMPLATE), 0);
        $this->addOption(static::OPT_OUTPUT_FILE, null, InputOption::VALUE_REQUIRED, "specifies the output file");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $options = $this->loadOptions($input);
            $pdfEngine = $this->createPdfEngine($options);
            $recordLoader = $this->createRecordReader($options);
            $renderer = new PdfRenderer($pdfEngine, $recordLoader, $options);

            file_put_contents($options->outputFile, $renderer->render());


        } catch (Exception $e) {
            $this->error(sprintf("An error occured: %s", $e->getMessage()));
            $this->debug(sprintf("Details:%s%s", PHP_EOL, $e->getTraceAsString()));
            return Command::FAILURE;
        }

        // return Command::INVALID; // missing args or invalid usage
        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    protected function loadOptions(InputInterface $input): CreateOptions
    {
        $dataUri = $input->getOption(static::OPT_DATA_URI);
        if($dataUri && stripos($dataUri, "://") === false) {
            $dataUri = "file://" . $dataUri;
        }

        $options = new CreateOptions();
        $options->pdfEngine = $input->getOption(static::OPT_PDF_ENGINE);
        $options->documentTemplate = $input->getOption(static::OPT_DOCUMENT_TEMPLATE);
        $options->documentCss = $input->getOption(static::OPT_DOCUMENT_CSS);
        $options->pageTemplates = $input->getOption(static::OPT_PAGE_TEMPLATE);
        $options->dataUri = $input->getOption(static::OPT_DATA_URI) ? new Uri($dataUri) : null;
        $options->dataRecordsPerPage = $input->getOption(static::OPT_DATA_RECORDS_PER_PAGE);
        $options->outputFile = $input->getOption(static::OPT_OUTPUT_FILE);


        $this->ensureFileExists($options->documentTemplate, sprintf("--%s file %s does not exist", static::OPT_DOCUMENT_TEMPLATE, $options->documentTemplate));
        $this->ensureFileExists($options->documentCss, sprintf("--%s file %s does not exist", static::OPT_DOCUMENT_CSS, $options->documentCss));

        if(count($options->pageTemplates) === 0) {
            throw new Exception(sprintf("at least one --%s is required", static::OPT_PAGE_TEMPLATE));
        }

//        // todo: improve this part
//        if($options->dataUri && $options->dataUri->getScheme() === CreateOptions::SCHEME_MEDIA_DIR) {
//            $this->debug("scheme %s will be used, page-templates are getting validated on lookup");
//            return $options;
//        }
//
//        foreach($options->pageTemplates as $pageTemplate) {
//            $this->ensureFileExists($pageTemplate, sprintf("--%s files %s does not exist", static::OPT_PAGE_TEMPLATE, $pageTemplate));
//        }

        return $options;
    }

    /**
     * @throws Exception
     */
    protected function ensureFileExists($file, string $message) {
        if($file === null) {
            return;
        }

        if(!file_exists($file)) {
            throw new Exception($message);
        }
    }

    /**
     * @param CreateOptions $options
     * @return EngineInterface
     * @throws Exception
     */
    public function createPdfEngine(CreateOptions $options): EngineInterface
    {
        // todo: add generic options
        switch ($options->pdfEngine) {
            case CreateOptions::PDF_ENGINE_MPDF:
                $mpdf = new Mpdf(['tempDir' => sys_get_temp_dir()]);
                $mpdf->img_dpi = 300;
                return new MpdfEngine($mpdf);
            case CreateOptions::PDF_ENGINE_DOMPDF:
                $dompdf = new Dompdf();
                $dompdf->setPaper('A4');
                return new DompdfEngine($dompdf);
        }
        throw new Exception(sprintf("Invalid engine: %s", $options->pdfEngine));
    }

    /**
     * @throws Exception
     */
    public function createRecordReader(CreateOptions $options): ReaderInterface
    {
        if(!$options->dataUri) {
            return new NullReader();
        }
        // todo: add generic options
        switch ($options->dataUri->getScheme()) {
            case CreateOptions::SCHEME_CSV:
                return new CsvReader($options->dataUri, $options->dataRecordsPerPage);

            case CreateOptions::SCHEME_MEDIA_DIR:
                return new MediaDirReader($options->dataUri);

        }
        throw new Exception(sprintf("Invalid engine: %s", $options->pdfEngine));
    }



//        try {
//            $pdfEngine = AbstractEngineFactory::createEngine("mpdf");
//            $api = new Api(new getID3, new MediaFileReader);
//
//
//            $inputPath = $input->getArgument(static::ARGUMENT_INPUT);
//            if (!is_dir($inputPath)) {
//                $this->error(sprintf("input path %s is not a directory", $inputPath));
//                return Command::FAILURE;
//            }
//
//            $paths = $this->loadPathItems($inputPath);
//            $pathItemGroups = [];
//            foreach ($paths as $pathItem) {
//                if ($pathItem->pageTemplate === null) {
//                    $this->warning(sprintf("no page template for %s", $pathItem->path));
//                    continue;
//                }
//                $key = (string)$pathItem->pageTemplate;
//                $pathItemGroups[$key] ??= [];
//                $pathItemGroups[$key][] = $pathItem;
//            }
//
//            $html = "";
//            foreach ($pathItemGroups as $pageTemplate => $pathItems) {
//                $loader = new ArrayLoader();
//                $loader->setTemplate("page", file_get_contents($pageTemplate));
//                $twig = new Environment($loader);
//
//                $pageItems = [];
//                foreach ($pathItems as $pathItem) {
//                    $pageItems[] = [
//                        "path" => $pathItem->path,
//                        "files" => $pathItem->files // todo: MediaFile, JsonFile, etc.
//                    ];
//                }
//
//
//                try {
//                    $html .= $twig->render('page', [
//                        'api' => $api,
//                        'pageItems' => $pageItems
//                    ]);
//                } catch (Throwable $e) {
//                    $this->warning(sprintf("Could not render template %s: %s", $pageTemplate, $e->getMessage()));
//                    $this->debug($e->getTraceAsString());
//                }
//
//            }
//            $loader = new ArrayLoader();
//            $loader->setTemplate("document", $this->documentTemplate);
//            $twig = new Environment($loader);
//            $documentHtml = $twig->render('document', [
//                'html' => $html
//            ]);
//        } catch (Exception $e) {
//            $this->error(sprintf("An error occured: %s", $e->getMessage()));
//            $this->debug(sprintf("Details:%s%s", PHP_EOL, $e->getTraceAsString()));
//        }


//    /**
//     * @param $inputPath
//     * @return PathItem[]
//     */
//    protected function loadPathItems($inputPath): array
//    {
//        $baseIterator = new RecursiveDirectoryIterator($inputPath);
//        $innerIterator = new RecursiveIteratorIterator($baseIterator);
//        $callbackIterator = new CallbackFilterIterator($innerIterator, function (SplFileInfo $file) {
//            return in_array($file->getExtension(), $this->extensions, true);
//        });
//
//        $paths = [];
//        foreach ($callbackIterator as $file) {
//            $path = $file->getPath();
//            $paths[$path] ??= [];
//            $paths[$path][] = $file;
//        }
//
//        $pathItems = [];
//        foreach ($paths as $path => $files) {
//            $pathItems[] = $this->buildPathItem($inputPath, $path, $files);
//        }
//
//        return $pathItems;
//    }
//
//    protected function buildPathItem($inputPath, $path, $files): PathItem
//    {
//        $splPath = new SplFileInfo($path);
//        $pathItem = new PathItem();
//        $pathItem->files = $files;
//        $pathItem->path = new SplFileInfo($path);
//
//        $splInputPath = new SplFileInfo($inputPath);
//        $inputPathLen = strlen($splInputPath);
//
//        $pageTemplate = null;
//        $len = strlen($splPath);
//        do {
//            $pageTemplateFile = new SplFileInfo($splPath . "/" . $this->optPageTemplate);
//            if ($pageTemplateFile->isFile()) {
//                $pageTemplate = $pageTemplateFile;
//            }
//
//            $splPath = new SplFileInfo($splPath->getPath());
//            $oldLen = $len;
//            $len = strlen($splPath);
//        } while ($len >= $inputPathLen && $len < $oldLen && ($pageTemplate === null/* || $pageItemTemplate === null*/));
//
//        $pathItem->pageTemplate = $pageTemplateFile;
//        return $pathItem;
//    }
}
