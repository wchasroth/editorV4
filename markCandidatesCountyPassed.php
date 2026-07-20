<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\AlfredPDO;

require_once('vendor/autoload.php');

//---markCandidatesCountyPassed.php
//
//   When a primary editor has finished reviewing the candidates for a county, we mark them
//   as "passed".  This means that all reviewed checkmarks are cleared, but the entity names
//   (jurisdictions, villages, etc.) now show up as green text.
//
//   We DON'T clear entities that have already been "passed" by virtue of (also) being in
//   an already "passed" county (e.g. courts or school districts that cross counties).

$county = $argv[1];
$env    = new EnvFile("_env");
$pdo    = PdoHelper::makePdo($env);

$sql = "SELECT id, name from s4counties where name LIKE '$county%'";
$result = $pdo->run($sql);
if ($result->getRowCount() != 1) {
   echo "No match, or too many matches.\n";
   exit(1);
}

$ctyId = intval($result->getRows()[0]["id"]);
$name  = $result->getRows()[0]["name"];
echo "OK to proceed with $name ($ctyId) ? (y/n): ";
$response = strtolower(trim(fgets(STDIN)));
if (! Str::startsWith($response, "y")) {
   echo "Aborting.\n";
   exit(1);
}

//---Find all the entities (org and district) that are "under" this county.
$sql = " SELECT DISTINCT s.org, s.district "
   . "     FROM      v4seats         AS s "
   . "     LEFT JOIN s4jurisdictions AS j  ON (s.district = j.id) "
   . "    WHERE (s.org LIKE 'city%'  OR  s.org LIKE 'town%') "
   . "      AND j.county_id = $ctyId "
   . "UNION ALL "
   . "   SELECT 'cnty' AS org, '$ctyId' AS district "
   . "UNION ALL "
   . "   SELECT DISTINCT s.org, v.id AS district "
   . "     FROM      v4seats         AS s "
   . "     LEFT JOIN s4villages   AS v  ON (s.district = v.id) "
   . "    WHERE (s.org LIKE 'vil%') "
   . "      AND v.county_id = $ctyId "
   . "UNION ALL "
   . "   SELECT DISTINCT s.org, c.id AS district "
   . "     FROM      v4seats      AS s "
   . "     LEFT JOIN s4schools    AS c ON (s.district = c.id) "
   . "    WHERE s.org = 'schl-cou' "
   . "      AND c.county_id = $ctyId "
   . "UNION ALL "
   . "   SELECT DISTINCT s.org, c.id AS district "
   . "     FROM      v4seats               AS s "
   . "     LEFT JOIN s4commcolleges        AS c ON (s.district = c.id) "
   . "     LEFT JOIN v4commcolleges_county AS y ON (c.id = y.id) "
   . "    WHERE s.org = 'comm-cou' "
   . "      AND y.county_id = $ctyId "
   . "UNION ALL "
   . "   SELECT DISTINCT type as org, shortname as district "
   . "     FROM v4courts "
   . "    WHERE county_id = $ctyId ";

$result = $pdo->run($sql);
if ($result->failed()) echo "Error: $sql\n";

$entitiesPassed = $result->getRows();
foreach ($entitiesPassed as $entity) {
   $sqlFields = new SqlFields(['org' => $entity['org'], 'district' => $entity['district']]);
   $sql = "SELECT 1 AS found FROM v4candidatePagesPassed WHERE " . $sqlFields->getSelectFragment();
   $result = $pdo->run($sql);
   $found  = intval($result->getSingleValue('found'));
   if ($found > 0) {
      fwrite(STDERR, "skipped: $sql\n");
      continue;
   }  // Already marked passed, don't do it again!

   $sql = "INSERT INTO v4candidatePagesPassed (org, district) values ('{$entity['org']}','{$entity['district']}')";
   $result = $pdo->run($sql);
   if ($result->failed()) fwrite(STDERR, "$sql\n");

   $sql = "UPDATE v4candidates SET reviewed=0 "
        . " WHERE seat_id IN "
        . "    (SELECT id FROM v4seats WHERE org='{$entity['org']}' AND district='{$entity['district']}') ";
   $result = $pdo->run($sql);
   if ($result->failed()) fwrite(STDERR, "$sql\n");
}