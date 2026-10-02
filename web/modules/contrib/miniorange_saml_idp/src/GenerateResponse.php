<?php

namespace Drupal\miniorange_saml_idp;

/**
 * This file is part of miniOrange Drupal SAML IDP module.
 *
 * @package miniOrange
 * @subpackage Plugins
 * @license GNU/GPLv3
 * @copyright Copyright 2015 miniOrange. All Rights Reserved.
 *
 * miniOrange Drupal SAML IDP module is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3
 * of the License, or (at your option) any later version.
 *
 * miniOrange Drupal IDP module is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public
 * License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with miniOrange SAML module. If not, see<http://www.gnu.org/licenses/>.
 */

/**
 * Class GenerateResponse.
 *
 * Generates a SAML response XML with required attributes based on input
 * parameters.
 */
class GenerateResponse {

  /**
   * The XML document to store the SAML response.
   *
   * @var \DOMDocument
   */
  private $xml;

  /**
   * The Assertion Consumer Service URL.
   *
   * @var string
   */
  private $acsUrl;

  /**
   * The issuer of the SAML response.
   *
   * @var string
   */
  private $issuer;

  /**
   * The audience of the SAML response.
   *
   * @var string
   */
  private $audience;
  /**
   * The username associated with the user.
   *
   * @var string
   */
  private $username;
  /**
   * The email address associated with the user.
   *
   * @var string
   */
  private $email;
  /**
   * The service provider's name or ID.
   *
   * @var string
   */
  private $mySp;
  /**
   * The format of the name ID attribute.
   *
   * @var string
   */
  private $nameIdAttrFormat;
  /**
   * The ID of the request to which this response corresponds.
   *
   * @var string|null
   */
  private $inResponseTo;
  /**
   * Flag indicating if the assertion is signed.
   *
   * @var bool|null
   */
  private $miniorangeIdpAssertionSigned;
  /**
   * The subject of the SAML assertion.
   *
   * @var string
   */
  private $subject;

  /**
   * GenerateResponse constructor.
   *
   * @param string $email
   *   The user's email.
   * @param string $username
   *   The user's username.
   * @param string $acs_url
   *   The Assertion Consumer Service URL.
   * @param string $issuer
   *   The issuer of the SAML response.
   * @param string $audience
   *   The audience of the SAML response.
   * @param string|null $inResponseTo
   *   The ID of the request (optional).
   * @param string|null $name_id_attr
   *   The name ID attribute (optional).
   * @param string|null $nameIdAttrFormat
   *   The format of the name ID attribute (optional).
   * @param bool|null $miniorangeIdpAssertionSigned
   *   Flag indicating if the assertion is signed (optional).
   */
  public function __construct($email, $username, $acs_url, $issuer, $audience, $inResponseTo = NULL, $name_id_attr = NULL, $nameIdAttrFormat = NULL, $miniorangeIdpAssertionSigned = NULL) {
    $this->xml = new \DOMDocument("1.0", "utf-8");
    $this->acsUrl = $acs_url;
    $this->issuer = $issuer;
    $this->audience = $audience;
    $this->email = $email;
    $this->username = $username;
    $this->mySp = $name_id_attr;
    $this->nameIdAttrFormat = $nameIdAttrFormat;
    $this->inResponseTo = $inResponseTo;
    $this->miniorangeIdpAssertionSigned = $miniorangeIdpAssertionSigned;
  }

  /**
   * Generates a SAML response XML.
   *
   * Creates and signs (if needed) a SAML response,
   * then returns it as an XML string.
   *
   * @return string
   *   The generated SAML response XML.
   */
  public function createSamlResponse() {

    $config = \Drupal::config('miniorange_saml_idp.settings');

    $response_params = $this->getResponseParams();

    // Create Response Element.
    $resp = $this->createResponseElement($response_params);
    $this->xml->appendChild($resp);

    // Build Issuer.
    $issuer = $this->buildIssuer();
    $resp->appendChild($issuer);

    // Build Status.
    $status = $this->buildStatus();
    $resp->appendChild($status);

    // Build Status Code.
    $statusCode = $this->buildStatusCode();
    $status->appendChild($statusCode);

    // Build Assertion.
    $assertion = $this->buildAssertion($response_params);
    $resp->appendChild($assertion);

    // Sign Assertion.
    if ($this->miniorangeIdpAssertionSigned) {
      $private_key = $config->get('miniorange_saml_idp_private_key');
      $subject_node_in_assertion = $assertion->getElementsByTagName('saml:Subject')->item(0);
      $this->signNode($private_key, $assertion, $subject_node_in_assertion, $response_params);
    }

    $samlResponse = $this->xml->saveXML();

    return $samlResponse;

  }

