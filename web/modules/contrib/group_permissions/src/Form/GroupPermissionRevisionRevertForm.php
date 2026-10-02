<?php

namespace Drupal\group_permissions\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\group_permissions\GroupPermissionsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a group permission revision.
 *
 * @internal
 */
class GroupPermissionRevisionRevertForm extends ConfirmFormBase {

  /**
   * The group permission revision.
   *
   * @var \Drupal\group_permissions\Entity\GroupPermissionInterface
   */
  protected $revision;

  /**
   * The group permission storage.
   *
   * @var \Drupal\group_permissions\Entity\Storage\GroupPermissionStorageInterface
   */
  protected $groupPermissionStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManager
   */
  protected $groupPermissionsManager;

  /**
   * Constructs a new GroupPermissionRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $group_permission_storage
   *   The group permission storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\group_permissions\GroupPermissionsManager $group_permissions_manager
   *   The group permissions manager.
   */
  public function __construct(
    EntityStorageInterface $group_permission_storage,
    DateFormatterInterface $date_formatter,
    TimeInterface $time,
    GroupPermissionsManager $group_permissions_manager,
  ) {
    $this->groupPermissionStorage = $group_permission_storage;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
    $this->groupPermissionsManager = $group_permissions_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('group_permission'),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
      $container->get('group_permission.group_permissions_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_permission_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert to the revision from @log (@revision-date)?', [
      '@log' => $this->revision->revision_log_message->value ?? '',
      '@revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime() ?? time()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.group_permission.version-history', [
      'group' => $this->revision->getGroup()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $group_permission_revision = NULL) {
    $this->revision = $group_permission_revision;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    /** @var \Drupal\group_permissions\Entity\GroupPermission $group_permission */
    $current_revision = $this->groupPermissionsManager->getGroupPermission($this->revision->getGroup());
    $original_date = $this->dateFormatter->format($this->revision->getRevisionCreationTime() ?? time());
    $original_revision_log_message = $this->revision->revision_log_message->value;

    $this->revision = $this->prepareRevertedRevision($this->revision);

    $this->revision->revision_log_message = $this->t('Copy of the revision :log from :date.', [
      ':date' => $original_date,
      ':log' => $original_revision_log_message,
    ]);
    $this->revision->validate();
    $this->revision->save();

    $this->logger('group_permissions')->notice('Group permission (@revision) @log @date has been reverted.', [
      '@log' => $original_revision_log_message,
      '@date' => $original_date,
      '@revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()
      ->addStatus($this->t('@current-log (@current-revision-date) has been reverted to the revision from @log (@revision-date).', [
        '@current-log' => $current_revision->revision_log_message->value,
        '@log' => $original_revision_log_message,
        '@revision-date' => $original_date,
        '@current-revision-date' => $this->dateFormatter->format($current_revision->getRevisionCreationTime() ?? time()),
      ]));

    $form_state->setRedirect(
      'entity.group_permission.version-history',
      [
        'group' => $this->revision->getGroup()->id(),
      ]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $revision
   *   The revision to be reverted.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(GroupPermissionInterface $revision) {
    $revision->setRevisionUserId($this->currentUser()->id());
    $revision->setRevisionCreationTime($this->time->getRequestTime());
    $revision->setChangedTime($this->time->getRequestTime());

    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
