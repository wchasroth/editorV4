<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\ArrayHelper;
use CharlesRothDotNet\Alfred\FieldFormatFixer;
use CharlesRothDotNet\Alfred\MichiganCounties;
use CharlesRothDotNet\Alfred\PdoRunResult;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\HttpGet;
use CharlesRothDotNet\Alfred\HttpPost;
use CharlesRothDotNet\Alfred\SmartyPage;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\DumbFileLogger;

require_once('../vendor/autoload.php');

date_default_timezone_set("America/New_York");

//---mismatch.php.  Show candidate "mismatches", where endorsed=1 but reviewed=0

$env     = new EnvFile("_env");
$email   = EnvHelper::getEmail($env);
$pdo     = PdoHelper::makePdo($env);
$logger  = new DumbFileLogger($env->get('logFile'));
$mc      = new MichiganCounties();

$sql = "SELECT s.org, s.office, s.district, c.name "
     . "  FROM      v4candidates AS c "
     . "  LEFT JOIN v4seats      AS s  ON (c.seat_id = s.id) "
     . " WHERE c.name != '' "
     . "   AND c.endorsed=1  AND  c.reviewed=0 "
     . " ORDER BY c.name";

$result = $pdo->run($sql);
$rawdata = $result->getRows();
$rows = [];
foreach ($rawdata as $data) {
   $org      = $data['org'];
   $district = $data['district'];
   $office   = $data['office'];
   if (Str::startsWith($org, 'crt-')) {
      $sql = "SELECT county_id, name FROM v4courts WHERE shortname='$district' AND type='$org' LIMIT 1";
      [$county, $officename] = getCountyAndName($pdo, $sql);
      addRow($rows, $mc->getName($county), $officename, $data['name']);
   }
   else if (Str::startsWith($org, 'city')) {
      if ($org === 'city-cou') $office = "Council";
      $sql = "SELECT county_id, name FROM s4jurisdictions WHERE id=$district AND type='c' LIMIT 1";
      [$county, $juris] = getCountyAndName($pdo, $sql);
      addRow($rows, $mc->getName($county), "$juris $office", $data['name']);
   }

   else if (Str::startsWith($org, 'town')) {
      if ($org === 'town-cou') $office = "Trustee";
      $sql = "SELECT county_id, name FROM s4jurisdictions WHERE id=$district AND type='t' LIMIT 1";
      [$county, $juris] = getCountyAndName($pdo, $sql);
      addRow($rows, $mc->getName($county), "$juris $office", $data['name']);
   }

   else if (Str::startsWith($org, 'vil')) {
      if ($org === 'vil-cou') $office = "Council";
      $sql = "SELECT county_id, name FROM s4villages WHERE id=$district LIMIT 1";
      [$county, $vilname] = getCountyAndName($pdo, $sql);
      addRow($rows, $mc->getName($county), "$vilname $office", $data['name']);
   }

   else if ($org === 'schl-cou') {
      $sql = "SELECT county_id, name FROM s4schools WHERE id=$district LIMIT 1";
      [$county, $school] = getCountyAndName($pdo, $sql);
      addRow($rows, $mc->getName($county), $school, $data['name']);
   }

   else if ($org === 'comcol-cou') {
      $sql = "SELECT t.county_id, c.name "
           . " FROM      s4commcolleges        AS c "
           . " LEFT JOIN v4commcolleges_county AS t ON (c.id = t.id) "
           . " WHERE c.id=$district LIMIT 1";
      [$county, $college] = getCountyAndName($pdo, $sql);
      addRow($rows, $mc->getName($county), $college, $data['name']);
   }
}

ksort($rows);
$smarty = new SmartyPage();
$smarty->assign('rows', $rows);
$smarty->display('mismatch.tpl');

function addRow(array &$rows, string $countyName, string $officeName, $candidateName): void {
   $key = "$countyName:$officeName:$candidateName";
   $rows[$key] = [ucwords($countyName), ucwords(strtolower($officeName)), $candidateName];
}

function getCountyAndName(AlfredPDO $pdo, string $sql): array {
   $result = $pdo->run($sql);
   return [$result->getSingleValue('county_id'), $result->getSingleValue('name')];
}
