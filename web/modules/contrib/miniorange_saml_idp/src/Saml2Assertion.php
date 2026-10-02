<?php

namespace Drupal\miniorange_saml_idp;

/**
 * Class representing a SAML2 Assertion.
 */
class Saml2Assertion {
  /**
   * The ID of the assertion.
   *
   * @var string
   */
  private $id;
  /**
   * The issue instant of the assertion.
   *
   * @var string
   */
  private $issueInstant;
  /**
   * The issuer of the assertion.
   *
   * @var string
   */
  private $issuer;
  /**
   * The NameID of the assertion subject.
   *
   * @var string
   */
  private $nameId;
  /**
   * The encrypted NameID of the assertion subject.
   *
   * @var string
   */
  private $encryptedNameId;
  /**
   * The encrypted attributes in the assertion.
   *
   * @var string
   */
  private $encryptedAttribute;
  /**
   * The key used for encryption.
   *
   * @var string
   */
  private $encryptionKey;
  /**
   * The time before which the assertion is not valid.
   *
   * @var string
   */
  private $notBefore;
  /**
   * The time after which the assertion is not valid.
   *
   * @var string
   */
  private $notOnOrAfter;
  /**
   * List of audiences that are valid for this assertion.
   *
   * @var array
   */
  private $validAudiences;
  /**
   * The session expiration time after which the session is no longer valid.
   *
   * @var string
   */
  private $sessionNotOnOrAfter;
  /**
   * The session index for the assertion.
   *
   * @var string
   */
  private $sessionIndex;
  /**
   * The authentication instant of the assertion.
   *
   * @var string
   */
  private $authnInstant;
  /**
   * The authentication context class reference.
   *
   * @var string
   */
  private $authnContextClassRef;
  /**
   * The authentication context declaration.
   *
   * @var string
   */
  private $authnContextDecl;
  /**
   * The authentication context declaration reference.
   *
   * @var string
   */
  private $authnContextDeclRef;
  /**
   * The authenticating authority for the assertion.
   *
   * @var string
   */
  private $authenticatingAuthority;
  /**
   * The attributes included in the assertion.
   *
   * @var array
   */
  private $attributes;
  /**
   * The name format for the assertion.
   *
   * @var string
   */
  private $nameFormat;
  /**
   * The key used to sign the assertion.
   *
   * @var string
   */
  private $signatureKey;
  /**
   * List of certificates associated with the assertion.
   *
   * @var array
   */
  private $certificates;
  /**
   * The signature data for the assertion.
   *
   * @var string
   */
  private $signatureData;
  /**
   * List of attributes that require encryption.
   *
   * @var array
   */
  private $requiredEncAttributes;
  /**
   * The subject confirmation method for the assertion.
   *
   * @var string
   */
  private $subjectConfirmation;
  /**
   * Indicates whether the assertion was signed at construction.
   *
   * @var bool
   */
  protected $wasSignedAtConstruction = FALSE;

  public function __construct(?DOMElement $xml = NULL) {
    $this->id = Utilities::generateId();
    $this->issueInstant = Utilities::generateTimestamp();
    $this->issuer = '';
    $this->authnInstant = Utilities::generateTimestamp();
    $this->attributes = [];
    $this->nameFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
    $this->certificates = [];
    $this->authenticatingAuthority = [];
    $this->subjectConfirmation = [];
    if ($xml === NULL) {
      return;
    }

    if ($xml->localName === 'EncryptedAssertion') {
      $data = Utilities::xpQuery($xml, './xenc:EncryptedData');
      $encryptedMethod = Utilities::xpQuery($xml, './xenc:EncryptedData/ds:KeyInfo');
      $method = $encryptedMethod[0]->firstChild->firstChild->getAttribute("Algorithm");
      $algo = Utilities::getEncryptionAlgorithm($method);
      if (count($data) === 0) {
        throw new Exception('Missing encrypted data in <saml:EncryptedAssertion>.');
      }
      elseif (count($data) > 1) {
        throw new Exception('More than one encrypted data element in <saml:EncryptedAssertion>.');
      }

      $key = new XMLSecurityKey($algo, ['type' => 'private']);
      $config = \Drupal::config('miniorange_saml_idp.settings');
      $private_key = $config->get('miniorange_saml_idp_private_key');
      $key->loadKey($private_key, FALSE);
      $alternateKey = new XMLSecurityKey($algo, ['type' => 'private']);
      $alternateKeyUrl = plugin_dir_path(__FILE__) . 'resources' . DIRECTORY_SEPARATOR . 'miniorange_sp_priv_key.key';
      $alternateKey->loadKey($alternateKeyUrl, TRUE);
      $blacklist = [];
      $xml = Utilities::decryptElement($data[0], $key, $blacklist, $alternateKey);
    }

    if (!$xml->hasAttribute('ID')) {
      throw new Exception('Missing ID attribute on SAML assertion.');
    }
    $this->id = $xml->getAttribute('ID');
    if ($xml->getAttribute('Version') !== '2.0') {
      /* Currently a very strict check. */
      throw new Exception('Unsupported version: ' . $xml->getAttribute('Version'));
    }

    $this->issueInstant = Utilities::xsDateTimeToTimestamp($xml->getAttribute('IssueInstant'));
    $issuer = Utilities::xpQuery($xml, './saml_assertion:Issuer');
    if (empty($issuer)) {
      throw new Exception('Missing <saml:Issuer> in assertion.');
    }
    $this->issuer = trim($issuer[0]->textContent);
    $this->parseConditions($xml);
    $this->parseAuthnStatement($xml);
    $this->parseAttributes($xml);
    $this->parseEncryptedAttributes($xml);
    $this->parseSignature($xml);
    $this->parseSubject($xml);
  }

