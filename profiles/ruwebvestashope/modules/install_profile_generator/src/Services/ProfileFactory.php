<?php

namespace Drupal\install_profile_generator\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\install_profile_generator\Profile;

/**
 * Creates Profile objects.
 *
 * @internal
 *   Install profile generator's API are the Drush commands.
 */
class ProfileFactory {

  /**
   * ProfileCreator constructor.
   *
   * @param string $appRoot
   *   App root container parameter.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    protected readonly string $appRoot,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Creates a new profile object.
   *
   * @param string $machine_name
   *   The machine name for the profile.
   * @param string $name
   *   The human readable name for the profile.
   * @param string $description
   *   The profile's description.
   *
   * @return \Drupal\install_profile_generator\Profile
   *   Constructed Profile object.
   */
  public function create(string $machine_name, string $name, string $description) : Profile {
    return new Profile($machine_name, $name, $description, $this->appRoot, $this->fileSystem, $this->configFactory);
  }

}
