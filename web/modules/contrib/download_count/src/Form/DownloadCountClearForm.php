<?php

namespace Drupal\download_count\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove form for book module.
 */
class DownloadCountClearForm extends ConfirmFormBase {

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->state = $container->get('state');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'download_count_clear_form';
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
    return $this->t('Clear Cache');
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
    return $this->t('Are you sure you want to clear the download count cache?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('download_count.file_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->state->set('download_count_last_cron', 0);
    $this->database->truncate('download_count_cache')->execute();
    $this->messenger()->addStatus($this->t('The download count cache has been cleared.'));
    $this->getLogger('download_count')->notice($this->t('The download count cache has been cleared.'));
    $form_state->setRedirect('download_count.file_settings');
  }

}
