<?php

namespace Drupal\cmlapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Product Variations route.
 */
class ProductVariation extends ControllerBase {

  //phpcs:disable
  private array $svoistva = [];
  private array $price = [];
  private array $stock = [];
  //phpcs:enable

  /**
   * Page import.
   */
  public function page($cml) {
    $i = 0;
    $max = 300;
    $cid = $cml;
    $variation_items = [];
    $svoistvo_items = [];
    $price_items = [];
    $stock_items = [];
    $data = \Drupal::service('cmlapi.parser_offers')->parseArray($cid);
    $this->svoistva = $data['svoistvo'] ?? [];
    $this->price = $data['price'] ?? [];
    $this->stock = $data['stock'] ?? [];
    if ($data) {
      foreach ($data['offer'] as $key => $variation) {
        if ($i < $max || \Drupal::request()->query->get('all') == 'TRUE') {
          $variation_items[] = $this->renderVariationFull($variation);
        }
      }
      foreach ($this->svoistva as $key => $value) {
        $svoistvo_items[] = $this->renderSvoistvaFull($value);
      }
      foreach ($this->price as $key => $value) {
        $price_items[] = $this->renderPriceFull($value);
      }
      foreach ($this->stock as $key => $value) {
        $stock_items[] = $this->renderStockFull($value);
      }
    }
    return [
      'info' => [
        '#markup' => __CLASS__ . "<br>id={$cid}",
      ],
      'svoistvo' => [
        '#theme' => 'item_list',
        '#list_type' => 'ol',
        '#title' => 'Свойства (Атрибуты товара)',
        '#items' => $svoistvo_items,
      ],
      'price' => [
        '#theme' => 'item_list',
        '#list_type' => 'ol',
        '#title' => 'Типы цен',
        '#items' => $price_items,
      ],
      'stock' => [
        '#theme' => 'item_list',
        '#list_type' => 'ol',
        '#title' => 'Склады',
        '#items' => $stock_items,
      ],
      'variations' => [
        '#theme' => 'item_list',
        '#list_type' => 'ol',
        '#title' => 'Список предложений',
        '#items' => $variation_items,
      ],
    ];

  }

  /**
   * Json import.
   */
  public function preparesvoistvo($svoistvos) {
    $result = [];
    if (!empty($svoistvos)) {
      $svoistvos = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($svoistvos);
      foreach ($svoistvos as $svoistvo) {
        $id = $svoistvo['Ид'];
        $name = $svoistvo['Наименование'];
        if ($svoistvo['ТипЗначений'] == 'Справочник') {
          $sprav = [];
          if (isset($svoistvo['ВариантыЗначений']['Справочник'])) {
            $sprav = \Drupal::service('cmlapi.xml_parser')
              ->xml2Val($svoistvo['ВариантыЗначений']['Справочник']);
          }
          $result['taxonomy']["$name"] = $sprav;
        }
        else {
          $result['field']["$name"] = $svoistvo['Значение'];
        }
      }
    }
    return $result;
  }

  /**
   * Json import.
   */
  public function prepareOffer($offers, $svoistvos) {
    $result = [];
    $fields = [];
    $svoistvos = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($svoistvos);
    foreach ($svoistvos as $svoistvo) {
      $id = $svoistvo['Ид'] ?? '';
      if (!empty($svoistvo['ВариантыЗначений']['Справочник'])) {
        $dictionary = \Drupal::service('cmlapi.xml_parser')
          ->arrayNormalize($svoistvo['ВариантыЗначений']['Справочник']);
        foreach ($dictionary as $kay => $value) {
          $fields[$id][$value['ИдЗначения']] = $value['Значение'];
        }
      }
    }
    if (!empty($offers)) {
      if (!empty($offers['Ид'])) {
        $offers = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($offers);
      }
      foreach ($offers as $offer) {
        $id = $offer['Id'];
        $name = $offer['Naimenovanie'];
        $svoistvo = [];
        if (!empty($offer['ZnacheniyaSvoystv'])) {
          foreach ($offer['ZnacheniyaSvoystv'] as $kay => $value) {
            $offer['ZnacheniyaSvoystv'] = $fields[$kay][$value];
          }
        }
        $result[$id] = $offer;
      }
    }
    return $result;
  }

