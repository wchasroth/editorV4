<?php

declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\SmartyPage;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\DumbFileLogger;
use CharlesRothDotNet\Alfred\CookieBoss;
use CharlesRothDotNet\Alfred\CookieVerifier;
use CharlesRothDotNet\EditorV4\EnvHelper;

require_once('../vendor/autoload.php');

date_default_timezone_set("America/New_York");

$env              = new EnvFile("_env");
$logger           = new DumbFileLogger($env->get('logFile'));
$email            = EnvHelper::getEmail($env);
$editableCounties = EnvHelper::getEditableCounties($env);
$pdo              = PdoHelper::makePdo($env);

$sql = "SELECT readCounties FROM azure_users WHERE email='$email'";
$result = $pdo->run($sql);
$readableCounties = $result->getSingleValue('readCounties');

$allowedCounties = getUnion($editableCounties, $readableCounties);
$allowedState    = Str::contains($allowedCounties, "999");
$allowedCountyNums = Str::split($allowedCounties, ",");

$sql = "   SELECT 'us' AS org, " . calculateTopSeats (            "'us', 'us-sen', 'us-hou'") . ", "
                                 . calculateTopMetric("reviewed", "'us', 'us-sen', 'us-hou'") . " AS rcount, "
                                 . calculateTopMetric("endorsed", "'us', 'us-sen', 'us-hou'") . " AS ecount "
     . "UNION "
     . "   SELECT 'mi' AS org, " . calculateTopSeats(             "'mi', 'mi-sos', 'mi-ag', 'crt-sup'") . ", "
     .                             calculateTopMetric("reviewed", "'mi', 'mi-sos', 'mi-ag', 'crt-sup'") . " AS rcount, "
     .                             calculateTopMetric("endorsed", "'mi', 'mi-sos', 'mi-ag', 'crt-sup'") . " AS ecount "
     . "UNION "
     . "   SELECT 'mi_sen' AS org, " . calculateTopSeats (            "'mi-sen'") . ", "
                                     . calculateTopMetric("reviewed", "'mi-sen'") . " AS rcount, "
                                   .   calculateTopMetric("endorsed", "'mi-sen'") . " AS ecount "
     . "UNION "
     . "   SELECT 'mi_hou' AS org, " . calculateTopSeats (            "'mi-hou'") . ", "
                                     . calculateTopMetric("reviewed", "'mi-hou'") . " AS rcount, "
                                     . calculateTopMetric("endorsed", "'mi-hou'") . " AS ecount "
     . "UNION "
     . "   SELECT 'mi_boe' AS org, " . calculateTopSeats (            "'mi-boe','mi-msu','mi-um','mi-wsu'") . ", "
                                     . calculateTopMetric("reviewed", "'mi-boe','mi-msu','mi-um','mi-wsu'") . " AS rcount, "
                                     . calculateTopMetric("endorsed", "'mi-boe','mi-msu','mi-um','mi-wsu'") . " AS ecount "
;
$result = $pdo->run($sql);
if ($result->failed()) $logger->log("TOP failed: $sql\n");
$topOffices = [];
foreach ($result->getRows() as $row)   $topOffices[$row['org']] = [$row['ecount'], $row['rcount'], $row['seats']];

$orgCnty = "org IN ('cnty', 'cnty-com')";
$orgCity = "org IN ('city', 'city-cou')";
$orgTown = "org IN ('town', 'town-cou')";
$orgVil  = "org IN ('vil',  'vil-cou')";
$orgSchl = "org IN ('schl-cou')";
$orgColl = "org IN ('comcol-cou')";
$orgCrt  = "org = type ";

