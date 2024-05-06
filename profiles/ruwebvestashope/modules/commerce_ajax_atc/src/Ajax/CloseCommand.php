<?php

namespace Drupal\commerce_ajax_atc\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to close content in a colorbox.
 */
class CloseCommand implements CommandInterface {

  /**
   * Render.
   *
   * {@inheritdoc}.
   */
  public function render() {
    return [
      'command' => 'colorboxLoadClose',
    ];
  }

}
