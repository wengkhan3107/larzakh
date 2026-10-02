<?php

namespace Drupal\views_birthday\Plugin\views\filter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\views\filter\Date;
use Drupal\views_birthday\Plugin\views\query\BirthdayDateSqlInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Date/time views filter.
 *
 * Even thought dates are stored as strings, the numeric filter is extended
 * because it provides more sensible operators.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("birthday")
 */
class Birthday extends Date {

  /**
   * The Birthday Date SQL service.
   *
   * @var \Drupal\views_birthday\Plugin\views\query\BirthdayDateSqlInterface
   */
  protected $birthday_date_sql;

  /**
   * Constructs a new Birthday filter handler.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to determine the current time.
   * @param \Drupal\views_birthday\Plugin\views\query\BirthdayDateSqlInterface $date_sql
   *   The Birthday Date SQL service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter, RequestStack $request_stack, BirthdayDateSqlInterface $date_sql) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $date_formatter, $request_stack);
    $this->birthday_date_sql = $date_sql;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('request_stack'),
      $container->get('views_birthday.date_sql')
    );
  }

  protected function opBetween($field) {
    $timezone = $this->getTimezone();
    $origin_offset = $this->getOffset($this->value['min'], $timezone);

    // Although both 'min' and 'max' values are required, default empty 'min'
    // value as UNIX timestamp 0.
    $min = (!empty($this->value['min'])) ? $this->value['min'] : '@0';

    $a = $this->formatDate($min);
    $b = $this->formatDate($this->value['max']);

    // This is safe because we are manually scrubbing the values.
    $operator = strtoupper($this->operator);

    $now = $this->formatDate('+0');
    $field = $this->query->getDateFormat(
      $this->birthday_date_sql->getNextBirthdayFormula($field, $now),
      $this->dateFormat,
      TRUE
    );

    $this->query->addWhereExpression($this->options['group'], "$field $operator $a AND $b");
  }

  protected function opSimple($field) {
    $value = $this->formatDate($this->value['value']);

    $now = $this->formatDate('+0');
    $field = $this->query->getDateFormat(
      $this->birthday_date_sql->getNextBirthdayFormula($field, $now),
      $this->dateFormat,
      TRUE
    );

    $this->query->addWhereExpression($this->options['group'], "$field $this->operator $value");
  }

  /**
   * Convert to ISO format and format for query.
   * UTC timezone is used since dates are stored in UTC.
   *
   * @param string $raw_value
   *
   * @return string
   *   Formatted string with timezone and offset applied
   */
  protected function formatDate($raw_value) {
    $timezone = $this->getTimezone();
    $origin_offset = $this->getOffset($raw_value, $timezone);

    $value = new DateTimePlus($raw_value, new \DateTimeZone($timezone));
    $value_formatted = $this->dateFormatter->format(
      $value->getTimestamp() + $origin_offset,
      'custom',
      DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
      DateTimeItemInterface::STORAGE_TIMEZONE
    );

    $date_field = $this->query->getDateField(
      "'$value_formatted'",
      TRUE,
      $this->calculateOffset
    );

    return $this->query->getDateFormat($date_field, $this->dateFormat, TRUE);
  }
}
