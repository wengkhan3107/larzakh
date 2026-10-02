<?php

namespace Drupal\views_birthday\Plugin\views\query;

use Drupal\Core\Database\Connection;

/**
 * MySQL-specific date handling.
 */
class MysqlBirthdayDateSql implements BirthdayDateSqlInterface {

  /**
   * Creates a MySQL formula for next birthday date
   *
   * @param string $field
   *   An appropriate query expression pointing to the date field.
   * @param string $now
   *   A preformatted representation of current datetime.
   *
   * @return string
   *   A string representing the formula for next birthday date
   */
  public function getNextBirthdayFormula($field, $now) {
    return "DATE_ADD($field, INTERVAL YEAR(CURDATE()) - YEAR($field)" .
      " + IF($now > DATE_FORMAT(" .
      "DATE_ADD($field, INTERVAL YEAR(CURDATE()) - YEAR($field) YEAR)," .
      "'%Y-%m-%d'), 1, 0) YEAR)";
  }
}
