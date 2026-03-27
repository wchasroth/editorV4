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
$showSaved  = 0;

//---Get form data (note that we have *three* different forms: data changes or seat deletions, new offices, or new commission/council seats.
$fieldsChanged = rtrim(HttpPost::value('fieldsChanged'), ",");
$office     = HttpPost::value('office');
$subdist    = HttpPost::value('subdist');
$org        = HttpPost::value('org');
$deleteSeat = HttpPost::value('deleteSeat');

//---Handle data changes (form submission).
if (! empty($fieldsChanged)) {
   foreach (Str::split($fieldsChanged, ",") as $field) {
      $value = HttpPost::value($field);
      $parts = Str::split($field, ':');
      $sql   = "UPDATE " . ($parts[0] == 'i' ? "v4incumbents" : "v4seats") . " SET ";
      if (Str::startsWith($parts[2], "term"))  $value = intval($value);
      $sqlFields = new SqlFields([$parts[2] => $value]);
      $query = $sql . $sqlFields->getUpdateFragment() . " WHERE id={$parts[1]}";
      $logger->log("SAVE: $query ");
      $result = $pdo->run($query);
   }
   $showSaved = 1;
}

//---Handle new offices (form submission)
else if (! empty($office)) {
   $sql = "INSERT INTO v4seats (org, office, district, seatnum) VALUES ('$org', '$office', '$qsDistrict', 1)";
   $pdo->run($sql);
}

//---Handle new seats on commission/council (form submission)
else if (! empty($subdist)) {
   $sql = "INSERT INTO v4seats (org, district, subdist) VALUES ('$org', '$qsDistrict', $subdist)";
   $pdo->run($sql);
}

else if (! empty($deleteSeat)) {
   $sql = "DELETE FROM v4seats WHERE id = (SELECT seat_id FROM v4incumbents WHERE id=$deleteSeat)";
   $result = $pdo->run($sql);
   $logger->log($sql . "  " . $result->getError());
   $sql = "DELETE FROM v4incumbents WHERE id = $deleteSeat";
   $result = $pdo->run($sql);
   $logger->log($sql . "  " . $result->getError());
   // renumber seats?
}


//$orgs         = Str::split($qsOrgs, ",");
$orgs         = Str::split(translateOrgs($qsOrgs), ",");
$org1         = $orgs[0];
$district     = $qsDistrict;
$showDistrict = Str::contains($qsShow, 'd');
$showSubDist  = Str::contains($qsShow, 'w');
$showSeat     = Str::contains($qsShow, 's');

for ($i=0;   $i<count($orgs);   $i++) $orgs[$i] = "'$orgs[$i]'";
$quotedOrgs = Str::join($orgs, ",");

$counties = [];
$sql = "SELECT s.*, i.name, i.party, t.shortname, i.phone, i.email, i.address, i.web, "
//   . "            i.votes_C, i.votes_D, i.votes_R, i.votes_O, i.votes_T,
     . "            i.id AS inc_id, \n"
     . "            (ROUND((i.votes_C * 100) / i.votes_T) * GREATEST(i.num2elect, 1)) as PCT, i.id AS inc_id \n"
//   . "            ROUND((i.votes_C * 100) / i.votes_T) as PCT, i.id AS inc_id \n"
     . "  FROM v4seats           AS s \n"
     . "  LEFT JOIN v4incumbents AS i   ON (s.id = i.seat_id) \n"
     . "  LEFT JOIN v4titles     AS t   ON (s.org = t.org  AND  s.office = t.office) \n"
     . "  WHERE s.org in ($quotedOrgs) \n"
     . makeDistrictClause($district) . "\n"
     . "  ORDER BY FIELD(s.org, $quotedOrgs), t.ballot_order, s.district + 0, s.subdist, s.seatnum \n";
$result = $pdo->run($sql);
//$logger->log("SQL: $sql");
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
//$offices = [];
//foreach ($expandableOrgs as $org) {
//   $sql = "SELECT office, shortname FROM v4titles WHERE org='$org' AND shortname != '' ";
//   $result = $pdo->run($sql);
//   $offices[$org] = $result->getRows();
//}

