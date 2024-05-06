<?php

namespace Drupal\snowball_stemmer\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps a query pre-execute event.
 */
final class SetLanguageEvent extends Event {

  public const LANGUAGE_CODE = 'snowball_stemmer.set_language_code'; 

  /**
   * The language code.
   *
   * @var string 
   */
  protected $languageCode;

  /**
   * Constructs a new class instance.
   *
   * @param string 
   *   The language code.
   */
  public function __construct(string $language_code) {
    $this->languageCode = $language_code;
  }

  /**
   * Retrieves the language code.
   *
   * @return string 
   *   The language code.
   */
  public function getLanguageCode(): string {
    return $this->languageCode;
  }

  /**
   * Set language code.
   */
  public function setLanguageCode(string $language_code) {
    $this->languageCode = $language_code;
  }

}