  /**
   * Product full.
   */
  private function renderSvoistvaFull($svoistvo) {
    $result = "<b>{$svoistvo['Наименование']}</b>: {$svoistvo['ТипЗначений']} ({$svoistvo['Ид']})\n";
    if ($svoistvo['ТипЗначений'] == 'Справочник') {
      $dictionary = \Drupal::service('cmlapi.xml_parser')
        ->arrayNormalize($svoistvo['ВариантыЗначений']['Справочник']);
      foreach ($dictionary as $value) {
        $result .= "{$value['Значение']} ({$value['ИдЗначения']})\n";
      }
    }
    return [
      '#prefix' => "<pre>",
      '#markup' => $result,
      '#suffix' => "</pre>",
    ];
  }

  /**
   * Product full.
   */
  private function renderPriceFull($price) {
    $result = "<b>{$price['Наименование']}</b>: ({$price['Ид']})\n";
    unset($price['Ид']);
    unset($price['Наименование']);
    foreach ($price as $key => $value) {
      $result .= "<b>{$key}</b>: ";
      if (!is_array($value)) {
        $result .= "$value\n";
      }
      else {
        $yml = Yaml::dump($value, 2);
        $result .= "<pre>$yml<pre>";
      }
    }
    return [
      '#prefix' => "<pre>",
      '#markup' => $result,
      '#suffix' => "</pre>",
    ];
  }

  /**
   * Product full.
   */
  private function renderStockFull($stock) {
    $result = "<b>{$stock['Наименование']}</b>: ({$stock['Ид']})\n";
    return [
      '#prefix' => "<pre>",
      '#markup' => $result,
      '#suffix' => "</pre>",
    ];
  }

  /**
   * Product full.
   */
  private function renderVariationFull($variation) {
    $znacenia_svoystv = $this->propZnacheniyaSvoystv($variation);
    $result = "";
    foreach ($variation as $key => $value) {
      $result .= "<b>{$key}</b>: ";
      if (!is_array($value)) {
        $result .= "$value\n";
      }
      else {
        switch ($key) {
          case 'Ceny':
            foreach ($value as $k => $v) {
              $value[$k]['ТипЦены'] = $this->price[$v['ИдТипаЦены']]['Наименование'];
            }
            $yml = Yaml::dump($value, 2);
            $result .= "<pre>$yml<pre>";
            break;

          case 'Sklad':
            $result .= "\n";
            foreach ($value as $k => $v) {
              $result .= "&nbsp;&nbsp;{$this->stock[$v['@attributes']['ИдСклада']]['Наименование']}: {$v['@attributes']['КоличествоНаСкладе']}\n";
            }
            break;
        }
      }
    }
    if ($znacenia_svoystv) {
      $result .= "<b>Znacheniya Svoystv:</b><br>";
      $result .= $znacenia_svoystv;
    }
    // $result .= "\n";
    return [
      '#prefix' => "<pre>",
      '#markup' => $result,
      '#suffix' => "</pre>",
    ];
  }

  /**
   * Свойтсва это справочники для типов товаров (категории).
   */
  private function propZnacheniyaSvoystv(array &$variation) : string {
    $znacenia_svoystv = "";
    foreach ($variation['ZnacheniyaSvoystv'] ?? [] as $svoistvoId => $svoistvoValue) {
      if (is_string($svoistvoValue)) {
        $svoistvo = $this->svoistva[$svoistvoId];
        $name = $svoistvo['Наименование'] ?? "";
        $type = $svoistvo['ТипЗначений'];
        if ($type == 'Справочник') {
          $dictionary = \Drupal::service('cmlapi.xml_parser')
            ->arrayNormalize($svoistvo['ВариантыЗначений']['Справочник']);
          foreach ($dictionary as $data) {
            if ($data['ИдЗначения'] == $svoistvoValue) {
              $znacenia_svoystv .= " 🤌 <b>[$type] $name</b>: {$data['Значение']}";
              $znacenia_svoystv .= " / <small>$svoistvoId: $svoistvoValue</small><br>";
            }
          }
        }
        else {
          $znacenia_svoystv .= " 🤌 <b>[$type] $name</b>: $svoistvoValue / <small>$svoistvoId</small><br>";
        }

      }
      else {
        $v = Yaml::dump($svoistvoValue);
        $znacenia_svoystv .= " 🤌🤌 <b>$svoistvoId</b>: $v<br>";
      }
    }
    unset($variation['ZnacheniyaSvoystv']);
    return $znacenia_svoystv;
  }

}
