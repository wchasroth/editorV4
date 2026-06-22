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

// importUncontested.php
//    Scan thru the v4filings table (produced by importJsonFilings), and find all of the
//    uncontested races.  Use those to populate v4candidates.

$env        = new EnvFile("_env");
$pdo        = PdoHelper::makePdo($env);

$uncontestedKeyRows = getKeysForUncontestedFilings($pdo);
foreach ($uncontestedKeyRows as $keyRow) {
   $seatIds = getSeatsMatchingKeyRow($pdo, $keyRow);

   if (count($seatIds) === 0) {
      $keyFields = makeKeyFieldsFromKeyRow($keyRow);
      $keyFields['source'] = 'jon-uncon';
      $result = $pdo->runSF ("INSERT INTO v4seats", "", new SqlFields($keyFields), true);
      $seatIds[] = $result->getInsertId();
//    echo "INSERT v4seats " . ArrayHelper::showKeyValuePairs($keyFields) . "\n";
   }

   $filing = getUncontestedFiling($pdo, $keyRow);

   $candidateId = findCandidateIdMatchingFiling($pdo, $filing, $seatIds);

//   $sql = "SELECT * FROM v4candidates WHERE seat_id IN (" . Str::join($candidateSeatIds, ',') . ")";
//   $candidates = $pdo->run($sql)->getRows();
//   $filings = $pdo->runSF("SELECT * FROM v4filings WHERE", "$termClause", new SqlFields($sqlFields), true);
}

function findCandidateIdMatchingFiling (AlfredPDO $pdo, array $filing, array $seatIds): int {
   $filingName = new MatchableName($filing['name']);

   $sql = "SELECT * FROM v4candidates WHERE seat_id IN (" . Str::join($seatIds, ',') . ")";
   $queryResult = $pdo->run($sql);
   $rows = $queryResult->getRows();
   $names = [];
   foreach ($rows AS $row)  $names[] = new MatchableName($row['name']);
   $bestIndex = $filingName->findBestMatch($names, 2);
   if ($bestIndex >= 0)  echo "Found match: " . $rows[$bestIndex]['name'] . "\n";
   else                  echo "NO match:    " . $filing['name'] . "\n";
   return ($bestIndex >= 0 ? intval($rows[$bestIndex]['id']) : -1);
}

function getUncontestedFiling(AlfredPDO $pdo, array $keyRow): array {
   $keyFields = makeKeyFieldsFromKeyRow($keyRow);
   $result = $pdo->runSF("SELECT * FROM v4filings WHERE", "", new SqlFields($keyFields), true);
   $rows = $result->getRows();
   if (count($rows) != 1) fwrite(STDERR, "getUncontestedFiling: Expected one row, got " . count($rows) . "\n");
   return $rows[0] ?? [];
}

function getSeatsMatchingKeyRow (AlfredPDO $pdo, array $keyRow) {
   $sqlFields  = new SqlFields(makeKeyFieldsFromKeyRow($keyRow));
   $termClause = " AND ( termlen =0  OR ((termcycle + 6*termlen) - 2026) % termlen = 0) ";
   $result = $pdo->runSF("SELECT id FROM v4seats WHERE", "$termClause", $sqlFields, true);
   return $result->getArrayOf('id');
}

function makeKeyFieldsFromKeyRow(array $keyRow): array {
   return ['org' => $keyRow['org'], 'office' => $keyRow['office'], 'district' => $keyRow['district'], 'subdist' => $keyRow['subdist']];
}

function getKeysForUncontestedFilings(AlfredPDO $pdo): array {
   $result = [];
   $sql = "SELECT count(*) AS ct, org, office, district, subdist FROM v4filings GROUP BY org, office, district, subdist";
   $queryResult = $pdo->run($sql);
   foreach ($queryResult->getRows() as $row) {
      if (intval($row['ct']) == 1)  $result[] = $row;
   }
   return $result;
}



function findMatchingCandidates(AlfredPDO $pdo, array $seatIds): array {
   return [];
}

