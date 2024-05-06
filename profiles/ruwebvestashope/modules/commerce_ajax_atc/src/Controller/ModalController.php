<?php

namespace Drupal\commerce_ajax_atc\Controller;

use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_ajax_atc\Ajax\CloseCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * The modal controller.
 */
class ModalController extends ControllerBase {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function closeModalForm() {
    $command = new CloseModalDialogCommand();
    $response = new AjaxResponse();

    $uses_colorbox_load = $this->configFactory->get('commerce_ajax_atc.settings')->get('pop_up_type') === 'colorbox';
    if ($uses_colorbox_load) {
      $response->addCommand(new CloseCommand());
    }
    else {
      $response->addCommand($command);
    }

    return $response;
  }

}
