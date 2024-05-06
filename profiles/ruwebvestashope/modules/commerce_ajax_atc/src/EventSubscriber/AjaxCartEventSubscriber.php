<?php

namespace Drupal\commerce_ajax_atc\EventSubscriber;

use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\EventSubscriber\CartEventSubscriber as CommerceCartEventSubscriber;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Replaces the original CartEventSubscriber from commerce_cart module.
 */
class AjaxCartEventSubscriber extends CommerceCartEventSubscriber {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CartEventSubscriber object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(MessengerInterface $messenger, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack,) {
    parent::__construct($messenger, $string_translation, $entity_type_manager);

    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function displayAddToCartMessage(CartEntityAddEvent $event) {
    $is_ajax = $this->currentRequest->isXmlHttpRequest();
    if (!$is_ajax) {
      parent::displayAddToCartMessage($event);
    }
  }

}
