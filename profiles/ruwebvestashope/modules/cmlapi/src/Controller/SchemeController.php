<?php

namespace Drupal\cmlapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Scheme from 1c.
 */
class SchemeController extends ControllerBase {

  /**
   * Page tree.
   */
  public function page(int $cml) {
    $cml_entity = \Drupal::entityTypeManager()->getStorage('cml')->load($cml);
    if ($cml_entity && $cml_entity->full->value) {
      $data = \Drupal::service('cmlapi.scheme')->init($cml);
    }
    else {
      return ['#markup' => "Только для полных обменов"];
    }
    \Drupal::messenger()->addWarning("category - это Категории (типы товаров) svoistvo = поля в том числе таксономия");
    \Drupal::messenger()->addWarning("svoistvo - просто поля (не справочники)");
    \Drupal::messenger()->addWarning("svoistva - это типы Свойства / справочники (props)");
    $output = Yaml::dump($data, 7, 2);
    return [
      'info' => ['#markup' => "<pre>$output</pre>"],
    ];
  }

}
