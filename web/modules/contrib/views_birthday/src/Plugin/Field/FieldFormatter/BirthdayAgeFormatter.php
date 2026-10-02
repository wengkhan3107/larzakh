<?php

namespace Drupal\views_birthday\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeTimeAgoFormatter;

/**
 * Plugin implementation of the 'Age' formatter for 'datetime' fields.
 *
 * @FieldFormatter(
 *   id = "birthday_age",
 *   label = @Translation("Age (birthday)"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class BirthdayAgeFormatter extends DateTimeTimeAgoFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'singular_format' => '@count year old',
      'plural_format' => '@count years old',
      'future' => false,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = FormatterBase::settingsForm($form, $form_state);
    $form['future'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Future'),
      '#default_value' => $this->getSetting('future'),
      '#description' => $this->t('If checked, display age from next birthday. Else, it will display current age. On birthday day, both will display current age.'),
    ];

    $form['plural_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format (plural)'),
      '#default_value' => $this->getSetting('plural_format'),
      '#description' => $this->t('Use <em>@count</em> where you want the age to appear.'),
    ];

    $form['singular_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format (singular)'),
      '#default_value' => $this->getSetting('singular_format'),
      '#description' => $this->t('Use <em>@count</em> where you want the age to appear.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = FormatterBase::settingsSummary();

    $age_plural = $this->formatPlural(
      27,
      $this->getSetting('singular_format'),
      $this->getSetting('plural_format'),
      [], []
    );
    $age_singular = $this->formatPlural(
      1,
      $this->getSetting('singular_format'),
      $this->getSetting('plural_format'),
      [], []
    );

    $summary[] = $this->t('Plural: %display', ['%display' => $age_plural]);
    $summary[] = $this->t('Singular: %display', ['%display' => $age_singular]);

    return $summary;
  }

  /**
   * Formats a date as a age display.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|object $date
   *   A date object.
   *
   * @return array
   *   The formatted date string using the format setting.
   */
  protected function formatDate(DrupalDateTime $from) {
    $now = new \DateTime();
    $now->setTimestamp($this->request->server->get('REQUEST_TIME'));
    $diff_year = $now->format('Y') - $from->format('Y');
    $to = (clone $from)->modify("+" . $diff_year . " year");

    if ($this->getSetting('future')) {
      if ($now->format('Y-m-d') > $to->format('Y-m-d')) {
        $diff_year++;
      }
    } else {
      if ($now->format('Y-m-d') < $to->format('Y-m-d')) {
        $diff_year--;
      }
    }

    $markup = $this->formatPlural(
      $diff_year,
      $this->getSetting('singular_format'),
      $this->getSetting('plural_format'),
      [], []
    );

    $build = [
      '#markup' => $markup,
    ];

    return $build;
  }

}
