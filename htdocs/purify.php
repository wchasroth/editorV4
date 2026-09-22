<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\SmartyPage;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\DumbFileLogger;
use CharlesRothDotNet\Alfred\AlfredHTMLPurifier;

require_once('../vendor/autoload.php');

date_default_timezone_set("America/New_York");

$env     = new EnvFile("_env");
$logger  = new DumbFileLogger($env->get('logFile'));
$pdo     = PdoHelper::makePdo($env);

$purifier = new AlfredHTMLPurifier();

$sql = "SELECT id, text AS tbefore, '' AS tafter FROM v4uitext "
     . " WHERE (id NOT LIKE '%-es') OR id='pg-endorsed-1-hdr-es'  ORDER BY id ASC";
$result = $pdo->run($sql);
$rows = $result->getRows();
$count = $result->getRowCount();
for ($i=0;  $i<$count;  $i++) {
   $rows[$i]['tafter'] = $purifier->purify($rows[$i]['tbefore']);
}

$smarty = new SmartyPage();
$smarty->assign('rows', $rows);
$smarty->assign('error', $result->getError());
$smarty->display('purify.tpl');
