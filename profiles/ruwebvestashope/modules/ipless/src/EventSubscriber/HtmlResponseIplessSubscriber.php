<?php


namespace Drupal\ipless\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Drupal\ipless\Asset\AssetResolverInterface;
use Drupal\ipless\IplessInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class HtmlResponseIplessSubscriber
 *
 * @package Drupal\big_pipe\Render
 */
class HtmlResponseIplessSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\ipless\IplessInterface
   */
  protected $ipless;

  /**
   * @var \Drupal\ipless\Asset\AssetResolverInterface
   */
  protected $assetResolver;

  /**
   * HtmlResponseIplessSubscriber constructor.
   *
   * @param \Drupal\ipless\IplessInterface $ipless
   * @param \Drupal\ipless\Asset\AssetResolverInterface $assetResolver
   */
  public function __construct(IplessInterface $ipless, AssetResolverInterface $assetResolver) {
    $this->ipless = $ipless;
    $this->assetResolver = $assetResolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after HtmlResponsePlaceholderStrategySubscriber (priority 5).
    $events[KernelEvents::RESPONSE][] = ['onRespond', 4];

    return $events;
  }

  /**
   * Generate Less files.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespond(ResponseEvent $event) {
    $response = $event->getResponse();

    if (!$response instanceof HtmlResponse || !$this->ipless->isEnabled()) {
      return;
    }

    // Generate all libraries.
    if ($this->ipless->mustRebuildAll()) {
      $this->ipless->generateAllLibraries();
    }
    // Generate current loaded libraries on dev mode.
    elseif ($this->ipless->isModeDevEnabled()) {
      $this->ipless->processOnResponse($response);
    }

    // Watching refresh.
    if ($this->ipless->isWatchModeEnable()) {

      $assets = $this->ipless->getResponseAssets($response);

      $attachments['drupalSettings']['ipless'] = [
        'libraries' => $this->assetResolver->getLessAssets($assets),
      ];

      foreach ($attachments['drupalSettings']['ipless']['libraries'] as &$library) {
        // Add last modified time to the library information.
        $library['last_m_time'] = filemtime($library['data']);
      }

      if ($attachments) {
        $response->addAttachments($attachments);
      }
    }
  }

}
