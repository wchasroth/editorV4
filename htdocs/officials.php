<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\AlfredPDO;
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

$env    = new EnvFile("_env");
$email  = EnvHelper::getEmail($env);
$pdo    = PdoHelper::makePdo($env);
$logger = new DumbFileLogger($env->get('logFile'));

$qsOrgs      = HttpGet::value('orgs');
$qsDistrict  = HttpGet::value('district');
$reviewedKey = $qsOrgs . ":" . $qsDistrict;
$qsShow     = HttpGet::value('show');
$showSaved  = 0;

//---Get form data (note that we have *three* different forms: data changes or seat deletions, new offices, or new commission/council seats.
$fieldsChanged = rtrim(HttpPost::value('fieldsChanged'), ",");
$office     = HttpPost::value('office');
$subdist    = HttpPost::value('subdist');
$org        = HttpPost::value('org');
$deleteSeat = HttpPost::value('deleteSeat');

//---Handle data changes (form submission).
if (! Str::isReallyEmpty($fieldsChanged)) {
   foreach (Str::split($fieldsChanged, ",") as $field) {
      if ($field == "reviewed") {
         $reviewed = intval(HttpPost::value('reviewed'));
         if ($reviewed === 1) $pdo->run("INSERT INTO v4pagesReviewed (page) VALUES ('$reviewedKey')");
         else                 $pdo->run("DELETE FROM v4pagesReviewed  WHERE    page='$reviewedKey'");
      }
      else {
         $value = HttpPost::value($field);
         $parts = Str::split($field, ':');
         $sql = "UPDATE " . ($parts[0] == 'i' ? "v4incumbents" : "v4seats") . " SET ";
         if (Str::startsWith($parts[2], "term")) $value = intval($value);
         $sqlFields = new SqlFields([$parts[2] => $value]);
         $query = $sql . $sqlFields->getUpdateFragment() . " WHERE id={$parts[1]}";
         $result = $pdo->run($query);
      }
   }

   $showSaved = 1;
}

//---Handle new offices (form submission)
else if (! Str::isReallyEmpty($office)) {
   $sql = "SELECT seats FROM v4titles WHERE org='$org' AND office='$office'";
   $result = $pdo->run($sql);
   $logger->log("seatmax: $sql   " . $result->getError());
   $seatmax = intval($result->getSingleValue('seats'));
   $sql = "INSERT INTO v4seats (org, office, district, seatnum, seatmax) VALUES ('$org', '$office', '$qsDistrict', 1, $seatmax)";
   $pdo->run($sql);
}

//---Handle new seats on commission/council (form submission)
else if (! Str::isReallyEmpty($subdist)) {
   $sql = "SELECT MAX(seatnum) as highseat FROM v4seats WHERE org='$org' AND district='$qsDistrict' AND subdist=$subdist";
   $result = runQueryReportErrors($pdo, $logger, $sql);
   $highseat = intval($result->getSingleValue('highseat')) + 1;
   $newOffice = (Str::contains($org, "town-cou", "vil-cou") ? "council" : "");
   $sql = "INSERT INTO v4seats (org, office, district, subdist, seatnum) VALUES ('$org', '$newOffice', '$qsDistrict', $subdist, $highseat)";
   runQueryReportErrors($pdo, $logger, $sql);
}

