<?php

namespace Drupal\download_count\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the reset form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\FormStateInterface
 */
class DownloadCountResetForm extends ConfirmFormBase {

  /**
   * The dc entry.
   *
   * @var mixed
   */
  public $dcEntry;

  /**
   * Show confirm form.
   *
   * @var bool
   */
  protected $confirm;

  /**
   * The question tag.
   *
   * @var bool
   */
  protected $question;

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'download_count_reset_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $download_count_entry = NULL): array {
    if ($download_count_entry != NULL) {
      $query = $this->database->select('download_count', 'dc');
      $query->join('file_managed', 'f', 'dc.fid = f.fid');
      $query->fields('dc', [
        'dcid',
        'fid',
        'uid',
        'type',
        'id',
        'ip_address',
        'referrer',
        'timestamp',
      ]);
      $query->fields('f', ['filename', 'uri', 'filemime', 'filesize']);
      $query->condition('dc.dcid', $download_count_entry);
      $this->dcEntry = $query->execute()->fetchObject();
    }
    else {
      $this->dcEntry = 'all';
    }
    if ($this->dcEntry && $this->dcEntry != 'all') {
      $form['dcid'] = [
        '#type' => 'value',
        '#value' => $this->dcEntry->dcid,
      ];
      $form['filename'] = [
        '#type' => 'value',
        '#value' => Html::escape($this->dcEntry->filename),
      ];
      $form['fid'] = [
        '#type' => 'value',
        '#value' => $this->dcEntry->fid,
      ];
      $form['type'] = [
        '#type' => 'value',
        '#value' => Html::escape($this->dcEntry->type),
      ];
      $form['id'] = [
        '#type' => 'value',
        '#value' => $this->dcEntry->id,
      ];
    }
    else {
      $form['dcid'] = [
        '#type' => 'value',
        '#value' => 'all',
      ];
    }
    $this->confirm = TRUE;
    $this->question = TRUE;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): MarkupInterface {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): MarkupInterface {
    if ($this->dcEntry != 'all') {
      return $this->t('Reset');
    }
    return $this->t('Reset All');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): MarkupInterface {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): MarkupInterface {
    if ($this->dcEntry && $this->dcEntry != 'all') {
      return $this->t('Are you sure you want to reset the download count for %filename on %entity #%id?', [
        '%filename' => $this->dcEntry->filename,
        '%entity' => $this->dcEntry->type,
        '%id' => $this->dcEntry->id,
      ]);
    }

    return $this->t('Are you sure you want to reset all download counts?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('download_count.reports');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form['dcid']['#value'] == 'all') {
      $result = $this->database->truncate('download_count')->execute();
      if ($result) {
        $this->database->truncate('download_count_cache')->execute();
        $this->messenger()->addStatus($this->t('All download counts have been reset.'));
        \Drupal::logger('download_count')
          ->notice('All download counts have been reset.');
      }
      else {
        $this->messenger()->addError($this->t('Unable to reset all download counts.'));
        \Drupal::logger('download_count')
          ->error('Unable to reset all download counts.');
      }
    }
    else {
      $result = $this->database->delete('download_count')
        ->condition('fid', $form['fid']['#value'])
        ->condition('type', $form['type']['#value'])
        ->condition('id', $form['id']['#value'])
        ->execute();
      if ($result) {
        $this->database->delete('download_count_cache')
          ->condition('fid', $form['fid']['#value'])
          ->condition('type', $form['type']['#value'])
          ->condition('id', $form['id']['#value'])
          ->execute();
        $this->messenger()->addStatus($this->t('Download count for %filename on %type %id was reset.', [
          '%filename' => $form['filename']['#value'],
          '%type' => $form['type']['#value'],
          '%id' => $form['id']['#value'],
        ]));
        \Drupal::logger('download_count')
          ->notice('Download count for %filename on %type %id was reset.', [
            '%filename' => $form['filename']['#value'],
            '%type' => $form['type']['#value'],
            '%id' => $form['id']['#value'],
          ]);
      }
      else {
        $this->messenger()->addError($this->t('Unable to reset download count for %filename on %type %id.', [
          '%filename' => $form['filename']['#value'],
          '%type' => $form['type']['#value'],
          '%id' => $form['id']['#value'],
        ]));
        \Drupal::logger('download_count')
          ->error('Unable to reset download count for %filename on %type %id.', [
            '%filename' => $form['filename']['#value'],
            '%type' => $form['type']['#value'],
            '%id' => $form['id']['#value'],
          ]);
      }
    }
    $form_state->setRedirect('download_count.reports');
  }

}
