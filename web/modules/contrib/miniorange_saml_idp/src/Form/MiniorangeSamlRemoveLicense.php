<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the form for removing the customer's license and account details.
 */
class MiniorangeSamlRemoveLicense extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_saml_remove_license';
  }

  /**
   * Builds the form for confirming account removal.
   *
   * @param array $form
   *   The form array to be built.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array|null $options
   *   Optional additional options for the form.
   *
   * @return array
   *   The built form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          'miniorange_saml_idp/miniorange_saml_idp.admin',
        ],
      ],
    ];

    $form['#prefix'] = '<div id="modal_example_form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['miniorange_saml_content'] = [
      '#markup' => t('Are you sure you want to remove your account? The configurations saved will not be lost.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  /**
   * Handles the AJAX submission of the modal form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $editConfig = \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings');
    $response   = new AjaxResponse();
    // If there are any form errors, AJAX replace the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_example_form', $form));
    }
    else {
      $editConfig->clear('miniorange_saml_idp_customer_admin_email')
        ->clear('miniorange_saml_customer_admin_phone')
        ->clear('miniorange_saml_customer_id')
        ->clear('miniorange_saml_customer_api_key')
        ->clear('miniorange_saml_customer_admin_token')
        ->set('miniorange_saml_status', 'CUSTOMER_SETUP')
        ->save();

      \Drupal::messenger()->addMessage(t('Your account has been removed successfully!'), 'status');

      $response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_saml_idp.customer_setup')->toString()));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Returns the names of the editable configuration for the form.
   *
   * @return array
   *   An array containing the name of the editable configuration.
   */
  protected function getEditableConfigNames() {
    return ['config.miniorange_saml_remove_license'];
  }

}
