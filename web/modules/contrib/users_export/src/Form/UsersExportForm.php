<?php

namespace Drupal\users_export\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides UsersExport Form.
 */
class UsersExportForm extends FormBase {

  /**
   * Returns the database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * Returns the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Returns the module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a UsersExportForm form.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Base Database API class.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Stores runtime messages sent out to individual users on the page.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Interface for classes that manage a set of enabled modules.
   */
  public function __construct(Connection $database, MessengerInterface $messenger, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'users_export_form';
  }

  /**
   * Form constructor for the users export form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['users_export_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Export file format'),
      '#empty_option' => $this->t(' - Select - '),
      '#required' => TRUE,
      '#options' => [
        'csv' => $this->t('CSV'),
        'json' => $this->t('JSON'),
        'xml' => $this->t('XML'),
      ],
    ];

    $form['users_export_filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filename to save as'),
      //'#required' => TRUE,
      '#access' => FALSE,
    ];

    $form['users_export_test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preview mode (Enable to limit the export to only the first 10 users to check formatting.)'),
    ];

    $form['basic_filters'] = [
      '#type'  => 'details',
      '#title' => $this->t('Basic Filters'),
      '#open'  => FALSE,
    ];

    $form['basic_filters']['users_export_with_access'] = [
      '#type' => 'select',
      '#title' => $this->t('Login Frequency'),
      '#empty_option' => $this->t(' - Select - '),
      '#default_value' => 2,
      '#required' => TRUE,
      '#options' => [
        2 => $this->t('All Users'),
        1 => $this->t('Get users who have logged in at least one time'),
        0 => $this->t('Get users who have never logged in'),
      ],
    ];

    // Add field to add 'blocked' users to the export as well.
    $form['basic_filters']['users_export_user_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Export users with status'),
      '#empty_option' => $this->t(' - Select - '),
      '#default_value' => 2,
      '#required' => TRUE,
      '#options' => [
        1 => $this->t('Active'),
        0 => $this->t('Blocked'),
        2 => $this->t('Both active and blocked'),
      ],
    ];

    // Option to Filter By Role. (Optional).
    $form['basic_filters']['users_export_filter_by_role'] = [
      '#type' => 'radios',
      '#title' => $this->t('Filter by role?'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
    ];

    $roles_options = [];
    foreach (user_roles(TRUE) as $role) {
      $roles_options[$role->id()] = $role->label();
    }

    $form['basic_filters']['users_export_user_role'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Export users with role'),
      '#options' => $roles_options,
      '#states' => [
        'invisible' => [
          ':input[name="users_export_filter_by_role"]' => ['value' => 0],
        ],
      ],
    ];

    $form['basic_filters']['users_export_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of results'),
      '#empty_option' => $this->t(' - Select - '),
      '#default_value' => 'uid',
      '#required' => TRUE,
      '#options' => [
        'uid' => $this->t('User ID'),
        'name' => $this->t('Username A-Z'),
        'mail' => $this->t('E-mail A-Z'),
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values['users_export_filter_by_role'] == 1 && !empty($values['users_export_user_role'])) {
      $values['users_export_user_role'] = array_filter($values['users_export_user_role']);
    }
    else {
      $values['users_export_user_role'] = FALSE;
    }

    $query = $this->database->select('users', 'u');
    $query->addField('u', 'uid');
    $query->join('users_field_data', 'd', 'u.uid = d.uid');

    $limit = $values['users_export_test_mode'] ? 10 : NULL;
    if ($limit) {
      $query->range(0, $limit);
    }

    $access = $values['users_export_with_access'];
    if (isset($access) && $access != 2) {
      switch ($access) {
        case 0:
          $operator = '=';
          break;

        case 1:
          $operator = '<>';
          break;

        default:
          $operator = '<>';
      }

      $query->condition('d.access', 0, $operator);
    }

    $roles = $values['users_export_user_role'];
    if (!empty($roles)) {
      $roles = array_filter($roles);

      // If selected "authenticated user" option return all users.
      if (!array_search(AccountInterface::AUTHENTICATED_ROLE, $roles)) {
        $query->leftJoin('user__roles', 'ur', 'ur.entity_id = u.uid');
        $query->condition('ur.roles_target_id', $roles, 'in');
      }
    }

    // Add status condition based on setting.
    $status = ($s = $values['users_export_user_status']) === 2 ? NULL : $s * 1;
    if (is_numeric($status) && ($status != 2)) {
      $query->condition('status', $status);
    }

    $result = $query->execute()->fetchAllAssoc('uid');
    $uids = array_keys($result);

    $context = [
      'format' => $values['users_export_type'],
      'sort' => $values['users_export_order'],
    ];

    $this->moduleHandler->alter('users_export_user_list', $uids, $context);

    $url = Url::fromRoute('view.user_export.' . $context['format'], [], [
      'query' => [
        'uids' => implode('+', $uids),
        'sort_by' => $context['sort'],
      ],
    ])->toString();

    $message = $this->t('Export complete. Download the file <a download href=":download_url"  data-download-enabled="false" id="vde-automatic-download">here</a>.', [':download_url' => $url]);
    $this->messenger->addMessage($message);
  }

}
