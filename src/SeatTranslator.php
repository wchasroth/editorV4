<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\MichiganCounties;
use CharlesRothDotNet\Alfred\Str;

class SeatTranslator {
   private AlfredPDO $pdo;
   private MichiganCounties $counties;

   private static $townOfficeMap = [
      'clerk' => 'town-clerk', 'township-clerk' => 'town-clerk', 'constable' => 'town-cons',
      'park-commissioner' => 'town-park', 'parks-board-commissioner' => 'town-park',
      'parks-commissioner' => 'town-park', 'township-park-board' => 'town-park',
      'supervisor' => 'town-super', 'township-supervisor' => 'town-super',
      'treasurer' => 'town-treas', 'township-treasurer' => 'town-treas',
   ];

   private static $townSpellingFixes = [
      'baymills' => 'bay mills', 'detour' => 'de tour',
      'kalamazoo charter township' => 'kalamazoo', 'genoa charter township' => 'genoa',
      'muskegon charter township'  => 'muskegon', 'almer charter township'  => 'almer'
   ];

   private static $cityOfficeMap = [
      'mayor' => 'mayor', 'treasurer' => 'treas', 'clerk' => 'clerk', 'city-comptroller' => 'comp',
      'assessor' => 'assess', 'constable' => 'cons'
   ];

   private static $romanNumerals = [
      'i' => 1, 'ii' => 2, 'iii' => 3, 'iv' => 4, 'v' => 5, 'vi' => 6,
      'vii' => 7, 'viii' => 8, 'ix' => 9, 'x' => 10
   ];

   function __construct(AlfredPDO $pdo) {
      $this->pdo = $pdo;
      $this->counties = new MichiganCounties();
   }

   public function translate(string $jsonSeatId): array {
      $result = ['org' => '', 'office' => '', 'district' => '', 'subdist' => 0, 'seatnum' => 0];
      if (Str::contains($jsonSeatId, 'delegate')) return $result;
      if (Str::contains($jsonSeatId, 'library'))  return $result;

      $parts = Str::splitIntoTokens($jsonSeatId, ':');
      $parts[3] = $parts[3] ?? '';
      $parts[4] = $parts[4] ?? '';
      $parts[5] = $parts[5] ?? '';

      // Circuit courts
      if ($parts[1] === 'circuit-court') {
         $result['org'] = 'crt-c';
         $result['district'] = Str::substringAfterLast($parts[2], '-');
      }

      // All township offices, including councils
      else if ($parts[3] === 'twp' || Str::contains($parts[5], 'township')) {
         if (Str::contains($parts[5], 'trustee')) {
            $result['org'] = 'town-cou';
            $result['office'] = 'council';
         } else {
            $result['org'] = 'town';
            $office = Str::replaceFirst($parts[5], $parts[4] . '-', '');  // sometimes office has township name in it!
            $result['office'] = self::$townOfficeMap[$office] ?? 'UNKNOWN';
            if ($result['office'] === 'UNKNOWN')  fwrite(STDERR, "Office: $office\n");
         }
         $countyCode   = $this->getCountyCode($parts[2]);
         $townshipName = Str::replaceAll($parts[4], '-', ' ');
         $townshipName = self::$townSpellingFixes[$townshipName] ?? $townshipName;
         if (!Str::contains($townshipName, 'township')) $townshipName .= " township";
         $sql = "SELECT id FROM s4jurisdictions WHERE county_id=$countyCode AND name = '$townshipName' AND type='t'";
         $result['district'] = $this->getJurisdictionId($sql, $jsonSeatId);
      }

      // Cities.
      else if ($parts[3] === 'city') {
         if (Str::contains($parts[5], 'council', 'commissioner')) {
            $result['org']    = 'city-cou';
            $result['subdist'] = $this->extractSubdistrictNumber($parts[6] ?? '');
         }
         else {
            $result['org'] = 'city';
            $result['office'] = self::$cityOfficeMap[$parts[5]] ?? '';
         }
         $cityName = Str::replaceFirst($parts[4], 'city-of-', '');
         $cityName = Str::replaceAll  ($cityName, '-', ' ');
         $cityName = Str::replaceFirst($cityName, "mt ", "mount ");
         if (! Str::contains($cityName, ' city'))  $cityName .= " city";

         $countyCode = $this->getCountyCode($parts[2]);
         $sql = "SELECT id FROM s4jurisdictions WHERE county_id=$countyCode AND name = '$cityName' AND type='c'";
         $result['district'] = $this->getJurisdictionId($sql, $jsonSeatId);
      }
      return $result;
   }

   private function getJurisdictionId(string $sql, string $jsonSeatId): string {
      $queryResult = $this->pdo->run($sql);
      $id = $queryResult->getSingleValue('id');
      if (! empty($id))  return strval($id);

      fwrite(STDERR, "Error: $jsonSeatId\n");
      return "0";
   }

   private function getCountyCode(string $countyName): int {
      return intval($this->counties->getNumber($countyName));
   }

   private function extractSubdistrictNumber(string $district): int {
      if (empty($district))                       return 0;
      if (! Str::contains($district, 'district')) return 0;
      $subdist = $this->extractTerminalRomanNumeral($district);
      return ($subdist ?: $this->extractNumberFrom($district));
   }

   private function extractTerminalRomanNumeral(string $district): int {
      $district = trim($district);
      foreach (self::$romanNumerals as $key => $value) {
         if (Str::endsWith($district, "-$key")) return $value;
      }
      return 0;
   }

   private function extractNumberFrom (string $district): int {
      $digits = preg_replace('/[^0-9]/', '', $district);
      return intval($digits);
   }
}