  /**
   * Parse subject in assertion.
   *
   * @param DOMElement $xml
   *   The assertion XML element.
   *
   * @throws Exception
   */
  private function parseSubject(DOMElement $xml) {
    $subject = Utilities::xpQuery($xml, './saml_assertion:Subject');
    if (empty($subject)) {
      /* No Subject node. */

      return;
    }
    elseif (count($subject) > 1) {
      throw new Exception('More than one <saml:Subject> in <saml:Assertion>.');
    }

    $subject = $subject[0];
    $nameId = Utilities::xpQuery($subject, './saml_assertion:NameID | ./saml_assertion:EncryptedID/xenc:EncryptedData');
    if (empty($nameId)) {
      throw new Exception('Missing <saml:NameID> or <saml:EncryptedID> in <saml:Subject>.');
    }
    elseif (count($nameId) > 1) {
      throw new Exception('More than one <saml:NameID> or <saml:EncryptedD> in <saml:Subject>.');
    }
    $nameId = $nameId[0];
    if ($nameId->localName === 'EncryptedData') {
      /* The NameID element is encrypted. */
      $this->encryptedNameId = $nameId;
    }
    else {
      $this->nameId = Utilities::parseNameId($nameId);
    }
  }

  /**
   * Parse conditions in assertion.
   *
   * @param DOMElement $xml
   *   The assertion XML element.
   *
   * @throws Exception
   */
  private function parseConditions(DOMElement $xml) {
    $conditions = Utilities::xpQuery($xml, './saml_assertion:Conditions');
    if (empty($conditions)) {
      /* No <saml:Conditions> node. */

      return;
    }
    elseif (count($conditions) > 1) {
      throw new Exception('More than one <saml:Conditions> in <saml:Assertion>.');
    }
    $conditions = $conditions[0];
    if ($conditions->hasAttribute('NotBefore')) {
      $notBefore = Utilities::xsDateTimeToTimestamp($conditions->getAttribute('NotBefore'));
      if ($this->notBefore === NULL || $this->notBefore < $notBefore) {
        $this->notBefore = $notBefore;
      }
    }
    if ($conditions->hasAttribute('NotOnOrAfter')) {
      $notOnOrAfter = Utilities::xsDateTimeToTimestamp($conditions->getAttribute('NotOnOrAfter'));
      if ($this->notOnOrAfter === NULL || $this->notOnOrAfter > $notOnOrAfter) {
        $this->notOnOrAfter = $notOnOrAfter;
      }
    }

    for ($node = $conditions->firstChild; $node !== NULL; $node = $node->nextSibling) {
      if ($node instanceof DOMText) {
        continue;
      }
      if ($node->namespaceURI !== 'urn:oasis:names:tc:SAML:2.0:assertion') {
        throw new Exception('Unknown namespace of condition: ' . var_export($node->namespaceURI, TRUE));
      }
      switch ($node->localName) {
        case 'AudienceRestriction':
          $audiences = Utilities::extractStrings($node, 'urn:oasis:names:tc:SAML:2.0:assertion', 'Audience');
          if ($this->validAudiences === NULL) {
            /* The first (and probably last) AudienceRestriction element. */
            $this->validAudiences = $audiences;
          }
          else {/*
             * The set of AudienceRestriction are ANDed together, so we need
             * the subset that are present in all of them.
             */
            $this->validAudiences = array_intersect($this->validAudiences, $audiences);
          }

          break;

        case 'OneTimeUse':
          /* Currently ignored. */

          break;

        case 'ProxyRestriction':
          /* Currently ignored. */

          break;

        default:
          throw new Exception('Unknown condition: ' . var_export($node->localName, TRUE));
      }
    }
  }

