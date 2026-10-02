<?php

namespace Drupal\user_activity_logger\Form;


use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class UserActivityLoggerSettingsForm extends ConfigFormBase
{

    protected function getEditableConfigNames()
    {
        return ['user_activity_logger.settings'];
    }

    public function getFormId()
    {
        return 'user_activity_logger_settings_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('user_activity_logger.settings');

        $form['enable_cleanup'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable automatic log cleanup'),
            '#default_value' => $config->get('enable_cleanup') ?? TRUE,
            '#description' => $this->t('If unchecked, logs will never be automatically deleted.'),
        ];

        $form['keep_logs_days'] = [
            '#type' => 'number',
            '#title' => $this->t('Days to keep logs'),
            '#default_value' => $config->get('keep_logs_days') ?: 30,
            '#min' => 1,
            '#description' => $this->t('How many days of user activity logs should be kept before automatic cleanup.'),
        ];

        // Add a "Clear Logs" button.
        $form['clear_logs'] = [
            '#type' => 'link',
            '#title' => $this->t('Clear all logs now'),
            '#url' => Url::fromRoute('user_activity_logger.clear_logs'),
            '#attributes' => [
                'class' => ['button', 'button--danger'],
            ],
        ];


        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('user_activity_logger.settings')
            ->set('enable_cleanup', $form_state->getValue('enable_cleanup'))
            ->set('keep_logs_days', $form_state->getValue('keep_logs_days'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
