<?php
declare(strict_types=1);

namespace CharlesRothDotNet\Editor26;

use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\SmartyPage;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\DumbFileLogger;
use CharlesRothDotNet\Alfred\CookieBoss;
use CharlesRothDotNet\Alfred\CookieVerifier;

require_once('../vendor/autoload.php');

date_default_timezone_set("America/New_York");

$env = new EnvFile("_env");
$boss  = new CookieBoss($env->get('domain'), $env->get('cookie_path'), $env->get('securekey'));
$email = CookieVerifier::getEmail($boss, $env->get('cookie'));
$allowedCounties = $boss->getValueFromHashedCookie($env->get('counties'));
$allowedState    = Str::contains($allowedCounties, "999");

$pdo = PdoHelper::makePdo($env);
$logger = new DumbFileLogger($env->get('logFile'));

$sql = "SELECT id FROM v4completed WHERE type='county' AND id IN ($allowedCounties) ORDER by id";
$result = $pdo->run($sql);
$countyNums = $result->getArrayOf('id');

$counties = [];
foreach ($countyNums as $countyNum) {

   $sql = "   SELECT 'cnty' AS org, id, name, 1 AS link "
        . "     FROM v4counties WHERE id = $countyNum "
        . "UNION "
        . "   SELECT 'city' AS org, j.id, j.name, IF(c.id IS NULL, 0, 1) AS link "
        . "     FROM      v4jurisdictions AS j "
        . "     LEFT JOIN v4completed     AS c  ON (c.id = j.id  AND c.type='city') "
        . "    WHERE j.type='c'  AND  j.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'town' AS org, j.id, j.name, 1 AS link "
        . "     FROM      v4jurisdictions AS j "
        . "    WHERE j.type='t'  AND  j.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'vil' AS org, v.id, v.name, IF(c.id IS NULL, 0, 1) AS link "
        . "     FROM      v4villages  AS v "
        . "     LEFT JOIN v4completed AS c  ON (c.id = v.id  AND c.type='village') "
        . "    WHERE v.county_id = $countyNum "
        . "UNION "
        . "   SELECT 'schl-cou' AS org, s.id, s.name, IF(c.id IS NULL, 0, 1) AS link "
        . "     FROM      v4schools   AS s "
        . "     LEFT JOIN v4completed AS c  ON (c.id = s.id  AND c.type='school') "
        . "    WHERE s.county_id = $countyNum "
        . "UNION "
        . "   SELECT type AS org, shortname AS id, name, 1 AS link "
        . "    FROM  court "
        . "    WHERE county_id = $countyNum "
        . "ORDER BY FIELD (org, 'city', 'town', 'vil', 'schl-cou', 'A', 'C', 'D', 'PD', 'P'), name ";

   $result = $pdo->run($sql);
   if ($result->failed()) $logger->log("Failed: leftpanel main select: " . $result->getError() . "  $sql");
   foreach ($result->getRows() as $row) {
      $org = $row['org'];
      $name = simplifyName($row['name']);
      $district = $row['id'];
//    $logger->log("Got: " . showArray($row));
      switch ($org) {
         case 'cnty':
            $name = Str::replaceAll($name, " County", "");
            $counties[$countyNum] = ['cnty' => [$org, $district, $name], 'juris' => [], 'vil' => [], 'schl' => [], 'crt' => []];
            break;

         case 'city':
         case 'town':
            $counties[$countyNum]['juris'][] = [$org, $district, $name];
            break;

         case 'vil':
            $counties[$countyNum]['vil']  [] = [$org, $district, $name];
            break;

         case 'schl-cou':
            $counties[$countyNum]['schl'] [] = [$org, $district, $name];
            break;

//         case 'crt-a':
//         case 'crt-c':
//         case 'crt-d':
//         case 'crt-m':
//         case 'crt-p':
         case 'A':
         case 'C':
         case 'D':
         case 'PD':
         case 'P':
            $counties[$countyNum]['crt']     [] = [$org, $district, $name, $org];
            $logger->log("CRT: " . showArray([$org, $district, $name, $org]));
            break;
      }
   }
}

$smarty = new SmartyPage();
$smarty->assign('allowedState', $allowedState);
$smarty->assign('counties', $counties);
$smarty->display('leftpanel.tpl');

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