  /**
   * Parse AuthnStatement in assertion.
   *
   * @param DOMElement $xml
   *   The assertion XML element.
   *
   * @throws Exception
   */
  private function parseAuthnStatement(DOMElement $xml) {
    $authnStatements = Utilities::xpQuery($xml, './saml_assertion:AuthnStatement');
    if (empty($authnStatements)) {
      $this->authnInstant = NULL;
      return;
    }
    elseif (count($authnStatements) > 1) {
      throw new Exception('More that one <saml:AuthnStatement> in <saml:Assertion> not supported.');
    }
    $authnStatement = $authnStatements[0];
    if (!$authnStatement->hasAttribute('AuthnInstant')) {
      throw new Exception('Missing required AuthnInstant attribute on <saml:AuthnStatement>.');
    }
    $this->authnInstant = Utilities::xsDateTimeToTimestamp($authnStatement->getAttribute('AuthnInstant'));
    if ($authnStatement->hasAttribute('SessionNotOnOrAfter')) {
      $this->sessionNotOnOrAfter = Utilities::xsDateTimeToTimestamp($authnStatement->getAttribute('SessionNotOnOrAfter'));
    }

    if ($authnStatement->hasAttribute('SessionIndex')) {
      $this->sessionIndex = $authnStatement->getAttribute('SessionIndex');
    }

    $this->parseAuthnContext($authnStatement);
  }

  /**
   * Parse AuthnContext in AuthnStatement.
   *
   * @param DOMElement $authnStatementEl
   *   The AuthnStatement element to parse.
   *
   * @throws Exception
   *   If there is an issue with the AuthnContext or its elements.
   */
  private function parseAuthnContext(DOMElement $authnStatementEl) {
    // Get the AuthnContext element.
    $authnContexts = Utilities::xpQuery($authnStatementEl, './saml_assertion:AuthnContext');
    if (count($authnContexts) > 1) {
      throw new Exception('More than one <saml:AuthnContext> in <saml:AuthnStatement>.');
    }
    elseif (empty($authnContexts)) {
      throw new Exception('Missing required <saml:AuthnContext> in <saml:AuthnStatement>.');
    }
    $authnContextEl = $authnContexts[0];
    // Get the AuthnContextDeclRef (if available)
    $authnContextDeclRefs = Utilities::xpQuery($authnContextEl, './saml_assertion:AuthnContextDeclRef');
    if (count($authnContextDeclRefs) > 1) {
      throw new Exception('More than one <saml:AuthnContextDeclRef> found?');
    }
    elseif (count($authnContextDeclRefs) === 1) {
      $this->setAuthnContextDeclRef(trim($authnContextDeclRefs[0]->textContent));
    }

    // Get the AuthnContextDecl (if available)
    $authnContextDecls = Utilities::xpQuery($authnContextEl, './saml_assertion:AuthnContextDecl');
    if (count($authnContextDecls) > 1) {
      throw new Exception('More than one <saml:AuthnContextDecl> found?');
    }
    elseif (count($authnContextDecls) === 1) {
      $this->setAuthnContextDecl(new SAML2_XML_Chunk($authnContextDecls[0]));
    }

    // Get the AuthnContextClassRef (if available)
    $authnContextClassRefs = Utilities::xpQuery($authnContextEl, './saml_assertion:AuthnContextClassRef');
    if (count($authnContextClassRefs) > 1) {
      throw new Exception('More than one <saml:AuthnContextClassRef> in <saml:AuthnContext>.');
    }
    elseif (count($authnContextClassRefs) === 1) {
      $this->setAuthnContextClassRef(trim($authnContextClassRefs[0]->textContent));
    }

    // Constraint from XSD: MUST have one of the three.
    if (empty($this->authnContextClassRef) && empty($this->authnContextDecl) && empty($this->authnContextDeclRef)) {
      throw new Exception('Missing either <saml:AuthnContextClassRef> or <saml:AuthnContextDeclRef> or <saml:AuthnContextDecl>');
    }

    $this->authenticatingAuthority = Utilities::extractStrings($authnContextEl, 'urn:oasis:names:tc:SAML:2.0:assertion', 'AuthenticatingAuthority');
  }

