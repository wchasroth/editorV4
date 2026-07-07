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

// importContestedNonexistentSeats.php
//    Scan thru the v4filings table (produced by importJsonFilings), and find all of the
//    contested races.  List any of them clearly reference seats that DO NOT EXIST in the
//    v4seats table.
//
//    When run on the 7/6/26 data from Jon O, only 13 rows were found, and some of those were suspect.
//    So right now we're not doing anything with them.  (If there had been a lot, we might have
//    inserted empty seats in v4seats -- but for now, the cost/benefit doesn't seem worth it.)

$env        = new EnvFile("_env");
$pdo        = PdoHelper::makePdo($env);

$contestedKeyRows = getKeysForContestedFilings($pdo);
foreach ($contestedKeyRows as $keyRow) {
   $seatIds = getSeatsMatchingKeyRow($pdo, $keyRow);

   if ($seatIds[0] === 0) {  // Can't match -- log and skip!
      fwrite(STDERR, "Possible missing seat: " . ArrayHelper::showKeyValuePairs($keyRow, ", ") . "\n");
   }
}

function getSeatsMatchingKeyRow (AlfredPDO $pdo, array $keyRow) {
   $sqlFields  = new SqlFields(makeKeyFieldsFromKeyRow($keyRow));
   $queryResult = $pdo->runSF("SELECT id, termlen, termcycle FROM v4seats WHERE", "", $sqlFields, true);
   $rows = $queryResult->getRows();

   $result = [];
   //---phase 1: this election, or partial term
   foreach ($rows AS $row) {
      $id          = intval($row['id']);
      $termlen     = intval($row['termlen']);
      $termcycle   = intval($row['termcycle']);
      $partialterm = intval($keyRow['partialterm']);
      $partialend  = intval($keyRow['partialend']);
      if ($termlen === 0                       ||                           /* unknown term length */
          cycle($termcycle,  $termlen)  === 0  ||                           /* up for election in 2026 */
          cycle($partialend, $termlen)  === cycle($termcycle, $termlen) ||  /* partial term ends in matching year */
          $partialterm === 1)                                               /* partialterm, but we don't know ending year */
        $result[] = $id;
   }
   if (count($result) > 0)  return $result;

   //---phase 2: no matches, but an unknown number of seats -- tell caller to insert a new row in v4seats.
   $sql = "SELECT seats FROM s4titles WHERE org='{$keyRow['org']}' AND office='{$keyRow['office']}'";
   $seats = $pdo->run($sql)->getSingleValue('seats');
   if ($seats === 0)  return [0];

   //---phase 3: no matches, hard-coded # of seats (usually 1): tell caller to skip inserting this row
   return [-1];
}

function cycle (int $termcycle, int $termlen): int {
   return ($termcycle + 6 * $termlen - 2026) % $termlen;
}

function makeKeyFieldsFromKeyRow(array $keyRow): array {
   return ['org' => $keyRow['org'], 'office' => $keyRow['office'], 'district' => $keyRow['district'], 'subdist' => $keyRow['subdist']];
}

function getKeysForContestedFilings(AlfredPDO $pdo): array {
   $result = [];
   $sql = "SELECT count(*) AS ct,    org, office, district, subdist, partialterm, partialend "
        . "  FROM v4filings GROUP BY org, office, district, subdist, partialterm, partialend ";
   $queryResult = $pdo->run($sql);
   foreach ($queryResult->getRows() as $row) {
      if (intval($row['ct']) > 1)  $result[] = $row;
   }
   return $result;
}
