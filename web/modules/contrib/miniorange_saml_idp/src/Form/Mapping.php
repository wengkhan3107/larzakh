<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_saml_idp\Utilities;
use Drupal\miniorange_saml_idp\MiniorangeSamlIdpConstants;

/**
 * @file
 * Contains Attribute for miniOrange SAML IDP Module.
 */

/**
 * Showing Settings form.
 */
class Mapping extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_saml_mapping';
  }

  /**
   * Builds the settings form for the SAML IDP.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The renderable form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();

    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          'miniorange_saml_idp/miniorange_saml_idp.admin',
          'core/drupal.dialog.ajax',
        ],
      ],
    ];

    $form['markup_idp_attr_header'] = [
      '#markup' => t('<div class="mo_saml_sp_main_container"><div class="mo_saml_container">'),
    ];

    /* Create container to hold @IdentityProviderMapping form elements. */
    $form['mo_saml_idp_mapping'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'padding:2% 2% 5%; margin-bottom:2%'],
    ];

    $form['mo_saml_idp_mapping']['miniorange_saml_idp_div_st'] = [
      '#markup' => t('<div class="mo_saml_idp_font_for_heading">Attribute Mapping</div>
                               <p style="clear: both"></p><hr><br>'),
    ];
    $form['mo_saml_idp_mapping']['markup_idp_sp_note'] = [
      '#markup' => t('<div class = "mo_saml_highlight_background_note" ><b>Note:</b></divspan> This attribute will be sent in the NameID (unique identifier) in the SAML Response.</div>
         '),
    ];

    $form['mo_saml_idp_mapping']['miniorange_saml_idp_nameid_attr_map'] = [
      '#type' => 'radios',
      '#title' => t('NameID Attribute:'),
      '#options' => [
        'emailAddress' => t('Drupal Email Address'),
        'username' => t('Drupal Username'),
      ],
      '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_nameid_attr_map'),
    ];

    $form['mo_saml_idp_mapping']['minioranghe_saml_save_signin'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Save Configuration'),
    ];

    $form['miniorange_saml_idp_additional_user_attrs'] = [
      '#type' => 'details',
      '#title' => t('Additional User Attributes <a href="@base_url">[Premium]</a>', ['@base_url' => $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL]),
      '#open' => TRUE,
    ];

    $form['miniorange_saml_idp_additional_user_attrs']['markup_idp_user_attr_note'] = [
      '#markup' => '<hr><div class="mo_saml_highlight_background_note" id="ConstAttrNote"><b>Attribute Name: </b>The attribute key sent in the SAML Assertion.
                                  <br/><b>User Profile Attribute Value:</b> The user attribute (machine name) whose value will be sent corresponding to the Attribute key</div>',
    ];

    $form['miniorange_saml_idp_additional_user_attrs']['user_profile_attribute_table'] = [
      '#type' => 'table',
      '#header' => [
        'name' => t('Attribute Name'),
        'value' => t('User Profile Attribute Value'),
        'action' => t('Operation'),
      ],
    ];

    $form['miniorange_saml_idp_additional_user_attrs']['user_profile_attribute_table'][0]['name'] = [
      '#type' => 'textfield',
      '#size' => 20,
      '#attributes' => ['placeholder' => 'Enter Attribute Name'],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_additional_user_attrs']['user_profile_attribute_table'][0]['value'] = [
      '#type' => 'select',
      '#options' => Utilities::customUserFields(),
    ];

    $form['miniorange_saml_idp_additional_user_attrs']['user_profile_attribute_table'][0]['action'] = [
      '#type' => 'submit',
      '#value' => 'Remove',
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_additional_user_attrs']['user_profile_attribute_add_button'] = [
      '#type' => 'submit',
      '#value' => 'Add more',
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_constant_attrs'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'Constant  Attributes <a href="' . $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL . '">[Premium]</a>',
    ];

    $form['miniorange_saml_idp_constant_attrs']['markup_idp_constant_attr_note'] = [
      '#markup' => '<hr><div class="mo_saml_highlight_background_note" id="ConstAttrNote"><b>Attribute Name:</b> The attribute key sent in the SAML Assertion.
                                  <br /><b>Attribute Value:</b> The constant value you want to send to the Service Provider corresponding to the key.</div>',
    ];

    $form['miniorange_saml_idp_constant_attrs']['miniorange_saml_idp_constant_attrs_table'] = [
      '#type' => 'table',
      '#header' => [
        'name' => 'Attribute Name',
        'value' => 'Attribute Value',
        'operation' => 'Operation',
      ],
    ];

    $form['miniorange_saml_idp_constant_attrs']['miniorange_saml_idp_constant_attrs_table'][0]['name'] = [
      '#type' => 'textfield',
      '#attributes' => ['placeholder' => 'Enter Attribute Name'],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_constant_attrs']['miniorange_saml_idp_constant_attrs_table'][0]['value'] = [
      '#type' => 'textfield',
      '#attributes' => ['placeholder' => 'Enter Attribute Name'],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_constant_attrs']['miniorange_saml_idp_constant_attrs_table'][0]['operation'] = [
      '#type' => 'submit',
      '#value' => 'Remove',
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_constant_attrs']['miniorange_saml_idp_constant_add_button'] = [
      '#type' => 'submit',
      '#value' => 'Add more',
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_basic_attrs'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'Basic Attribute Mapping <a href="' . $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL . '">[Premium]</a>',
    ];

    $form['miniorange_saml_idp_basic_attrs']['markup_idp_basic_attr_note'] = [
      '#markup' => '<hr><div class="mo_saml_highlight_background_note" id="ConstAttrNote"><b>Note: </b> Use this mapping to send basic attributes like email, username, and user roles in the SAML response. Enter the relevant attribute name of your choice and select the corresponding value from the dropdown. Keep the attribute name blank to remove mapping.</div>',
    ];

    $form['miniorange_saml_idp_basic_attrs']['miniorange_saml_idp_basic_attrs_table'] = [
      '#type' => 'table',
      '#header' => [
        'name' => 'Attribute Name',
        'value' => 'Attribute Value',
      ],
    ];

    for ($count = 1; $count <= 3; $count++) {
      $form['miniorange_saml_idp_basic_attrs']['miniorange_saml_idp_basic_attrs_table'][$count]['name'] = [
        '#type' => 'textfield',
        '#attributes' => ['placeholder' => 'Enter the attribute name'],
        '#disabled' => TRUE,
        '#size' => 30,
      ];

      $form['miniorange_saml_idp_basic_attrs']['miniorange_saml_idp_basic_attrs_table'][$count]['value'] = [
        '#type' => 'select',
        '#options' => [
          'select' => t('- Select Attribute Value -'),
          'mail' => t('Email Address'),
          'name' => t('Username'),
          'roles' => t('User Roles'),
        ],
      ];
    }

    $form['miniorange_saml_idp_profile_attrs'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'Profile Module  Attributes <a href="' . $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL . '">[Premium]</a>',
    ];

    $form['miniorange_saml_idp_profile_attrs']['markup_idp_profile_attr_note'] = [
      '#markup' => '<hr><div class="mo_saml_highlight_background_note" id="ConstAttrNote"><b>Note: </b>This mapping allows you to send attributes from the profile module.<br><b>Profile Type:</b> The profile type created with the profile module.</div>',
    ];

    $form['miniorange_saml_idp_profile_attrs']['miniorange_saml_idp_profile_attrs_table'] = [
      '#type' => 'table',
      '#header' => [
        'type' => 'Profile Type',
        'field' => 'Field Name',
        'name' => 'Attribute Name',
        'operation' => 'Operation',
      ],
    ];

    $form['miniorange_saml_idp_profile_attrs']['miniorange_saml_idp_profile_attrs_table'][0]['type'] = [
      '#type' => 'select',
      '#disabled' => TRUE,
      '#options' => [
        '' => t('- Select Profile Type -'),
      ],
    ];

    $form['miniorange_saml_idp_profile_attrs']['miniorange_saml_idp_profile_attrs_table'][0]['field'] = [
      '#type' => 'select',
      '#disabled' => TRUE,
      '#options' => [
        '' => t('- Please Select Profile Type -'),
      ],
    ];

    $form['miniorange_saml_idp_profile_attrs']['miniorange_saml_idp_profile_attrs_table'][0]['name'] = [
      '#type' => 'textfield',
      '#attributes' => ['placeholder' => 'Enter Attribute Name'],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_profile_attrs']['miniorange_saml_idp_profile_attrs_table'][0]['operation'] = [
      '#type' => 'submit',
      '#value' => 'Remove',
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_profile_attrs']['miniorange_saml_idp_profile_add_button'] = [
      '#type' => 'submit',
      '#value' => 'Add more',
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_form_end'] = [
      '#markup'  => '</div>',
    ];

    Utilities::advertise2fa($form, $form_state);

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_value = $form_state->getValues();
    $nameid_attr = $form_value['miniorange_saml_idp_nameid_attr_map'];
    $nameid_attr_value = $nameid_attr == '' ? 'emailAddress' : $nameid_attr;
    \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_nameid_attr_map', $nameid_attr_value)->save();
    \Drupal::messenger()->addMessage(t('Your settings are saved successfully.'));
  }

}
