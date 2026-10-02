<?php

namespace Drupal\group_permissions\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\group_permissions\Entity\Storage\GroupPermissionStorageInterface;
use Drupal\group_permissions\GroupPermissionsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Group Permissions Controller.
 *
 *  Returns responses for group permissions routes.
 */
class GroupPermissionsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $groupPermissionStorage;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * The permission handler.
   *
   * @var \Drupal\group\Access\GroupPermissionHandlerInterface
   */
  protected $groupPermissionHandler;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Group Permissions Controller constructor.
   *
   * @param \Drupal\group_permissions\Controller\DateFormatter $date_formatter
   *   Date formatter.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer.
   * @param \Drupal\group_permissions\Controller\FormBuilderInterface $form_builder
   *   Entity form builder.
   * @param \Drupal\group_permissions\Entity\Storage\GroupPermissionStorageInterface $group_permission_storage
   *   Group permission storage.
   * @param \Drupal\group_permissions\GroupPermissionsManagerInterface $group_permission_manager
   *   The group permissions manager.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(
    DateFormatter $date_formatter,
    Renderer $renderer,
    FormBuilderInterface $form_builder,
    GroupPermissionStorageInterface $group_permission_storage,
    GroupPermissionsManagerInterface $group_permission_manager,
    GroupPermissionHandlerInterface $permission_handler,
    RouteMatchInterface $route_match,
  ) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->formBuilder = $form_builder;
    $this->groupPermissionStorage = $group_permission_storage;
    $this->groupPermissionsManager = $group_permission_manager;
    $this->groupPermissionHandler = $permission_handler;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('form_builder'),
      $container->get('entity_type.manager')->getStorage('group_permission'),
      $container->get('group_permission.group_permissions_manager'),
      $container->get('group.permissions'),
      $container->get('current_route_match')
    );
  }

  /**
   * View group permission.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function view(GroupInterface $group) {
    $group_permission = $this->groupPermissionsManager->loadByGroup($group);
    if ($group_permission) {
      return $this->getPermissionTable($group, $group_permission->getPermissions());
    }
    else {
      return [
        '#type' => 'link',
        '#title' => $this->t('Add group permissions'),
        '#url' => Url::fromRoute('entity.group_permission.add-form', [
          'group' => $group->id(),
        ]),
      ];
    }
  }

  /**
   * Displays a group permissions revision.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   * @param int $group_permission_revision
   *   The group permissions revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow(GroupInterface $group, $group_permission_revision) {
    $group_permission = $this->groupPermissionsManager->loadByGroup($group);
    $revision = $this->entityTypeManager()->getStorage('group_permission')->loadRevision($group_permission_revision);

    return $this->getPermissionTable($group, $group_permission->getPermissions(), $revision->getPermissions());
  }

  /**
   * Get permission table.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   * @param array $group_permissions
   *   Group permissions.
   * @param array $revision_custom_permissions
   *   Group permissions revision.
   *
   * @return array
   *   Table renderable array.
   */
  protected function getPermissionTable($group, $group_permissions = [], $revision_custom_permissions = []) {
    $build = [];
    $rows = [];

    $header = [
      'title' => $this->t('Permission'),
    ];

    $group_roles = $this->groupPermissionsManager->getGroupRoles($group);

    // Retrieve information for every role to user further down. We do this to
    // prevent the same methods from being fired (rows * permissions) times.
    foreach ($group_roles as $role_name => $group_role) {
      $role_label = $group_role->label();
      if ($group_role->isOutsider() && !$group_role->inPermissionsUI()) {
        $role_label .= ' ' . $this->t('(Outsider)');
      }

      // Permissions should be explicitly assigned another case we don't
      // provide the permission.
      $role_info[$role_name] = [
        'label' => $role_label,
        'permissions' => !empty($group_permissions[$role_name]) ? $group_permissions[$role_name] : [],
        'is_anonymous' => $group_role->isAnonymous(),
        'is_outsider' => $group_role->isOutsider(),
        'is_member' => $group_role->isMember(),
      ];
    }

    // Create a column with header for every group role.
    foreach ($role_info as $role_name => $info) {
      $header[$role_name] = [
        'data' => $info['label'],
        'class' => 'checkbox',
      ];
    }

    $permissions_list = $this->getGroupPermissions($group);

    foreach ($permissions_list as $provider => $sections) {
      // Print a full width row containing the provider name for each provider.
      $rows[] = [
        [
          'colspan' => count($group_roles) + 1,
          'class' => 'module',
          'data' => $this->moduleHandler()->getName($provider),
        ],
      ];

      foreach ($sections as $section => $permissions) {
        $rows[] = [
          [
            'colspan' => count($group_roles) + 1,
            'class' => 'section',
            'data' => $section,
          ],
        ];

        foreach ($permissions as $perm => $perm_item) {

          $row = [];
          $row[] = [
            'data' => [
              '#type' => 'inline_template',
              '#template' => '<span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em><br />{% endif %}{{ description }}</div>{% endif %}',
              '#context' => [
                'title' => $perm_item['title'],
                'description' => $perm_item['description'],
                'warning' => $perm_item['warning'],
              ],
            ],
            'class' => 'permission',
          ];

          foreach ($role_info as $role_name => $info) {
            // Determine whether the permission is available for this role.
            $na = $info['is_anonymous'] && !in_array('anonymous', $perm_item['allowed for']);
            $na = $na || ($info['is_outsider'] && !in_array('outsider', $perm_item['allowed for']));
            $na = $na || ($info['is_member'] && !in_array('member', $perm_item['allowed for']));

            if ($na) {
              $row[] = [
                'data' => [
                  '#type' => 'markup',
                  '#markup' => '-',
                ],
                'class' => 'checkbox module',
              ];
            }
            else {
              $group_permission_has_permission = FALSE;
              $revision_has_permission = FALSE;
              if (!empty($group_permissions[$role_name])) {
                $group_permission_has_permission = in_array($perm, $group_permissions[$role_name]);
                $revision_has_permission = in_array($perm, $revision_custom_permissions[$role_name] ?? []);
              }

              $sign = $group_permission_has_permission ? '&check;' : '&times;';
              if (!empty($revision_custom_permissions)) {
                $color = $group_permission_has_permission == $revision_has_permission ? '#008000' : '#ff0000';
              }
              else {
                $color = $group_permission_has_permission ? '#008000' : '#ff0000';
              }

              $row[] = [
                'data' => [
                  '#type' => 'markup',
                  '#markup' => $sign,
                ],
                'style' => "color: {$color};",
                'class' => 'checkbox module',
              ];
            }

          }

          $rows[] = $row;
        }
      }
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No content has been found.'),
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
    ];

    $build['table']['#attached']['library'][] = 'group/permissions';

    return [
      '#type' => '#markup',
      '#markup' => $this->renderer->render($build),
    ];
  }

  /**
   * Gets the permissions to display in this form.
   *
   * @return array
   *   A multidimensional associative array of permissions, keyed by the
   *   providing module first and then by permission name.
   */
  protected function getGroupPermissions(GroupInterface $group) {
    $by_provider_and_section = [];

    // Create a list of group permissions ordered by their provider and section.
    foreach ($this->groupPermissionHandler->getPermissionsByGroupType($group->getGroupType()) as $permission_name => $permission) {
      if (empty($permission['gid']) || !empty($permission['gid']) && $permission['gid'] == $group->id()) {
        $by_provider_and_section[$permission['provider']][(string) $permission['section']][$permission_name] = $permission;
      }
    }

    // Always put the 'General' section at the top if provided.
    foreach ($by_provider_and_section as $provider => $sections) {
      if (isset($sections['General'])) {
        $by_provider_and_section[$provider] = ['General' => $sections['General']] + $sections;
      }
    }

    return $by_provider_and_section;
  }

  /**
   * Page title callback for a group permissions revision.
   *
   * @param int $group_permission_revision
   *   The group permissions revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($group_permission_revision) {
    return $this->t('Revision %revision', [
      '%revision' => $group_permission_revision,
    ]);
  }

  /**
   * Provides the form to add group permission.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   A group permission form.
   */
  public function addGroupPermission(GroupInterface $group) {
    return $this->formBuilder->getForm('\Drupal\group_permissions\Form\GroupPermissionsForm', $group);
  }

  /**
   * Provides the form to edit group permission.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   A group permission form.
   */
  public function editGroupPermission(GroupInterface $group) {
    return $this->formBuilder->getForm('\Drupal\group_permissions\Form\GroupPermissionsForm', $group);
  }

  /**
   * Generates an overview table of older revisions.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionOverview(GroupInterface $group) {
    $group_permission = $this->groupPermissionsManager->loadByGroup($group);
    $build['#title'] = $this->t('Revisions for %title', ['%title' => $group_permission->getGroup()->label()]);
    $header = [
      $this->t('Revision ID'),
      $this->t('Date'),
      $this->t('Operations'),
    ];

    $rows = [];
    $default_revision = $group_permission->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($group_permission) as $vid) {
      $revision_url_parameters = [
        'group_permission_revision' => $vid,
        'group_permission' => $group_permission->id(),
        'group' => $group_permission->getGroup()->id(),
      ];
      /** @var \Drupal\group_permissions\Entity\GroupPermissionInterface $revision */
      $revision = $this->groupPermissionStorage->loadRevision($vid);
      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];

      $date = $this->t('Revision');
      if (!empty($revision->revision_created->value)) {
        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_created->value, 'short');
      }

      // We treat also the latest translation-affecting revision as current
      // revision, if it was the default revision, as its values for the
      // current language will be the same of the current default revision in
      // this case.
      $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
      if (!$is_current_revision) {
        $link = Link::fromTextAndUrl($vid, new Url('entity.group_permission.revision', $revision_url_parameters))
          ->toString();
      }
      else {
        $link = $group_permission->toLink($vid)->toString();
        $current_revision_displayed = TRUE;
      }

      $row = [];
      $row[] = [
        'data' => [
          '#markup' => $link,
        ],
      ];
      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
          '#context' => [
            'date' => $date,
            'username' => $this->renderer->renderPlain($username),
            'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
          ],
        ],
      ];
      // @todo Simplify once https://www.drupal.org/node/2334319 lands.
      $this->renderer->addCacheableDependency($column['data'], $username);
      $row[] = $column;
      $links = [];
      if ($is_current_revision) {
        $row[] = [
          'data' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Current revision'),
            '#suffix' => '</em>',
          ],
        ];

        $rows[] = [
          'data' => $row,
          'class' => ['revision-current'],
        ];
      }
      else {

        $links['revert'] = $this->getRevertLink($revision_url_parameters);
        $links['delete'] = $this->getDeleteLink($revision_url_parameters);

        $row[] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];

        $rows[] = $row;
      }

    }

    $build['group_permission_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Returns an array with a revert revision link.
   *
   * @param array $url_parameters
   *   Url params.
   *
   * @return array
   *   Link array.
   */
  private function getRevertLink(array $url_parameters) {
    return $this->getLinkArray($url_parameters, $this->t('Revert'), 'revision-revert');
  }

  /**
   * Returns an array with a delete revision link.
   *
   * @param array $url_parameters
   *   Url params.
   *
   * @return array
   *   Link array.
   */
  private function getDeleteLink(array $url_parameters) {
    return $this->getLinkArray($url_parameters, $this->t('Delete'), 'revision-delete');
  }

  /**
   * Get link array.
   *
   * @param array $url_parameters
   *   Url params.
   * @param string $title
   *   Title.
   * @param string $action
   *   Action.
   *
   * @return array
   *   Link array.
   */
  private function getLinkArray(array $url_parameters, $title, $action): array {
    return [
      'title' => $this->t(':title', [':title' => $title])->render(),
      'url' => Url::fromRoute("entity.group_permission.$action", $url_parameters),
    ];
  }

  /**
   * Gets a list of revisions IDs for a specific group permission.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $group_permission
   *   The group permission entity.
   *
   * @return int[]
   *   Group permission revision IDs (in descending order).
   */
  protected function getRevisionIds(GroupPermissionInterface $group_permission) {
    $result = $this->groupPermissionStorage->getQuery()
      ->accessCheck()
      ->allRevisions()
      ->condition($group_permission->getEntityType()->getKey('id'), $group_permission->id())
      ->sort($group_permission->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }

  /**
   * Get custom group permissions.
   *
   * @return array
   *   Group permissions.
   */
  public function getPermissions() {
    $permissions = [];
    $group_type = $this->routeMatch->getParameter('group_type');
    if (empty($group_type)) {
      $group = $this->routeMatch->getParameter('group');
      if ($group instanceof GroupInterface) {
        $group_type = $group->getGroupType();
      }
    }

    if (!empty($group_type) && $group_type->getThirdPartySetting('group_permissions', 'enabled')) {
      $permissions['override group permissions'] = [
        'title' => 'Allows to override group permissions.',
      ];
    }

    return $permissions;
  }

}
