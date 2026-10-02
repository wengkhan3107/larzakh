<?php

namespace Drupal\views_birthday\Plugin\views\query;

/**
 * Defines an interface for handling birthday date queries with SQL.
 *
 */
interface BirthdayDateSqlInterface {

  /**
   * Creates a native database formula for next birthday date
   *
   * @param string $field
   *   An appropriate query expression pointing to the date field.
   * @param string $now
   *   A preformatted representation of current datetime.
   *
   * @return string
   *   A string representing the formula for next birthday date
   */
  public function getNextBirthdayFormula($field, $now);

}
