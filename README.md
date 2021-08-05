# labelmaker


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
