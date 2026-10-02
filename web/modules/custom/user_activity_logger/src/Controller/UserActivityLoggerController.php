<?php

namespace Drupal\user_activity_logger\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user_activity_logger\Form\UserActivityLogFilterForm;
use Drupal\user\Entity\User;

/**
 * Controller for user activity logging.
 */
class UserActivityLoggerController extends ControllerBase
{

  protected $database;

  public function __construct(Connection $database)
  {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database')
    );
  }

  public function logs()
  {
    $build = [];

    $build['filter_form'] = \Drupal::formBuilder()->getForm(UserActivityLogFilterForm::class);

    $header = [
      ['data' => $this->t('User')],
      ['data' => $this->t('Activity')],
      ['data' => $this->t('Entity')],
      ['data' => $this->t('Entity ID')],
      ['data' => $this->t('Path')],
      ['data' => $this->t('Timestamp')],
    ];

    $query = \Drupal::database()->select('user_activity_log', 'l')
      ->fields('l', ['uid', 'activity', 'entity_type', 'entity_id', 'path', 'timestamp'])
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(50);


    $username = \Drupal::request()->query->get('username');
    if (!empty($username)) {
      $uids = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->getQuery()
        ->condition('name', $username, 'CONTAINS')
        ->execute();

      if (!empty($uids)) {
        $query->condition('uid', $uids, 'IN');
      } else {
        // No matching user, return empty table
        $build['logs'] = [
          '#markup' => $this->t('No results found for username: @name', ['@name' => $username]),
        ];
        return $build;
      }
    }
    $results = $query->execute()->fetchAll();

    $rows = [];
    foreach ($results as $record) {
      $user = $this->entityTypeManager()->getStorage('user')->load($record->uid);
      $username = $user ? $user->toLink() : $this->t('Anonymous');

      $rows[] = [
        'user' => $username,
        'activity' => $record->activity,
        'entity_type' => $record->entity_type ?: '-',
        'entity_id' => $record->entity_id ?: '-',
        'path' => $record->path ?: '-',
        'timestamp' => \Drupal::service('date.formatter')->format($record->timestamp, 'short'),
      ];
    }

    $build['logs'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No activity logs available.'),
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  public function trackClick()
  {
    $request = \Drupal::request();
    $href = $request->request->get('href');
    $label = $request->request->get('label', '');
    $account = $this->currentUser();

    if ($account->isAuthenticated()) {
      \Drupal::database()->insert('user_activity_log')
        ->fields([
          'uid' => $account->id(),
          'activity' => 'link_click',
          'entity_type' => 'link',
          'entity_id' => 0,
          'timestamp' => \Drupal::time()->getRequestTime(),
          'extra' => $label ?: $href, // optional extra info column
        ])
        ->execute();
    }

    return new JsonResponse(['status' => 'ok']);
  }

}
