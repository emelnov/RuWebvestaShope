<?php

namespace Drupal\Tests\snowball_stemmer\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\snowball_stemmer\Event\SetLanguageEvent;

/**
 * Tests altering the Language Code.
 *
 * @group snowball_stemmer
 */
class LanguageCodeEventTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'snowball_stemmer'
  ];

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Tests search_simplify() and the stemmer hook_search_preprocess integration.
   */
  public function testSearchSimplify() {
    $dispatcher = $this->container->get('event_dispatcher');
    $english = new SetLanguageEvent('en');
    $dispatcher->dispatch($english, SetLanguageEvent::LANGUAGE_CODE);
    $this->assertEquals('en', $english->getLanguageCode());
    $british = new SetLanguageEvent('en-gb');
    $dispatcher->dispatch($british, SetLanguageEvent::LANGUAGE_CODE);
    $this->assertEquals('en', $british->getLanguageCode());
    $norwegian = new SetLanguageEvent('nb');
    $dispatcher->dispatch($norwegian, SetLanguageEvent::LANGUAGE_CODE);
    $this->assertEquals('no', $norwegian->getLanguageCode());
  }

}
