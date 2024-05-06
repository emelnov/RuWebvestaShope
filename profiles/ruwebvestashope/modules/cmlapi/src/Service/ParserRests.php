<?php

namespace Drupal\cmlapi\Service;

/**
 * Class Parser Rests.
 */
class ParserRests extends ParserBase {

  /**
   * Parse.
   */
  public function parse($cid = FALSE, $cache_on = TRUE) {
    $size = 300;
    $expire = \Drupal::time()->getRequestTime() + 60 * 60 * 24 * 1;
    $rows = FALSE;
    $uris = $this->cmlService->getFilesPath($cid, 'rests');
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
    $row = &drupal_static("ParserRests::parse():$uri");
    if (!isset($row)) {
      $cache_key = 'ParserRests:' . $uri;
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

    $xml = $this->xmlParserService->xmlString;
    $data = [
      'svoistvo' => $this->parseSvoistvo($xml),
      'offer' => [],
    ];
    $offers = $this->parseOffer($xml);
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
  public function parseSvoistvo($xml) {
    $this->xmlParserService->parseXmlString($xml);
    $this->xmlParserService->get('offers', 'svoistvo');
    return $this->xmlParserService->xmlfind;
  }

  /**
   * Parse.
   */
  public function parseOffer($xml) {
    $this->xmlParserService->parseXmlString($xml);
    $this->xmlParserService->get('offers', 'offer');
    return $this->xmlParserService->xmlfind;
  }

}
