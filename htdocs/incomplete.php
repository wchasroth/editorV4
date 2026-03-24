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
$logger->log("Incomplete startup");

$qsOrgs     = HttpGet::value('orgs');
$qsDistrict = HttpGet::value('district');
$qsShow     = HttpGet::value('show');

$org = Str::substringBefore($qsOrgs . ',', ',');
$sql = "SELECT '' ";
if ($qsOrgs === 'schl-cou') {
   $sql = "SELECT name FROM v4counties "
        . " WHERE id     IN (SELECT county_id FROM v4schools   WHERE id=$qsDistrict) "
        . "   AND id NOT IN (SELECT id        FROM v4completed WHERE type='county') "
        . "  ORDER BY name";
}
else if ($qsOrgs === 'city') {
   $sql = "SELECT name FROM v4counties "
      . " WHERE id     IN (SELECT county_id FROM v4jurisdictions WHERE type='c' AND id=$qsDistrict) "
      . "   AND id NOT IN (SELECT id        FROM v4completed     WHERE type='county') "
      . "  ORDER BY name";
}

$result = $pdo->run($sql);
$rows = $result->getRows();
$rowCount = $result->getRowCount();
for ($i=0;   $i < $rowCount;   $i++) $rows[$i]['name'] = ucwords (strtolower ($rows[$i]['name']));

$smarty = new SmartyPage();

$smarty->assign('qsOrgs',     $qsOrgs);
$smarty->assign('qsDistrict', $qsDistrict);
$smarty->assign('qsShow',     $qsShow);
$smarty->assign('rows',       $rows);

$smarty->display('incomplete.tpl');