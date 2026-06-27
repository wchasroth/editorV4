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

date_default_timezone_set("America/New_York");

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);
$logger = new DumbFileLogger($env->get('logFile'));

$county     = HttpGet::value('county');
$qsOrgs     = HttpGet::value('orgs');
$qsDistrict = HttpGet::value('district');
$qsShow     = HttpGet::value('show');
$can_id     = HttpGet::value('can_id');


$sql     = "SELECT seat_id FROM v4candidates WHERE id=$can_id LIMIT 1";
$seat_id = $pdo->run($sql)->getSingleValue('seat_id');

$sql = "SELECT COUNT(*) AS ct FROM v4candidates WHERE seat_id = $seat_id ";
$ct  = $pdo->run($sql)->getSingleValue('ct');

$sql = "DELETE FROM v4candidates WHERE id = $can_id";
$pdo->run($sql);
if ($ct == 1) {
   $sql = "INSERT INTO v4candidates (seat_id) VALUES ($seat_id)";
   $pdo->run($sql);
}

//$logger->log("Redirecting to: candidates.php?county={$county}&orgs={$qsOrgs}&district={$qsDistrict}&show={$qsShow}");
//header("Location: $urlBase/candidates.php?county={$county}&orgs={$qsOrgs}&district={$qsDistrict}&show={$qsShow}");
header("Location: candidates.php?county={$county}&orgs={$qsOrgs}&district={$qsDistrict}&show={$qsShow}");
exit;