<?php

namespace Drupal\snowball_stemmer\EventSubscriber;

use Drupal\snowball_stemmer\Event\SetLanguageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Snowball stemmer event subscriber.
 */
class RegionLanguageCodeSubscriber implements EventSubscriberInterface {

  /**
   * Remove any region or local from the lanuage code. 
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function languageCodeAlter(SetLanguageEvent $event) {
    if ($hyphen = strpos($event->getLanguageCode(), '-')) {
      $event->setLanguageCode(substr($event->getLanguageCode(), 0, $hyphen));
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
