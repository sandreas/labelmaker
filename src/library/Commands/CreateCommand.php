<?php

namespace LabelMaker\Commands;

use Exception;
use getID3;
use GuzzleHttp\Psr7\Uri;
use LabelMaker\Api\LabelMakerApi;
use LabelMaker\Media\Loader\FallbackLoader;
use LabelMaker\Media\Loader\MediaFileTagLoaderComposite;
use LabelMaker\Media\Loader\Mp3Loader;
use LabelMaker\Media\Loader\Mp4Loader;
use LabelMaker\Options\CreateOptions;
use LabelMaker\Pdf\DompdfEngine;
use LabelMaker\Pdf\EngineInterface;
use LabelMaker\Pdf\MpdfEngine;
use LabelMaker\Pdf\PdfRenderer;
use LabelMaker\Reader\CsvReader;
use LabelMaker\Reader\MediaDirReader;
use LabelMaker\Reader\NullReader;
use LabelMaker\Reader\ReaderInterface;
use LabelMaker\Themes\Loaders\ThemeCompositeThemeLoader;
use LabelMaker\Themes\Loaders\ThemeDefaultsLoader;
use LabelMaker\Themes\Loaders\ThemeDirectoryFileLoader;
use LabelMaker\Themes\Loaders\ThemeFileLoader;
use LabelMaker\Themes\Theme;
use Mpdf\Mpdf;
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
    const OPT_OMIT_DEFAULT_CSS = "omit-default-css";
    const OPT_PAGE_TEMPLATE = "page-template";
    const OPT_DATA_HOOK = "data-hook";
    const OPT_DATA_URI = "data-uri";
    const OPT_DATA_RECORDS_PER_PAGE = "data-records-per-page";
    const OPT_OUTPUT_FILE = "output-file";
    const OPT_THEME = "theme";

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
        $this->addOption(static::OPT_DATA_HOOK, null, InputOption::VALUE_OPTIONAL, "path to custom data hook function to convert or prepare data before rendering the pdf");

        $this->addOption(static::OPT_THEME, null, InputOption::VALUE_OPTIONAL, "location of a custom theme containing templates and hooks to render a pdf (a theme is a shorthand for other template options)");

        $this->addOption(static::OPT_DATA_URI, null, InputOption::VALUE_OPTIONAL, "tries to load data from specified uri and provide it as variables for the page template");
        $this->addOption(static::OPT_DATA_RECORDS_PER_PAGE, null, InputOption::VALUE_OPTIONAL, sprintf("splits records loaded from --%s in chunks of this size before injecting into --%s", static::OPT_DATA_URI, static::OPT_PAGE_TEMPLATE), 0);
        $this->addOption(static::OPT_OUTPUT_FILE, null, InputOption::VALUE_REQUIRED, "specifies the output file");
        $this->addOption(static::OPT_OMIT_DEFAULT_CSS, null, InputOption::VALUE_NONE, "prevent labelmaker prepending normalize.css and providing some default classes prefixed with lmk- (e.g. .lmk-next-page)");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $options = $this->loadOptions($input);
            $pdfEngine = $this->createPdfEngine($options->pdfEngine);


            $recordLoader = $this->createRecordReader($options);
            $api = new LabelMakerApi();


            $themeLoader = new ThemeCompositeThemeLoader();
            $themeLoader->add(new ThemeDirectoryFileLoader($options->theme));
            $themeLoader->add(new ThemeFileLoader($options->documentTemplate, $options->documentCss, $options->pageTemplates, $options->dataHook));
            $themeLoader->add(new ThemeDefaultsLoader(CreateOptions::DEFAULT_DOCUMENT_TEMPLATE));
            $theme = $themeLoader->load(new Theme());

            if(!$input->getOption(static::OPT_OMIT_DEFAULT_CSS)) {
                $theme->documentCss = CreateOptions::DEFAULT_DOCUMENT_CSS . $theme->documentCss;
            }

            $violations = $this->validateTheme($theme);
            if(count($violations) > 0) {
                foreach($violations as $message) {
                    $this->error($message);
                }
                return Command::FAILURE;
            }


            $renderer = new PdfRenderer($recordLoader, $api, $options);
            file_put_contents($options->outputFile, $renderer->render($pdfEngine, $theme));


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
        // if format is not url, assume that a mediadir is used and encode its components via rawurlencode
        if ($dataUri && stripos($dataUri, "://") === false) {
            if (!file_exists($dataUri)) {
                throw new Exception(sprintf("the location of --%s does not exist (file_exists(%s))", static::OPT_DATA_URI, $dataUri));
            }
            $realPath = realpath($dataUri);
            if ($realPath === false) {
                throw new Exception(sprintf("the location of --%s does not exist (realpath)", static::OPT_DATA_URI));
            }

            $encodedPathParts = array_map(function ($pathPart) {
                return rawurlencode($pathPart);
            }, explode(DIRECTORY_SEPARATOR, $realPath));

            $dataUri = sprintf("%s://%s", CreateOptions::SCHEME_FILE, implode("/", $encodedPathParts));
        }

        $options = new CreateOptions();
        $options->pdfEngine = $input->getOption(static::OPT_PDF_ENGINE);
        $options->documentTemplate = $input->getOption(static::OPT_DOCUMENT_TEMPLATE);
        $options->documentCss = $input->getOption(static::OPT_DOCUMENT_CSS);
        $options->pageTemplates = $input->getOption(static::OPT_PAGE_TEMPLATE);
        $options->dataHook = $input->getOption(static::OPT_DATA_HOOK);
        $options->theme = $input->getOption(static::OPT_THEME);
        $options->dataUri = $dataUri ? new Uri($dataUri) : null;
        $options->dataRecordsPerPage = $input->getOption(static::OPT_DATA_RECORDS_PER_PAGE);
        $options->outputFile = $input->getOption(static::OPT_OUTPUT_FILE);

        return $options;
    }

    protected function validateTheme(Theme $theme): array{
        $violations = [];
        // todo: validate theme
        return $violations;


    }


    /**
     * @param string $pdfEngine
     * @return EngineInterface
     * @throws Exception
     */
    public function createPdfEngine(string $pdfEngine): EngineInterface
    {
        // todo: add generic options for different engines
        switch ($pdfEngine) {
            case CreateOptions::PDF_ENGINE_MPDF:
                $mpdf = new Mpdf(['tempDir' => sys_get_temp_dir()]);
                $mpdf->img_dpi = 300;
                return new MpdfEngine($mpdf);
            case CreateOptions::PDF_ENGINE_DOMPDF:
                $dompdf = new Dompdf();
                $dompdf->setPaper('A4');
                return new DompdfEngine($dompdf);
        }
        throw new Exception(sprintf("Invalid engine: %s", $pdfEngine));
    }

    /**
     * @throws Exception
     */
    public function createRecordReader(CreateOptions $options): ReaderInterface
    {
        if (!$options->dataUri) {
            return new NullReader();
        }


        switch ($options->dataUri->getScheme()) {
            case CreateOptions::SCHEME_CSV:
                return new CsvReader($options->dataUri, $options->dataRecordsPerPage);

            case CreateOptions::SCHEME_FILE:
                $tagLoader = new MediaFileTagLoaderComposite(new getID3, [
                    new Mp3Loader(),
                    new Mp4Loader()
                ]);
                return new MediaDirReader($tagLoader, $options->dataUri, $options->dataRecordsPerPage, $options->pageTemplates);

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
