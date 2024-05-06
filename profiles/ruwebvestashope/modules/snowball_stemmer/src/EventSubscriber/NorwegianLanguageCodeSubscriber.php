<?php

namespace Drupal\snowball_stemmer\EventSubscriber;

use Drupal\snowball_stemmer\Event\SetLanguageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Snowball stemmer event subscriber for Norwegian Language Codes.
 */
class NorwegianLanguageCodeSubscriber implements EventSubscriberInterface {

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function languageCodeAlter(SetLanguageEvent $event) {
    if ($event->getLanguageCode() == 'nb' || $event->getLanguageCode() == 'nn') {
      $event->setLanguageCode('no');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SetLanguageEvent::LANGUAGE_CODE => ['languageCodeAlter'],
    ];
  }

}
