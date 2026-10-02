<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\miniorange_saml_idp\Utilities;

class ConfirmDeleteConfiguration extends ConfirmFormBase
{

    /**
     * @inheritDoc
     */
    public function getQuestion()
    {
      return $this->t('Are you sure you want to delete the configurations for the %sp?', ['%sp' => $this->config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_name')]);
    }

    /**
     * @inheritDoc
     */
    public function getCancelUrl()
    {
      return new Url('miniorange_saml_idp.idp_setup');
    }

    /**
     * @inheritDoc
     */
    public function getFormId()
    {
        return 'confirm-delete-configuration';
    }

    /**
     * @inheritDoc
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
      $variables_and_values = [
        'miniorange_saml_idp_name',
        'miniorange_saml_idp_entity_id',
        'miniorange_saml_idp_acs_url',
        'miniorange_saml_idp_relay_state',
        'miniorange_saml_idp_nameid_format',
        'miniorange_saml_idp_assertion_signed',
      ];
      $this->messenger()->addMessage($this->t('Deleted the configurations successfully.'));
      Utilities::miniorangeSetGetConfigurations($variables_and_values, 'CLEAR');
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
}
