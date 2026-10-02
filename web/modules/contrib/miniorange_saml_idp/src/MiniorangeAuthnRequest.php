<?php

namespace Drupal\miniorange_saml_idp;

/**
 * Class representing the AuthnRequest for the MiniOrange SAML IDP.
 */
class MiniorangeAuthnRequest {
  /**
   * The NameID policy used in the AuthnRequest.
   *
   * @var array
   */
  private $nameIdPolicy;
  /**
   * Flag indicating whether ForceAuthn is enabled.
   *
   * @var bool
   */
  private $forceAuthn;
  /**
   * Flag indicating whether the request is passive.
   *
   * @var bool
   */
  private $isPassive;
  /**
   * List of requester IDs associated with the request.
   *
   * @var array
   */
  private $requesterID = [];
  /**
   * The URL of the Assertion Consumer Service (ACS).
   *
   * @var string|null
   */
  private $assertionConsumerServiceURL;
  /**
   * The protocol binding used in the AuthnRequest.
   *
   * @var string|null
   */
  private $protocolBinding;
  /**
   * The requested authentication context.
   *
   * @var array|null
   */
  private $requestedAuthnContext;
  /**
   * The namespace URI associated with the request.
   *
   * @var string|null
   */
  private $namespaceURI;
  /**
   * The destination URL where the request is being sent.
   *
   * @var string|null
   */
  private $destination;
  /**
   * The issuer of the AuthnRequest.
   *
   * @var string|null
   */
  private $issuer;
  /**
   * The version of the request.
   *
   * @var string|null
   */
  private $version;
  /**
   * The timestamp of when the request was issued.
   *
   * @var string|null
   */
  private $issueInstant;
  /**
   * The unique identifier for the request.
   *
   * @var string|null
   */
  private $requestID;

  /**
   * MiniorangeAuthnRequest constructor.
   *
   * @param \DOMElement|null $xml
   *   The XML element to parse for AuthnRequest.
   */
  public function __construct(?\DOMElement $xml = NULL) {
    $this->nameIdPolicy = [];
    $this->forceAuthn = FALSE;
    $this->isPassive = FALSE;
    if ($xml === NULL) {
      return;
    }
    $this->forceAuthn = Utilities::parseBoolean($xml, 'ForceAuthn', FALSE);
    $this->isPassive = Utilities::parseBoolean($xml, 'IsPassive', FALSE);
    if ($xml->hasAttribute('AssertionConsumerServiceURL')) {
      $this->assertionConsumerServiceURL = $xml->getAttribute('AssertionConsumerServiceURL');
    }
    if ($xml->hasAttribute('ProtocolBinding')) {
      $this->protocolBinding = $xml->getAttribute('ProtocolBinding');
    }
    if ($xml->hasAttribute('AttributeConsumingServiceIndex')) {
      $this->attributeConsumingServiceIndex = (int) $xml->getAttribute('AttributeConsumingServiceIndex');
    }
    if ($xml->hasAttribute('AssertionConsumerServiceIndex')) {
      $this->assertionConsumerServiceIndex = (int) $xml->getAttribute('AssertionConsumerServiceIndex');
    }
    if ($xml->hasAttribute('Destination')) {
      $this->destination = $xml->getAttribute('Destination');
    }
    if (isset($xml->namespaceURI)) {
      $this->namespaceURI = $xml->namespaceURI;
    }
    if ($xml->hasAttribute('Version')) {
      $this->version = $xml->getAttribute('Version');
    }
    if ($xml->hasAttribute('IssueInstant')) {
      $this->issueInstant = $xml->getAttribute('IssueInstant');
    }
    if ($xml->hasAttribute('ID')) {
      $this->requestID = $xml->getAttribute('ID');
    }

    $this->parseNameIdPolicy($xml);
    $this->parseIssuer($xml);
    $this->parseRequestedAuthnContext($xml);
    $this->parseScoping($xml);
  }

  /**
   * Gets the version of the request.
   *
   * @return string|null
   *   The version of the request.
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * Gets the request ID.
   *
   * @return string|null
   *   The request ID.
   */
  public function getRequestId() {
    return $this->requestID;
  }

  /**
   * Gets the issue instant timestamp of the request.
   *
   * @return string|null
   *   The issue instant timestamp.
   */
  public function getIssueInstant() {
    return $this->issueInstant;
  }

