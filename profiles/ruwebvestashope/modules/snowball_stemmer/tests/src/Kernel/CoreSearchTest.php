<?php

namespace Drupal\Tests\snowball_stemmer\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Tests the search_excerpt() function.
 *
 * @group snowball_stemmer
 */
class CoreSearchTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'search',
    'snowball_stemmer'
  ];

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stemmerService = $this->createMock('Drupal\snowball_stemmer\Stemmer');
    $this->container->set('snowball_stemmer.stemmer', $this->stemmerService);
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->container->set('language_manager', $this->languageManager);
    \Drupal::setContainer($this->container);
  }

  /**
   * Tests search_simplify() and the stemmer hook_search_preprocess integration.
   */
  public function testSearchSimplify() {
    $this->stemmerService
      ->expects($this->once())
      ->method('setLanguage')
      ->with('en')
      ->willReturn(TRUE);

    $this->stemmerService
      ->expects($this->exactly(4))
      ->method('stem')
      ->withConsecutive(['the'], ['quick'], ['brown'], ['fox']);

    $text = 'The quick brown fox';
    $language = 'en';
    \Drupal::service('search.text_processor')->analyze($text, $language);
  }

  /**
   * Tests the hook stemming alone.
   */
  public function testSearchStemming() {
    // HTML is stripped by Search module, but non-alpha-numeric characters are
    // maintained for later tokenizing. Search simplify has lowercased the
    // string.
    $text = 'van de groep 65-plussers is dat 14%.';
    $language = 'nl';
    $expected = 'van de groep 65-plusser is dat 14%.';

    $this->stemmerService
      ->expects($this->once())
      ->method('setLanguage')
      ->with('nl')
      ->willReturn(TRUE);

    $this->stemmerService
      ->expects($this->exactly(8))
      ->method('stem')
      ->withConsecutive(['van'], ['de'], ['groep'], ['65'], ['plussers'], ['is'], ['dat'], ['14'])
      ->will($this->onConsecutiveCalls(
        'van', 'de', 'groep', '65', 'plusser', 'is', 'dat', '14'
      ));

    $out = snowball_stemmer_search_preprocess($text, $language);
    $this->assertEquals($out, $expected);
  }

}