else if (! Str::isReallyEmpty($deleteSeat)) {
   //---Handle renumbering seats (if necessary).  (Extract into function?)
   $sql = "SELECT org, office, district, subdist, seatnum FROM v4seats WHERE id=$deleteSeat";
   $result = runQueryReportErrors($pdo, $logger, $sql);
   if ($result->getRowCount() > 0) {
      $row     = $result->getRows()[0];
      $seatnum = $row['seatnum'];
      $sqlFields = new SqlFields(['org' => $row['org'], 'office' => $row['office'], 'district' => $row['district'], 'subdist' => $row['subdist']]);
      $sql = "SELECT id FROM v4seats WHERE seatnum > $seatnum AND " . $sqlFields->getSelectFragment();
      $result = $pdo->run($sql);
      foreach ($result->getRows() as $row) {
         $sql = "UPDATE v4seats SET seatnum=$seatnum WHERE id=" . $row['id'];
         $pdo->run($sql);
         ++$seatnum;
      }
   }

   runQueryReportErrors($pdo, $logger, "INSERT INTO v4deletedIncumbents SELECT * FROM v4incumbents WHERE seat_id = $deleteSeat");
   runQueryReportErrors($pdo, $logger, "DELETE FROM v4incumbents WHERE seat_id = $deleteSeat");
   $sql = "INSERT INTO v4deletedSeats (id, org, office, district, subdist, seatnum, seatmax, termlen, termcycle, whodid) "
        . "                     SELECT id, org, office, district, subdist, seatnum, seatmax, termlen, termcycle, '$email' "
        . "   FROM v4seats WHERE id = $deleteSeat";
   runQueryReportErrors($pdo, $logger, $sql);
   runQueryReportErrors($pdo, $logger, "DELETE FROM v4seats WHERE id = $deleteSeat");
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

$sql = "SELECT 1 AS reviewed FROM v4pagesReviewed WHERE page='$reviewedKey'";
$reviewed = intval($pdo->run($sql)->getSingleValue('reviewed'));

$counties = [];
$sql = "SELECT s.*, i.name, i.party, t.shortname, i.phone, i.email, i.address, i.web, "
//   . "            i.votes_C, i.votes_D, i.votes_R, i.votes_O, i.votes_T,
     . "            i.id AS inc_id, t.seats, 0 AS appointed \n"
//   . "            (ROUND((i.votes_C * 100) / i.votes_T) * GREATEST(i.num2elect, 1)) as PCT, i.id AS inc_id \n"
     . "  FROM v4seats           AS s \n"
     . "  LEFT JOIN v4incumbents AS i   ON (s.id = i.seat_id) \n"
     . "  LEFT JOIN v4titles     AS t   ON (s.org = t.org  AND  s.office = t.office) \n"
     . "  WHERE s.org in ($quotedOrgs) \n"
     . makeDistrictClause($district) . "\n"
     . "  ORDER BY FIELD(s.org, $quotedOrgs), t.ballot_order, s.district + 0, s.subdist, s.seatnum \n";
$result = $pdo->run($sql);
//$logger->log("BIG SQL: $sql");
if ($result->failed()) $logger->log("Failed main select: " . $result->getError() . "  $sql");

//---Where the LEFT JOIN v4incumbents found no incumbent rows, create empty ones, with the seat_id set.
$rows  = $result->getRows();
$count = $result->getRowCount();
for ($i=0;   $i<$count;   $i++) {
   if (Str::isReallyEmpty($rows[$i]['inc_id'])) {
      $insertIncumbent = "INSERT INTO v4incumbents (seat_id) VALUES ({$rows[$i]['id']}) ";
      $insertResult    = $pdo->run($insertIncumbent);
      if ($insertResult->failed())  $logger->log("Failed new empty v4seats: " . $insertResult->getError() . "  $insertIncumbent");
      $newIndex = $insertResult->getInsertId();
      $rows[$i]['inc_id'] = $newIndex;
   }
}

$expandableOrgs = array_intersect(getUniqueOrgsFoundIn($rows),
   ['city', 'city-cou', 'cnty', 'cnty-com', 'crt-a', 'crt-c', 'crt-d', 'crt-m', 'crt-p', 'schl-cou', 'town', 'town-cou', 'vil', 'vil-cou', 'comcol-cou']);
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
// if (intval($rows[$i]['PCT']) > 100)  $rows[$i]['PCT'] = '??';
   $rows[$i]['web'] = stripHttps ($rows[$i]['web']);
   $rows[$i]['url'] = addProtocol($rows[$i]['web']);
   if (Str::contains($rows[$i]['office'], '-appt') ) $rows[$i]['appointed'] = 1;
}

$regionColumnName = "Reg";
if (Str::contains($qsOrgs, "cnty"))  $regionColumnName = "Dist";
if (Str::contains($qsOrgs, "city"))  $regionColumnName = "Ward";
$smarty = new SmartyPage();
$smarty->assign('rows', $rows);
$smarty->assign('name', calculatePageName($pdo, $orgs, $district, $logger));
$smarty->assign('showDistrict', $showDistrict);
$smarty->assign('showSubDist',  $showSubDist && showSubDistricts($rows));
$smarty->assign('showSeat',     $showSeat);
$smarty->assign('expandableOrgs', $expandableOrgs);
$smarty->assign('regionColumnName', $regionColumnName);
$smarty->assign('reviewedChecked', ($reviewed == 1 ? "checked" : ""));

