<?php

namespace Drupal\ipless;

use Drupal\Core\Render\HtmlResponse;

/**
 * IplessInterface.
 */
interface IplessInterface {

  /**
   * Check configuration and generate {Less} files.
   *
   * @param array $libraries
   *   Array of libraries to generate. [0 => foo/bar, 1 => example/example]
   * @param int|null $time
   *   If set only library edited after this time was generated.
   *
   * @return array
   *   The list of libraries compiled.
   */
  public function generate(array $libraries, $time = NULL): array;

  /**
   * Flush all compiled files.
   *
   * @return void
   */
  public function flushFiles(): void;

  /**
   * Compile all Less files on HTML response.
   *
   * @param HtmlResponse $response
   */
  public function processOnResponse(HtmlResponse $response);

  /**
   * Ask for rebuild all libraries.
   *
   * @param bool $rebuild_need
   *   Indicate if the CSS needs to be rebuilt.
   *
   * @return void
   */
  public function askForRebuild(bool $rebuild_need = TRUE): void;

  /**
   * Compile Less file present on all libraries.
   */
  public function generateAllLibraries();

  /**
   * Return true if the libraries must be rebuilt.
   *
   * @return bool
   */
  public function mustRebuildAll(): bool;

  /**
   * Indicate if the LESS compilation is enabled.
   *
   * @return bool
   */
  public function isEnabled(): bool;

  /**
   * Indicate if the watch mode is enabled.
   *
   * @return bool
   */
  public function isWatchModeEnable(): bool;

  /**
   * Indicate if the LESS dev mode is enabled.
   *
   * @return bool
   */
  public function isModeDevEnabled(): bool;

}
