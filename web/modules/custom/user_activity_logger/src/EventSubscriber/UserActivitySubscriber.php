<?php

namespace Drupal\user_activity_logger\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Path\CurrentPathStack;

class UserActivitySubscriber implements EventSubscriberInterface {

  protected $currentUser;
  protected $database;
  protected $currentPath;

  public function __construct(AccountProxyInterface $current_user, Connection $database, CurrentPathStack $current_path) {
    $this->currentUser = $current_user;
    $this->database = $database;
    $this->currentPath = $current_path;
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

  public function onRequest(RequestEvent $event) {
    // Skip anonymous or non-master requests.
    if ($this->currentUser->isAnonymous() || !$event->isMainRequest()) {
      return;
    }

    $path = $this->currentPath->getPath();
    $alias = \Drupal::service('path_alias.manager')->getAliasByPath($path);

    $this->database->insert('user_activity_log')
      ->fields([
        'uid' => $this->currentUser->id(),
        'activity' => 'Visited page',
        'entity_type' => 'route',
        'entity_id' => 0,
        'path' => $alias ?: $path,
        'timestamp' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();
  }
}
