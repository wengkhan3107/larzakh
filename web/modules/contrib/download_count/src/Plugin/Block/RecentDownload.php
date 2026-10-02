<?php

namespace Drupal\download_count\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Recent Download Count' block.
 *
 * @Block(
 *   id = "recent_download",
 *   admin_label = @Translation("Recently Downloaded Files")
 * )
 */
class RecentDownload extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $limit = $config['download_count_recent_block_limit'] ?? 10;
    $rows = [];
    $connection = Database::getConnection();

    $sql = $connection->select('download_count', 'dc');
    $sql->join('file_managed', 'f', 'f.fid = dc.fid');
    $sql->addExpression('MAX(dc.timestamp)', 'date');
    $sql->fields('dc', ['fid']);
    $sql->fields('f', ['filename', 'filesize']);
    $sql->groupBy('dc.fid');
    $sql->groupBy('f.filename');
    $sql->groupBy('f.filesize');
    $sql->orderBy('date', 'DESC');
    $header = [
      [
        'data' => $this->t('Name'),
        'class' => 'filename',
      ],
      [
        'data' => $this->t('Size'),
        'class' => 'size',
      ],
      [
        'data' => $this->t('Last Downloaded'),
        'class' => 'last',
      ],
    ];

    $result = $connection->queryRange($sql, 0, $limit);
    foreach ($result as $file) {
      $row = [];
      $row[] = Html::escape($file->filename);
      $row[] = ByteSizeMarkup::create($file->filesize);
      $row[] = $this->t('%time ago', [
        '%time' => $this->dateFormatter
          ->formatInterval(\Drupal::time()->getRequestTime() - $file->date),
      ]);
      $rows[] = $row;
    }

    if (count($rows)) {
      return [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['download_count_recent_block_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of items to display'),
      '#default_value' => $config['download_count_recent_block_limit'] ?? 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['download_count_recent_block_limit'] = $form_state->getValue('download_count_recent_block_limit');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access recent download');
  }

}