  /**
   * Retrieves the response parameters for the SAML response.
   *
   * @return array
   *   The response parameters.
   */
  public function getResponseParams() {
    $config = \Drupal::config('miniorange_saml_idp.settings');
    $response_params = [];
    $time = time();
    $response_params['IssueInstant'] = str_replace('+00:00', 'Z', gmdate("c", $time));
    $response_params['NotOnOrAfter'] = str_replace('+00:00', 'Z', gmdate("c", $time + 300));
    $response_params['NotBefore'] = str_replace('+00:00', 'Z', gmdate("c", $time - 30));
    $response_params['AuthnInstant'] = str_replace('+00:00', 'Z', gmdate("c", $time - 120));
    $response_params['SessionNotOnOrAfter'] = str_replace('+00:00', 'Z', gmdate("c", $time + 3600 * 8));
    $response_params['ID'] = $this->generateUniqueId(40);
    $response_params['AssertID'] = $this->generateUniqueId(40);
    $response_params['Issuer'] = $this->issuer;
    $public_key = $config->get('miniorange_saml_idp_x509_certificate');
    $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
    $objKey->loadKey($public_key, FALSE, TRUE);
    $response_params['x509'] = $objKey->getX509Certificate();
    return $response_params;
  }

  /**
   * Creates the SAML response element.
   *
   * @param array $response_params
   *   The response parameters.
   *
   * @return DOMElement
   *   The created SAML response element.
   */
  public function createResponseElement($response_params) {
    $resp = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol', 'samlp:Response');
    $resp->setAttribute('ID', $response_params['ID']);
    $resp->setAttribute('Version', '2.0');
    $resp->setAttribute('IssueInstant', $response_params['IssueInstant']);
    $resp->setAttribute('Destination', $this->acsUrl);
    if (isset($this->inResponseTo) && !is_null($this->inResponseTo)) {
      $resp->setAttribute('InResponseTo', $this->inResponseTo);
    }
    return $resp;
  }

  /**
   * Builds the Issuer element.
   *
   * @return \DOMElement
   *   The created Issuer element.
   */
  public function buildIssuer() {
    $issuer = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Issuer', $this->issuer);
    return $issuer;
  }

  /**
   * Builds the Status element.
   *
   * @return \DOMElement
   *   The created Status element.
   */
  public function buildStatus() {
    $status = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol', 'samlp:Status');
    return $status;
  }

  /**
   * Builds the StatusCode element.
   *
   * @return \DOMElement
   *   The created StatusCode element.
   */
  public function buildStatusCode() {
    $statusCode = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol', 'samlp:StatusCode');
    $statusCode->setAttribute('Value', 'urn:oasis:names:tc:SAML:2.0:status:Success');
    return $statusCode;
  }

  /**
   * Builds the Assertion element.
   *
   * @return \DOMElement
   *   The created Assertion element.
   */
  public function buildAssertion($response_params) {
    $assertion = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Assertion');
    $assertion->setAttribute('ID', $response_params['AssertID']);
    $assertion->setAttribute('IssueInstant', $response_params['IssueInstant']);
    $assertion->setAttribute('Version', '2.0');

    // Build Issuer.
    $issuer = $this->buildIssuer($response_params);
    $assertion->appendChild($issuer);

    // Build Subject.
    $subject = $this->buildSubject($response_params);
    $assertion->appendChild($subject);

    // Build Condition.
    $condition = $this->buildCondition($response_params);
    $assertion->appendChild($condition);

    // Build AuthnStatement.
    $authnstat = $this->buildAuthnStatement($response_params);
    $assertion->appendChild($authnstat);

    return $assertion;
  }

  /**
   * Builds the Subject element.
   *
   * @return \DOMElement
   *   The created Subject element.
   */
  public function buildSubject($response_params) {

    $subject = $this->xml->createElement('saml:Subject');
    $nameid = $this->buildNameIdentifier();

    $subject->appendChild($nameid);
    $confirmation = $this->buildSubjectConfirmation($response_params);
    $subject->appendChild($confirmation);
    return $subject;
  }

  /**
   * Signs the XML node.
   *
   * @param string $private_key
   *   The private key to sign the node.
   * @param \DOMElement $node
   *   The XML node to be signed.
   * @param \DOMElement $subject
   *   The XML node where the signature will be inserted.
   * @param array $response_params
   *   The response parameters, including the X.509 certificate.
   */
  public function signNode($private_key, $node, $subject, $response_params) {
    // Private KEY.
    $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
    // $objKey->loadKey($private_key, TRUE);
    $objKey->loadKey($private_key, FALSE);
    // Sign the Assertion.
    $objXMLSecDSig = new XMLSecurityDSig();
    $objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

    $objXMLSecDSig->addReferenceList(
          [
            $node,
          ],
          XMLSecurityDSig::SHA256,
          [
            'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
            XMLSecurityDSig::EXC_C14N,
          ],
          [
            'id_name' => 'ID',
            'overwrite' => FALSE,
          ],
      );
    $objXMLSecDSig->sign($objKey);
    $objXMLSecDSig->add509Cert($response_params['x509']);
    $objXMLSecDSig->insertSignature($node, $subject);
  }

