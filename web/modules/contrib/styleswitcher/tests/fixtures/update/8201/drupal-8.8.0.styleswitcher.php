<?php

/**
 * @file
 * DB additions for the update path testing of styleswitcher_update_8201().
 *
 * @depends core/modules/system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz
 */

use Drupal\Tests\styleswitcher\Functional\Update\UpdatePathTestInstallHelper;

UpdatePathTestInstallHelper::enableExtension('styleswitcher');
UpdatePathTestInstallHelper::setSchemaVersion('styleswitcher', 8000);

UpdatePathTestInstallHelper::addConfigYml('styleswitcher.custom_styles', __DIR__ . '/styleswitcher.custom_styles.before.yml');
UpdatePathTestInstallHelper::addConfigYml('styleswitcher.styles_settings', __DIR__ . '/styleswitcher.styles_settings.before.yml');
