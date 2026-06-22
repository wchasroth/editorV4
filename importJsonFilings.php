<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\SqlFields;

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
   if (!Str::startsWith(strtolower($candidate['party'] ?? ''), 'r')) {
      $seatArray = $translator->translate($candidate['seat_id']);
      if (empty($seatArray['org'])) continue;

      $party = trim((($candidate['party'] ?? '') . " ")[0]);
      $id = calculateId($candidate);
      if ($candidate['partial_term']) $seatArray['partialterm'] = 1;

      if (Str::contains($candidate['status'], 'withdrawn', 'disqualified')) {
         $pdo->run("DELETE FROM v4filings WHERE id = '$id'");
      } else {
         $meta = $candidate['metadata'];
         $sqlFields = new SqlFields([
            'id' => $candidate['id'], 'org' => $seatArray['org'], 'office' => $seatArray['office'],
            'district' => $seatArray['district'], 'subdist' => $seatArray['subdist'],
            'name' => $candidate['name'], 'party' => $party,
            'partialterm' => $seatArray['partialterm'], 'partialend' => $seatArray['partialend'],
            'web' => $meta['campaign_website'] ?? '', 'email' => $meta['email'] ?? '',
            'headshot' => $meta['headshot_url'] ?? '', 'description' => $meta['statement'] ?? '',
            'phone' => $meta['phone'] ?? ''
         ]);
         $result = $pdo->runSF("INSERT INTO v4filings", "", $sqlFields, true);
         if ($result->failed()) fwrite(STDERR, "INSERT failed: " . $result->getError() . "  " . $result->getRawSql() . "\n");
      }
  }
}

function calculateId(array $candidate): string {
   $name = NameSimplifier::simplify($candidate['name']);
   return substr(hash("sha256", $candidate['election_id'] . $candidate['seat_id'] . $name), 0, 32);
}
