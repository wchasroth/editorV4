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

$sql = "   SELECT 'us' AS org, " . calculateTopSeats("'us', 'us-sen', 'us-hou'")
     . "UNION "
     . "   SELECT 'mi' AS org, " . calculateTopSeats("'mi', 'mi-sos', 'mi-ag', 'crt-sup'")
     . "UNION "
     . "   SELECT 'mi_sen' AS org, " . calculateTopSeats("'mi-sen'")
     . "UNION "
     . "   SELECT 'mi_hou' AS org, " . calculateTopSeats("'mi-hou'")
     . "UNION "
     . "   SELECT 'mi_boe' AS org, " . calculateTopSeats("'mi-boe','mi-msu','mi-um','mi-wsu'")
;
$result = $pdo->run($sql);
$topOffices = [];
foreach ($result->getRows() as $row)   $topOffices[$row['org']] = [$row['seats']];

$counties = [];
$allowedCountyNums = [81, 82];
foreach ($allowedCountyNums as $countyNum) {

   $sql = "   SELECT 'cnty' AS org, id, name, 1 AS link, " .  calculateSeats("'cnty', 'cnty-com'", "c.id")
        . "     FROM s4counties AS c  WHERE id = $countyNum "
        . "UNION "
        . "   SELECT 'city' AS org, j.id, j.name, IF(c.id IS NULL, 0, 1) AS link, " . calculateSeats("'city', 'city-cou'", "j.id")
        . "     FROM      s4jurisdictions AS j "
        . "     LEFT JOIN v4completed     AS c  ON (c.id = j.id  AND c.type='city') "
        . "    WHERE j.type='c'  AND  j.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'town' AS org, j.id, j.name, 1 AS link , "  .  calculateSeats("'town', 'town-cou'", "j.id")
        . "     FROM      s4jurisdictions AS j "
        . "    WHERE j.type='t'  AND  j.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'vil' AS org, v.id, v.name, IF(c.id IS NULL, 0, 1) AS link,"  .  calculateSeats("'vil-cou'", "v.id")
        . "     FROM      s4villages  AS v "
        . "     LEFT JOIN v4completed AS c  ON (c.id = v.id  AND c.type='village') "
        . "    WHERE v.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'schl-cou' AS org, s.id, s.name, IF(c.id IS NULL, 0, 1) AS link , "  . calculateSeats("'schl-cou'", "s.id")
        . "     FROM      s4schools   AS s "
        . "     LEFT JOIN v4completed AS c  ON (c.id = s.id  AND c.type='school') "
        . "    WHERE s.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'comcol-cou' AS org, m.id, m.name, IF(c.id IS NULL, 0, 1) AS link, " . calculateSeats("'comcol-cou'", "m.id")
        . "     FROM      s4commcolleges        AS m "
        . "     LEFT JOIN v4commcolleges_county AS y  ON (m.id = y.id) "
        . "     LEFT JOIN v4completed           AS c  ON (c.id = m.id  AND c.type='college') "
        . "    WHERE y.county_id = $countyNum "
        . "UNION "
        . "   SELECT type AS org, shortname AS id, name, 1 AS link, "
        . "      (SELECT COUNT(*) FROM v4seats WHERE org=type AND district=shortname) AS seats "
        . "    FROM  v4courts "
        . "    WHERE county_id = $countyNum "
        . "ORDER BY FIELD (org, 'city', 'town', 'vil', 'schl-cou', 'comcol-cou', 'crt-a', 'crt-c', 'crt-d', 'crt-pd', 'crt-p', 'crt-m'), name ";

// if ($countyNum === 81) $logger->log("Big: $sql");

   $result = $pdo->run($sql);
   if ($result->failed()) $logger->log("Failed: leftpanel main select: " . $result->getError() . "  $sql");
   foreach ($result->getRows() as $row) {
      $org = $row['org'];
      $name = simplifyName($row['name']);
      $district = $row['id'];
      $link     = intval($row['link']);
      $seats    = intval($row['seats']);
      switch ($org) {
         case 'cnty':
            $name = Str::replaceAll($name, " County", "");
            $counties[$countyNum] = ['cnty' => [$org, $district, $name, 1, $seats],
               'city' => [], 'town' => [], 'vil' => [], 'schl' => [], 'crt' => [], 'comcol' => [],
               'city_num' => 0, 'city_den' => 0,
               'town_num' => 0, 'town_den' => 0,
               'vil_num'  => 0, 'vil_den'  => 0,
               'schl_num' => 0, 'schl_den' => 0,
               'col_num'  => 0, 'col_den'  => 0,
               'crt_num'  => 0, 'crt_den'  => 0,
               'grd_num'  => $seats,
               'grd_den'  => $seats
            ];
            break;

         case 'city':
            $counties[$countyNum]['city'][] = [$org, $district, $name, $link, $seats];
            rollUp($counties[$countyNum], 'city', $seats);
            break;

         case 'town':
            $counties[$countyNum]['town'][] = [$org, $district, $name, $link, $seats];
            rollUp($counties[$countyNum], 'town', $seats);
            break;

         case 'vil':
            $counties[$countyNum]['vil']  [] = [$org, $district, $name, $link, $seats];
            rollUp($counties[$countyNum], 'vil', $seats);
            break;

         case 'schl-cou':
            $counties[$countyNum]['schl'] [] = [$org, $district, $name, $link, $seats];
            rollUp($counties[$countyNum], 'schl', $seats);
            break;

         case 'comcol-cou':
            $counties[$countyNum]['comcol'] [] = [$org, $district, $name, $link, $seats];
            rollUp($counties[$countyNum], 'col', $seats);
            break;

         case 'crt-a':
         case 'crt-c':
         case 'crt-d':
         case 'crt-pd':
         case 'crt-p':
         case 'crt-m':
            $counties[$countyNum]['crt'] [] = [$org, $district, $name, $org, $seats];
            rollUp($counties[$countyNum], 'crt', $seats);
            break;
      }
   }
}

$smarty = new SmartyPage();
$smarty->assign('allowedState', $allowedState);
$smarty->assign('counties', $counties);
$smarty->assign('topOffices', $topOffices);
$smarty->display('leftpanel-c.tpl');

function rollUp(array &$county, string $org, int $seats): void {
   $county["{$org}_num"] += $seats;
   $county['grd_num']    += $seats;
   $county["{$org}_den"] += max(1, $seats);
   $county['grd_den']    += max(1, $seats);   // if we have zero seats, pretend we have at least one for roll-up purposes.
}

function calculateSeats (string $orgs, string $districtField): string {
   return "(SELECT COUNT(*) FROM v4seats WHERE org IN ($orgs) AND district=$districtField AND termlen>0 AND termcycle>0 AND ((termcycle + 6*termlen) - 2026) % termlen = 0) AS seats ";
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

function calculateTopSeats (string $orgs): string {
   return " (SELECT COUNT(*) FROM v4seats WHERE org IN ($orgs) AND termlen>0 AND termcycle>0 AND ((termcycle + 6*termlen) - 2026) % termlen = 0) AS seats ";
}