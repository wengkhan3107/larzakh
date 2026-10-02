<?php

namespace Drupal\xmlfeedviews\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Test XML Feed Views configurations.
 */
class XmlFeedViewsTest extends ViewTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'views_ui',
    'xmlfeedviews',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The strict config schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Basic checks : default views are inserted.
   */
  public function testDefaultView() {
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Find the views in the view list page.
    $this->drupalGet('admin/structure/views');
    $this->assertSession()->statusCodeEquals(200);

    // XML Feed Views.
    $this->assertSession()->responseContains($this->t('XML Feed Views'));
    $this->assertSession()->responseContains($this->t('xmlfeedviews_view'));
  }

}
