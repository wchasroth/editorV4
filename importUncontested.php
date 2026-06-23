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

   if ($seatIds[0] === -1) {  // Can't match -- log and skip!
      fwrite(STDERR, "Probable error: " . ArrayHelper::showKeyValuePairs($keyRow, ", ") . "\n");
      continue;
   }

   if ($seatIds[0] === 0) {
      $keyFields = makeKeyFieldsFromKeyRow($keyRow);
      $keyFields['source'] = 'jon-uncon';
      $result = $pdo->runSF ("INSERT INTO v4seats", "", new SqlFields($keyFields), true);
      $seatIds[] = $result->getInsertId();
   }

   $filing = getUncontestedFiling($pdo, $keyRow);

   $candidateId = findCandidateIdMatchingFiling($pdo, $filing, $seatIds);

   //---If we found a match, update each info field (where the original was empty)
   if ($candidateId > 0) {
      foreach (['web', 'email', 'phone', 'headshot_url', 'description', 'party'] as $fieldKey) {
         $fieldValue = $filing[$fieldKey] ?? '';
         if (! empty($fieldValue)) {
            $sql = "UPDATE v4candidates SET $fieldKey = '$fieldValue' WHERE id = $candidateId AND $fieldKey = ''";
            $result = $pdo->run($sql);
            if ($result->failed())  fwrite(STDERR, "Field update failed: $sql\n");
         }
      }
   }

   // No matches found.  First, look for EMPTY candidate rows that otherwise match.
   else {
      $emptyCandidateId = findMatchingEmptyCandidateId($pdo, $seatIds);
      $name = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $filing['name']);

      //---Found existing empty candidate row; fill it in ENTIRELY from v4filings row.
      if ($emptyCandidateId > 0) {
         $updateFields = [
            'name' => $name, 'party' => $filing['party'],
            'web' => $filing['web'], 'email' => $filing['email'], 'phone' => $filing['phone'],
            'headshot_url' => $filing['headshot_url'] ?? '', 'description' => $filing['description'],
            'source' => 'jon-uncon'
         ];
         $sqlFields = new SqlFields($updateFields);
         $sql = "UPDATE V4candidates SET " . $sqlFields->getSetFragment() . " WHERE id=$emptyCandidateId ";
         $updateResult = $pdo->run($sql);
         if ($updateResult->failed()) {
            fwrite(STDERR, "Candidate update failed: " . $updateResult->getError() . "  $sql\n");
         }
      }

      //---No rows at all in v4candidates; add this candidate, pointing to the 1st seatId.
      else {
         $insertFields = [
            'seat_id' => $seatIds[0], 'name' => $name, 'party' => $filing['party'],
            'web' => $filing['web'], 'email' => $filing['email'], 'phone' => $filing['phone'],
            'headshot_url' => $filing['headshot_url'] ?? '', 'description' => $filing['description'],
            'source' => 'jon-uncon'
         ];
         $insertResult = $pdo->runSF("INSERT INTO v4candidates", "", new SqlFields($insertFields), true);
         if ($insertResult->failed()) fwrite(STDERR, "Insert candidate failed: " . $insertResult->getError() . "  " . $insertResult->getRawSql() . "\n");
      }
   }
}

function findMatchingEmptyCandidateId (AlfredPDO $pdo, array $seatIds): int {
   $sql = "SELECT id FROM v4candidates WHERE seat_id IN (" . Str::join($seatIds, ',') . ") AND name='' ";
   $queryResult = $pdo->run($sql);
   if ($queryResult->getRowCount() === 0)  return -1;
   return intval($queryResult->getSingleValue('id'));
}

function findCandidateIdMatchingFiling (AlfredPDO $pdo, array $filing, array $seatIds): int {
   $filingName = new MatchableName($filing['name']);

   $sql = "SELECT * FROM v4candidates WHERE seat_id IN (" . Str::join($seatIds, ',') . ")";
   $queryResult = $pdo->run($sql);
   $rows = $queryResult->getRows();
   $names = [];
   foreach ($rows AS $row)  $names[] = new MatchableName($row['name']);
   $bestIndex = $filingName->findBestMatch($names, 2);
// if ($bestIndex >= 0)  echo "Found match: " . $rows[$bestIndex]['name'] . "\n";
// else                  echo "NO match:    " . $filing['name'] . "\n";
   return ($bestIndex >= 0 ? intval($rows[$bestIndex]['id']) : -1);
}

function getUncontestedFiling(AlfredPDO $pdo, array $keyRow): array {
   $keyFields = makeKeyFieldsFromKeyRow($keyRow);
   $result = $pdo->runSF("SELECT * FROM v4filings WHERE", "", new SqlFields($keyFields), true);
   $rows = $result->getRows();
   if (count($rows) != 1) fwrite(STDERR, "getUncontestedFiling: Expected one row, got " . count($rows) . "\n");
   return $rows[0] ?? [];
}

//function getSeatsMatchingKeyRow (AlfredPDO $pdo, array $keyRow) {
//   $sqlFields  = new SqlFields(makeKeyFieldsFromKeyRow($keyRow));
//   $termClause = " AND ( termlen =0  OR ((termcycle + 6*termlen) - 2026) % termlen = 0) ";
//   $result = $pdo->runSF("SELECT id FROM v4seats WHERE", "$termClause", $sqlFields, true);
//   return $result->getArrayOf('id');
//}

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

function getKeysForUncontestedFilings(AlfredPDO $pdo): array {
   $result = [];
   $sql = "SELECT count(*) AS ct,    org, office, district, subdist, partialterm, partialend, confidence "
        . "  FROM v4filings GROUP BY org, office, district, subdist, partialterm, partialend, confidence ";
   $queryResult = $pdo->run($sql);
   foreach ($queryResult->getRows() as $row) {
      if (intval($row['ct']) == 1)  $result[] = $row;
   }
   return $result;
}