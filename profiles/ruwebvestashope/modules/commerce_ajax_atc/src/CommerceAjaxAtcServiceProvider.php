<?php

namespace Drupal\commerce_ajax_atc;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Replaces the add to cart message.
 */
class CommerceAjaxAtcServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replace the server side add to cart messaging.
    if ($container->hasDefinition('commerce_cart.cart_subscriber')) {
      $definition = $container->getDefinition('commerce_cart.cart_subscriber');
      $definition->setClass('Drupal\commerce_ajax_atc\EventSubscriber\AjaxCartEventSubscriber')
        ->addArgument(new Reference('request_stack'));
    }
  }

}
