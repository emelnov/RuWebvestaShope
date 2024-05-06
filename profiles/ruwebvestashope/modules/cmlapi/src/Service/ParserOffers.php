<?php

namespace Drupal\cmlapi\Service;

/**
 * Class Parser Offers.
 */
class ParserOffers extends ParserBase {

  /**
   * Parse.
   */
  public function parse($cid = FALSE, $cache_on = TRUE) {
    $rows = [];
    $uris = $this->cmlService->getFilesPath($cid, 'offers');
    if ($uris) {
      foreach ($uris as $uri) {
        $row = $this->getFromCache($uri, $cache_on);
        if (empty($rows)) {
          $rows = $row;
        }
        else {
          $rows['offer'] = array_merge($rows['offer'], $row['offer']);
        }
      }
    }
    return $rows;
  }

  /**
   * Get Data.
   */
  private function getFromCache($uri, $cache_on) {
    $size = 300;
    $expire = \Drupal::time()->getRequestTime() + 60 * 60 * 24 * 1;
    $row = &drupal_static("ParserOffers::parse():$uri");
    if (!isset($row)) {
      $cache_key = 'ParserOffers:' . $uri;
      if (!$cache_on) {
        $cache_key .= rand();
      }
      if ($cache = \Drupal::cache()->get($cache_key)) {
        $row = [];
        if (is_numeric($cache->data)) {
          $chunks = intdiv($cache->data, $size);
          for ($i = 0; $i <= $chunks; $i++) {
            $chunk = \Drupal::cache()->get("$cache_key::data::$i")->data;
            $row = array_merge($row, $chunk);
          }
        }
      }
      else {
        if ($uri) {
          $data = $this->getData($uri);
          if (!empty($data['offer'])) {
            $row = $data['offer'];
          }
        }
        if (isset($row)) {
          $count = count($row);
          \Drupal::cache()->set($cache_key, $count, $expire);
          $chunks = array_chunk($row, $size);
          foreach ($chunks as $i => $chunk) {
            \Drupal::cache()->set("$cache_key::data::$i", $chunk, $expire);
          }
        }
      }
    }
    return $row;
  }

  /**
   * Parse.
   */
  public function parseOffersSvoistvo($cid = FALSE) : array {
    $data = [];
    $uris = $this->cmlService->getFilesPath($cid, 'offers');
    if ($uris) {
      foreach ($uris as $uri) {
        $xml = $this->xmlParserService->parseXmlFileHeader($uri);
        if ($xml) {
          $this->xmlParserService->parseXmlString($xml);
          \Drupal::messenger()->addWarning("TODO: " . __CLASS__ . ":" . __LINE__);
          // Foreach ($this->parseSvoistvo($xml) as $key => $value) {
          // $data[$value['Ид']] = $value;
          // }.
        }
      }
    }
    return $data;
  }

  /**
   * Parse.
   */
  public function parseArray($cid = FALSE, $cache_on = TRUE) : array {
    $size = 300;
    $expire = \Drupal::time()->getRequestTime() + 60 * 60 * 24 * 1;
    $rows = [];
    $uris = $this->cmlService->getFilesPath($cid, 'offers');
    if ($uris) {
      foreach ($uris as $uri) {
        $row = $this->getArrayFromCache($uri, $cache_on);
        if (empty($rows)) {
          $rows = $row;
        }
        else {
          $rows = array_merge($rows, $row);
        }
      }
    }
    return $rows;
  }

  /**
   * Get Data.
   */
  private function getArrayFromCache($uri, $cache_on) : array {
    $size = 300;
    $expire = \Drupal::time()->getRequestTime() + 60 * 60 * 24 * 1;
    $rows = &drupal_static("ParserOffers::parse():$uri");
    if (!isset($rows)) {
      $cache_key = 'ParserOffers:' . $uri;
      if (!$cache_on) {
        $cache_key .= rand();
      }
      if ($cache = \Drupal::cache()->get($cache_key . '1')) {
        $rows = [];
        if (is_numeric($cache->data)) {
          $chunks = intdiv($cache->data, $size);
          for ($i = 0; $i <= $chunks; $i++) {
            $chunk = \Drupal::cache()->get("$cache_key::data::$i")->data;
            $rows = array_merge($rows, $chunk);
          }
          $arr = $rows;
          $rows = [];
          $rows['offer'] = $arr;
          $m = \Drupal::cache()->get("$cache_key::data::svoistvo");
          $rows['svoistvo'] = \Drupal::cache()->get("$cache_key::data::svoistvo")->data;
        }
      }
      else {
        if ($uri) {
          $data = $this->getData($uri);
          if (!empty($data)) {
            $rows = $data;
          }
        }
        if (isset($rows['offer'])) {
          $count = count($rows['offer']);
          \Drupal::cache()->set($cache_key, $count, $expire);
          $chunks = array_chunk($rows['offer'], $size);
          foreach ($chunks as $i => $chunk) {
            \Drupal::cache()->set("$cache_key::data::$i", $chunk, $expire);
          }
        }
        if (isset($rows['svoistvo'])) {
          \Drupal::cache()->set("$cache_key::data::svoistvo", $rows['svoistvo'], $expire);
        }
      }
    }
    return $rows;
  }

  /**
   * Get Data.
   */
  public function getData($uri) {
    $this->xmlParserService->parseXmlFile($uri);
    $xml = $this->xmlParserService->xmlString;
    $data = $this->parseXml($xml);
    return $data;
  }

  /**
   * Parse.
   */
  public function parseXml($xml) {
    $config = \Drupal::config('cmlapi.mapsettings');
    $trans = \Drupal::transliteration();
    $map = $this->map('offers-standart', 'offers-dop');

    // $xml = $this->xmlParserService->xmlString;
    $svoistvo = [];
    if (!empty($data = $this->parseData($xml, 'svoistvo'))) {
      if (is_string($data['Ид'] ?? FALSE)) {
        $data = [$data];
      }
      foreach ($data as $key => $value) {
        $svoistvo[$value['Ид']] = $value;
      }
    }
    $price = [];
    if (!empty($data = $this->parseData($xml, 'price'))) {
      if (is_string($data['Ид'] ?? FALSE)) {
        $data = [$data];
      }
      foreach ($data as $key => $value) {
        $price[$value['Ид']] = $value;
      }
    }
    $stock = [];
    if (!empty($data = $this->parseData($xml, 'stock'))) {
      if (is_string($data['Ид'] ?? FALSE)) {
        $data = [$data];
      }
      foreach ($data as $key => $value) {
        $stock[$value['Ид']] = $value;
      }
    }
    $data = [
      'svoistvo' => $svoistvo,
      'price' => $price,
      'stock' => $stock,
      'offer' => [],
    ];
    $offers = $this->parseData($xml, 'offer');
    if ($offers) {
      if (isset($offers['Ид'])) {
        $offers = [$offers];
      }
      foreach ($offers as $offer1c) {
        $offer = [];
        foreach ($map as $map_key => $map_info) {
          $name = $trans->transliterate($map_key, '');
          $offer[$name] = $this->xmlParserService->prepare($offer1c, $map_key, $map_info);
        }
        $id = $offer1c['Ид'];
        $data['offer'][$id] = $offer;
      }
    }
    return $data;
  }

  /**
   * Parse.
   */
  public function parseData($xml, $field) {
    $this->xmlParserService->parseXmlString($xml);
    $this->xmlParserService->get('offers', $field);
    return $this->xmlParserService->xmlfind;
  }

}