  /**
   * Parse attribute statements in assertion.
   *
   * @param DOMElement $xml
   *   The XML element with the assertion.
   *
   * @throws Exception
   */
  private function parseAttributes(DOMElement $xml) {
    $firstAttribute = TRUE;
    $attributes = Utilities::xpQuery($xml, './saml_assertion:AttributeStatement/saml_assertion:Attribute');
    foreach ($attributes as $attribute) {
      if (!$attribute->hasAttribute('Name')) {
        throw new Exception('Missing name on <saml:Attribute> element.');
      }
      $name = $attribute->getAttribute('Name');
      if ($attribute->hasAttribute('NameFormat')) {
        $nameFormat = $attribute->getAttribute('NameFormat');
      }
      else {
        $nameFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
      }

      if ($firstAttribute) {
        $this->nameFormat = $nameFormat;
        $firstAttribute = FALSE;
      }
      else {
        if ($this->nameFormat !== $nameFormat) {
          $this->nameFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
        }
      }

      if (!array_key_exists($name, $this->attributes)) {
        $this->attributes[$name] = [];
      }

      $values = Utilities::xpQuery($attribute, './saml_assertion:AttributeValue');
      foreach ($values as $value) {
        $this->attributes[$name][] = trim($value->textContent);
      }
    }
  }

  /**
   * Parse encrypted attribute statements in assertion.
   *
   * @param DOMElement $xml
   *   The XML element with the assertion.
   */
  private function parseEncryptedAttributes(DOMElement $xml) {
    $this->encryptedAttribute = Utilities::xpQuery($xml, './saml_assertion:AttributeStatement/saml_assertion:EncryptedAttribute');
  }

  /**
   * Parse signature on assertion.
   *
   * @param DOMElement $xml
   *   The assertion XML element.
   */
  private function parseSignature(DOMElement $xml) {
    /* Validate the signature element of the message. */
    $sig = Utilities::validateElement($xml);
    if ($sig !== FALSE) {
      $this->wasSignedAtConstruction = TRUE;
      $this->certificates = $sig['Certificates'];
      $this->signatureData = $sig;
    }
  }

  /**
   * Set the authentication context declaration.
   *
   * @param \SAML2_XML_Chunk $authnContextDecl
   *   The authentication context declaration object.
   *
   * @throws Exception
   *   If both AuthnContextDeclRef and AuthnContextDecl are set.
   */
  public function setAuthnContextDecl(SAML2_XML_Chunk $authnContextDecl) {
    if (!empty($this->authnContextDeclRef)) {
      throw new Exception('AuthnContextDeclRef is already registered! May only have either a Decl or a DeclRef, not both!');
    }

    $this->authnContextDecl = $authnContextDecl;
  }

  /**
   * Set the authentication context declaration reference.
   *
   * @param string $authnContextDeclRef
   *   The authentication context declaration reference string.
   *
   * @throws Exception
   *   If both AuthnContextDeclRef and AuthnContextDecl are set.
   */
  public function setAuthnContextDeclRef($authnContextDeclRef) {
    if (!empty($this->authnContextDecl)) {
      throw new Exception('AuthnContextDecl is already registered! May only have either a Decl or a DeclRef, not both!');
    }

    $this->authnContextDeclRef = $authnContextDeclRef;
  }

