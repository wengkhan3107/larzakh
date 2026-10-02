<?php

namespace Drupal\miniorange_saml_idp;

/**
 * Class MetadataReader is responsible for reading and parsing metadata.
 */
class MetadataReader {
  /**
   * ServiceProvider array.
   *
   * @var array
   * An array of ServiceProvider objects.
   */
  private $serviceProviders;

  /**
   * MetadataReader constructor.
   *
   * @param \DOMNode|null $xml
   *   The XML DOM node to parse for metadata.
   */
  public function __construct(?\DOMNode $xml = NULL) {
    $this->serviceProviders = [];
    $entityDescriptors = Utilities::xpQuery($xml, './saml_metadata:EntityDescriptor');
    foreach ($entityDescriptors as $entityDescriptor) {
      $spSsoDescriptor = Utilities::xpQuery($entityDescriptor, './saml_metadata:SPSSODescriptor');
      if (isset($spSsoDescriptor) && !empty($spSsoDescriptor)) {
        array_push($this->serviceProviders, new ServiceProviders($entityDescriptor));
      }
    }
  }

  /**
   * Returns the list of service providers.
   *
   * @return array
   *   An array of ServiceProvider objects.
   */
  public function getServiceProviders() {
    return $this->serviceProviders;
  }

}

/**
 * Class ServiceProviders represents a SP's metadata in the XML document.
 */
class ServiceProviders {
  /**
   * Unique identifier for the service provider.
   *
   * @var string
   * The entity ID of the service provider.
   */
  private $entityID;

  /**
   * URL where the service provider expects to receive SAML assertions.
   *
   * @var string
   * The Assertion Consumer Service (ACS) URL for the service provider.
   */
  private $acsURL;

  /**
   * Indicating whether the assertions must be signed.
   *
   * @var string
   * Whether the assertions from the service provider should be signed.
   */
  private $assertionsSigned;

  /**
   * ServiceProviders constructor.
   *
   * @param \DOMElement|null $xml
   *   The XML element containing the service provider metadata.
   */
  public function __construct(?\DOMElement $xml = NULL) {
    if ($xml->hasAttribute('entityID')) {
      $this->entityID = $xml->getAttribute('entityID');
    }

    $spSsoDescriptor = Utilities::xpQuery($xml, './saml_metadata:SPSSODescriptor');
    if (count($spSsoDescriptor) > 1) {
      throw new Exception('More than one <SPSSODescriptor> in <EntityDescriptor>.');
    }
    elseif (empty($spSsoDescriptor)) {
      throw new Exception('Missing required <SPSSODescriptor> in <EntityDescriptor>.');
    }

    $this->parseAcsUrl($spSsoDescriptor);
    $this->assertionsSigned($spSsoDescriptor);
  }

  /**
   * Parses the Assertion Consumer Service (ACS) URL.
   *
   * @param array $spSsoDescriptor
   *   An array of <SPSSODescriptor> elements.
   */
  private function parseAcsUrl($spSsoDescriptor) {
    $assertionConsumerService = Utilities::xpQuery($spSsoDescriptor[0], './saml_metadata:AssertionConsumerService');
    foreach ($assertionConsumerService as $sign) {
      if ($sign->hasAttribute('Location')) {
        $this->acsURL = $sign->getAttribute('Location');
      }
    }
  }

  /**
   * Sets whether assertions are signed.
   *
   * @param array $spSsoDescriptor
   *   An array of <SPSSODescriptor> elements.
   */
  private function assertionsSigned($spSsoDescriptor) {
    foreach ($spSsoDescriptor as $sign) {
      if ($sign->hasAttribute('WantAssertionsSigned')) {
        $this->assertionsSigned = $sign->getAttribute('WantAssertionsSigned');
      }
    }
  }

  /**
   * Gets the entity ID of the service provider.
   *
   * @return string
   *   The entity ID of the service provider.
   */
  public function getEntityId() {
    return $this->entityID;
  }

  /**
   * Gets the ACS URL of the service provider.
   *
   * @return string
   *   The ACS URL.
   */
  public function getAcsUrl() {
    return $this->acsURL;
  }

  /**
   * Gets whether the service provider's assertions are signed.
   *
   * @return string
   *   The value of the 'WantAssertionsSigned' attribute.
   */
  public function getAssertionsSigned() {
    return $this->assertionsSigned;
  }

}
