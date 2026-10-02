<?php

namespace Drupal\user_activity_logger\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class UserActivityLoggerClearLogsForm extends ConfirmFormBase
{

    public function getFormId()
    {
        return 'user_activity_logger_clear_logs_form';
    }

    public function getQuestion()
    {
        return $this->t('Are you sure you want to delete all user activity logs?');
    }

    public function getCancelUrl()
    {
        return new Url('user_activity_logger.settings');
    }

    public function getConfirmText()
    {
        return $this->t('Delete all logs');
    }

    public function getDescription()
    {
        return $this->t('This action cannot be undone. All user activity logs will be permanently removed.');
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $connection = \Drupal::database();
        $connection->truncate('user_activity_log')->execute();

        $this->messenger()->addStatus($this->t('All user activity logs have been cleared.'));
        $form_state->setRedirect('user_activity_logger.settings');
    }
}
