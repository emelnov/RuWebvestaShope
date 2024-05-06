<?php

namespace Drupal\cmlapi\Service;

use Symfony\Component\Yaml\Yaml;

/**
 * Abstract? Parser  \Base Class.
 */
class ParserBase {

  //phpcs:disable
  protected CmlService $cmlService;
  protected XmlParser $xmlParserService;
  //phpcs:enable

  /**
   * Constructs a new ParserBase object.
   */
  public function __construct(CmlService $cml, XmlParser $xml_parser) {
    $this->cmlService = $cml;
    $this->xmlParserService = $xml_parser;
  }

  /**
   * Map.
   */
  public function map($set1, $set2) {
    $config = \Drupal::config('cmlapi.mapsettings');
    $map_sdandart = Yaml::parse($config->get($set1));
    $map_dop = Yaml::parse($config->get($set2));
    if (is_array($map_dop)) {
      $map = array_merge($map_sdandart, $map_dop);
    }
    else {
      $map = $map_sdandart;
    }
    return $map;
  }

}
