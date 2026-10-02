<?php

namespace Drupal\download_count\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;

/**
 * Provides a 'Top Downloaded Files' block.
 *
 * @Block(
 *   id = "top_download",
 *   admin_label = @Translation("Top Downloaded Files")
 * )
 */
class TopDownload extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $limit = $config['download_count_top_block_limit'] ?? 10;
    $rows = [];
    $connection = Database::getConnection();
    $sql = $connection->select('download_count_cache', 'dc');
    $sql->join('file_managed', 'f', 'f.fid = dc.fid');
    $sql->fields('dc', ['fid', 'count']);
    $sql->fields('f', ['filename', 'filesize']);
    $sql->orderBy('dc.count', 'DESC');
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
      $row[] = $file->count;
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

    $form['download_count_top_block_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of items to display'),
      '#default_value' => $config['download_count_top_block_limit'] ?? 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['download_count_top_block_limit'] = $form_state->getValue('download_count_top_block_limit');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access top download');
  }

}
