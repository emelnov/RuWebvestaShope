<?php

namespace Drupal\cmlapi\Service;

/**
 * Scheme from 1c.
 */
class Scheme {

  //phpcs:disable
  protected array $svoistvo;
  protected array $taxonomy;
  protected ParserCatalog $catalogParcer;
  protected XmlParser $xmlParser;
  //phpcs:enable

  /**
   * Constructs a new Scheme object.
   */
  public function __construct(
    ParserCatalog $catalog,
    XmlParser $xml_parser
  ) {
    $this->svoistvo = [];
    $this->taxonomy = [];
    $this->catalogParcer = $catalog;
    $this->xmlParser = $xml_parser;
  }

  /**
   * Page tree.
   */
  public function init(int $cml) : array {
    $data = $this->catalogParcer->parse($cml);
    if (\Drupal::moduleHandler()->moduleExists('devel')) {
      dsm($data);
    }
    $scheme = [
      'category' => $this->categoryList($data),
      'svoistvo' => $this->svoistvo,
      'taxonomy' => $this->taxonomy,
    ];
    return $scheme;
  }

  /**
   * Category.
   */
  private function categoryList($data) {
    $category = [];
    if ($data && isset($data['category']) && $data['category']) {
      foreach ($data['category'] as $id => $cat) {
        $props = [];
        foreach (array_keys($cat['Свойства']) as $key) {
          if (!empty($data['svoistvo'][$key])) {
            $svoistvo = $data['svoistvo'][$key];
            if ($svoistvo['ТипЗначений'] == 'Справочник') {
              $svoistvo['ВариантыЗначений'] = "see " . __CLASS__;
              $this->taxonomy[$key] = $svoistvo;
            }
            else {
              $this->svoistvo[$key] = $svoistvo;
            }
            $cat['Свойства'][$key] = $svoistvo;
          }
        }
        $category[$id] = $cat;
      }
    }
    return $category;
  }

}
