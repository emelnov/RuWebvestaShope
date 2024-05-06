<?php

namespace Drupal\cmlapi\Service;

use Drupal\cmlapi\Entity\CmlEntity;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;

/**
 * Class Cml Service.
 */
class CmlService {

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
   * Actual.
   */
  public function actual() : ?CmlEntity {
    // Has 'progress' status.
    if ($current = $this->current()) {
      return $current;
    }
    // Has 'new' status, oldest.
    if ($next = $this->next()) {
      return $next;
    }
    // [failure|success] latest.
    if ($last = $this->last()) {
      return $last;
    }
    return NULL;
  }

  /**
   * List.
   */
  public function all() {
    $list = $this->query();
    return $list;
  }

  /**
   * Next.
   */
  public function next() : ?CmlEntity {
    $next = NULL;
    $count = 1;
    $status = ['new'];
    if (!empty($list = $this->query($count, $status))) {
      $next = array_shift($list);
    }
    return $next;
  }

  /**
   * Last.
   */
  public function last() : ?CmlEntity {
    $last = NULL;
    $count = 1;
    $status = ['failure', 'success'];
    if (!empty($list = $this->query($count, $status, 'DESC'))) {
      $last = array_shift($list);
    }
    return $last;
  }

  /**
   * Current.
   */
  public function current() : ?CmlEntity {
    $config = $this->configFactory->get('cmlapi.settings');
    $current = $config->get('runing_cml');
    if (!empty($current)) {
      return $this->cmlStorage->load($current);
    }
    $current = NULL;
    $count = 1;
    $status = ['progress'];
    if (!empty($list = $this->query($count, $status))) {
      $current = array_shift($list);
    }
    return $current;
  }

  /**
   * Query.
   */
  public function query($count = FALSE, $status = ['new', 'progress'], $sort = 'ASC') {
    $entities = [];
    $entity_type = 'cml';
    $query = $this->cmlStorage->getQuery()
      ->condition('status', 1)
      ->sort('created', $sort)
      ->condition('type', 'catalog')
      ->condition('state', $status, 'IN')
      ->accessCheck(TRUE)
      ->condition('field_file', 'NULL', '!=');
    if ($count) {
      $query->range(0, $count);
    }
    $ids = $query->execute();
    if (!empty($ids)) {
      foreach ($this->cmlStorage->loadMultiple($ids) as $id => $entity) {
        $entities[$id] = $entity;
      }
    }
    return $entities;
  }

  /**
   * Load.
   */
  public function load($id): ?CmlEntity {
    return $this->cmlStorage->load($id);
  }

  /**
   * Files Path.
   */
  public function getFilesPath(int | bool $cid, $xmlkey) : array {
    $files = [];
    if (!$cid && $cml = $this->actual()) {
      $cid = $cml->id();
    }
    if (is_numeric($cid)) {
      $files = &drupal_static("CmlService::getFilesPath():$xmlkey:$cid");
      if (!isset($files)) {
        $cache_key = "CmlService-$xmlkey:$cid";
        if ($cache = \Drupal::cache()->get($cache_key)) {
          $files = $cache->data;
        }
        else {
          $cml = $this->load($cid);
          if (is_object($cml)) {
            $cml_xml = $cml->field_file->getValue();
            $files = [];
            $data = FALSE;
            $filekeys[$xmlkey] = TRUE;
            if (!empty($cml_xml)) {
              foreach ($cml_xml as $xml) {
                $file = File::load($xml['target_id']);
                $filename = $file->getFilename();
                if (strpos($filename, 'import') === 0) {
                  $filekey = 'import';
                }
                if (strpos($filename, 'offers') === 0) {
                  $filekey = 'offers';
                }
                if (strpos($filename, 'prices') === 0) {
                  $filekey = 'prices';
                }
                if (strpos($filename, 'rests') === 0) {
                  $filekey = 'rests';
                }
                if (isset($filekeys[$filekey]) && $filekeys[$filekey]) {
                  $files[] = $file->getFileUri();
                }
              }
            }
            \Drupal::cache()->set($cache_key, $files);
          }
        }
      }
    }
    return $files ?? [];
  }

}
