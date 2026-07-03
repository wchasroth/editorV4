<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\FieldFormatFixer;
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

//---applyPick.  Apply a selection from the multiple-candidates "pick list", and
//   fill in the (otherwise) empty candidate slot with the data from the v4filings row.
//
//   Then direct back to candidates.php, where the new data should appear.

date_default_timezone_set("America/New_York");

$env     = new EnvFile("_env");
$pdo     = PdoHelper::makePdo($env);
$logger  = new DumbFileLogger($env->get('logFile'));

$county      = HttpGet::value('county');
$qsOrgs      = HttpGet::value('orgs');
$qsDistrict  = HttpGet::value('district');
$qsShow      = HttpGet::value('show');
$can_id      = HttpGet::value('can_id');
$filing_id   = HttpGet::value('filing_id');

$sql = "SELECT * FROM v4filings WHERE id = '$filing_id'";
$result = $pdo->run($sql);
if ($result->succeeded()  &&  $result->getRowCount() > 0) {
   $row = $result->getRows()[0];
   $fields = ['name' => $row['name'], 'party' => $row['party'], 'web' => $row['web'], 'email' => $row['email'], 'phone' => $row['phone'],
      'headshot_url' => $row['headshot_url'], 'description' => $row['description'], 'source' => 'AI' ,
      'headshot' => $row['headshot'], 'headcropped' => $row['headcropped']];
   $sqlFields = new SqlFields($fields);
   $sql = "UPDATE v4candidates SET " . $sqlFields->getSetFragment() . " WHERE id='$can_id'";
   $result = $pdo->run($sql);
   if ($result->failed()) $logger->log("applyPick error: $sql");
}

header("Location: candidates.php?county=$county&orgs=$qsOrgs&district=$qsDistrict&show=$qsShow");
exit;