<?php

namespace Drupal\userprotect\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a user protection attribute object.
 *
 * Plugin Namespace: Plugin\UserProtection.
 *
 * @see \Drupal\userprotect\Plugin\UserProtection\UserProtectionManager
 * @see \Drupal\userprotect\Plugin\UserProtection\UserProtectionInterface
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class UserProtection extends Plugin {

  /**
   * Constructs a UserProtection attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the protection.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   A brief description of the protection.
   * @param int $weight
   *   A default weight used for presentation in the user interface only.
   * @param bool $status
   *   Whether this protection is enabled or disabled by default.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly int $weight = 0,
    public readonly bool $status = FALSE,
  ) {}

}
