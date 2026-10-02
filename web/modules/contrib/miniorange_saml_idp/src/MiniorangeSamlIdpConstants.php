<?php

namespace Drupal\miniorange_saml_idp;

/**
 * Class containing constant values used in the Miniorange SAML IDP module.
 */
class MiniorangeSamlIdpConstants {
  const BASE_URL = 'https://login.xecurify.com';
  const LICENSE_PAGE_URL = '/admin/config/people/miniorange_saml_idp/Licensing';
  const SUPPORT_EMAIL = 'drupalsupport@xecurify.com';
  const CREATE_ACCOUNT_URL = 'https://portal.miniorange.com/signup';
  const FROM_MAIL = 'no-reply@xecurify.com';
  const FROM_NAME = 'miniOrange';
  const API_INVALID_CRED_RESPONSE = 'Invalid username or password. Please try again.';
  const API_QUERY_SUCCESS_RESPONSE = 'Query submitted.';
  const API_INVALID_EMAIL_RESPONSE = 'Invalid email.';
  const API_NO_RESPONSE = 'Something went wrong.';
  const NOT_INCLUDE_IN_MAPPING = ['pass' => 'pass', 'uuid' => 'uuid', 'init' => 'init'];
  const MODULE_INFO = [
    'name' => 'SAML Identity Provider',
    'saml_features' => 'https://plugins.miniorange.com/drupal-saml-idp',
    'setup_guides' => 'https://www.drupal.org/docs/contributed-modules/saml-idp-20-single-sign-on-sso-saml-identity-provider',
    'case_studies' => 'https://www.drupal.org/node/3196471/case-studies',
    'landing_page' => 'https://plugins.miniorange.com/drupal',
    'customers' => 'https://plugins.miniorange.com/drupal-customers',
    'drupalsupport' => self::SUPPORT_EMAIL,
  ];
}