  /**
   * Builds the NameIdentifier element.
   *
   * @return \DOMElement
   *   The created NameIdentifier element.
   */
  public function buildNameIdentifier() {

    if ($this->mySp === "emailAddress") {
      $nameid = $this->xml->createElement('saml:NameID', $this->email);
    }
    else {
      $nameid = $this->xml->createElement('saml:NameID', $this->username);
    }
    if (empty($this->nameIdAttrFormat)) {
      $nameid->setAttribute('Format', 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress');
    }
    else {
      $nameid->setAttribute('Format', 'urn:oasis:names:tc:SAML:' . $this->nameIdAttrFormat);
    }
    $nameid->setAttribute('SPNameQualifier', $this->audience);

    return $nameid;
  }

  /**
   * Builds the SubjectConfirmation element.
   *
   * @return \DOMElement
   *   The created SubjectConfirmation element.
   */
  public function buildSubjectConfirmation($response_params) {
    $confirmation = $this->xml->createElement('saml:SubjectConfirmation');
    $confirmation->setAttribute('Method', 'urn:oasis:names:tc:SAML:2.0:cm:bearer');
    $confirmationdata = $this->getSubjectConfirmationData($response_params);
    $confirmation->appendChild($confirmationdata);
    return $confirmation;
  }

  /**
   * Generates the SubjectConfirmationData element.
   *
   * @param array $response_params
   *   The response parameters.
   *
   * @return \DOMElement
   *   The generated SubjectConfirmationData element.
   */
  public function getSubjectConfirmationData($response_params) {
    $confirmationdata = $this->xml->createElement('saml:SubjectConfirmationData');
    $confirmationdata->setAttribute('NotOnOrAfter', $response_params['NotOnOrAfter']);
    $confirmationdata->setAttribute('Recipient', $this->acsUrl);
    if (isset($this->inResponseTo) && !is_null($this->inResponseTo)) {
      $confirmationdata->setAttribute('InResponseTo', $this->inResponseTo);
    }
    return $confirmationdata;
  }

  /**
   * Builds the Conditions element.
   *
   * @param array $response_params
   *   The response parameters.
   *
   * @return \DOMElement
   *   The created Conditions element.
   */
  public function buildCondition($response_params) {
    $condition = $this->xml->createElement('saml:Conditions');
    $condition->setAttribute('NotBefore', $response_params['NotBefore']);
    $condition->setAttribute('NotOnOrAfter', $response_params['NotOnOrAfter']);

    // Build AudienceRestriction.
    $audiencer = $this->buildAudienceRestriction();
    $condition->appendChild($audiencer);

    return $condition;
  }

  /**
   * Builds the AudienceRestriction element.
   *
   * @return \DOMElement
   *   The created AudienceRestriction element.
   */
  public function buildAudienceRestriction() {
    $audiencer = $this->xml->createElement('saml:AudienceRestriction');
    $audience = $this->xml->createElement('saml:Audience', $this->audience);
    $audiencer->appendChild($audience);
    return $audiencer;
  }

  /**
   * Builds the AuthnStatement element.
   *
   * @param array $response_params
   *   The response parameters.
   *
   * @return \DOMElement
   *   the created AuthnStatement element.
   */
  public function buildAuthnStatement($response_params) {
    $authnstat = $this->xml->createElement('saml:AuthnStatement');
    $authnstat->setAttribute('AuthnInstant', $response_params['AuthnInstant']);
    $authnstat->setAttribute('SessionIndex', '_' . $this->generateUniqueId(30));
    $authnstat->setAttribute('SessionNotOnOrAfter', $response_params['SessionNotOnOrAfter']);

    $authncontext = $this->xml->createElement('saml:AuthnContext');
    $authncontext_ref = $this->xml->createElement('saml:AuthnContextClassRef', 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport');
    $authncontext->appendChild($authncontext_ref);
    $authnstat->appendChild($authncontext);

    return $authnstat;
  }

  /**
   * Generates a unique ID of the specified length.
   *
   * @param int $length
   *   The length of the unique ID to generate.
   *
   * @return string
   *   The generated unique ID.
   */
  public function generateUniqueId($length) {
    $randomString=bin2hex(random_bytes((int) ceil($length / 2)));
    return substr('a' . $randomString, 0, $length);
  }

}
