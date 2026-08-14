#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

// Produce report on candidates for upcoming election.

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$greenCounties  = "3, 13, 23, 28, 33, 38, 39, 41, 47, 50, 61, 63, 70, 81";
$yellowCounties = "9, 11, 25, 46, 56, 58, 73, 74, 82";
$inCounties = "($greenCounties)";

echo Str::join(["Category", "Seats", "2026", "Cans+Picks", "Cans", "Web", "Reviewed", "Endorsed"], "\t") . "\n";

$category = "(s.org LIKE ('us%') OR s.org LIKE('mi%') OR s.org = 'crt-sup') ";
showTotalsByCategory($pdo, "ToT", $category, "");

$category = "s.org LIKE 'cnty%' AND s.district IN $inCounties ";
showTotalsByCategory($pdo, "County", $category, "");

$category = "(s.org LIKE 'city%' AND j.county_id IN $inCounties) ";
showTotalsByCategory($pdo, "City", $category, "LEFT JOIN s4jurisdictions AS j ON (s.district=j.id)" );

$category = "(s.org LIKE 'town%' AND j.county_id IN $inCounties) ";
showTotalsByCategory($pdo, "Township", $category, "LEFT JOIN s4jurisdictions AS j ON (s.district=j.id)" );

$category = "(s.org LIKE 'vil%' AND v.county_id IN $inCounties) ";
showTotalsByCategory($pdo, "Village", $category, "LEFT JOIN s4villages AS v ON (s.district=v.id)" );

$category = "(s.org = 'schl-cou' AND o.county_id IN $inCounties) ";
showTotalsByCategory($pdo, "School*", $category, "LEFT JOIN s4schools AS o ON (s.district=o.id)" );

$category = "(s.org LIKE 'crt-%' AND s.org!='crt-sup' AND t.county_id IN $inCounties) ";
showTotalsByCategory($pdo, "Court*", $category, "LEFT JOIN v4courts AS t ON (s.district=t.shortname)" );

function showTotalsByCategory (AlfredPDO $pdo, string $rowName, string $category, string $joinTable): void {
   $select = "SELECT COUNT(*) AS number FROM v4seats AS s ";
   $is2026 = " (s.termcycle=2026 OR s.is_open=1) ";
   $joinCan = " LEFT JOIN v4candidates AS c ON (c.seat_id = s.id) ";
   $hasName = " (c.name IS NOT NULL AND c.name!='') ";
   $hasWeb  = " (c.web  IS NOT NULL AND c.web !='') ";
   $hasRev  = " (c.reviewed = 1) ";
   $hasEnd  = " (c.endorsed = 1) ";

   $totTotal = runQuery($pdo, "$select          $joinTable WHERE $category");
   $tot2026  = runQuery($pdo, "$select          $joinTable WHERE $category AND $is2026");
   $totCand  = runQuery($pdo, "$select $joinCan $joinTable WHERE $category AND $is2026 AND $hasName");
   $totWeb   = runQuery($pdo, "$select $joinCan $joinTable WHERE $category AND $is2026 AND $hasName AND $hasWeb");
   $totRev   = runQuery($pdo, "$select $joinCan $joinTable WHERE $category AND $is2026 AND $hasName AND $hasRev");
   $totEnd   = runQuery($pdo, "$select $joinCan $joinTable WHERE $category AND $is2026 AND $hasName AND $hasEnd");
   $pickQuery = "SELECT COUNT(DISTINCT f.name) AS number "
      . "  FROM      v4seats      AS s "
      . "  LEFT JOIN v4candidates AS c  ON (c.seat_id = s.id) "
      . "  LEFT JOIN v4filings    AS f "
      . "     ON (s.org=f.org AND s.office=f.office AND s.district=f.district AND (s.subdist=f.subdist OR f.subdist=0)) "
      .    $joinTable
      . " WHERE $category "
      . "   AND (f.name IS NOT NULL AND f.name!='') "
      . "   AND (c.name IS NULL  OR c.name='') ";
   $totPick = runQuery($pdo, $pickQuery);
   echo Str::join([$rowName, $totTotal, $tot2026, $totPick + $totCand, $totCand, $totWeb, $totRev, $totEnd], "\t") . "\n";

}

function runQuery(AlfredPDO $pdo, string $sql): int {
#  echo "$sql\n";
   $result = $pdo->run($sql);
   if ($result->failed()) {
      fwrite (STDERR, "Error: $sql\n");
      return 0;
   }

   return $result->getSingleValue('number');
}