  /**
   * Convert this assertion to an XML element.
   *
   * @param DOMNode|null $parentElement
   *   The DOM node the assertion should be created in.
   *
   * @return DOMElement
   *   This assertion.
   */
  public function toXml(?DOMNode $parentElement = NULL) {
    if ($parentElement === NULL) {
      $document = new DOMDocument();
      $parentElement = $document;
    }
    else {
      $document = $parentElement->ownerDocument;
    }

    $root = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:' . 'Assertion');
    $parentElement->appendChild($root);
    /* Ugly hack to add another namespace declaration to the root element. */
    $root->setAttributeNS('urn:oasis:names:tc:SAML:2.0:protocol', 'samlp:tmp', 'tmp');
    $root->removeAttributeNS('urn:oasis:names:tc:SAML:2.0:protocol', 'tmp');
    $root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:tmp', 'tmp');
    $root->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'tmp');
    $root->setAttributeNS('http://www.w3.org/2001/XMLSchema', 'xs:tmp', 'tmp');
    $root->removeAttributeNS('http://www.w3.org/2001/XMLSchema', 'tmp');
    $root->setAttribute('ID', $this->id);
    $root->setAttribute('Version', '2.0');
    $root->setAttribute('IssueInstant', gmdate('Y-m-d\TH:i:s\Z', $this->issueInstant));
    $issuer = Utilities::addString($root, 'urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Issuer', $this->issuer);
    $this->addSubject($root);
    $this->addConditions($root);
    $this->addAuthnStatement($root);
    if ($this->requiredEncAttributes == FALSE) {
      $this->addAttributeStatement($root);
    }
    else {
      $this->addEncryptedAttributeStatement($root);
    }

    if ($this->signatureKey !== NULL) {
      Utilities::insertSignature($this->signatureKey, $this->certificates, $root, $issuer->nextSibling);
    }