  /**
   * Gets the destination URL of the request.
   *
   * @return string|null
   *   The destination URL.
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * Gets the issuer of the request.
   *
   * @return string|null
   *   The issuer.
   */
  public function getIssuer() {
    return $this->issuer;
  }

  /**
   * Gets the Assertion Consumer Service URL.
   *
   * @return string|null
   *   The ACS URL.
   */
  public function getAssertionConsumerServiceUrl() {
    return $this->assertionConsumerServiceURL;
  }

  /**
   * Parses the Issuer element from the XML.
   *
   * @param \DOMElement $xml
   *   The XML element to parse the issuer from.
   */
  protected function parseIssuer(\DOMElement $xml) {
    $issuer = Utilities::xpQuery($xml, './saml_assertion:Issuer');
    if (empty($issuer)) {
      throw new Exception('Missing <saml:Issuer> in assertion.');
    }
    $this->issuer = trim($issuer[0]->textContent);
  }

  /**
   * Parses the NameIDPolicy element from the XML.
   *
   * @param \DOMElement $xml
   *   The XML element to parse the NameIDPolicy from.
   */
  protected function parseNameIdPolicy(\DOMElement $xml) {
    $nameIdPolicy = Utilities::xpQuery($xml, './saml_protocol:NameIDPolicy');
    if (empty($nameIdPolicy)) {
      return;
    }
    $nameIdPolicy = $nameIdPolicy[0];
    if ($nameIdPolicy->hasAttribute('Format')) {
      $this->nameIdPolicy['Format'] = $nameIdPolicy->getAttribute('Format');
    }
    if ($nameIdPolicy->hasAttribute('SPNameQualifier')) {
      $this->nameIdPolicy['SPNameQualifier'] = $nameIdPolicy->getAttribute('SPNameQualifier');
    }
    if ($nameIdPolicy->hasAttribute('AllowCreate')) {
      $this->nameIdPolicy['AllowCreate'] = Utilities::parseBoolean($nameIdPolicy, 'AllowCreate', FALSE);
    }
  }

  /**
   * Parses the RequestedAuthnContext element from the XML.
   *
   * @param \DOMElement $xml
   *   The XML element to parse the RequestedAuthnContext from.
   */
  protected function parseRequestedAuthnContext(\DOMElement $xml) {
    $requestedAuthnContext = Utilities::xpQuery($xml, './saml_protocol:RequestedAuthnContext');
    if (empty($requestedAuthnContext)) {
      return;
    }
    $requestedAuthnContext = $requestedAuthnContext[0];
    $rac = ['AuthnContextClassRef' => [], 'Comparison' => 'exact'];
    $accr = Utilities::xpQuery($requestedAuthnContext, './saml_assertion:AuthnContextClassRef');
    foreach ($accr as $i) {
      $rac['AuthnContextClassRef'][] = trim($i->textContent);
    }
    if ($requestedAuthnContext->hasAttribute('Comparison')) {
      $rac['Comparison'] = $requestedAuthnContext->getAttribute('Comparison');
    }
    $this->requestedAuthnContext = $rac;
  }

  /**
   * Parses the Scoping element from the XML.
   *
   * @param \DOMElement $xml
   *   The XML element to parse the Scoping from.
   */
  protected function parseScoping(\DOMElement $xml) {
    $scoping = Utilities::xpQuery($xml, './saml_protocol:Scoping');
    if (empty($scoping)) {
      return;
    }
    $scoping = $scoping[0];
    if ($scoping->hasAttribute('ProxyCount')) {
      $this->ProxyCount = (int) $scoping->getAttribute('ProxyCount');
    }
    $idpEntries = Utilities::xpQuery($scoping, './saml_protocol:IDPList/saml_protocol:IDPEntry');
    foreach ($idpEntries as $idpEntry) {
      if (!$idpEntry->hasAttribute('ProviderID')) {
        throw new Exception("Could not get ProviderID from Scoping/IDPEntry element in AuthnRequest object");
      }
      $this->IDPList[] = $idpEntry->getAttribute('ProviderID');
    }
    $requesterIDs = Utilities::xpQuery($scoping, './saml_protocol:RequesterID');
    foreach ($requesterIDs as $requesterID) {
      $this->RequesterID[] = trim($requesterID->textContent);
    }
  }

}
