<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\AlfredPDO;

require_once('vendor/autoload.php');

// importJsonFilings.php
//    using JSON produced by Jon O for candidates

$jsonFile   = $argv[1];
$env        = new EnvFile("_env");
$pdo        = PdoHelper::makePdo($env);
$translator = new SeatTranslator($pdo);

$jsonString = file_get_contents($jsonFile);
$candidates = json_decode($jsonString, true);

foreach ($candidates as $candidate) {
   if (! Str::startsWith (strtolower($candidate['party'] ?? ''), 'r')) {
      $seatArray = $translator->translate($candidate['seat_id']);
      if (! empty($seatArray['org'])) {
         $party = trim((($candidate['party'] ?? '') . " ")[0]);
         echo $candidate['name'] . "  $party  " . Str::join($seatArray, ':') . "\n";
      }
   }
}