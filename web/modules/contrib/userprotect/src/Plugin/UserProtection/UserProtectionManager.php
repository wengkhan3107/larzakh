<?php

namespace Drupal\userprotect\Plugin\UserProtection;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\userprotect\Annotation\UserProtection as UserProtectionAnnotation;
use Drupal\userprotect\Attribute\UserProtection as UserProtectionAttribute;

/**
 * Manages user protection plugins.
 */
class UserProtectionManager extends DefaultPluginManager {

  /**
   * Constructs a UserProtectionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // Check if there is support for attributed plugins.
    // @todo Remove BC layer when dropping support for Drupal < 10.2.0.
    if (!class_exists('\Drupal\Component\Plugin\Attribute\Plugin')) {
      // No attribute support yet.
      parent::__construct(
        'Plugin/UserProtection',
        $namespaces,
        $module_handler,
        'Drupal\userprotect\Plugin\UserProtection\UserProtectionInterface',
        UserProtectionAnnotation::class,
      );
    }
    else {
      parent::__construct(
        'Plugin/UserProtection',
        $namespaces,
        $module_handler,
        'Drupal\userprotect\Plugin\UserProtection\UserProtectionInterface',
        UserProtectionAttribute::class,
        UserProtectionAnnotation::class,
      );
    }

    $this->alterInfo('user_protection_info');
    $this->setCacheBackend($cache_backend, 'user_protection_plugins');
  }

}
