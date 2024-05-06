<?php

namespace Drupal\cmlapi\Service;

/**
 * Class Xml Parser.
 */
class XmlParser {

  // phpcs:disable
  protected string $queryMap;
  public string | NULL $xmlString = NULL;
  public array $xmlArray = [];
  public array $xmlfind = [];
  public array $stages = [
    'category',
    'svoistvo',
    'product',
    'price',
    'sku',
    'stock',
    'offer',
    'order',
    'image',
  ];

  /**
   * XmlImportMapping.
   */
  public array $xmlImportMapping = [
    'gruppa'   => 'Классификатор/Группы/Группа',
    'category' => 'Классификатор/Категории/Категория',
    'svoistvo'  => 'Классификатор/Свойства/Свойство',
    'price'    => 'Классификатор/ТипыЦен',
    'stock'    => 'Классификатор/Склады',
    'product'  => 'Каталог/Товары/Товар',
  ];

  /**
   * XmlOffersMapping.
   */
  public array $xmlOffersMapping = [
    'price'   => 'ПакетПредложений/ТипыЦен/ТипЦены',
    'stock'   => 'ПакетПредложений/Склады/Склад',
    'svoistvo' => 'Классификатор/Свойства/Свойство',
    'offer'   => 'ПакетПредложений/Предложения/Предложение',
  ];

  /**
   * Stages.
   */
  public array $productMaps = [
    'ЗначенияРеквизитов'   => 'ЗначениеРеквизита',
    'ЗначенияСвойств'      => 'ЗначенияСвойства',
    'ХарактеристикиТовара' => 'ХарактеристикаТовара',
    'СтавкиНалогов'        => 'СтавкаНалога',
    'Цены'                 => 'Цена',
    'Изготовитель'         => '',
  ];
  // phpcs:enable

  /**
   * Constructs a new XmlParser object.
   */
  public function __construct() {

  }

  /**
   * Get Last.
   */
  public function xmlString($uri) {
    $filepath = \Drupal::service('file_system')->realpath($uri);
    return $filepath;
  }

  /**
   * Parse_xml_file.
   */
  public function parseXmlFile($file_uri) : string | NULL {
    $filepath = \Drupal::service('file_system')->realpath($file_uri);
    $this->xmlString = NULL;

    if (is_file($filepath) && is_readable($filepath)) {
      $file = fopen($filepath, "r");
      while ($xml = fread($file, filesize($filepath))) {
        $this->xmlString .= $xml;
      }
      fclose($file);
    }
    else {
      \Drupal::messenger()->addError("$file_uri is not readable");
    }
    return $this->xmlString;
  }

  /**
   * Parse_xml_file .
   */
  public function parseXmlFileHeader($file_uri) : string | NULL {
    $filepath = \Drupal::service('file_system')->realpath($file_uri);
    $xml = "";
    if (is_file($filepath) && is_readable($filepath)) {
      $file = new \SplFileObject($filepath);
      while (!$file->eof()) {
        $line = $file->fgets();
        $xml .= $line;
        if (strpos($line, "</Классификатор>")) {
          $xml .= "</КоммерческаяИнформация>\n";
          break;
        }
      }
      $file = NULL;
    }
    else {
      \Drupal::messenger()->addError("$file_uri is not readable");
    }
    $this->xmlString = $xml;
    return $xml;
  }

  /**
   * Find.
   */
  public function prepare($data, $key, $map) {
    $result = NULL;
    // Skip.
    if (isset($map['skip']) && $map['skip']) {
      return $result;
    }
    // Parse.
    if ($field = $data[$key] ?? FALSE) {
      $type = $this->prepareType($map);
      switch ($type) {
        case 'string':
          $result = $this->prepareString($field);
          break;

        case 'array':
          $result = $this->prepareArray($field, $key);
          break;

        case 'keyval':
          $result = $this->prepareKeyVal($field, $key);
          break;

        case 'attr':
          $result = $this->prepareAttribute($field, $map);
          break;

        default:
          break;
      }

    }

    return $result;
  }

  /**
   * Prepare String.
   */
  public function prepareKeyVal($field, string $key) : array {
    $result = [];
    $map = $this->productMaps[$key] ?? FALSE;
    if (!empty($field && $map !== FALSE)) {
      $value = $field[$map] ?? $field;
      $result = $this->xml2KeyVal($value);
    }
    return $result;
  }

  /**
   * Conv 1c data to key-val.
   */
  private function xml2KeyVal(array $arr) : array {
    $result = [];
    $keys = [
      'Ид',
      'Наименование',
    ];
    $values = [
      'Значение',
      'Ставка',
    ];
    if (!empty($arr)) {
      foreach ($this->arrayNormalize($arr) as $value) {
        $dat = array_values($value);
        $dat[1] = is_string($dat[1]) ? $dat[1] : "";
        if (count($dat) == 2) {
          $result[$dat[0]] = $dat[1];
        }
      }
    }
    return $result;
  }

