<?php

namespace Drupal\cmlapi\Service;

use Drupal\Component\Transliteration\PhpTransliteration;

/**
 * Class Parser Product.
 */
class ParserProduct extends ParserBase {

  /**
   * Parse.
   */
  public function parse(bool | int $cid = FALSE, bool $cache_on = FALSE) : array {
    $rows = [];
    $uris = $this->cmlService->getFilesPath($cid, 'import');
    if ($uris) {
      foreach ($uris as $uri) {
        $row = $this->getFromCache($uri, $cache_on);
        if (empty($rows)) {
          $rows = $row;
        }
        else {
          $rows['data'] = array_merge($rows['data'], $row['data']);
        }
      }
    }
    return $rows;
  }

  /**
   * Get Data.
   */
  private function getFromCache(string $uri, bool $cache_on) : array {
    $size = 300;
    $expire = \Drupal::time()->getRequestTime() + 60 * 60 * 24 * 1;
    $row = [];
    $row = &drupal_static("ParserProduct::parse():$uri");
    if (!isset($row)) {
      $cache_key = 'ParserProduct:' . $uri;
      if (!$cache_on) {
        $cache_key .= rand();
      }
      if ($cache = \Drupal::cache()->get($cache_key)) {
        $row = [
          'info' => \Drupal::cache()->get("$cache_key::info")->data,
          'data' => [],
        ];
        if (is_numeric($cache->data)) {
          $chunks = intdiv($cache->data, $size);
          for ($i = 0; $i <= $chunks; $i++) {
            $chunk = \Drupal::cache()->get("$cache_key::data::$i")->data;
            $row['data'] = array_merge($row['data'], $chunk);
          }
        }
      }
      else {
        if ($uri) {
          $data = $this->getData($uri);
          if (!empty($data)) {
            $row = $data;
          }
        }
        if (empty($row['data'])) {
          return [];
        }
        \Drupal::cache()->set("$cache_key::info", $row['info'], $expire);
        if (isset($row['data'])) {
          $count = count($row['data']);
          \Drupal::cache()->set($cache_key, $count, $expire);
          $chunks = array_chunk($row['data'], $size, TRUE);
          foreach ($chunks as $i => $chunk) {
            \Drupal::cache()->set("$cache_key::data::$i", $chunk, $expire);
          }
        }
      }
    }
    return $row;
  }

  /**
   * Get Data.
   */
  private function getData(string $uri) : array {
    $this->xmlParserService->parseXmlFile($uri);
    if ($xml = $this->xmlParserService->xmlString) {
      return $this->parseXml($xml);
    }
    return [];
  }

  /**
   * Parse XML.
   */
  private function parseXml(string $xml) : array {
    $result = [];
    $trans = new PhpTransliteration();
    $map = $this->map('tovar-standart', 'tovar-dop');
    $products = $this->xmlParserService
      ->parseXmlString($xml)
      ->get('import', 'product');
    $result['info']['svoistva'] = $this->xmlParserService->get('import', 'svoistvo');

    foreach ($products as $products1c) {
      $key = $products1c['Ид'];
      $id = strstr("{$key}#", "#", TRUE);
      $result['data'][$id]['offers'][$key] = [];
      $product = [
        'status' => $products1c['@attributes']['Статус'] ?? 1,
      ];
      foreach ($map as $map_key => $map_info) {
        $name = $trans->transliterate($map_key, '');
        $value = $this->xmlParserService->prepare($products1c, $map_key, $map_info);
        $product[$name] = $value;
        $this->setOffersExtras($result, $map_info, $id, $key, $name, $value);
      }
      $result['data'][$id]['product'] = $product;
    }
    return $result;
  }

  /**
   * Offers Extras.
   */
  private function setOffersExtras(array &$result, $map_info, string $id, string $key, $name, $value) : void {
    $destination = $map_info['dst'] ?? "";
    if ($destination == 'offers') {
      $result['data'][$id]['offers'][$key][$name] = $value;
    }
    // Return [];.
  }

}
