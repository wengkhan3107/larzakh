<?php

namespace Drupal\views_birthday\Plugin\views\query;

use Drupal\Core\Database\Connection;

/**
 * PostgreSQL-specific date handling.
 */
class PostgresqlBirthdayDateSql implements BirthdayDateSqlInterface {

  /**
   * Creates a PostgreSQL formula for next birthday date
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
    $set_current_year = "DATE($field) + (EXTRACT(YEAR FROM CURRENT_TIMESTAMP) - EXTRACT(YEAR FROM DATE($field))) * interval '1 year'";

    return "$set_current_year + interval '1 year' * CASE WHEN ($now) > ($set_current_year) THEN 1 ELSE 0 END";
  }
}
