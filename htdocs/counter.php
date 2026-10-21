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

//---counter.php.  Show count of distinct voter visits in last 24 hours.

$env     = new EnvFile("_env");
$pdo     = PdoHelper::makePdo($env);

$sql = "SELECT COUNT(DISTINCT hash_addr) AS count24 FROM v4voter_log "
     . " WHERE timestamp <= now() "
     . "   AND timestamp >= now() - interval 1 day "
     . "   AND hash_addr != ''";

$result = $pdo->run($sql);
$count24 = $result->getSingleValue('count24');

$smarty = new SmartyPage();
$smarty->assign('count24', $count24);
$smarty->display('counter.tpl');
