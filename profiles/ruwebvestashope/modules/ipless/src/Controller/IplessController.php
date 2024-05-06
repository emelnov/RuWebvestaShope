<?php

namespace Drupal\ipless\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ipless\IplessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class IplessController
 *
 * @package Drupal\ipless\Controller
 */
class IplessController extends ControllerBase {

  /**
   * @var \Drupal\ipless\Ipless
   */
  protected $ipless;

  /**
   * IplessController constructor.
   *
   * @param \Drupal\ipless\IplessInterface $ipless
   */
  public function __construct(IplessInterface $ipless) {
    $this->ipless = $ipless;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ipless.base')
    );
  }

  /**
   * Access callback.
   */
  public function access() {
    // The route is allowed if the watch mode is enabled.
    return ($this->ipless->isWatchModeEnable()) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Route callback. This route is used by the watch mode script.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function watching(Request $request) {

    try {
      // The libraries posted by the javascript.
      $libraries = $request->get('libraries');

      // The time of the last edited file.
      $time = $request->get('time');

      // The output contain all updated libraries.
      $output = $this->ipless->generate($libraries, $time);

    } catch (\Exception $exception) {
      return new AjaxResponse(['error' => $exception->getMessage()], 500);
    }

    return new AjaxResponse($output);
  }

}