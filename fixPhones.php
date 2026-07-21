#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\FieldFormatFixer;

require "vendor/autoload.php";

// Change all incumbent and candidate phone #s to a standard format: "123-456-7890 (additional text)".

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$incumbentsFixed = fixPhonesForTable($pdo, "v4incumbents");
$candidatesFixed = fixPhonesForTable($pdo, "v4candidates");
echo "Incumbent phones fixed: $incumbentsFixed\n";
echo "Candidate phones fixed: $candidatesFixed\n";

function fixPhonesForTable(AlfredPDO $pdo, string $tableName): int {
   $sql = "SELECT id, phone FROM $tableName WHERE phone != ''";
   $result = $pdo->run($sql);
   $count = 0;
   foreach ($result->getRows() as $row) {
      $phone = FieldFormatFixer::fixPhone($row['phone']);
      if ($phone != $row['phone']) {
         $sql = "UPDATE $tableName SET phone = '$phone' WHERE id = $row[id]";
         $pdo->run($sql);
         ++$count;
      }
   }
   return $count;
}