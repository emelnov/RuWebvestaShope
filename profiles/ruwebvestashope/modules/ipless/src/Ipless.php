<?php

namespace Drupal\ipless;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\State\StateInterface;
use Drupal\ipless\Asset\AssetRendererInterface;


/**
 * Description of Ipless.
 */
class Ipless implements IplessInterface {

  use MessengerTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\ipless\Asset\AssetRendererInterface
   */
  protected $assetRenderer;

  /**
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Ipless constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\ipless\Asset\AssetRendererInterface $assetRenderer
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(ConfigFactoryInterface $configFactory, AssetRendererInterface $assetRenderer, LibraryDiscoveryInterface $libraryDiscovery, ModuleHandlerInterface $moduleHandler, ThemeHandlerInterface $themeHandler, FileSystemInterface $fileSystem, StateInterface $state) {
    $this->configFactory = $configFactory;
    $this->assetRenderer = $assetRenderer;
    $this->libraryDiscovery = $libraryDiscovery;
    $this->moduleHandler = $moduleHandler;
    $this->themeHandler = $themeHandler;
    $this->fileSystem = $fileSystem;
    $this->state = $state;

    $this->config = $this->configFactory->get('system.performance');
  }

  /**
   * {@inheritdoc}
   */
  public function processOnResponse(HtmlResponse $response) {
    $assets = $this->getResponseAssets($response);
    $this->generate($assets->getLibraries());
  }

  public function getResponseAssets(HtmlResponse $response) {
    $attached = $response->getAttachments();

    unset($attached['html_response_attachment_placeholders']);

    return AttachedAssets::createFromRenderArray(['#attached' => $attached]);
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $libraries, $time = NULL): array {
    if (!$this->checkLib()) {
      return [];
    }
    return $this->generateCss($libraries, $time);
  }

  /**
   * Check that the library Less php is installed.
   *
   * @return bool
   */
  protected function checkLib() {
    if (!class_exists('Less_Parser')) {
      $this->messenger()->addWarning('The class lessc is not installed.');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function mustRebuildAll(): bool {
    return (bool) $this->state->get('ipless.force_rebuild');
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return (bool) $this->config->get('ipless.enabled');
  }

  /**
   * {@inheritdoc}
   */
  public function isWatchModeEnable(): bool {
    return $this->isModeDevEnabled() && $this->config->get('ipless.watch_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function isModeDevEnabled(): bool {
    return (bool) $this->config->get('ipless.modedev');
  }

  /**
   * Generate Less files.
   *
   * @param array $libraries
   *   Array of libraries to compile.
   * @param int|null $time
   *   Timestamp, is set, only file edited after this date is generated.
   *
   * @return array
   *   The list of libraries generated.
   */
  protected function generateCss(array $libraries, int $time = NULL): array {
    return $this->assetRenderer->render($libraries, $time);
  }

  /**
   * {@inheritdoc}
   */
  public function generateAllLibraries() {

    $modules = $this->moduleHandler->getModuleList();
    $themes = $this->themeHandler->rebuildThemeData();

    $extensions = array_merge($modules, $themes);

    $libraries = [];
    foreach (array_keys($extensions) as $extension_name) {
      $ext_libs = $this->libraryDiscovery->getLibrariesByExtension($extension_name);
      foreach ($ext_libs as $library_name => $lib_info) {
        if ($library_name !== 'drupalSettings') {
          $libraries[] = "$extension_name/$library_name";
        }
      }
    }

    $this->generate($libraries);
    // Disable the rebuild.
    $this->askForRebuild(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function askForRebuild(bool $rebuild_need = TRUE): void {
    $this->state->set('ipless.force_rebuild', $rebuild_need);
  }

  /**
   * {@inheritdoc}
   */
  public function flushFiles(): void {
    $this->fileSystem->deleteRecursive('public://ipless/');
  }

}
