<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\CsvFileArray;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

$csv = new CsvFileArray();
$csv->loadfile($argv[1], " ");

//// ip dash1 dash2 date op code bytes referrer browser

//$referrerCounts = countFields($csv, "referrer");
//foreach ($referrerCounts as $key => $value) {
//   echo $value . ":  $key\n";
//}

//$browserCounts = countFields($csv, "browser");
//foreach ($browserCounts as $key => $value) {
//   echo $value . ":  $key\n";
//}

echo "Count = " . count($csv->getRows()) . "\n";

$iMessages = countAndRemove($csv, "browser",  "GoogleMessages/20.2 facebookexternalhit/1.1 Facebot Twitterbot");
echo "iMessages = $iMessages\n";
echo "Count = " . count($csv->getRows()) . "\n";


function countAndRemove (CsvFileArray $csv, string $fieldName, string $fieldValue) {
   $count = 0;
   foreach ($csv->getRows() as $rowkey => $row) {
      if (str_contains($row[$fieldName], $fieldValue)) {
         ++$count;
         $csv->removeRow($rowkey);
      }
   }
   return $count;
}

function countFields (CsvFileArray $csv, string $fieldName): array {
   $counter = [];
   foreach ($csv->getRows() as $rowkey => $row) {
      $key = $row[$fieldName];
      $counter[$key] = ($counter[$key] ?? 0) + 1;
   }
   arsort($counter);
   return $counter;
}