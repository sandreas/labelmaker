# labelmaker

## Todo
- [ ] add `--theme` parameter for template+css packages (e.g. in form of a zip file)
- [ ] fix and test `mediadir` reader

## themes
- default values are the lowest prio "fallback" - page-template has no fallback
- `--theme` is the second lowest prio "fallback"
- `--page-template` will be used for override, if a file is present in the current folder?

## Via data file
```bash
labelmaker --document-template="document.twig" --document-css="docment.css" --page-template="page.twig" --data-file="csv:///home/user/file.csv" --count-per-page=8
```

## Different engine
```bash
labelmaker --engine="dompdf"
```

## Via data directory
```bash
labelmaker --document-template="document.twig" --document-css="docment.css" --page-template="page.twig" --data-dir="/home/user/audiobooks/" --count-per-page=8
```

## Via page only
```bash
labelmaker --page-template="page.twig"
```


## Notes

```

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
```
