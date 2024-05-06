<?php

namespace Drupal\cmlapi\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class Cml Cleaner.
 */
class CmlCleaner {

  //phpcs:disable
  protected ConfigFactoryInterface $configFactory;
  protected EntityStorageInterface $cmlStorage;
  //phpcs:enable

  /**
   * Creates a new CmlService manager.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->cmlStorage = $entity_type_manager->getStorage('cml');
  }

  /**
   * Get ids.
   */
  public function view() {
    $empty = $this->queryEmpty();
    $expired = $this->queryExpired();
    return array_merge($empty, $expired);
  }

  /**
   * Clean.
   */
  public function clean() {
    $empty = $this->deleteEmpty();
    $expired = $this->deleteExpired();
    return array_merge($empty, $expired);
  }

  /**
   * Empty delete.
   */
  public function deleteEmpty() {
    $ids = $this->queryEmpty();
    if (!empty($ids)) {
      foreach ($this->cmlStorage->loadMultiple($ids) as $id => $cml) {
        $cml->delete(TRUE);
      }
    }
    return $ids;
  }

  /**
   * Expired delete.
   */
  public function deleteExpired() {
    $ids = $this->queryExpired();
    if (!empty($ids)) {
      $config = $this->configFactory->get('cmlapi.mapsettings');
      $force = $config->get('cleaner-force');
      foreach ($this->cmlStorage->loadMultiple($ids) as $id => $cml) {
        foreach ($cml->field_file as $key => $value) {
          $file = $value->entity;
          if (is_object($file)) {
            $file->delete(TRUE);
          }
        }
        if ($force) {
          $dir = $this->cmlDir($cml);
          \Drupal::service('file_system')->deleteRecursive($dir);
        }
        $cml->delete(TRUE);
      }
    }
    return $ids;
  }

  /**
   * Empty cml.
   */
  public function queryEmpty() {
    $config = $this->configFactory->get('cmlapi.mapsettings');
    $expired = $config->get('cleaner-expired');
    $query = $this->cmlStorage->getQuery();
    $query->notExists('field_file')
      ->condition('created', strtotime($expired), '<')
      ->sort('created', 'ASC')
      ->accessCheck(FALSE)
      ->range(0, 25);
    $ids = $query->execute();
    $result = [];
    if (!empty($ids)) {
      foreach ($ids as $id) {
        $result[$id] = $id;
      }
    }
    return $result;
  }

  /**
   * Expired cml.
   */
  public function queryExpired() {
    $config = $this->configFactory->get('cmlapi.mapsettings');
    $skip = $config->get('cleaner-keep');
    $expired = $config->get('cleaner-expired');
    $query = $this->cmlStorage->getQuery();
    $query->condition('field_file', 'NULL', '!=')
      ->condition('state', 'success', '=')
      ->condition('created', strtotime($expired), '<')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE)
      ->range($skip, 25);
    $ids = $query->execute();
    $result = [];
    if (!empty($ids)) {
      foreach ($ids as $id) {
        $result[$id] = $id;
      }
    }
    return $result;
  }

  /**
   * Get cml_id dir.
   */
  public function cmlDir($cml) {
    $config = $this->configFactory->get('cmlexchange.settings');
    $dir = 'cml-files';
    if ($config->get('file-path')) {
      $dir = $config->get('file-path');
    }
    $type = $cml->type->value;
    $time = \Drupal::service('date.formatter')->format($cml->created->value, 'custom', 'Y-m-d--H-i-s');
    $key = substr($cml->uuid->value, 0, 8);
    $cid = $cml->id();
    $dir = "public://{$dir}/{$type}/{$time}-$key-{$cid}";
    return $dir;
  }

}