$smarty->assign('qsOrgs',     translateOrgs($qsOrgs));      // for <form> action querystring.
$smarty->assign('qsDistrict', $qsDistrict);
$smarty->assign('qsShow',     $qsShow);

$existing1SeatOffices   = computeExistingSingleSeatOffices($rows);
$allAddableOfficeNames  = computeOfficeNames($pdo, $org1, $existing1SeatOffices, $logger);
$smarty->assign('offices',    $allAddableOfficeNames);

$smarty->assign('sql', $sql);
$smarty->assign('showSaved', $showSaved);
$smarty->display('officials.tpl');


//$smarty->registerPlugin(Smarty::PLUGIN_MODIFIER, "displayCode2",    [HttpCode::class, "display"]);

function runQueryReportErrors($pdo, DumbFileLogger $logger, string $sql, $alwaysLog=false): PdoRunResult {
   $result = $pdo->run($sql);
   if      ($alwaysLog)        $logger->log("RunQueryReportErrors: $sql");
   else if ($result->failed()) $logger->log("Error: $sql  " . $result->getError());
   return $result;
}

function addProtocol (string $url): string {
   if (! Str::isReallyEmpty($url)  &&  ! Str::startsWith($url, "http://"))  $url = "https://$url";
   return $url;
}

function computeExistingSingleSeatOffices(array $rows): array {
   $results = [];
   foreach ($rows as $row) {
      if (intval($row['seats']) == 1) $results[$row['office']] = 1;
   }
   return array_keys($results);
}

function showSubDistricts(array $rows): bool {
   $result = true;
   foreach ($rows as $row) {
      if ($row['org'] === 'city-cou') {
         $result = false;
         if (intval($row['subdist']) > 0)  return true;
      }
   }
   return $result;
}

function computeOfficeNames($pdo, $org, array $existing1SeatOffices, $logger): array {
   $sql = "SELECT office, shortname FROM v4titles WHERE org='$org' AND shortname != '' ORDER BY shortname ";
   $result = $pdo->run($sql);
   $rows = $result->getRows();
   $rowCount = $result->getRowCount();
   // Remove the ones that already exist as 1-seaters
   for ($i=0;   $i<$rowCount;   ++$i) {
      if (in_array($rows[$i]['office'], $existing1SeatOffices)) unset($rows[$i]);
   }
   return $rows;
}

function nextElectionYearForSeat(array $row, int $thisYear): string {
   $termcycle = intval($row['termcycle']);
   $termlen   = intval($row['termlen']);
   if ($termcycle == 0  ||  $termlen == 0)  return strval($row['termcycle']);
   while ($termcycle < $thisYear) $termcycle += $termlen;
   return strval($termcycle);
}

function stripHttps(?string $url): string {
   if (Str::isReallyEmpty($url))  return "";
   return (Str::startsWith($url, "https://") ? Str::substringAfter($url, "https://") : $url);
}

function correctCase(?string $name): string {
   if (Str::isReallyEmpty($name))  return "";
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
//    $logger->log("officials, nothing in entity26: $sql");
      if (Str::startsWith($orgs[0], "'vil'")) {
         $sql = "SELECT name FROM v4villages WHERE id=$district";
         $result = $pdo->run($sql);
         $name = correctCase($result->getSingleValue('name'));
         if (! Str::contains(strtolower($name), "village")) $name = "Village of $name";
         return $name;
      }
      if (Str::startsWith($orgs[0], "'comcol")) {
         $sql = "SELECT name FROM v4commcolleges WHERE id=$district";
         $result = $pdo->run($sql);
         $name = $result->getSingleValue('name');
         return correctCase($name);
      }
      if (Str::startsWith($orgs[0], "'town")) {
         $sql = "SELECT name FROM v4jurisdictions WHERE type='t' AND id=$district";
         $result = $pdo->run($sql);
         $name = $result->getSingleValue('name');
         return correctCase($name);
      }
      if (Str::startsWith($orgs[0], "'city")) {
         $sql = "SELECT name FROM v4jurisdictions WHERE type='c' AND id=$district";
         $result = $pdo->run($sql);
         $name = $result->getSingleValue('name');
         return correctCase($name);
      }
      if (Str::startsWith($orgs[0], "'schl")) {
         $sql = "SELECT name FROM v4schools WHERE  id=$district";
         $result = $pdo->run($sql);
         $name = $result->getSingleValue('name');
         return correctCase($name);
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