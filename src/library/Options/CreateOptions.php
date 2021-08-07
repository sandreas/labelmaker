<?php

namespace LabelMaker\Options;

use GuzzleHttp\Psr7\Uri;

class CreateOptions
{
    const PDF_ENGINE_MPDF = "mpdf";
    const PDF_ENGINE_DOMPDF = "dompdf";

    const SCHEME_CSV = "csv";
    const SCHEME_MEDIA_DIR = "mediadir";

    const DEFAULT_DOCUMENT_TEMPLATE = <<<EOT
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style>
  {{ css|raw }}
  </style>
</head>
<body>
{{ html|raw }}
</body>
</html>
EOT;

    const DEFAULT_DOCUMENT_CSS = <<<EOT
/*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}main{display:block}h1{font-size:2em;margin:.67em 0}hr{box-sizing:content-box;height:0;overflow:visible}pre{font-family:monospace,monospace;font-size:1em}a{background-color:transparent}abbr[title]{border-bottom:none;text-decoration:underline;text-decoration:underline dotted}b,strong{font-weight:bolder}code,kbd,samp{font-family:monospace,monospace;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}img{border-style:none}button,input,optgroup,select,textarea{font-family:inherit;font-size:100%;line-height:1.15;margin:0}button,input{overflow:visible}button,select{text-transform:none}[type=button],[type=reset],[type=submit],button{-webkit-appearance:button}[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner,button::-moz-focus-inner{border-style:none;padding:0}[type=button]:-moz-focusring,[type=reset]:-moz-focusring,[type=submit]:-moz-focusring,button:-moz-focusring{outline:1px dotted ButtonText}fieldset{padding:.35em .75em .625em}legend{box-sizing:border-box;color:inherit;display:table;max-width:100%;padding:0;white-space:normal}progress{vertical-align:baseline}textarea{overflow:auto}[type=checkbox],[type=radio]{box-sizing:border-box;padding:0}[type=number]::-webkit-inner-spin-button,[type=number]::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}[type=search]::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}details{display:block}summary{display:list-item}template{display:none}[hidden]{display:none}  
        .lmk-next-page { page-break-before: always; }
        .lmk-card {width:83.8mm;height:50.8mm}
        .lmk-col-0 {margin-left:22mm}
        .lmk-col-1 {margin-left:0;}
        .lmk-row-0 {margin-top:22mm}
        .lmk-row-1 {}
        .lmk-row-2 {}
        .lmk-row-3 {}
        .lmk-row-4 {}
EOT;

    public string $pdfEngine;
    public ?string $documentTemplate;
    public ?string $documentCss;
    /** @var string[] */
    public array $pageTemplates;
    public ?Uri $dataUri;
    public int $dataRecordsPerPage;
    public string $outputFile;

}
