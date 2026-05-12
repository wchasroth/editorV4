<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\AlfredPDO;

require_once('vendor/autoload.php');

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

$sql = "SELECT * FROM v4pagesReviewed";
$result = $pdo->run($sql);
$pagesReviewed = $result->getRows();
foreach ($pagesReviewed as $pageReviewed) {
   $page = $pageReviewed["page"];
   $orgs = Str::substringBefore($page, ":");
   $id   = Str::substringAfter ($page, ":");

   if (Str::startsWith($orgs, "city"))
      $success = shouldMarkPassed("SELECT 1 AS success FROM s4jurisdictions WHERE id=$id AND county_id=$ctyId AND type='c'", $pdo);

   else if (Str::startsWith($orgs, 'town'))
      $success = shouldMarkPassed("SELECT 1 AS success FROM s4jurisdictions WHERE id=$id AND county_id=$ctyId AND type='t'", $pdo);

   else if (Str::startsWith($orgs, 'schl'))
      $success = shouldMarkPassed("SELECT 1 AS success FROM s4schools WHERE id=$id AND county_id=$ctyId", $pdo);

   else if (Str::startsWith($orgs, 'vil'))
      $success = shouldMarkPassed("SELECT 1 AS success FROM s4villages WHERE id=$id AND county_id=$ctyId", $pdo);

   else if (Str::startsWith($orgs, 'crt'))
      $success = shouldMarkPassed("SELECT 1 AS success FROM v4courts WHERE shortname='$id' AND county_id=$ctyId", $pdo);

   else if (Str::startsWith($orgs, 'comcol'))
      $success = shouldMarkPassed("SELECT 1 AS success FROM v4commcolleges_county WHERE id=$id AND county_id=$ctyId", $pdo);

   else if (Str::startsWith($orgs, 'cnty'))  $success = (intval($id) === $ctyId);

   else $success = false;

   if ($success) {
      $sql = "INSERT INTO v4pagesPassed (page, who, dt) SELECT page, who, dt FROM v4pagesReviewed WHERE page='$page'";
      $result = $pdo->run($sql);
      if ($result->failed()) { fwrite (STDERR, "INSERT: $sql\n");  continue; }
      $sql = "DELETE FROM v4pagesReviewed WHERE page='$page'";
      $pdo->run($sql);
   }

}

function shouldMarkPassed(string $sql, AlfredPDO $pdo): bool {
   $result = $pdo->run($sql);
   if ($result->failed())  fwrite(STDERR, "Error: $sql\n");
   $success = $result->getSingleValue('success');
   return (intval($success) === 1);
}


//comcol-cou
