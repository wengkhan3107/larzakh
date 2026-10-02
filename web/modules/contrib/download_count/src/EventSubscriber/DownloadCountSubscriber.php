<?php

namespace Drupal\download_count\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\FileRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber for file downloads.
 */
class DownloadCountSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new DownloadCountSubscriber.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\file\FileRepositoryInterface $fileRepository
   *   File repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *  The stream wrapper manager.
   */
  public function __construct(protected AccountProxyInterface $currentUser, protected FileRepositoryInterface $fileRepository, protected RouteMatchInterface $routeMatch, protected StreamWrapperManagerInterface $streamWrapperManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onResponse'],
    ];
  }

  /**
   * Track a file download.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   */
  public function onResponse(ResponseEvent $event) {
    if (!$event->isMainRequest() || $this->routeMatch->getRouteName() !== 'system.files') {
      return;
    }

    $request = $event->getRequest();

    $target = $request->get('file');
    $scheme = $request->get('scheme');

    $uri = $this->streamWrapperManager->normalizeUri($scheme . '://' . $target);
    $file = $this->fileRepository->loadByUri($uri);
    _download_count_track_file_download($file, 'download', $this->currentUser);
  }

}
