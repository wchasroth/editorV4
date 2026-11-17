<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\CsvFileArray;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

$csv = new CsvFileArray();
$csv->loadfile($argv[1], " ");

//// ip dash1 dash2 date op code bytes referrer useragent

//$referrerCounts = countFields($csv, "referrer");
//foreach ($referrerCounts as $key => $value) {
//   echo $value . ":  $key\n";
//}



$totalRows = count($csv->getRows());
$summary = [];
$summary['preview-iMessages'] = countAndRemove($csv, "useragent",  "facebookexternalhit/1.1 Facebot Twitterbot");
$summary['preview-facebook']  = countAndRemove($csv, "useragent",  "facebookexternalhit/1.1");
$summary['bot-google']        = countAndRemove($csv, "useragent",  "www.google.com/bot");
$summary['bot-slack']         = countAndRemove($csv, "useragent",  "api.slack.com/robots");
$summary['bot-duck']          = countAndRemove($csv, "useragent",  "duckduckbot");
$summary['bot-bing']          = countAndRemove($csv, "useragent",  "bingbot");
$summary['bot-misc1']         = countAndRemove($csv, "useragent",  "robot");
$summary['bot-misc2']         = countAndRemove($csv, "useragent",  "/bot");
$summary['bot-misc3']         = countAndRemove($csv, "useragent",  "bot");
$summary['bot-spider']        = countAndRemove($csv, "useragent",  "spider");
$summary['bot-crawler']       = countAndRemove($csv, "useragent",  "crawler");

$summary['click-facebook']    = countAndRemove($csv, "op",         "?fbclid");
$summary['click-wcdp']        = countAndRemove($csv, "op",         "utm_source=Washtenaw+County+Democratic");
$summary['click-email-guide'] = countAndRemove($csv, "op",         "source=email-reminder-voter-guide-delivery-activities");
$summary['click-email-early'] = countAndRemove($csv, "op",         "source=email-early-voting-poll");
$summary['click-chatgpt']     = countAndRemove($csv, "op",         "utm_source=chatgpt.com");
$summary['click-icdp']        = countAndRemove($csv, "op",         "utm_campaign=ICDP");

$summary['ref-co-wcdp']          = countAndRemove($csv, "referrer",   "washtenawdems");
$summary['ref-mi-dems']          = countAndRemove($csv, "referrer",   "michigandems.com");
$summary['ref-co-oakland']       = countAndRemove($csv, "referrer",   "oaklandcountydemocrats.org");
$summary['ref-co-ingham']        = countAndRemove($csv, "referrer",   "inghamcountydemocraticparty");
$summary['ref-co-calhoun']       = countAndRemove($csv, "referrer",   "calhoundems.com");
$summary['ref-charles']          = countAndRemove($csv, "referrer",   "charlesroth.net");
$summary['ref-mi-indivisible']   = countAndRemove($csv, "referrer",   "statewideindivisiblemi.com");
$summary['ref-co-emmet']         = countAndRemove($csv, "referrer",   "emmetdemocrats.com");
$summary['ref-co-emmet']        += countAndRemove($csv, "referrer",   "emmetdemocrats.org");
$summary['ref-co-jackson']       = countAndRemove($csv, "referrer",   "jacksoncountydems.org");
$summary['ref-co-manistee']      = countAndRemove($csv, "referrer",   "manisteedems.org");
$summary['ref-co-manistee']     += countAndRemove($csv, "referrer",   "manisteecountydem");
$summary['ref-co-lenawee']       = countAndRemove($csv, "referrer",   "lenaweedemocraticparty.org");
$summary['ref-co-clinton']       = countAndRemove($csv, "referrer",   "clintoncodems.org");
$summary['ref-mi-qr']            = countAndRemove($csv, "referrer",   "qr.mivoter.org");
$summary['ref-mi-wave']          = countAndRemove($csv, "referrer",   "wave.mivoter.org");
countAndRemove($csv, "referrer",   "mivoter.org");
countAndRemove($csv, "referrer",   "MIvoter.org");

$summary['ref-facebook']         = countAndRemove($csv, "referrer",   "facebook.com");

$summary['search-google']        = countAndRemove($csv, "referrer",   "google.com");
$summary['search-google']       += countAndRemove($csv, "referrer",   "googlequicksearchbox");
$summary['search-google']       += countAndRemove($csv, "referrer",   "google.android");

$summary['zzz-none']             = countAndRemove($csv, "referrer",   "-");
$summary['zzz-misc']             = count($csv->getRows());


ksort($summary);
foreach ($summary as $sumkey => $sumvalue) echo "$sumkey:  $sumvalue\n";
echo "\n";

//$useragentCounts = countFields($csv, "useragent");
//foreach ($useragentCounts as $key => $value) {
//   echo $value . ":  $key\n";
//}

//$opCounts = countFields($csv, "op");
//foreach ($opCounts as $key => $value) {
//   echo $value . ":  $key\n";
//}

$referrerCounts = countFields($csv, "referrer");
foreach ($referrerCounts as $key => $value) {
   echo $value . ":  $key\n";
}


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