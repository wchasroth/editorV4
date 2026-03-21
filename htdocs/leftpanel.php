<?php
declare(strict_types=1);

namespace CharlesRothDotNet\Editor26;

use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\SmartyPage;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\DumbFileLogger;

require_once('../vendor/autoload.php');

date_default_timezone_set("America/New_York");

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);
$logger = new DumbFileLogger($env->get('logFile'));
$logger->log("LeftPanel startup");

$counties = [];

$sql = "SELECT e.org, e.district, e.name \n"
     . "  FROM entity26         AS e \n"
     . "  JOIN entity2county26  AS c ON (e.org = c.org  AND  e.district = c.district) \n"
     . "  LEFT JOIN v4completed AS d ON (e.district = d.id) "
     . "  WHERE e.org in ('cnty', 'city', 'town', 'vil', 'schl-cou', 'crt-a', 'crt-c', 'crt-d', 'crt-m', 'crt-p') \n"
     . "    AND d.type='county' "
     . "  ORDER BY c.county_id, "
     . "        FIELD(e.org, 'cnty', 'city', 'town', 'vil', 'schl-cou', 'crt-a', 'crt-c', 'crt-d', 'crt-m', 'crt-p'), e.name ";
$result = $pdo->run($sql);
if ($result->failed()) $logger->log("Failed: leftpanel main select: " . $result->getError() . "  $sql");
$cid = 0;  // This is a bug/hack!
foreach ($result->getRows() as $row) {
   $org      = $row['org'];
   $name     = simplifyName($row['name']);
   $district = $row['district'];
   $logger->log("Got: " . showArray($row));
   switch ($org) {
      case 'cnty':
         $cid = intval($district);
         $name = Str::replaceAll($name, " County", "");
         $counties[$cid] = ['cnty' => [$org, $district, $name], 'juris' => [], 'vil' => [], 'schl' => [], 'crt' => []];
         break;

      case 'city':
      case 'town':       $counties[$cid]['juris'][] = [$org, $district, $name];    break;

      case 'vil':        $counties[$cid]['vil']  [] = [$org, $district, $name];    break;

      case 'schl-cou':   $counties[$cid]['schl'] [] = [$org, $district, $name];    break;

      case 'crt-a':
      case 'crt-c':
      case 'crt-d':
      case 'crt-m':
      case 'crt-p':      $counties[$cid]['crt']     [] = [$org, $district, $name, $org];  break;
   }
}

$smarty = new SmartyPage();
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