$counties = [];
foreach ($allowedCountyNums as $countyNum) {

   $sql = "   SELECT 'cnty' AS org, id, name, 1 AS link, "
        .        calculateSeats (            $orgCnty, "c.id") . ", "
        .        calculateMetric("reviewed", $orgCnty, "c.id") . " AS rcount, "
        .        calculateMetric("endorsed", $orgCnty, "c.id") . " AS ecount "
        . "     FROM s4counties AS c  WHERE id = $countyNum "
        . "UNION "
        . "   SELECT 'city' AS org, j.id, j.name, IF(c.id IS NULL, 0, 1) AS link, "
        .        calculateSeats (            $orgCity, "j.id") . ", "
        .        calculateMetric("reviewed", $orgCity, "j.id") . " AS rcount, "
        .        calculateMetric("endorsed", $orgCity, "j.id") . " AS ecount "
        . "     FROM      s4jurisdictions AS j "
        . "     LEFT JOIN v4completed     AS c  ON (c.id = j.id  AND c.type='city') "
        . "    WHERE j.type='c'  AND  j.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'town' AS org, j.id, j.name, 1 AS link , "
        .        calculateSeats (            $orgTown, "j.id") . ", "
        .        calculateMetric("reviewed", $orgTown, "j.id") . " AS rcount, "
        .        calculateMetric("endorsed", $orgTown, "j.id") . " AS ecount "
        . "     FROM      s4jurisdictions AS j "
        . "    WHERE j.type='t'  AND  j.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'vil' AS org, v.id, v.name, IF(c.id IS NULL, 0, 1) AS link,"
        .        calculateSeats (            $orgVil, "v.id") . ", "
        .        calculateMetric("reviewed", $orgVil, "v.id") . " AS rcount, "
        .        calculateMetric("endorsed", $orgVil, "v.id") . " AS ecount "
        . "     FROM      s4villages  AS v "
        . "     LEFT JOIN v4completed AS c  ON (c.id = v.id  AND c.type='village') "
        . "    WHERE v.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'schl-cou' AS org, h.id, h.name, IF(c.id IS NULL, 0, 1) AS link , "
        .        calculateSeats (            $orgSchl, "h.id") . ", "
        .        calculateMetric("reviewed", $orgSchl, "h.id") . " AS rcount, "
        .        calculateMetric("endorsed", $orgSchl, "h.id") . " AS ecount "
        . "     FROM      s4schools   AS h "
        . "     LEFT JOIN v4completed AS c  ON (c.id = h.id  AND c.type='school') "
        . "    WHERE h.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'comcol-cou' AS org, m.id, m.name, IF(c.id IS NULL, 0, 1) AS link, "
        .        calculateSeats (            $orgColl, "m.id") . ", "
        .        calculateMetric("reviewed", $orgColl, "m.id") . " AS rcount, "
        .        calculateMetric("endorsed", $orgColl, "m.id") . " AS ecount "
        . "     FROM      s4commcolleges        AS m "
        . "     LEFT JOIN v4commcolleges_county AS y  ON (m.id = y.id) "
        . "     LEFT JOIN v4completed           AS c  ON (c.id = m.id  AND c.type='college') "
        . "    WHERE y.county_id = $countyNum "
        . "UNION "
        . "   SELECT type AS org, shortname AS id, name, 1 AS link, "
        .        calculateSeats (            $orgCrt, "shortname") . ", "
        .        calculateMetric("reviewed", $orgCrt, "shortname") . " AS rcount, "
        .        calculateMetric("endorsed", $orgCrt, "shortname") . " AS ecount "
        . "    FROM  v4courts AS ct"
        . "    WHERE county_id = $countyNum "
        . "ORDER BY FIELD (org, 'city', 'town', 'vil', 'schl-cou', 'comcol-cou', 'crt-a', 'crt-c', 'crt-d', 'crt-pd', 'crt-p', 'crt-m'), name ";

// $logger->log("Big: $sql");

   $result = $pdo->run($sql);
   if ($result->failed()) $logger->log("Failed: leftpanel main select: " . $result->getError() . "  $sql");
   foreach ($result->getRows() as $row) {
      $org = $row['org'];
      $name = simplifyName($row['name']);
      $district = $row['id'];
      $link     = intval($row['link']);
      $seats    = intval($row['seats']);
      $rcount   = intval($row['rcount']);
      $ecount   = intval($row['ecount']);
      switch ($org) {
         case 'cnty':
            $name = Str::replaceAll($name, " County", "");
            $counties[$countyNum] = ['cnty' => [$org, $district, $name, 1, $seats, $rcount, $ecount],
               'city' => [], 'town' => [], 'vil' => [], 'schl' => [], 'crt' => [], 'comcol' => [],
               'city_end' => 0, 'city_rev' => 0, 'city_den' => 0,
               'town_end' => 0, 'town_rev' => 0, 'town_den' => 0,
               'vil_end'  => 0, 'vil_rev'  => 0, 'vil_den'  => 0,
               'schl_end' => 0, 'schl_rev' => 0, 'schl_den' => 0,
               'col_end'  => 0, 'col_rev'  => 0, 'col_den'  => 0,
               'crt_end'  => 0, 'crt_rev'  => 0, 'crt_den'  => 0,
               'grd_end'  => $seats,
               'grd_rev'  => $seats,
               'grd_den'  => $seats
            ];
            break;

         case 'city':
            $counties[$countyNum]['city'][] = [$org, $district, $name, $link, $seats, $rcount, $ecount];
            rollUp($counties[$countyNum], 'city', $seats, $rcount, $ecount);
            break;

         case 'town':
            $counties[$countyNum]['town'][] = [$org, $district, $name, $link, $seats, $rcount, $ecount];
            rollUp($counties[$countyNum], 'town', $seats, $rcount, $ecount);
            break;

         case 'vil':
            $counties[$countyNum]['vil']  [] = [$org, $district, $name, $link, $seats, $rcount, $ecount];
            rollUp($counties[$countyNum], 'vil', $seats, $rcount, $ecount);
            break;

         case 'schl-cou':
            $counties[$countyNum]['schl'] [] = [$org, $district, $name, $link, $seats, $rcount, $ecount];
            rollUp($counties[$countyNum], 'schl', $seats, $rcount, $ecount);
            break;

         case 'comcol-cou':
            $counties[$countyNum]['comcol'] [] = [$org, $district, $name, $link, $seats, $rcount, $ecount];
            rollUp($counties[$countyNum], 'col', $seats, $rcount, $ecount);
            break;

         case 'crt-a':
         case 'crt-c':
         case 'crt-d':
         case 'crt-pd':
         case 'crt-p':
         case 'crt-m':
            $counties[$countyNum]['crt'] [] = [$org, $district, $name, $org, $seats, $rcount, $ecount];
            rollUp($counties[$countyNum], 'crt', $seats, $rcount, $ecount);
            break;
      }
   }
}

