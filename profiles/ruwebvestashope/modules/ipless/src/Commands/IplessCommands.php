<?php

namespace Drupal\ipless\Commands;

use Drush\Commands\DrushCommands;
use Drupal\ipless\Ipless;

/**
 * Ipless drush commands.
 */
class IplessCommands extends DrushCommands {

  /**
   * Ipless service.
   *
   * @var \Drupal\ipless\Ipless
   */
  protected $ipless;

  /**
   * IplessCommands constructor.
   *
   * @param \Drupal\ipless\Ipless $ipless
   *   Ipless service.
   */
  public function __construct(Ipless $ipless) {
    parent::__construct();
    $this->ipless = $ipless;
  }

  /**
   * Generate Simple Less CSS files.
   *
   * @command ipless:generate
   * @usage drush ipless:generate.
   * @aliases ipless
   */
  public function generate() {
    $this->ipless->generateAllLibraries();
  }

}