//---Apply simple transformations for display:
$thisYear = intval(date('Y'));
for ($i=0;   $i<$count;   $i++) {
   $rows[$i]['name']      = correctCase($rows[$i]['name']);  // Fix all-upper-case names
   $rows[$i]['termcycle'] = nextElectionYearForSeat($rows[$i], $thisYear);
   if (intval($rows[$i]['PCT']) > 100)  $rows[$i]['PCT'] = '??';
   $rows[$i]['web'] = stripHttps ($rows[$i]['web']);
   $rows[$i]['url'] = addProtocol($rows[$i]['web']);
}

$regionColumnName = "Reg";
if (Str::contains($qsOrgs, "cnty"))  $regionColumnName = "Dist";
if (Str::contains($qsOrgs, "city"))  $regionColumnName = "Ward";
$smarty = new SmartyPage();
$smarty->assign('rows', $rows);
$smarty->assign('name', calculatePageName($pdo, $orgs, $district, $logger));
$smarty->assign('showDistrict', $showDistrict);
$smarty->assign('showSubDist',  $showSubDist);
$smarty->assign('showSeat',     $showSeat);
$smarty->assign('expandableOrgs', $expandableOrgs);
$smarty->assign('regionColumnName', $regionColumnName);

$smarty->assign('qsOrgs',     translateOrgs($qsOrgs));      // for <form> action querystring.
$smarty->assign('qsDistrict', $qsDistrict);
$smarty->assign('qsShow',     $qsShow);
$smarty->assign('offices',    computeOfficeNames($pdo, $org1));

$smarty->assign('sql', $sql);
$smarty->assign('showSaved', $showSaved);
$smarty->display('officials.tpl');


//$smarty->registerPlugin(Smarty::PLUGIN_MODIFIER, "displayCode2",    [HttpCode::class, "display"]);

function addProtocol (string $url): string {
   if (! empty($url)  &&  ! Str::startsWith($url, "http://"))  $url = "https://$url";
   return $url;
}

function computeOfficeNames($pdo, $org): array {
   $sql = "SELECT office, shortname FROM v4titles WHERE org='$org' AND shortname != '' ORDER BY shortname ";
   $result = $pdo->run($sql);
   return $result->getRows();
}

function nextElectionYearForSeat(array $row, int $thisYear): string {
   $termcycle = intval($row['termcycle']);
   $termlen   = intval($row['termlen']);
   if ($termcycle == 0  ||  $termlen == 0)  return strval($row['termcycle']);
   while ($termcycle < $thisYear) $termcycle += $termlen;
   return strval($termcycle);
}

function stripHttps(?string $url): string {
   if (empty($url))  return "";
   return (Str::startsWith($url, "https://") ? Str::substringAfter($url, "https://") : $url);
}

function correctCase(?string $name): string {
   if (empty($name))  return "";
   $upper = strtoupper($name);
   if ($upper != $name)  return $name;
   return ucwords(strtolower($name));
}

function calculatePageName(AlfredPDO $pdo, array $orgs, string $district, DumbFileLogger $logger): string {
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

   //---This is a horrible hack-around for the issues with entity26 -- which itself should be replaced!
   if (count($rows) == 0) {
      $logger->log("officials, nothing in entity26: $sql");
      if (Str::startsWith($orgs[0], "'vil'")) {
         $sql = "SELECT name FROM v4villages WHERE id=$district";
         $result = $pdo->run($sql);
         $name = $result->getSingleValue('name');
         if (! Str::contains(strtolower($name), "village")) $name = "Village of " . ucwords(strtolower($name));
         return $name;
      }
      if (Str::startsWith($orgs[0], "'comcol")) {
         $sql = "SELECT name FROM v4commcolleges WHERE id=$district";
         $result = $pdo->run($sql);
         $name = $result->getSingleValue('name');
         return $name;
      }
   }
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

function translateOrgs(string $orgs):  string {  // Handle weird court orgs from 'court' table.
   $transform = ['A' => 'crt-a', 'C' => 'crt-c', 'D' => 'crt-d', 'P' => 'crt-p', 'PD' => 'crt-d,crt-p'];
   $orgNames = Str::split($orgs, ",");
   $results = [];
   foreach ($orgNames as $org) {
      if (isset($transform[$org]))  $results[] = $transform[$org];
      else                          $results[] = $org;
   }
   return Str::join($results, ",");
}