    return $root;
  }

  /**
   * Add a Subject-node to the assertion.
   *
   * @param DOMElement $root
   *   The assertion element we should add the subject to.
   */
  private function addSubject(DOMElement $root) {
    if ($this->nameId === NULL && $this->encryptedNameId === NULL) {
      /* We don't have anything to create a Subject node for. */

      return;
    }

    $subject = $root->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Subject');
    $root->appendChild($subject);
    if ($this->encryptedNameId === NULL) {
      Utilities::addNameId($subject, $this->nameId);
    }
    else {
      $eid = $subject->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:' . 'EncryptedID');
      $subject->appendChild($eid);
      $eid->appendChild($subject->ownerDocument->importNode($this->encryptedNameId, TRUE));
    }

    foreach ($this->subjectConfirmation as $sc) {
      $sc->toXml($subject);
    }
  }

  /**
   * Add a Conditions-node to the assertion.
   *
   * @param DOMElement $root
   *   The assertion element we should add the conditions to.
   */
  private function addConditions(DOMElement $root) {
    $document = $root->ownerDocument;
    $conditions = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Conditions');
    $root->appendChild($conditions);
    if ($this->notBefore !== NULL) {
      $conditions->setAttribute('NotBefore', gmdate('Y-m-d\TH:i:s\Z', $this->notBefore));
    }
    if ($this->notOnOrAfter !== NULL) {
      $conditions->setAttribute('NotOnOrAfter', gmdate('Y-m-d\TH:i:s\Z', $this->notOnOrAfter));
    }

    if ($this->validAudiences !== NULL) {
      $ar = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AudienceRestriction');
      $conditions->appendChild($ar);
      Utilities::addStrings($ar, 'urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Audience', FALSE, $this->validAudiences);
    }
  }

  /**
   * Add a AuthnStatement-node to the assertion.
   *
   * @param DOMElement $root
   *   The assertion element we should add the authentication statement to.
   */
  private function addAuthnStatement(DOMElement $root) {
    if ($this->authnInstant === NULL ||
          ($this->authnContextClassRef === NULL &&
              $this->authnContextDecl === NULL &&
              $this->authnContextDeclRef === NULL)) {
      /* No authentication context or AuthnInstant => no authentication statement. */

      return;
    }

    $document = $root->ownerDocument;
    $authnStatementEl = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AuthnStatement');
    $root->appendChild($authnStatementEl);
    $authnStatementEl->setAttribute('AuthnInstant', gmdate('Y-m-d\TH:i:s\Z', $this->authnInstant));
    if ($this->sessionNotOnOrAfter !== NULL) {
      $authnStatementEl->setAttribute('SessionNotOnOrAfter', gmdate('Y-m-d\TH:i:s\Z', $this->sessionNotOnOrAfter));
    }
    if ($this->sessionIndex !== NULL) {
      $authnStatementEl->setAttribute('SessionIndex', $this->sessionIndex);
    }

    $authnContextEl = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AuthnContext');
    $authnStatementEl->appendChild($authnContextEl);
    if (!empty($this->authnContextClassRef)) {
      Utilities::addString($authnContextEl, 'urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AuthnContextClassRef', $this->authnContextClassRef);
    }
    if (!empty($this->authnContextDecl)) {
      $this->authnContextDecl->toXml($authnContextEl);
    }
    if (!empty($this->authnContextDeclRef)) {
      Utilities::addString($authnContextEl, 'urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AuthnContextDeclRef', $this->authnContextDeclRef);
    }

    Utilities::addStrings($authnContextEl, 'urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AuthenticatingAuthority', FALSE, $this->authenticatingAuthority);
  }

  /**
   * Add an AttributeStatement-node to the assertion.
   *
   * @param DOMElement $root
   *   The assertion element we should add the subject to.
   */
  private function addAttributeStatement(DOMElement $root) {
    if (empty($this->attributes)) {
      return;
    }

    $document = $root->ownerDocument;
    $attributeStatement = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AttributeStatement');
    $root->appendChild($attributeStatement);
    foreach ($this->attributes as $name => $values) {
      $attribute = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Attribute');
      $attributeStatement->appendChild($attribute);
      $attribute->setAttribute('Name', $name);
      if ($this->nameFormat !== 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified') {
        $attribute->setAttribute('NameFormat', $this->nameFormat);
      }

      foreach ($values as $value) {
        if (is_string($value)) {
          $type = 'xs:string';
        }
        elseif (is_int($value)) {
          $type = 'xs:integer';
        }
        else {
          $type = NULL;
        }

        $attributeValue = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AttributeValue');
        $attribute->appendChild($attributeValue);
        if ($type !== NULL) {
          $attributeValue->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', $type);
        }
        if (is_null($value)) {
          $attributeValue->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:nil', 'true');
        }

        if ($value instanceof DOMNodeList) {
          for ($i = 0; $i < $value->length; $i++) {
            $node = $document->importNode($value->item($i), TRUE);
            $attributeValue->appendChild($node);
          }
        }
        else {
          $attributeValue->appendChild($document->createTextNode($value));
        }
      }
    }
  }

  /**
   * Add an EncryptedAttribute Statement-node to the assertion.
   *
   * @param DOMElement $root
   *   The assertion element we should add the Encrypted Attribute Statement to.
   */
  private function addEncryptedAttributeStatement(DOMElement $root) {
    if ($this->requiredEncAttributes == FALSE) {
      return;
    }

    $document = $root->ownerDocument;
    $attributeStatement = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AttributeStatement');
    $root->appendChild($attributeStatement);
    foreach ($this->attributes as $name => $values) {
      $document2 = new DOMDocument();
      $attribute = $document2->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Attribute');
      $attribute->setAttribute('Name', $name);
      $document2->appendChild($attribute);
      if ($this->nameFormat !== 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified') {
        $attribute->setAttribute('NameFormat', $this->nameFormat);
      }

      foreach ($values as $value) {
        if (is_string($value)) {
          $type = 'xs:string';
        }
        elseif (is_int($value)) {
          $type = 'xs:integer';
        }
        else {
          $type = NULL;
        }

        $attributeValue = $document2->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AttributeValue');
        $attribute->appendChild($attributeValue);
        if ($type !== NULL) {
          $attributeValue->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', $type);
        }

        if ($value instanceof DOMNodeList) {
          for ($i = 0; $i < $value->length; $i++) {
            $node = $document2->importNode($value->item($i), TRUE);
            $attributeValue->appendChild($node);
          }
        }
        else {
          $attributeValue->appendChild($document2->createTextNode($value));
        }
      }
      /*Once the attribute nodes are built, the are encrypted*/
      $encAssert = new XMLSecEnc();
      $encAssert->setNode($document2->documentElement);
      $encAssert->type = 'http://www.w3.org/2001/04/xmlenc#Element';
      /*
       * Attributes are encrypted with a session key and this one with
       * $EncryptionKey
       */
      $symmetricKey = new XMLSecurityKey(XMLSecurityKey::AES256_CBC);
      $symmetricKey->generateSessionKey();
      $encAssert->encryptKey($this->encryptionKey, $symmetricKey);
      $encrNode = $encAssert->encryptNode($symmetricKey);
      $encAttribute = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:EncryptedAttribute');
      $attributeStatement->appendChild($encAttribute);
      $n = $document->importNode($encrNode, TRUE);
      $encAttribute->appendChild($n);
    }
  }

}