$smarty = new SmartyPage();
$smarty->assign('allowedState', $allowedState);
$smarty->assign('counties', $counties);
$smarty->assign('topOffices', $topOffices);
$smarty->display('leftpanel-c.tpl');

function rollUp(array &$county, string $org, int $seats, int $reviewed, int $endorsed): void {
   $county["{$org}_end"] += $endorsed;
   $county['grd_end']    += $endorsed;
   $county["{$org}_rev"] += $reviewed;
   $county["grd_rev"]    += $reviewed;
// $county["{$org}_den"] += max(1, $seats);  // hmm, why did we do this?
   $county["{$org}_den"] += $seats;
// $county['grd_den']    += max(1, $seats);   // if we have zero seats, pretend we have at least one for roll-up purposes.
   $county['grd_den']    += $seats;
}

function calculateSeats (string $orgClause, string $districtField): string {
   return "(SELECT COUNT(*) FROM v4seats WHERE $orgClause AND district=$districtField AND termlen>0 AND termcycle>0 AND ((termcycle + 6*termlen) - 2026) % termlen = 0) AS seats ";
}

function calculateMetric(string $metricName, string $orgClause, string $districtField): string {
   return "(SELECT SUM(counter.metric) AS total_metric "
      . "   FROM ( SELECT MAX(c.$metricName) AS metric "
      . "            FROM      v4seats      AS s "
      . "            LEFT JOIN v4candidates AS c  ON (c.seat_id = s.id) "
      . "           WHERE s.$orgClause "
      . "             AND district=$districtField "
      . "           GROUP BY s.org, s.office, s.district, s.subdist, s.seatnum "
      . "   ) AS counter) ";
}

function calculateTopSeats (string $orgs): string {
   return " (SELECT COUNT(*) FROM v4seats WHERE org IN ($orgs) AND termlen>0 AND termcycle>0 AND ((termcycle + 6*termlen) - 2026) % termlen = 0) AS seats ";
}

function simplifyName(string $text): string {
   $name = ucwords(strtolower($text));
   if (Str::contains($name, " Community "))       $name = Str::replaceFirst($name, " Community ", " Comm. ");
   if (Str::endsWith($name, " Schools"))          $name = Str::replaceFirst($name, " Schools",    "");
   if (Str::endsWith($name, " School District"))  $name = Str::replaceFirst($name, " School District",    "");
   return $name;
}

function showArray (array $aa): string {
   $keyValues = [];
   foreach ($aa as $key => $value) $keyValues[] = "$key=>$value";
   return "[" . Str::join($keyValues, ", ") . "]";
}

function getUnion (string $counties1, string $counties2): string {
   $c1 = Str::split($counties1, ",");
   $c2 = Str::split($counties2, ",");
   $union = array_unique(array_merge($c1, $c2));
   return Str::join($union, ",");
}

function calculateTopMetric(string $metricName, string $orgs): string {
   return "(SELECT SUM(counter.metric) AS total_metric "
        . "   FROM ( SELECT MAX(c.$metricName) AS metric "
        . "            FROM      v4seats      AS s "
        . "            LEFT JOIN v4candidates AS c  ON (c.seat_id = s.id) "
        . "           WHERE s.org IN ($orgs) "
        . "           GROUP BY s.org, s.office, s.district, s.subdist, s.seatnum "
        . "   ) AS counter) ";
}