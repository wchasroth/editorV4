<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\SqlFields;

require_once('vendor/autoload.php');

// importMarkContested.php
//    Scan thru the v4filings table (produced by importJsonFilings), and find all of the
//    contested races.  Mark them as contested=1.

$env        = new EnvFile("_env");
$pdo        = PdoHelper::makePdo($env);

$contestedKeyRows = getKeysForContestedFilings($pdo);
foreach ($contestedKeyRows as $keyRow) {
   $fields = ['org' => $keyRow['org'], 'office' => $keyRow['office'], 'district' => $keyRow['district'],
      'subdist' => $keyRow['subdist'], 'partialterm' => $keyRow['partialterm'], 'partialend' => $keyRow['partialend']];
   $sqlFields = new SqlFields($fields);
   $sql = "UPDATE v4filings SET contested=1 WHERE " . $sqlFields->getUpdateFragment();
   $result = $pdo->run($sql);
   if ($result->failed())  fwrite(STDERR, "Error: $sql\n");
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