<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\HttpGet;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;

require_once('../vendor/autoload.php');

date_default_timezone_set("America/New_York");

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

$county     = HttpGet::number('county');
$qsOrgs     = HttpGet::value('orgs');
$qsDistrict = EnvHelper::safeDistrict(HttpGet::value('district'));
$qsShow     = HttpGet::value('show');
$can_id     = HttpGet::number('can_id');
$clickPick  = HttpGet::value('clickpick');

$email   = EnvHelper::getEmail($env);
$row     = EnvHelper::getUserPermissions($pdo, $email);
$canEdit = EnvHelper::canUserEdit($row, $county);
if (! $canEdit)  exit();

$sql     = "SELECT seat_id FROM v4candidates WHERE id=$can_id LIMIT 1";
$seat_id = $pdo->run($sql)->getSingleValue('seat_id');

$sql = "INSERT INTO v4candidates (seat_id) VALUES ($seat_id)";
$pdo->run($sql);

header("Location: candidates.php?county={$county}&orgs={$qsOrgs}&district={$qsDistrict}&show={$qsShow}&clickpick={$clickPick}");
exit;