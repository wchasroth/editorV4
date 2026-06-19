<?php
declare(strict_types=1);

use CharlesRothDotNet\EditorV4\NameSimplifier;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NameSimplifierTest extends TestCase {
   #[Test]
   public function shouldSimplifyNames_putWordsInAscendingOrder() {
      self::assertEquals ("moss vanessa", NameSimplifier::simplify("Moss, Vanessa M."));
      self::assertEquals ("moss vanessa", NameSimplifier::simplify("Vanessa M. Moss"));

      self::assertEquals ("aaron vanhorn", NameSimplifier::simplify("Aaron L. VanHorn, SR."));
      self::assertEquals ("at frank", NameSimplifier::simplify("A.T. Frank"));
      self::assertEquals ("adrienne hinnant johnson", NameSimplifier::simplify("Adrienne Hinnant-Johnson"));
      self::assertEquals ("conlin patrick", NameSimplifier::simplify("Conlin Jr., Patrick J."));
      self::assertEquals ("griffin herman", NameSimplifier::simplify("Griffin IV, Herman"));
   }

}