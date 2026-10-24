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
use DateInterval;
use DateTime;
use DatePeriod;

require_once('../vendor/autoload.php');

date_default_timezone_set("America/New_York");

//---waveqr.php.  Show count of wave and qr redirects to www.mivoter.org

$env     = new EnvFile("_env");
$pdo     = PdoHelper::makePdo($env);

$rows = [];
$day0 = new DateTime('2026-10-19');
$day1 = new DateTime('today');
$day1->modify('+1 day');

$period = new DatePeriod($day0, new DateInterval('P1D'), $day1);
foreach ($period as $date) {
   $day = $date->format('Y-m-d');
   $rows[$day] = ['date' => $day, 'session' => 0, 'wave' => 0, 'qr' => 0];
}

$sql = "select date(timestamp) AS day, count(*) as wave "
     . "  from v4redirects "
     . " where redirect='wave'  and  timestamp > '2026-10-20' "
     . " group by date(timestamp) "
     . " order by day DESC";
$result = $pdo->run($sql);
$waves = $result->getRows();
foreach ($waves as $wave) $rows[$wave['day']]['wave'] = $wave['wave'];

$sql = "select date(timestamp) AS day, count(*) as qr "
     . "  from v4redirects "
     . " where redirect='qr'  and  timestamp > '2026-10-19' "
     . " group by date(timestamp) "
     . " order by day DESC";
$result = $pdo->run($sql);
$qrs = $result->getRows();
foreach ($qrs as $qr) $rows[$qr['day']]['qr'] = $qr['qr'];

$sql = "SELECT DATE(timestamp) AS day, COUNT(DISTINCT hash_addr) AS session "
     . "  FROM v4voter_log "
     . " WHERE timestamp > '2026-10-20' "
     . " GROUP BY DATE(timestamp) "
     . " ORDER BY day DESC";
$result = $pdo->run($sql);
$sessions = $result->getRows();
foreach ($sessions as $session) $rows[$session['day']]['session'] = $session['session'];

$smarty = new SmartyPage();
$smarty->assign('rows', $rows);
$smarty->display('waveqr.tpl');
