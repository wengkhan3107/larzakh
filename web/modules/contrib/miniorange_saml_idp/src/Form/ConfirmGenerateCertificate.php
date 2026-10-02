<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;

class ConfirmGenerateCertificate extends ConfirmFormBase
{

    /**
     * @inheritDoc
     */
    public function getQuestion()
    {
      return $this->t('Are you sure you want to generate a new certificate?');
    }


    /**
     * @inheritDoc
     */
    public function getCancelUrl()
    {
      return new Url('miniorange_saml_idp.sp_setup');
    }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will invalidate the previous certificate. You need to update your Service Provider (SP) configuration with the new certificate.');
  }

    /**
     * @inheritDoc
     */
    public function getFormId()
    {
      return 'miniorange_confirm_generate_certificate';
    }

    /**
     * @inheritDoc
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
      try {
      \Drupal::service('miniorange_saml_idp.cert_generator')->ensureSamlCertificateExists(TRUE);
      \Drupal::messenger()->addMessage($this->t('Certificate generated successfully.'));
      } catch (\Exception $exception) {
        \Drupal::messenger()->addError($this->t('Error generating certificate. Please try again.'));
        Error::logException(\Drupal::logger('miniorange_saml_idp'), $exception);
      }
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
}
