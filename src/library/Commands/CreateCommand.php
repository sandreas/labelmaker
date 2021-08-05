<?php

namespace LabelMaker\Commands;

use CallbackFilterIterator;
use Dompdf\Options;
use getID3;
use LabelMaker\Api;
use LabelMaker\Media\MediaFileReader;
use LabelMaker\Paths\PathItem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Dompdf\Dompdf;
use Throwable;
use Twig\Environment;
use Twig\Loader\ArrayLoader;


class CreateCommand extends AbstractCommand
{
    protected static $defaultName = 'create';

    protected array $extensions = ["txt", "jpg"];

    protected string $optPageTemplate = ".labelmaker.page.twig";

    protected string $documentTemplate = <<<EOT
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style>/*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}main{display:block}h1{font-size:2em;margin:.67em 0}hr{box-sizing:content-box;height:0;overflow:visible}pre{font-family:monospace,monospace;font-size:1em}a{background-color:transparent}abbr[title]{border-bottom:none;text-decoration:underline;text-decoration:underline dotted}b,strong{font-weight:bolder}code,kbd,samp{font-family:monospace,monospace;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}img{border-style:none}button,input,optgroup,select,textarea{font-family:inherit;font-size:100%;line-height:1.15;margin:0}button,input{overflow:visible}button,select{text-transform:none}[type=button],[type=reset],[type=submit],button{-webkit-appearance:button}[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner,button::-moz-focus-inner{border-style:none;padding:0}[type=button]:-moz-focusring,[type=reset]:-moz-focusring,[type=submit]:-moz-focusring,button:-moz-focusring{outline:1px dotted ButtonText}fieldset{padding:.35em .75em .625em}legend{box-sizing:border-box;color:inherit;display:table;max-width:100%;padding:0;white-space:normal}progress{vertical-align:baseline}textarea{overflow:auto}[type=checkbox],[type=radio]{box-sizing:border-box;padding:0}[type=number]::-webkit-inner-spin-button,[type=number]::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}[type=search]::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}details{display:block}summary{display:list-item}template{display:none}[hidden]{display:none}  
        .lmk-page-group {page-break-after: always;}
        .lmk-page { page-break-before: always; }
        .lmk-card {width:83.8mm;height:50.8mm}
        .lmk-col-0 {margin-left:22mm}
        .lmk-col-1 {margin-left:0;}
        .lmk-row-0 {margin-top:22mm}
        .lmk-row-1 {}
        .lmk-row-2 {}
        .lmk-row-3 {}
        .lmk-row-4 {}
  </style>
</head>
<body>
{{ html|raw }}
</body>
</html>
EOT;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('create will create a new pdf output file based on templates in the input directory or a provided theme');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputPath = $input->getArgument(static::ARGUMENT_INPUT);

        $options = new Options();
        $options->setIsRemoteEnabled(true);
//        $options->setRootDir($inputPath);
//        $options->setChroot(array($inputPath));

        $dompdf = new Dompdf($options);

        $dompdf->setPaper('A4');

        if (!is_dir($inputPath)) {
            $this->error(sprintf("input path %s is not a directory", $inputPath));
            return Command::FAILURE;
        }

        $api = new Api(new getID3, new MediaFileReader);
        $paths = $this->loadPathItems($inputPath);

        $pathItemGroups = [];
        foreach ($paths as $pathItem) {
            if ($pathItem->pageTemplate === null) {
                $this->warning(sprintf("no page template for %s", $pathItem->path));
                continue;
            }
            $key = (string)$pathItem->pageTemplate;
            $pathItemGroups[$key] ??= [];
            $pathItemGroups[$key][] = $pathItem;
        }

        $html = "";
        foreach ($pathItemGroups as $pageTemplate => $pathItems) {
            $loader = new ArrayLoader();
            $loader->setTemplate("page", file_get_contents($pageTemplate));
            $twig = new Environment($loader);

            $pageItems = [];
            foreach ($pathItems as $pathItem) {
                $pageItems[] = [
                    "path" => $pathItem->path,
                    "files" => $pathItem->files // todo: MediaFile, JsonFile, etc.
                ];
            }
            try {
                $html .= $twig->render('page', [
                    'api' => $api,
                    'pageItems' => $pageItems
                ]);
            } catch (Throwable $e) {
                $this->warning(sprintf("Could not render template %s: %s", $pageTemplate, $e->getMessage()));
                $this->debug($e->getTraceAsString());
            }

        }
        $loader = new ArrayLoader();
        $loader->setTemplate("document", $this->documentTemplate);
        $twig = new Environment($loader);
        $documentHtml = $twig->render('document', [
            'html' => $html
        ]);


        $dompdf->loadHtml($documentHtml);
        $dompdf->render();

        $output = $dompdf->output();
        file_put_contents(__dir__ . '/../../../var/output/output.pdf', $output);

// print_r($paths);


//        $file = "/home/mediacenter/shared/audio/audiobooks/Fantasy/Alana Falk/Gods of Ivy Hall/1 - Cursed Kiss.m4b";
//        $file = "/home/mediacenter/projects/labelmaker/var/input/animals/Affe/a-wie-affe.mp3";
//        $mediaFile = $api->loadMediaFile($file);
//        print_r($mediaFile);
//        $analyzeResult = $id3->analyze($file);
//        // print_r(array_keys($analyzeResult['quicktime']));
//
//        $container = $analyzeResult['quicktime']['moov'];
//        print_r(array_keys($this->searchCover($container)));

//        exit;

//        $getID3 = new getID3;
//        $fileinfo = $getID3->analyze($filename);
//        $picture = @$fileinfo['id3v2']['APIC'][0]['data']; // binary image data
//        $comments = @$fileinfo['id3v2']['comments']; // multi-dimensional array

        // page -> pageItems
        // pageItem -> path, data
        // labelmaker var/input/ --data-file="audible.txt:json"

        // config (.labelmaker.json)
        // page template
        // - pageItems[]
        // pageItem template
        // - data, image, mapping?
        //


//        $pageTemplate = <<<EOT
//<div class="page">
//
//</div>
//EOT;


//
//        // $this->warning('testing');
//        $dompdf = new Dompdf();
//        $dompdf->loadHtml('hello world');
//
//// (Optional) Setup the paper size and orientation
//        $dompdf->setPaper('A4');
//
//
//// Render the HTML as PDF
//        $dompdf->render();
//
//        $output = $dompdf->output();
//        file_put_contents(__dir__.'/../../../var/output/output.pdf', $output);


        // ... put here the code to create the user

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }

    /**
     * @param $inputPath
     * @return PathItem[]
     */
    protected function loadPathItems($inputPath): array
    {
        $baseIterator = new RecursiveDirectoryIterator($inputPath);
        $innerIterator = new RecursiveIteratorIterator($baseIterator);
        $callbackIterator = new CallbackFilterIterator($innerIterator, function (SplFileInfo $file) {
            return in_array($file->getExtension(), $this->extensions, true);
        });

        $paths = [];
        foreach ($callbackIterator as $file) {
            $path = $file->getPath();
            $paths[$path] ??= [];
            $paths[$path][] = $file;
        }

        $pathItems = [];
        foreach ($paths as $path => $files) {
            $pathItems[] = $this->buildPathItem($inputPath, $path, $files);
        }

        return $pathItems;
    }

    protected function buildPathItem($inputPath, $path, $files): PathItem
    {
        $splPath = new SplFileInfo($path);
        $pathItem = new PathItem();
        $pathItem->files = $files;
        $pathItem->path = new SplFileInfo($path);

        $splInputPath = new SplFileInfo($inputPath);
        $inputPathLen = strlen($splInputPath);

        $pageTemplate = null;
        $len = strlen($splPath);
        do {
            $pageTemplateFile = new SplFileInfo($splPath . "/" . $this->optPageTemplate);
            if ($pageTemplateFile->isFile()) {
                $pageTemplate = $pageTemplateFile;
            }

            $splPath = new SplFileInfo($splPath->getPath());
            $oldLen = $len;
            $len = strlen($splPath);
        } while ($len >= $inputPathLen && $len < $oldLen && ($pageTemplate === null/* || $pageItemTemplate === null*/));

        $pathItem->pageTemplate = $pageTemplateFile;
        return $pathItem;
    }
}
