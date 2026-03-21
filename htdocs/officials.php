<?php
declare(strict_types=1);

namespace CharlesRothDotNet\Editor26;

use CharlesRothDotNet\Alfred\AlfredPDO;
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

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);
$logger = new DumbFileLogger($env->get('logFile'));
$logger->log("Officials startup");

$qsOrgs     = HttpGet::value('orgs');
$qsDistrict = HttpGet::value('district');
$qsShow     = HttpGet::value('show');

//====Handle form submission, if any.
$fieldsChanged = rtrim(HttpPost::value('fieldsChanged'), ",");
if (! empty($fieldsChanged)) {
   foreach (Str::split($fieldsChanged, ",") as $field) {
      $value = HttpPost::value($field);
      $parts = Str::split($field, ':');
      $sql   = "UPDATE " . ($parts[0] == 'i' ? "v4incumbents" : "v4seats") . " SET ";
      if (Str::startsWith($parts[2], "term")  ||  Str::startsWith($parts[2], "votes"))  $value = intval($value);
      $sqlFields = new SqlFields([$parts[2] => $value]);
      $result = $pdo->runSF($sql, "WHERE id={$parts[1]}", $sqlFields, true);
   }
}

$orgs         = Str::split($qsOrgs, ",");
$district     = $qsDistrict;
$showDistrict = Str::contains($qsShow, 'd');
$showSubDist  = Str::contains($qsShow, 'w');
$showSeat     = Str::contains($qsShow, 's');

for ($i=0;   $i<count($orgs);   $i++) $orgs[$i] = "'$orgs[$i]'";
$quotedOrgs = Str::join($orgs, ",");

$counties = [];
$sql = "SELECT s.*, i.name, i.party, t.shortname, i.phone, i.email, i.address, i.web, "
     . "            i.votes_C, i.votes_D, i.votes_R, i.votes_O, i.votes_T, i.id AS inc_id \n"
     . "  FROM v4seats           AS s \n"
     . "  LEFT JOIN v4incumbents AS i   ON (s.id = i.seat_id) \n"
     . "  LEFT JOIN v4titles     AS t   ON (s.org = t.org  AND  s.office = t.office) \n"
     . "  WHERE s.org in ($quotedOrgs) \n"
     . makeDistrictClause($district) . "\n"
     . "  ORDER BY FIELD(s.org, $quotedOrgs), t.ballot_order, s.district + 0, s.subdist, s.seatnum \n";
$result = $pdo->run($sql);
if ($result->failed()) $logger->log("Failed main select: " . $result->getError() . "  $sql");

//---Where the LEFT JOIN v4incumbents found no incumbent rows, create empty ones, with the seat_id set.
$rows  = $result->getRows();
$count = $result->getRowCount();
for ($i=0;   $i<$count;   $i++) {
   if (empty($rows[$i]['inc_id'])) {
      $insertIncumbent = "INSERT INTO v4incumbents (seat_id) VALUES ({$rows[$i]['id']}) ";
      $insertResult    = $pdo->run($insertIncumbent);
      if ($insertResult->failed())  $logger->log("Failed new empty v4seats: " . $insertResult->getError() . "  $insertIncumbent");
      $newIndex = $insertResult->getInsertId();
      $rows[$i]['inc_id'] = $newIndex;
   }
}

$expandableOrgs = array_intersect(getUniqueOrgsFoundIn($rows),
   ['city', 'city-cou', 'cnty', 'cnty-cou', 'crt-a', 'crt-c', 'crt-d', 'crt-m', 'crt-p', 'schl-cou', 'town', 'town-cou', 'vil', 'vil-cou']);
$offices = [];
foreach ($expandableOrgs as $org) {
   $sql = "SELECT office, shortname FROM v4titles WHERE org='$org' AND shortname != '' ";
   $result = $pdo->run($sql);
   $offices[$org] = $result->getRows();
}

$smarty = new SmartyPage();
$smarty->assign('rows', $rows);
$smarty->assign('name', calculatePageName($pdo, $orgs, $district));
$smarty->assign('showDistrict', $showDistrict);
$smarty->assign('showSubDist',  $showSubDist);
$smarty->assign('showSeat',     $showSeat);
$smarty->assign('expandableOrgs', $expandableOrgs);

$smarty->assign('qsOrgs',     $qsOrgs);      // for <form> action querystring.
$smarty->assign('qsDistrict', $qsDistrict);
$smarty->assign('qsShow',     $qsShow);

$smarty->assign('sql', $sql);
$smarty->display('officials.tpl');


//$smarty->registerPlugin(Smarty::PLUGIN_MODIFIER, "displayCode2",    [HttpCode::class, "display"]);

function calculatePageName(AlfredPDO $pdo, array $orgs, string $district): string {
   switch ($orgs[0]) {
      case "'us'":      return "United States";
      case "'mi'":      return "State of Michigan";
      case "'mi-sen'":  return "Michigan Senate";
      case "'mi-hou'":  return "Michigan House";
      case "'mi-boe'":  return "State and University Education Boards";
   }

   $quotedOrgs = Str::join($orgs, ",");
   $sql = "SELECT name FROM entity26 WHERE org IN ($quotedOrgs) " . makeDistrictClause($district);
   $rows = $pdo->run($sql)->getRows();
   return ucwords(strtolower((count($rows) > 0) ? $rows[0]['name'] : "No Name Found"));
}

function makeDistrictClause (string $district): string {
   return ($district ? " AND district = '{$district}' " : " ");
}

function getUniqueOrgsFoundIn(array $rows): array {
   $orgsFound = [];
   foreach ($rows as $row) {
      $org = $row['org'];
      if (! in_array($org, $orgsFound)) {
         $orgsFound[] = $org;
         $cou = $org . "-cou";
         // If we have city/cnty/town/vil, always ensure we include city-cou/cnty-cou/town-cou/vil-cou.
         if (in_array($org, ['city', 'cnty', 'town', 'vil']) && !in_array($cou, $orgsFound)) $orgsFound[] = $cou;
      }
   }
   return $orgsFound;
}