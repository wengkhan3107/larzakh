<?php

namespace Drupal\download_count\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure download count settings.
 */
class DownloadCountSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'download_count_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['download_count.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('download_count.settings');
    $form['excluded file extensions'] = [
      '#type' => 'details',
      '#title' => $this->t('Excluded file extensions'),
      '#open' => TRUE,
    ];
    $form['excluded file extensions']['download_count_excluded_file_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Excluded file extensions'),
      '#default_value' => $config->get('download_count_excluded_file_extensions'),
      '#maxlength' => 255,
      '#description' => $this->t("To exclude files of certain types, enter the extensions to exclude separated by spaces. This is useful if you have private image fields and don't wish to include them in download counts."),
    ];
    $form['download count page'] = [
      '#type' => 'details',
      '#title' => $this->t('Report page options'),
      '#description' => $this->t('Settings for <a href="@page">this</a> page.', ['@page' => Url::fromRoute('download_count.reports')->toString()]),
      '#open' => FALSE,
    ];
    $form['download count page']['download_count_view_page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config->get('download_count_view_page_title'),
    ];
    $form['download count page']['download_count_view_page_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Total number of items to display'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_view_page_limit'),
      '#description' => $this->t('Set to 0 for no limit.'),
    ];
    $form['download count page']['download_count_view_page_items'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of items per page'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_view_page_items'),
      '#description' => $this->t('Set to 0 for no pager.'),
    ];
    $header = $config->get('download_count_view_page_header');
    $form['download count page']['download_count_view_page_header'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Header'),
      '#format' => $header['format'] ?? NULL,
      '#default_value' => $header['value'] ?? NULL,
      '#description' => $this->t('Text to appear between the title of the page and the download count table.'),
    ];
    $footer = $config->get('download_count_view_page_footer');
    $form['download count page']['download_count_view_page_footer'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Footer'),
      '#format' => $footer['format'] ?? NULL,
      '#default_value' => $footer['value'] ?? NULL,
      '#description' => $this->t('Text to appear underneath the download count table.'),
    ];
    $form['details'] = [
      '#type' => 'details',
      '#title' => $this->t('Details Page Options'),
      '#open' => FALSE,
    ];
    $form['details']['download_count_details_daily_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of days to display on the details page.'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_details_daily_limit'),
    ];
    $form['details']['download_count_details_weekly_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of weeks to display on the details page.'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_details_weekly_limit'),
    ];
    $form['details']['download_count_details_monthly_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of months to display on the details page.'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_details_monthly_limit'),
    ];
    $form['details']['download_count_details_yearly_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of years to display on the details page.'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_details_yearly_limit'),
    ];

    $form['download_count_flood_control'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Flood Control Settings'),
      '#open' => FALSE,
    ];
    $form['download_count_flood_control']['download_count_flood_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flood control limit'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_flood_limit'),
      '#description' => $this->t('Maximum number of times to count the file download per time window. Enter 0 for no flood control limits.'),
    ];
    $form['download_count_flood_control']['download_count_flood_window'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flood control window'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_flood_window'),
      '#description' => $this->t('Number of seconds in the time window for counting a file download.'),
    ];
    $form['download_count_cache_clear'] = [
      '#type' => 'details',
      '#title' => $this->t('Clear Download Count Cache'),
      '#description' => '<p>' . $this->t('This will delete the cached download count data from the database. It
             will be rebuilt during drupal cron runs.') . '<br /><strong>' . $this->t('Note:') . '</strong>' . $this->t('This will affect the details page until the data has been rebuilt.') . '</p>',
      '#open' => FALSE,
    ];
    $form['download_count_cache_clear']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear Cache'),
      '#submit' => ['::downloadCountClearSubmit'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('download_count.settings');
    $config->set('download_count_flood_window', $form_state->getValue('download_count_flood_window'))
      ->set('download_count_flood_limit', $form_state->getValue('download_count_flood_limit'))
      ->set('download_count_sparkline_width', $form_state->getValue('download_count_sparkline_width'))
      ->set('download_count_sparkline_height', $form_state->getValue('download_count_sparkline_height'))
      ->set('download_count_sparkline_min', $form_state->getValue('download_count_sparkline_min'))
      ->set('download_count_sparklines', $form_state->getValue('download_count_sparklines'))
      ->set('download_count_details_yearly_limit', $form_state->getValue('download_count_details_yearly_limit'))
      ->set('download_count_details_monthly_limit', $form_state->getValue('download_count_details_monthly_limit'))
      ->set('download_count_details_weekly_limit', $form_state->getValue('download_count_details_weekly_limit'))
      ->set('download_count_details_daily_limit', $form_state->getValue('download_count_details_daily_limit'))
      ->set('download_count_view_page_items', $form_state->getValue('download_count_view_page_items'))
      ->set('download_count_view_page_limit', $form_state->getValue('download_count_view_page_limit'))
      ->set('download_count_view_page_title', $form_state->getValue('download_count_view_page_title'))
      ->set('download_count_excluded_file_extensions', $form_state->getValue('download_count_excluded_file_extensions'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Implements submit callback for download count clear.
   */
  public function downloadCountClearSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('download_count.clear');
  }

}
