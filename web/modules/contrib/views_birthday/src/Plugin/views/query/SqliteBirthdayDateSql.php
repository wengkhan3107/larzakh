<?php

namespace Drupal\views_birthday\Plugin\views\query;

use Drupal\Core\Database\Connection;

/**
 * SQLite-specific date handling.
 */
class SqliteBirthdayDateSql implements BirthdayDateSqlInterface {

  /**
   * Creates a SQLite formula for next birthday date
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
    $set_current_year = "date($field, (strftime('%Y', 'now') - strftime('%Y', $field) )|| ' year')";

    $next_birthday = "date($set_current_year, (CASE WHEN ($now) > ($set_current_year) THEN 1 ELSE 0 END) || ' year')";

    return "strftime('%s', $next_birthday)";
  }
}
