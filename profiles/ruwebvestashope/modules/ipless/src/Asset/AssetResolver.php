<?php

namespace Drupal\ipless\Asset;

use Drupal\Core\Asset\AssetResolver as AssetResolverDefault;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Description of AssetResolver.
 */
class AssetResolver extends AssetResolverDefault implements AssetResolverInterface {

  /**
   * @var \Drupal\ipless\Asset\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * Constructs a new AssetResolver instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Asset\LibraryDependencyResolverInterface $library_dependency_resolver
   *   The library dependency resolver.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list service.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, LibraryDependencyResolverInterface $library_dependency_resolver, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager, LanguageManagerInterface $language_manager, CacheBackendInterface $cache, ModuleExtensionList $moduleExtensionList, ThemeExtensionList $themeExtensionList) {
    parent::__construct($library_discovery, $library_dependency_resolver, $module_handler, $theme_manager, $language_manager, $cache);
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public function getLessAssets(AttachedAssetsInterface $assets) {

    $theme_info = $this->themeManager->getActiveTheme();

    // Add the theme name to the cache key since themes may implement
    $cid = 'less:' . $theme_info->getName() . ':' . Crypt::hashBase64(serialize($assets->getLibraries()));

    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }

    $less = [];
    $default_options = [
      'type' => 'file',
      //      'group'      => LESS_AGGREGATE_DEFAULT,
      'weight' => 0,
      'media' => 'all',
      'preprocess' => TRUE,
      'browsers' => [],
    ];

    foreach ($assets->getLibraries() as $library) {
      list($extension, $name) = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (!isset($definition['less'])) {
        continue;
      }

      // @todo: add group sort (as $group => $data)
      foreach ($definition['less'] as $data) {
        foreach ($data as $file => $options) {
          $options['library'] = $library;

          $path = NULL;
          if ($this->moduleHandler->moduleExists($extension)) {
            $path = $this->moduleExtensionList->getPath($extension);
          }
          else {
            $path = $this->themeExtensionList->getPath($extension);
          }

          if (!$path) {
            continue;
          }

          $pathinfo = pathinfo($file);

          $options['data'] = $path . '/' . $file;

          if (empty($options['less_path'])) {
            if ($pathinfo['dirname'] !== '.') {
              $options['less_path'] = '/' . $path . '/' . $pathinfo['dirname'];
            }
            else {
              $options['less_path'] = '/' . $path;
            }
          }

          if (!$this->isValidUri($options['output'])) {
            $options['output'] = $path . '/' . $options['output'];
          }

          $options += $default_options;
          $options['browsers'] += [
            'IE' => TRUE,
            '!IE' => TRUE,
          ];

          // Files with a query string cannot be preprocessed.
          if ($options['type'] === 'file' && $options['preprocess']) {
            $options['preprocess'] = FALSE;
          }

          // Always add a tiny value to the weight, to conserve the insertion
          // order.
          $options['weight'] += count($less) / 1000;

          // LESS files are being keyed by the libraries and the full path.
          $less[$library . '|' . $options['data']] = $options;
        }
      }
    }

    // Allow modules and themes to alter the LESS assets.
    $this->moduleHandler->alter('less', $less, $assets);
    $this->themeManager->alter('less', $less, $assets);

    $this->cache->set($cid, $less, CacheBackendInterface::CACHE_PERMANENT, ['library_info']);

    return $less;
  }

  /**
   * Check if the given URL is valid.
   *
   * @param string $uri
   *   The URI to test.
   *
   * @return false|int
   *   Return true if it's an URI, false otherwise.
   */
  protected function isValidUri($uri) {
    return preg_match('/^(public|private|base):\/\//', $uri);
  }

}
