<?php

namespace LabelMaker\Commands;

use Exception;
use getID3;
use GuzzleHttp\Psr7\Uri;
use LabelMaker\Api\LabelMakerApi;
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
use LabelMaker\Themes\Loaders\ThemeFallbackLoader;
use LabelMaker\Themes\Loaders\ThemeLoader;
use LabelMaker\Themes\Loaders\ThemeFileLoader;
use LabelMaker\Themes\Theme;
use Mpdf\Mpdf;
use SplFileInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Dompdf\Dompdf;
use Throwable;


class CreateCommand extends AbstractCommand
{
    const OPT_PDF_ENGINE = "pdf-engine";
    const OPT_PDF_ENGINE_OPTIONS = "pdf-engine-options";

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

    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('create a new pdf output file based on templates in the input directory or a provided theme');

        $this->addOption(static::OPT_PDF_ENGINE, null, InputOption::VALUE_OPTIONAL, "pdf engine that should be used (mpdf, dompdf)", CreateOptions::PDF_ENGINE_MPDF);
        $this->addOption(static::OPT_PDF_ENGINE_OPTIONS, null, InputOption::VALUE_OPTIONAL, "json file with options for PDF engine (mpdf, dompdf)", CreateOptions::PDF_ENGINE_MPDF);

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

            $pdfEngineOptionsFile = $input->getOption(static::OPT_PDF_ENGINE_OPTIONS);
            $pdfEngineOptions = [];
            if($pdfEngineOptionsFile && file_exists($pdfEngineOptionsFile)) {
                $pdfEngineOptions = json_decode($pdfEngineOptionsFile, true);
            }

            $pdfEngine = $this->createPdfEngine($options->pdfEngine, $pdfEngineOptions);

            $recordLoader = $this->createRecordReader($options);
            $api = new LabelMakerApi();

            $themeLoader = new ThemeCompositeThemeLoader();
            $themeLoader->add(new ThemeLoader($options->theme));
            $themeLoader->add(new ThemeFileLoader($options->documentTemplate, $options->documentCss, $options->pageTemplates, $options->dataHook));
            $themeLoader->add(new ThemeFallbackLoader(CreateOptions::DEFAULT_DOCUMENT_TEMPLATE));
            $theme = $themeLoader->load(new Theme());

            if(!$input->getOption(static::OPT_OMIT_DEFAULT_CSS)) {
                $theme->documentCss = CreateOptions::DEFAULT_DOCUMENT_CSS . ($theme->documentCss ?? "");
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


        } catch (Throwable $e) {
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

            $realPathObj = new SplFileInfo($realPath);
            $ext = $realPathObj->isFile() ? $realPathObj->getExtension() : null;
            $destinationScheme = in_array($ext, CreateOptions::FILE_EXTENSION_SCHEMES) ? $ext : CreateOptions::SCHEME_FILE;

            $encodedPathParts = array_map(function ($pathPart) {
                return rawurlencode($pathPart);
            }, explode(DIRECTORY_SEPARATOR, $realPath));

            $dataUri = sprintf("%s://%s", $destinationScheme, ltrim(implode("/", $encodedPathParts), "/"));
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

        if(empty($theme->documentTemplate)) {
            $violations[] = "document template is empty";
        }

        if(count($theme->pageTemplates) === 0) {
            $violations[] = "no page template defined";
        }

        return $violations;
    }


    /**
     * @param string $pdfEngine
     * @param array $options
     * @return EngineInterface
     * @throws Exception
     */
    public function createPdfEngine(string $pdfEngine, array $options=[]): EngineInterface
    {
        try  {
            $cacheId = random_bytes(32);
        } catch(Exception $e)  {
            $cacheId = uniqid("", true);
        }
        switch ($pdfEngine) {
            // https://mpdf.github.io/reference/mpdf-variables/overview.html
            case CreateOptions::PDF_ENGINE_MPDF:
                if(count($options) === 0) {
                    $options["img_dpi"] = 300;
                }
                // usage in phar fails with default tempdir
                $options["tempDir"] ??= sys_get_temp_dir()."/".bin2hex($cacheId);
                if(!is_dir($options["tempDir"])) {
                    mkdir($options["tempDir"], 755, true);
                }
                $mpdf = new Mpdf($options);
                return new MpdfEngine($mpdf);
            // https://github.com/dompdf/dompdf/blob/master/src/Options.php
            case CreateOptions::PDF_ENGINE_DOMPDF:
                $dompdf = new Dompdf($options);
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
                return new CsvReader($options->dataUri);

            case CreateOptions::SCHEME_FILE:
                $tagLoader = new MediaFileTagLoaderComposite(new getID3, [
                    new Mp3Loader(),
                    new Mp4Loader()
                ]);
                return new MediaDirReader($tagLoader, $options->dataUri);

        }
        throw new Exception(sprintf("Invalid engine: %s", $options->pdfEngine));
    }
}
