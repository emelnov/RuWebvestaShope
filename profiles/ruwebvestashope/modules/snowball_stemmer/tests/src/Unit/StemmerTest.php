<?php

namespace Drupal\Tests\snowball_stemmer\Unit;

use Drupal\snowball_stemmer\Event\SetLanguageEvent;
use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\snowball_stemmer\Stemmer;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Wamania\Snowball\Stemmer\German;
use Wamania\Snowball\Stemmer\English;

/**
 * Test Snowball Stemmer class wrapping.
 *
 * @coversDefaultClass \Drupal\snowball_stemmer\Stemmer
 *
 * @group snowball_stemmer
 */
class StemmerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Create new object for Stemmer.
   */
  protected function setUp(): void {
    parent::setUp();

    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $event_dispatcher->dispatch(Argument::type(SetLanguageEvent::class), SetLanguageEvent::LANGUAGE_CODE)->willReturnArgument(0);
    $this->stemmer = new Stemmer($event_dispatcher->reveal());
  }

  /**
   * Test stemming for each language.
   *
   * @dataProvider languageStrings
   */
  public function testStemming($language, $original, $stemmed) {
    $success = $this->stemmer->setLanguage($language);
    $this->assertTrue($success);
    $word = $this->stemmer->stem($original);
    $this->assertEquals($word, $stemmed);
  }

  /**
   * Test unknown language.
   */
  public function testUnknownLanguage() {
    $this->expectException('\Drupal\snowball_stemmer\LanguageNotSetException');
    $success = $this->stemmer->setLanguage('xx');
    $this->assertFalse($success);
    $this->stemmer->stem('word');
  }

  /**
   * List of words for each language.
   *
   * The class itself tests multiple strings, just need a string for each
   * language to check it is called correctly.
   */
  public function languageStrings() {
    return [
      ['da', 'barnløshed', 'barnløs'],
      ['de', 'aalglatten', 'aalglatt'],
      ['en', 'backing', 'back'],
      ['es', 'carnavaleros', 'carnavaler'],
      ['fr', 'désintéressement', 'désintéress'],
      ['it', 'entusiastico', 'entusiast'],
      ['nl', 'fraudebestrijding', 'fraudebestrijd'],
      ['no', 'gjestearbeidende', 'gjestearbeid'],
      ['pt', 'humildemente', 'humild'],
      ['ro', 'intelectualismului', 'intelectualist'],
      ['ru', 'пересчитаешь', 'пересчита'],
      ['sv', 'jämförelsen', 'jämför'],
    ];
  }

  /**
   * Test overrides.
   *
   * @covers ::setOverrides
   * @covers ::hasOverride
   */
  public function testOverrides() {
    $this->stemmer->setLanguage('en');
    $this->stemmer->setOverrides([
      'our' => 'special',
      'term' => 'kept',
    ]);
    $this->stemmer->setOverrides([
      'term' => 'english',
      'also' => 'overriden',
    ], 'en');

    $this->assertEquals($this->stemmer->stem('our'), 'special');
    $this->assertEquals($this->stemmer->stem('term'), 'english');
    $this->assertEquals($this->stemmer->stem('also'), 'overriden');
  }

  /**
   * Test word cache.
   *
   * Feels like this test could be simpler with a bit of refactoring the class.
   */
  public function testWordCache() {
    $reflector = new \ReflectionClass('\Drupal\snowball_stemmer\Stemmer');
    $language = $reflector->getProperty('language');
    $language->setAccessible(TRUE);
    $stemmers = $reflector->getProperty('stemmers');
    $stemmers->setAccessible(TRUE);

    $prophecyGerman = $this->prophesize(German::CLASS);
    $prophecyGerman->stem('wordish')->willReturn('word');
    $prophecyGerman->stem('otherly')->willReturn('other');
    $prophecyEnglish = $this->prophesize(English::CLASS);
    $prophecyEnglish->stem('wordish')->willReturn('wordy');
    $prophecyEnglish->stem('justly')->willReturn('just');
    $stemmers->setValue($this->stemmer, [
      'de' => $prophecyGerman->reveal(),
      'en' => $prophecyEnglish->reveal(),
    ]);

    $language->setValue($this->stemmer, 'de');
    $this->assertEquals($this->stemmer->stem('wordish'), 'word');
    $this->assertEquals($this->stemmer->stem('otherly'), 'other');
    $language->setValue($this->stemmer, 'en');
    $this->assertEquals($this->stemmer->stem('wordish'), 'wordy');
    $this->assertEquals($this->stemmer->stem('justly'), 'just');
  }

}
