<?php

namespace Drupal\ipless\Asset;

/**
 * Interface AssetRendererInterface.
 *
 * @author Damien LAGUERRE
 */
interface AssetRendererInterface {

  /**
   * Render method.
   *
   * @param array $libraries
   *   Array of libraries.
   * @param int $time
   *   Timestamp, is set, only file edited after this date is generated.
   *
   * @return array
   *   The list of libraries compiled.
   */
  public function render(array $libraries, $time = NULL);

  /**
   * Return Less processor.
   *
   * @return \Less_Parser
   *   Return instance of Less_Parser.
   */
  public function getLess();

}