  /**
   * Conv 1c data to key-val.
   */
  public function xml2Val($arr) {
    $result = [];
    if (!empty($arr)) {
      foreach ($this->arrayNormalize($arr) as $value) {
        $dat = array_values($value);
        if (count($dat) == 2) {
          $result[] = $dat[1];
        }
      }
    }
    return $result;
  }

  /**
   * Prepare String.
   */
  private function prepareType($map) {
    $type = 'string';
    if (isset($map['type'])) {
      $type = $map['type'];
      if (is_array($type)) {
        $type = 'array';
      }
    }
    return $type;
  }

  /**
   * Prepare String.
   */
  private function prepareString($field) {
    $result = '';
    if (!is_array($field)) {
      $result = $field;
    }
    return $result;
  }

  /**
   * Prepare String.
   */
  private function prepareAttribute($field, $map) {
    $result = '';
    if (isset($map['attr'])) {
      $attr = $map['attr'];
      if (isset($field['@attributes'][$attr])) {
        $result = $field['@attributes'][$attr];
      }
    }
    return $result;
  }

  /**
   * Prepare Array.
   */
  private function prepareArray($field, $key) {
    $result = [];
    if (isset($this->productMaps[$key]) && $m = $this->productMaps[$key]) {
      $result = $this->arrayNormalize($field[$m]);
    }
    elseif ($key == 'Группы') {
      foreach ($this->arrayNormalize($field) as $group) {
        $result[] = $group['Ид'];
      }
    }
    else {
      if (isset($map['type']['inside'])) {
        $result = $this->arrayNormalize($field[$map['type']['inside']]);
      }
      else {
        $result = $this->arrayNormalize($field);
      }
      // Выводим только значения определнного поля.
      if (isset($map['type']['list'])) {
        $buffer = [];
        foreach ($result as $index => $row) {
          if (isset($row[$map['type']['list']])) {
            $buffer[] = $row[$map['type']['list']];
          }
        }
        if (count($result) == count($buffer)) {
          $result = $buffer;
        }
        else {
          $result = NULL;
        }
      }
      // Преобразуем в json.
      if (isset($map['type']['json']) && $jsonStatement = $map['type']['json']) {
        $result = json_encode($result, JSON_UNESCAPED_UNICODE);
      }
    }
    return $result;
  }

  /**
   * Find.
   */
  public function find($map) : array {
    $query = explode("/", $map);
    $result = [];
    $this->xmlfind = $this->xmlArray;
    foreach ($query as $q) {
      if (isset($this->xmlfind[$q])) {
        $this->xmlfind = $this->xmlfind[$q];
      }
      else {
        $this->xmlfind = [];
      }
    }
    $result = $this->xmlfind;
    return $result;
  }

  /**
   * Find.
   */
  public function get($type, $key) : array {
    $map = FALSE;
    if ($type == 'import') {
      $mapping = $this->xmlImportMapping;
    }
    elseif ($type == 'offers') {
      $mapping = $this->xmlOffersMapping;
    }
    if (isset($mapping[$key])) {
      $map = $mapping[$key];
      $this->queryMap = $map;
    }
    $data = $this->find($map);
    if (is_string($data['Ид'] ?? FALSE)) {
      $data = [$data];
    }
    return $data;
  }

  /**
   * Parse xml string to array.
   */
  public function parseXmlString(string $xml_string) : XmlParser {
    $xml = simplexml_load_string($xml_string);
    if (!$xml) {
      \Drupal::messenger()->addError("Data can not be parsed");
      return [];
    }
    $json = json_encode($xml, JSON_FORCE_OBJECT);
    $this->xmlArray = json_decode($json, TRUE);
    $this->xmlString = 'parse DONE && string remove';
    return $this;
  }

  /**
   * HELPER: Array Normalize.
   */
  public function mapMerge($array1, $array2) {
    if (!is_array($array1)) {
      $array1 = [];
    }
    if (!is_array($array2)) {
      $array2 = [];
    }
    $map = array_merge($array1, $array2);
    return $map;
  }

  /**
   * HELPER: Array Normalize.
   */
  public function arrayNormalize($array) : array {
    $norm = FALSE;
    if (!is_array($array)) {
      $norm = FALSE;
    }
    else {
      foreach ($array as $key => $value) {
        if (is_numeric($key)) {
          $norm = TRUE;
        }
      }
    }

    if ($norm) {
      return $array;
    }
    else {
      return [$array];
    }
  }

}
