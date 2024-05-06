<?php

namespace Drupal\cmlapi\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Catalog Page Route.
 */
class Catalog extends ControllerBase {

  /**
   * Page tree.
   */
  public function page(int $cml) {
    $tree = '';
    $data = \Drupal::service('cmlapi.parser_catalog')->parse($cml);
    if ($data) {
      $tree = $this->renderGroups($data['group'], TRUE);
    }
    return [
      'catalog' => [
        '#markup' => "<div id='jstree'>{$tree}</div>",
        '#attached' => ['library' => ['cmlapi/cmlapi.jstree']],
      ],
    ];
  }

  /**
   * Render.
   */
  public function renderGroups($groups, $parent = FALSE) {
    $output = '<ul>';
    if (!empty($groups)) {
      $groups = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($groups);
      foreach ($groups as $group) {
        $data = '';
        if ($parent) {
          $data = "data-jstree='{ \"opened\" : true }' ";
        }
        $output .= "<li $data>";
        $output .= $group['Наименование'];
        if (!empty($group['Группы']['Группа'])) {
          $output .= $this->renderGroups($group['Группы']['Группа']);
        }
        $output .= '</li>';
      }
      $output .= '</ul>';
    }
    return $output;
  }

}
