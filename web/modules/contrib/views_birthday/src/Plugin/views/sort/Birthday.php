<?php

namespace Drupal\views_birthday\Plugin\views\sort;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\views\sort\Date;
use Drupal\views_birthday\Plugin\views\query\BirthdayDateSqlInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic sort handler for datetime fields.
 *
 * This handler enables granularity, which is the ability to make dates
 * equivalent based upon nearness.
 *
 * @ViewsSort("birthday")
 */
class Birthday extends Date {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

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
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\views_birthday\Plugin\views\query\BirthdayDateSqlInterface $date_sql
   *   The Birthday Date SQL service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
    DateFormatterInterface $date_formatter,
    BirthdayDateSqlInterface $date_sql) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
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
      $container->get('views_birthday.date_sql')
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['granularity'] = ['default' => 'day'];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    unset($form['granularity']['#options']['second']);
    unset($form['granularity']['#options']['minute']);
    unset($form['granularity']['#options']['hour']);
  }

  /**
   * {@inheritdoc}
   *
   * Override to account for dates stored as strings.
   */
  public function getDateField() {
    return $this->birthday_date_sql->getNextBirthdayFormula(
      "$this->tableAlias.$this->realField",
      $this->getNow()
    );
  }

  /**
   * {@inheritdoc}
   *
   * Overridden in order to pass in the string date flag.
   */
  public function getDateFormat($format) {
    return $this->query->getDateFormat($this->getDateField(), $format, TRUE);
  }

  /**
   * Convert current time to ISO format and format for query.
   * UTC timezone is used since dates are stored in UTC.
   *
   * @param string $raw_value
   *
   * @return string
   *   Formatted string with timezone and offset applied
   */
  protected function getNow() {
    $timezone = DateTimeItemInterface::STORAGE_TIMEZONE;
    $origin_offset = $this->getOffset("", $timezone);

    $value = new DateTimePlus("", new \DateTimeZone($timezone));
    $value_formatted = $this->dateFormatter->format(
      $value->getTimestamp() + $origin_offset,
      'custom',
      DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
      DateTimeItemInterface::STORAGE_TIMEZONE
    );

    $date_field = $this->query->getDateField(
      "'$value_formatted'",
      TRUE,
      FALSE
    );

    return $this->query->getDateFormat(
      $date_field,
      DateTimeItemInterface::DATE_STORAGE_FORMAT,
      TRUE
    );
  }

  /**
   * Get the proper offset from UTC to use in computations.
   *
   * @param string $time
   *   A date/time string compatible with \DateTime. It is used as the
   *   reference for computing the offset, which can vary based on the time
   *   zone rules.
   * @param string $timezone
   *   The time zone that $time is in.
   *
   * @return int
   *   The computed offset in seconds.
   */
  protected function getOffset($time, $timezone) {
    // Date-only fields do not have a time zone or offset from UTC associated
    // with them. For relative (i.e. 'offset') comparisons, we need to compute
    // the user's offset from UTC for use in the query.
    return timezone_offset_get(
      new \DateTimeZone(
        date_default_timezone_get()),
        new \DateTime($time, new \DateTimeZone($timezone)
      )
    );
  }

}
