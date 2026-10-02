<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Defines application features from the specific context.
 */
class AccordionBlocksFeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * Helper function cleanup blocks.
   *
   * Remove terms that we probably created. Nodes
   * are handled because when a user is deleted their content
   * is deleted as well. This not true for terms
   * that they create though.
   *
   * @AfterScenario
   */
  public function cleanupBlocks() {
    $query = Drupal::entityQuery('block_content');
    $result = $query->condition('info', 'BDD TESTING', 'STARTS_WITH')
      ->execute();
    $eids = array_keys($result);
    \Drupal::service('entity_type.manager')->getStorage('block_content')->delete(\Drupal::service('entity_type.manager')->getStorage('block_content')->loadMultiple($eids));

  }

  /**
   * Helper function.
   *
   * @Then /^I wait for the suggestion box to appear$/
   */
  public function iWaitForTheSuggestionBoxToAppear() {
    $this->getSession()->wait(5000,
       "jQuery('.ui-dialog.ui-widget').length > 0"
     );
  }

  /**
   * Helper function.
   *
   * @When I wait for the field items to appear
   */
  public function iWaitForTheFieldItemsToAppear() {
    $this->getSession()->wait(5000,
        "jQuery('input[name=\"field_blocks[2][target_id]\"]').length > 0"
      );
  }

  /**
   * Helper function.
   *
   * @When I Save Block Placement
   */
  public function iSaveBlockPlacement() {
    $this->getSession()->wait(100,
        "jQuery('.ui-dialog-buttonset.form-actions button').click()"
      );
  }
}
