<?php

require dirname(dirname(__FILE__))."/vendor/autoload.php";

use hydra\text\TextTokenizer;
use hydra\text\StreamTokenizer;
use hydra\text\reader\CsvReader;
use hydra\text\reader\LineReader;

// ini_set("memory_limit", "1M");

// $memory_limit = ini_get('memory_limit');
// echo $memory_limit;
// exit;

// $loop = React\EventLoop\Factory::create();
$file = dirname(__FILE__)."/old_faithful.csv";
// $csv = new CsvReader($file, $loop);
$csv = new CsvReader($file);

$csv->on("row", function ($row) {
    var_dump(json_encode($row));
});

//$loop->run();

$csv->run();