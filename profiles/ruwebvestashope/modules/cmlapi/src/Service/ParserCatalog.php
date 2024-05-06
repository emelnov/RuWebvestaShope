<?php

namespace Drupal\cmlapi\Service;

/**
 * Class Parser Catalog.
 */
class ParserCatalog extends ParserBase {

  /**
   * Parse.
   */
  public function parseFlatCatalog($cid = FALSE, $cache_on = TRUE) {
    $rows = [];
    $expire = \Drupal::time()->getRequestTime() + 60 * 60 * 24 * 1;
    $uris = $this->cmlService->getFilesPath($cid, 'import');
    if ($uris) {
      foreach ($uris as $uri) {
        $row = $this->getFromCache($uri, $cache_on);
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
  private function getFromCache($uri, $cache_on) {
    $expire = \Drupal::time()->getRequestTime() + 60 * 60 * 24 * 1;
    $row = [];
    $row = &drupal_static("ParserCatalog::getRows():$uri");
    if (!isset($row)) {
      $cache_key = 'ParserCatalog:' . $uri;
      if (!$cache_on) {
        $cache_key .= rand();
      }
      if ($cache = \Drupal::cache()->get($cache_key)) {
        $row = $cache->data;
      }
      else {
        if ($uri) {
          $data = $this->getData($uri);
          if (!empty($data)) {
            $row = $data;
          }
        }
        \Drupal::cache()->set($cache_key, $row, $expire);
      }
    }
    return $row;
  }

  /**
   * Get Data.
   */
  private function getData($uri) {
    $this->xmlParserService->parseXmlFileHeader($uri);
    if ($xml = $this->xmlParserService->xmlString ?? "") {
      $data = $this->parseGroup($xml, TRUE);
    }
    return $data;
  }

  /**
   * Parse.
   */
  public function parse($cid = FALSE) : array {
    $data = [];
    $uris = $this->cmlService->getFilesPath($cid, 'import');
    if ($uris) {
      foreach ($uris as $uri) {
        $xml = $this->xmlParserService->parseXmlFileHeader($uri);
        if ($xml) {
          $this->xmlParserService->parseXmlString($xml);
          $data = [
            'catalog' => $this->parseGroup(),
            'group' => $this->parseGroup(FALSE),
            'svoistvo' => $this->parseSvoistvo($xml),
            'category' => $this->parseCategory($xml),
          ];
        }
      }
    }
    return $data;
  }

  /**
   * Категории это типы товаров.
   */
  private function parseCategory() : array {
    $result = [];
    $data = $this->xmlParserService->get('import', 'category');
    foreach ($data as $cat) {
      $id = $cat['Ид'];
      if (!empty($cat['Свойства']['Ид'])) {
        if (is_string($cat['Свойства']['Ид'])) {
          // Если только один Ид.
          $cat['Свойства']['Ид'] = [$cat['Свойства']['Ид']];
        }
        $props = [];
        foreach ($cat['Свойства']['Ид'] as $pid) {
          $props[$pid] = [];
        }
        $cat['Свойства'] = $props;
      }
      else {
        $cat['Свойства'] = [];
      }
      $result[$id] = $cat;
    }
    return $result;
  }

  /**
   * Фичи - поля типа товара (категории) которые не справочники.
   */
  private function parseSvoistvo() : array {
    $data = $this->xmlParserService->get('import', 'svoistvo');
    $result = [];
    foreach ($data as $svoistvo) {
      $id = $svoistvo['Ид'];
      $result[$id] = $svoistvo;
      switch ($svoistvo['ТипЗначений'] ?? "") {
        case 'Справочник':
          $result[$id]['values'] = $this->itemsList($svoistvo);
          break;

        case 'Строка':
          break;

        default:
          break;
      }
    }
    return $result;
  }

  /**
   * Фичи - поля типа товара (категории) которые не справочники.
   */
  private function itemsList(array $svoistvo) : array {
    $items = [];
    $dictionary = \Drupal::service('cmlapi.xml_parser')
      ->arrayNormalize($svoistvo['ВариантыЗначений']['Справочник']);
    foreach ($dictionary as $k => $item) {
      $k = $item['ИдЗначения'];
      $items[$k] = $item['Значение'];
    }
    return $items;
  }

  /**
   * Группы - структура таксономии каталог.
   */
  private function parseGroup(bool $flatTree = TRUE) {
    $tree = $this->xmlParserService->get('import', 'gruppa');
    if ($flatTree && is_array($tree)) {
      return $this->flatTree($tree);
    }
    return $tree;
  }

  /**
   * Catalog flatTree (не дерево а плоский список).
   */
  private function flatTree(array $data, $parentId = NULL, $parent = TRUE) {
    $result = [];
    $i = 0;
    if (!empty($data)) {
      $data = $this->xmlParserService->arrayNormalize($data);
      foreach ($data as $key => $val) {
        $i++;
        $id = $val['Ид'];
        $result[$id] = [
          'id' => $val['Ид'],
          'name' => $val['Наименование'],
          'term_weight' => $i,
          'delete' => $val['ПометкаУдаления'] ?? FALSE,
        ];
        if ($parentId) {
          $result[$id]['parent'] = $parentId && !$parent ? $parentId : FALSE;
        }
        if (!empty($val['Группы']['Группа'])) {
          $result = array_merge($result, $this->flatTree($val['Группы']['Группа'], $id, FALSE));
        }
      }
    }
    return $result;
  }

}
