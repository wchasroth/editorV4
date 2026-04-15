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

$sql = "SELECT id FROM v4completed WHERE type='county' AND id IN ($allowedCounties) ORDER by id";
$result = $pdo->run($sql);
$countyNums = $result->getArrayOf('id');

//     <li><a href="#" onClick="return loadOfficials('mi-boe,mi-msu,mi-um,mi-wsu', '', 's');" class="child">MI Education</a></li>

function calculateTopReviewed(string $page): string {
   return " (SELECT 1 AS reviewed FROM v4pagesReviewed WHERE page='$page:') AS reviewed ";
}
function calculateTopSeats (string $orgs): string {
   return " (SELECT COUNT(*) FROM v4seats WHERE org IN ($orgs)) AS seats ";
}

$sql = "   SELECT 'us' AS org, " . calculateTopReviewed('us,us-vp,us-sen,us-hou')         . ", " . calculateTopSeats("'us', 'us-sen', 'us-hou'")
     . "UNION "
     . "   SELECT 'mi' AS org, " . calculateTopReviewed('mi,mi-lt,mi-sos,mi-ag,crt-sup')  . ", " . calculateTopSeats("'mi', 'mi-sos', 'mi-ag', 'crt-sup'")
     . "UNION "
     . "   SELECT 'mi_sen' AS org, " . calculateTopReviewed('mi-sen')                     . ", " . calculateTopSeats("'mi-sen'")
     . "UNION "
     . "   SELECT 'mi_hou' AS org, " . calculateTopReviewed('mi-hou')                     . ", " . calculateTopSeats("'mi-hou'")
     . "UNION "
     . "   SELECT 'mi_boe' AS org, " . calculateTopReviewed('mi-boe,mi-msu,mi-um,mi-wsu') . ", " . calculateTopSeats("'mi-boe','mi-msu','mi-um','mi-wsu'")
;
$result = $pdo->run($sql);
$topOffices = [];
foreach ($result->getRows() as $row)   $topOffices[$row['org']] = [$row['seats'], $row['reviewed']];

