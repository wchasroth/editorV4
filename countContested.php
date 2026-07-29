<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\MichiganCounties;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\ArrayHelper;
use CharlesRothDotNet\Alfred\SqlFields;

require_once('vendor/autoload.php');

// countContested.php
// Produces by-county counts of contested & non-contested seats.

$env        = new EnvFile("_env");
$pdo        = PdoHelper::makePdo($env);
$michiganCounties = new MichiganCounties();

$csvRow = ["name", "id", "seats", "empty", "uncontested", "contested"];
echo Str::join($csvRow, ",") . "\n";

for ($countyId=1;   $countyId<=83;  ++$countyId) {
   $sql =  // Decided NOT to include state house or senate seat for now, but leaving query text just in case.
//      "   SELECT id, org, office,  $countyId AS district, subdist, seatnum "
//      . "     FROM      v4seats "
//      . "    WHERE org = 'mi-sen' "
//      . "      AND district IN (SELECT DISTINCT senate FROM s4streets WHERE county_code = $countyId) "
//      . "      AND termcycle = 2026 "
//      . "UNION ALL "
//      . "   SELECT id, org, office,  $countyId AS district, subdist, seatnum "
//      . "     FROM      v4seats "
//      . "    WHERE org = 'mi-hou' "
//      . "      AND district IN (SELECT DISTINCT house FROM s4streets WHERE county_code = $countyId) "
//      . "      AND termcycle = 2026 "
//      . "UNION ALL "
        "   SELECT id, org, office,  district, subdist, seatnum "
      . "     FROM      v4seats "
      . "    WHERE org LIKE 'cnty%' "
      . "      AND district  = $countyId "
      . "      AND termcycle = 2026 "
      . "UNION ALL "
      . "   SELECT s.id, s.org, s.office, s.district, s.subdist, s.seatnum "
      . "     FROM      v4seats         AS s "
      . "     LEFT JOIN s4jurisdictions AS j  ON (s.district = j.id) "
      . "    WHERE (s.org LIKE 'city%'  OR  s.org LIKE 'town%') "
      . "      AND j.county_id = $countyId "
      . "      AND s.termcycle = 2026 "
      . "UNION ALL "
      . "   SELECT DISTINCT s.id,  s.org, s.office, s.district, s.subdist, s.seatnum "
      . "     FROM      v4seats      AS s "
      . "     LEFT JOIN s4villages   AS v  ON (s.district = v.id) "
      . "    WHERE (s.org LIKE 'vil%') "
      . "      AND v.county_id = $countyId "
      . "      AND termcycle = 2026 "
      . "UNION ALL "
      . "   SELECT s.id,  s.org, s.office, s.district, s.subdist, s.seatnum "
      . "     FROM      v4incumbents AS i "
      . "     LEFT JOIN v4seats      AS s ON (i.seat_id  = s.id) "
      . "     LEFT JOIN s4schools    AS c ON (s.district = c.id) "
      . "    WHERE s.org = 'schl-cou' "
      . "      AND c.county_id = $countyId "
      . "      AND termcycle = 2026 "
      . "UNION ALL "
      . "   SELECT s.id,  s.org, s.office, s.district, s.subdist, s.seatnum "
      . "     FROM      v4seats               AS s "
      . "     LEFT JOIN s4commcolleges        AS c ON (s.district = c.id) "
      . "     LEFT JOIN v4commcolleges_county AS y ON (c.id = y.id) "
      . "    WHERE s.org = 'comcol-cou' "
      . "      AND y.county_id = $countyId "
      . "      AND s.termcycle = 2026 "
      . "UNION ALL "
      . "   SELECT s.id,  s.org, s.office, s.district, s.subdist, s.seatnum "
      . "     FROM      v4seats  AS s "
      . "     LEFT JOIN v4courts AS c ON (s.org = c.type AND s.district = c.shortname) "
      . "    WHERE c.county_id = $countyId "
      . "      AND s.termcycle = 2026 "
      . "ORDER BY org, subdist, seatnum, seatnum ";
   $result = $pdo->run($sql);
   if ($result->failed()) fwrite (STDERR, "$sql\n");

   $noCandidates = 0;
   $contested    = 0;
   $uncontested  = 0;
   $seats        = $result->getRowCount();
   foreach ($result->getRows() as $row) {
      $sql = "SELECT name FROM v4candidates WHERE seat_id = " . $row["id"] . " AND name != ''";
      $candidateResults = $pdo->run($sql);
      $candidateCount   = $candidateResults->getRowCount();
//    fwrite(STDERR, "Big: count=$candidateCount, org={$row['org']}, office={$row['office']}, dist={$row['district']}, "
//       . " sub={$row['subdist']}\n");
      if ($candidateCount > 1) { ++$contested; continue; }

      $match = ['org' => $row["org"], 'office' => $row['office'], 'district' => $row['district']];
      $matchFields = new SqlFields($match);
      $sql = "SELECT name FROM v4filings WHERE " . $matchFields->getSelectFragment()
           . "   AND (subdist={$row['subdist']} OR subdist=0)";
      $filingResults = $pdo->run($sql);
      $filingCount   = $filingResults->getRowCount();
//    fwrite (STDERR, "Can=$candidateCount, Fil=$filingCount, sql=$sql\n");
      if      ($filingCount === 0  &&  $candidateCount === 0) ++$noCandidates;
      else if ($filingCount > 1)                              ++$contested;
      else                                                    ++$uncontested;
   }
   $csvRow = [$michiganCounties->getName($countyId), $countyId, $seats, $noCandidates, $uncontested, $contested];
   echo Str::join($csvRow, ",") . "\n";
}