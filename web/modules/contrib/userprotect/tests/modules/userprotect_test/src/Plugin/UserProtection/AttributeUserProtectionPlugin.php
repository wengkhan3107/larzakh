<?php

namespace Drupal\userprotect_test\Plugin\UserProtection;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\userprotect\Attribute\UserProtection as UserProtectionAttribute;
use Drupal\userprotect\Plugin\UserProtection\UserProtectionBase;

/**
 * Test plugin that uses attributes for discovery.
 */
#[UserProtectionAttribute(
  id: 'attribute_user_protection',
  label: new TranslatableMarkup('Attribute User Protection plugin'),
  description: new TranslatableMarkup('Used for testing if this plugin is found by \\Drupal\\userprotect\\Plugin\\UserProtection\\UserProtectionManager.'),
  weight: 0
)]
class AttributeUserProtectionPlugin extends UserProtectionBase {

}
