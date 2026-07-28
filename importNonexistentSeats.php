<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\ArrayHelper;
use CharlesRothDotNet\Alfred\SqlFields;

require_once('vendor/autoload.php');

// importNonexistentSeats.php
//    Scan thru the v4filings table (produced by importJsonFilings), and find any seats
//    that DO NOT EXIST in the v4seats table.  Create them!  Assume termcycle is 2026.

$env        = new EnvFile("_env");
$pdo        = PdoHelper::makePdo($env);

$filingRows = getKeysFromFilings($pdo);
foreach ($filingRows as $filingRow) {
   $count = getCountSeatsMatchingKeyRow($pdo, $filingRow);

   if ($count === 0) {
      $fields = makeKeyFieldsFromKeyRow($filingRow);
      $fields['termcycle'] = 2026;
//    $fields['subdist'] = intval($fields['subdist']);
      $sqlFields = new SqlFields($fields);
      $sql = "INSERT INTO v4candidates " . $sqlFields->getInsertFragment();
      echo "$sql\n";
//    fwrite(STDERR, "Possible missing seat: " . ArrayHelper::showKeyValuePairs($filingRow, ", ") . "\n");
   }
}

function getCountSeatsMatchingKeyRow (AlfredPDO $pdo, array $keyRow): int {
   $sqlFields  = new SqlFields(makeKeyFieldsFromKeyRow($keyRow));
   $sql = "SELECT count(*) AS ct FROM v4seats WHERE " . $sqlFields->getSelectFragment();
   $queryResult = $pdo->run($sql);
   return intval($queryResult->getSingleValue('ct'));
}

function makeKeyFieldsFromKeyRow(array $keyRow): array {
// return ['org' => $keyRow['org'], 'office' => $keyRow['office'], 'district' => $keyRow['district'], 'subdist' => $keyRow['subdist']];
   return ['org' => $keyRow['org'], 'office' => $keyRow['office'], 'district' => $keyRow['district']];
}

function getKeysFromFilings(AlfredPDO $pdo): array {
// $sql = "SELECT DISTINCT org, office, district, subdist FROM v4filings";
   $sql = "SELECT DISTINCT org, office, district FROM v4filings";
   $queryResult = $pdo->run($sql);
   return $queryResult->getRows();
}
