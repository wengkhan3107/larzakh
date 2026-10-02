<?php

namespace Drupal\userprotect_test\Plugin\UserProtection;

use Drupal\userprotect\Plugin\UserProtection\UserProtectionBase;

/**
 * Test plugin that uses annotation for discovery.
 *
 * @UserProtection(
 *   id = "annotate_user_protection",
 *   label = @Translation("Annotate User Protection plugin"),
 *   description = @Translation("Used for testing if this plugin is found by UserProtectionManager."),
 *   weight = 0
 * )
 */
class AnnotateUserProtectionPlugin extends UserProtectionBase {

}
