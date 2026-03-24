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

$smarty = new SmartyPage();

$smarty->assign('qsOrgs',     $qsOrgs);
$smarty->assign('qsDistrict', $qsDistrict);
$smarty->assign('qsShow',     $qsShow);

$smarty->display('incomplete.tpl');