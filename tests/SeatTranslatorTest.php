<?php
declare(strict_types=1);

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\EditorV4\SeatTranslator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SeatTranslatorTest extends TestCase {
   private AlfredPDO      $pdo;
   private SeatTranslator $translator;

   protected function setUp(): void {
      parent::setUp();
      $env              = new EnvFile("_env");
      $this->pdo        = PdoHelper::makePdo($env);
      $this->translator = new SeatTranslator($this->pdo);
   }

   #[Test]
   public function shouldTranslateCircuitCourt(): void {
      $filing = $this->translator->translate("mi:circuit-court:circuit-16");
      $this->assertIs($filing, "crt-c", "", "16");
   }

   #[Test]
   public function shouldTranslateTownshipOffices() {
      $filing = $this->translator->translate("mi:county:allegan:twp:cheshire-township:supervisor");
      $this->assertIs($filing, "town", "town-super", "15200");
   }

   #[Test]
   public function shouldTranslateTownshipOffices_withoutTownshipInTitle() {
      $filing = $this->translator->translate("mi:county:berrien:twp:bainbridge:township-clerk");
      $this->assertIs($filing, "town", "town-clerk", "4840");
   }

   #[Test]
   public function shouldTranslateTownshipCouncil() {
      $filing = $this->translator->translate("mi:county:allegan:twp:valley-township:trustee");
      $this->assertIs($filing, "town-cou", "council", "81580");
   }

   #[Test]
   public function shouldFailOnBadTownship() {
      $filing = $this->translator->translate("mi:county:allegan:twp:hobbiton-township:supervisor");
      $this->assertIs($filing, "town", "town-super", "0");
   }

   #[Test]
   public function shouldTranslateCityCouncil() {
      $filing = $this->translator->translate("mi:county:berrien:city:niles:city-council-member:district-ward-1");
      $this->assertIs($filing, 'city-cou', '', '57760', 1);
      $filing = $this->translator->translate("mi:county:genesee:city:swartz-creek:council-member:district-district-iv");
      $this->assertIs($filing, 'city-cou', '', '77700', 4);
      $filing = $this->translator->translate("mi:county:genesee:city:mount-morris:council-member");
      $this->assertIs($filing, 'city-cou', '', '55960', 0);
   }

   private function assertIs (array $filing, string $org, string $office, string $district, int $subdist = -1) {
      $this->assertEquals ($org,      $filing['org']);
      $this->assertEquals ($office,   $filing['office']);
      $this->assertEquals ($district, $filing['district']);
      if ($subdist >= 0)
         $this->assertEquals ($subdist, $filing['subdist']);
   }


}