$counties = [];
$time0 = (int) (microtime(true) * 1000);
foreach ($countyNums as $countyNum) {

   $sql = "   SELECT 'cnty' AS org, id, name, 1 AS link, " . calculateReviewed('cnty,cnty-com', 'c.id') . ","
        .            calculateSeats("'cnty', 'cnty-com'", "c.id")
        . "     FROM v4counties AS c  WHERE id = $countyNum "
        . "UNION "
        . "   SELECT 'city' AS org, j.id, j.name, IF(c.id IS NULL, 0, 1) AS link, " . calculateReviewed('city,city-cou', 'j.id') . ","
        .            calculateSeats("'city', 'city-cou'", "j.id")
        . "     FROM      v4jurisdictions AS j "
        . "     LEFT JOIN v4completed     AS c  ON (c.id = j.id  AND c.type='city') "
        . "    WHERE j.type='c'  AND  j.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'town' AS org, j.id, j.name, 1 AS link , "  . calculateReviewed('town,town-cou', 'id') . ","
        .            calculateSeats("'town', 'town-cou'", "j.id")
        . "     FROM      v4jurisdictions AS j "
        . "    WHERE j.type='t'  AND  j.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'vil' AS org, v.id, v.name, IF(c.id IS NULL, 0, 1) AS link,"  . calculateReviewed('vil,vil-cou', 'v.id') . ","
        .            calculateSeats("'vil-cou'", "v.id")
        . "     FROM      v4villages  AS v "
        . "     LEFT JOIN v4completed AS c  ON (c.id = v.id  AND c.type='village') "
        . "    WHERE v.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'schl-cou' AS org, s.id, s.name, IF(c.id IS NULL, 0, 1) AS link , "  . calculateReviewed('schl-cou', 's.id') . ","
        .             calculateSeats("'schl-cou'", "s.id")
        . "     FROM      v4schools   AS s "
        . "     LEFT JOIN v4completed AS c  ON (c.id = s.id  AND c.type='school') "
        . "    WHERE s.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'comcol-cou' AS org, m.id, m.name, IF(c.id IS NULL, 0, 1) AS link, " . calculateReviewed('comcol-cou', 'm.id') . ","
        .             calculateSeats("'comcol-cou'", "m.id")
        . "     FROM      v4commcolleges        AS m "
        . "     LEFT JOIN v4commcolleges_county AS y  ON (m.id = y.id) "
        . "     LEFT JOIN v4completed           AS c  ON (c.id = m.id  AND c.type='college') "
        . "    WHERE y.county_id = $countyNum "
        . "UNION "
        . "   SELECT type AS org, shortname AS id, name, 1 AS link, "
        . "      (SELECT 1 AS reviewed FROM v4pagesReviewed WHERE page=CONCAT(type, ':', shortname)) AS reviewed, "
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
      $reviewed = intval($row['reviewed']);
      $seats    = intval($row['seats']);
      switch ($org) {
         case 'cnty':
            $name = Str::replaceAll($name, " County", "");
            $counties[$countyNum] = ['cnty' => [$org, $district, $name, 1, $reviewed, $seats],
               'city' => [], 'town' => [], 'vil' => [], 'schl' => [], 'crt' => [], 'comcol' => [],
               'city_num' => 0, 'city_den' => 0,
               'town_num' => 0, 'town_den' => 0,
               'vil_num'  => 0, 'vil_den'  => 0,
               'schl_num' => 0, 'schl_den' => 0,
               'col_num'  => 0, 'col_den'  => 0,
               'crt_num'  => 0, 'crt_den'  => 0,
               'grd_num'  => $seats * $reviewed,
               'grd_den'  => $seats
            ];
            break;

         case 'city':
            $counties[$countyNum]['city'][] = [$org, $district, $name, $link, $reviewed, $seats];
            rollUp($counties[$countyNum], 'city', $seats, $reviewed);
            break;

         case 'town':
            $counties[$countyNum]['town'][] = [$org, $district, $name, $link, $reviewed, $seats];
            rollUp($counties[$countyNum], 'town', $seats, $reviewed);
            break;

         case 'vil':
            $counties[$countyNum]['vil']  [] = [$org, $district, $name, $link, $reviewed, $seats];
            rollUp($counties[$countyNum], 'vil', $seats, $reviewed);
            break;

         case 'schl-cou':
            $counties[$countyNum]['schl'] [] = [$org, $district, $name, $link, $reviewed, $seats];
            rollUp($counties[$countyNum], 'schl', $seats, $reviewed);
            break;

         case 'comcol-cou':
            $counties[$countyNum]['comcol'] [] = [$org, $district, $name, $link, $reviewed, $seats];
            rollUp($counties[$countyNum], 'col', $seats, $reviewed);
            break;

//         case 'crt-a':
//         case 'crt-c':
//         case 'crt-d':
//         case 'crt-m':
//         case 'crt-p':
         case 'crt-a':
         case 'crt-c':
         case 'crt-d':
         case 'crt-pd':
         case 'crt-p':
         case 'crt-m':
            $counties[$countyNum]['crt'] [] = [$org, $district, $name, $org, $reviewed, $seats];
            rollUp($counties[$countyNum], 'crt', $seats, $reviewed);
            break;
      }
   }
}
$time1 = (int) (microtime(true) * 1000);
$logger->log("Time: " . strval($time1 - $time0));

$smarty = new SmartyPage();
$smarty->assign('allowedState', $allowedState);
$smarty->assign('counties', $counties);
$smarty->assign('topOffices', $topOffices);
$smarty->display('leftpanel.tpl');

function rollUp(array &$county, string $org, int $seats, int $reviewed): void {
   $county["{$org}_num"] += $seats * $reviewed;
   $county['grd_num']    += $seats * $reviewed;
   $county["{$org}_den"] += max(1, $seats);
   $county['grd_den']    += max(1, $seats);   // if we have zero seats, pretend we have at least one for roll-up purposes.
}

function calculateSeats (string $orgs, string $districtField): string {
   return "(SELECT COUNT(*) FROM v4seats WHERE org IN ($orgs) AND district=$districtField) AS seats ";
}

function calculateReviewed(string $orgs, string $districtField): string {
   return "(SELECT 1 AS reviewed FROM v4pagesReviewed WHERE page=CONCAT('$orgs:', $districtField)) AS reviewed ";
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