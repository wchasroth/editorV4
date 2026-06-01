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

$env     = new EnvFile("_env");
$email   = EnvHelper::getEmail($env);
$pdo     = PdoHelper::makePdo($env);
$logger  = new DumbFileLogger($env->get('logFile'));

$canId    = $_GET['canId']    ?? '';
$name     = $_GET['name']     ?? '';
$headshot = $_GET['headshot'] ?? '';


$smarty = new SmartyPage();
$smarty->assign('canId',    $canId);
$smarty->assign('name',     $name);
$smarty->assign('headshot', $headshot);
$smarty->display('photo.tpl');
