<?php

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\PdoRunResult;

class CandidateCounter {
   private AlfredPDO $pdo;

   function __construct(AlfredPDO $pdo) {
      $this->pdo = $pdo;
   }

   function calculateOverallCounts(string $modifier): array {
      static $term = " (s.termcycle=2026 OR s.is_open=1) ";
      static $what = " COUNT(DISTINCT s.org, s.office, s.district, s.subdist, c.reviewed, s.seatnum) ";
      static $join = " v4seats AS s LEFT JOIN v4candidates AS c ON (c.seat_id = s.id) ";

      $counts   = [];

      $numSeats    = "SELECT " . $this->what("")           . " AS numSeats    FROM v4seats AS s WHERE $term $modifier";
      $counts['numSeats']    = $this->runQuery($numSeats)->getSingleValue('numSeats');

      $numReviewed = "SELECT " . $this->what('c.reviewed') . " AS numReviewed FROM $join WHERE $term $modifier AND c.reviewed=1";
      $counts['numReviewed'] = $this->runQuery($numReviewed)->getSingleValue('numReviewed');

      $numEndorsed = "SELECT " . $this->what('c.endorsed') . " AS numEndorsed FROM $join WHERE $term $modifier AND c.reviewed=1 AND c.endorsed=1";
      $counts['numEndorsed'] = $this->runQuery($numEndorsed)->getSingleValue('numEndorsed');

      return $counts;
   }

   private function runQuery(string $sql): PdoRunResult {
      $result = $this->pdo->run($sql);
      return $result;
   }

   function what (string $column): string {
      return " COUNT(DISTINCT s.org, s.office, s.district, s.subdist, s.seatnum "
         . (! empty($column) ? ", $column" : "") . ") ";
   }

}