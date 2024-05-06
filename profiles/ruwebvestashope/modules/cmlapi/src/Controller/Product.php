<?php

namespace Drupal\cmlapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Producs route.
 */
class Product extends ControllerBase {

  //phpcs:disable
  private array $category = [];
  private array $catalog = [];
  private array $svoistva = [];
  //phpcs:enable

  /**
   * Page.
   */
  public function page($cml) {
    $i = 0;
    $max = 3;
    $cid = $cml;
    $data = \Drupal::service('cmlapi.parser_product')->parse($cid);
    $struct = \Drupal::service('cmlapi.parser_catalog')->parse($cid);
    $this->category = $struct['category'] ?? [];
    $this->catalog = $struct['catalog'] ?? [];
    $this->svoistva = $struct['svoistvo'] ?? [];
    if (!empty($data)) {
      $vids = [];
      $tips = [];
      $izgotovitels = [];
      $product_items = [];
      foreach ($data['data'] as $key => $value) {
        $product = $value['product'];
        if ($i < $max || \Drupal::request()->query->get('all') == 'TRUE') {
          $product_items[] = $this->renderProductFull($product);
        }
        else {
          $item = $this->renderProductSmall($product);
          if ($i == $max) {
            $item['#prefix'] = $this->countSeparator($max);
          }
          $product_items[] = $item;
        }
      }
    }
    return [
      'info' => [
        '#markup' => __CLASS__ . "<br>id={$cid}",
      ],
      'tip' => [
        '#theme' => 'item_list',
        '#title' => 'ТипНоменклатуры',
        '#items' => $tips ?? [],
      ],
      'vid' => [
        '#theme' => 'item_list',
        '#title' => 'ВидНоменклатуры',
        '#items' => $vids ?? [],
        '#list_type' => 'ol',
      ],
      'izgotovitel' => [
        '#theme' => 'item_list',
        '#title' => 'Изготовитель',
        '#items' => $izgotovitels ?? [],
      ],
      'products' => [
        '#theme' => 'item_list',
        '#list_type' => 'ol',
        '#title' => 'Список товаров',
        '#items' => $product_items ?? [],
      ],
    ];
  }

  /**
   * Product full.
   */
  private function countSeparator($max) {
    $uri = \Drupal::request()->getRequestUri();
    $all_link = "<a href='$uri?all=TRUE'>Посмотреть всё</a>";
    return "<h3>{$max}+ | Дальше сокращённый вывод данных [$all_link]</h3>";
  }

  /**
   * Product full.
   */
  private function renderProductFull($product) {
    $znacenia_rekvizitov = $this->propZnacheniyaRekvizitov($product);
    $znacenia_svoystv = $this->propZnacheniyaSvoystv($product);
    $product['Kategoriya'] = $this->propCategory($product);
    $product['Gruppy'] = $this->propGruppy($product);
    $result = "";
    foreach ($product as $k => $v) {
      $result .= "<b>{$k}</b>: ";
      if (!is_array($v)) {
        $result .= "$v\n";
      }
      else {
        $yml = Yaml::dump($v, 2);
        $result .= "<pre>$yml<pre>";
      }
    }
    if ($znacenia_rekvizitov) {
      $result .= "<b>Znacheniya Rekvizitov:</b><br>";
      $result .= $znacenia_rekvizitov;
    }
    if ($znacenia_svoystv) {
      $result .= "<b>Znacheniya Svoystv:</b><br>";
      $result .= $znacenia_svoystv;
    }
    return [
      '#prefix' => "<pre>",
      '#markup' => $result,
      '#suffix' => "</pre>",
    ];
  }

  /**
   * Реквизиты - эток key->value которое настраивается при выгрузке.
   */
  private function propZnacheniyaRekvizitov(array &$product) : string {
    $znacenia_rekvizitov = "";
    foreach ($product['ZnacheniyaRekvizitov'] ?? [] as $key => $value) {
      $znacenia_rekvizitov .= " 🦋 <b>$key</b>: $value<br>";
    }
    unset($product['ZnacheniyaRekvizitov']);
    return $znacenia_rekvizitov;
  }

  /**
   * Свойтсва это справочники для типов товаров (категории).
   */
  private function propZnacheniyaSvoystv(array &$product) : string {
    $znacenia_svoystv = "";
    foreach ($product['ZnacheniyaSvoystv'] ?? [] as $key => $value) {
      if (is_string($value)) {
        $svoistvo = $this->svoistva[$key];
        $name = $svoistvo['Наименование'] ?? "";
        $type = $svoistvo['ТипЗначений'];
        if ($svoistvo['ТипЗначений'] == 'Справочник') {
          $val = $svoistvo['values'][$value] ?? " -- 🍭 не указано -- ";
          $znacenia_svoystv .= " 🤌 <b>[$type] $name</b>: $val";
          $znacenia_svoystv .= " / <small>$key: $value</small><br>";
        }
        else {
          $znacenia_svoystv .= " 🤌 <b>[$type] $name</b>: $value / <small>$key</small><br>";
        }

      }
      else {
        $v = Yaml::dump($value);
        $znacenia_svoystv .= " 🤌🤌 <b>$key</b>: $v<br>";
      }
    }
    unset($product['ZnacheniyaSvoystv']);
    return $znacenia_svoystv;
  }

  /**
   * Группа - Место товара в каталоге.
   */
  private function propGruppy(array &$product) : string | array | NULL {
    if (isset($product['Gruppy'][0]) && is_string($product['Gruppy'][0])) {
      $key = $product['Gruppy'][0];
      $name = $this->catalog[$key]['name'] ?? "";
      if ($name) {
        return "$name / <small>$key таксономия каталог</small>";
      }
    }
    return " ⚡️ товар без группы! не привяжется к таксонммии каталог!";
  }

  /**
   * Категория аналог типа товара.
   */
  private function propCategory(array &$product) : string {
    $result = "";
    if ($id = $product['Kategoriya'] ?? FALSE) {
      if ($category = $this->category[$id] ?? FALSE) {
        $result = "{$category['Наименование']} <small> / $id</small>";
        if (!empty($category['Свойства'])) {
          $count = count($category['Свойства']);
          $result .= " 🍕 Тип товара в котором: $count свойств(а)";
        }
      }
    }
    return $result;
  }

  /**
   * Product small.
   */
  private function renderProductSmall($product) {
    $result = "<b>Наименование:</b> {$product['Naimenovanie']}\n";
    if (isset($product['ZnaceniaRekvizitov']['ВидНоменклатуры'])) {
      $result .= "<b>Вид:</b> {$product['ZnaceniaRekvizitov']['ВидНоменклатуры']}\n";
    }
    if (isset($product['Gruppy'][0])) {
      $result .= "<br>&nbsp; &nbsp; <b>Gruppy:</b> {$product['Gruppy'][0]}\n";
    }
    return [
      '#markup' => $result,
    ];
  }

}
