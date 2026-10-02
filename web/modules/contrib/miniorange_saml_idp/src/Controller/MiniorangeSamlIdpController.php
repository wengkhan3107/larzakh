<?php

namespace Drupal\miniorange_saml_idp\Controller;

use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Drupal\miniorange_saml_idp\GenerateResponse;
use Drupal\miniorange_saml_idp\MiniorangeAuthnRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Html;
use Drupal\miniorange_saml_idp\Utilities;
use Drupal\Core\Render\Markup;

/**
 * Class MiniorangeSamlIdpController.
 *
 * Controller for handling SAML IDP (Identity Provider)
 * functionalities in the miniOrange SAML module.
 */
class MiniorangeSamlIdpController extends ControllerBase {

   /**
    * The configuration object for the SAML IDP settings.
    *
    * @var \Drupal\Core\Config\Config
    * the configuration object for accessing SAML IDP settings stored in the Drupal configuration system.
    */
  private $config;

  /**
   * Form builder service for dynamically creating forms.
   *
   * @var \Drupal\Core\Form\FormBuilder
   * Form builder service for creating forms dynamically.
   */
  protected $formBuilder;

  /**
   * MiniorangeSamlIdpController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder|null $formBuilder
   *   A FormBuilder service object.
   */

  public function __construct(ConfigFactoryInterface $config_factory,?FormBuilder $formBuilder = NULL) {
    $this->config = $config_factory->get('miniorange_saml_idp.settings');
    $this->formBuilder = $formBuilder;
  }

  /**
   * Factory method to create a controller instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container service.
   *
   * @return static
   *   The controller instance.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'),
    $container->get("form_builder"));
  }

  /**
   * Handles feedback and opens the modal form for removing a license.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object containing the modal form.
   */
  public function openModalForm() {
    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_saml_idp\Form\MiniorangeSamlRemoveLicense');
    $response->addCommand(new OpenModalDialogCommand('Remove Account', $modal_form, ['width' => '800']));
    return $response;
  }

  /**
   * This function is used to get the timestamp value.
   *
   * @return string
   *   The timestamp value.
   */
  public function getOauthTimestamp() {
    $url = 'https://login.xecurify.com/moas/rest/mobile/get-timestamp';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    // Required for https urls.
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    $content = curl_exec($ch);
    if (curl_errno($ch)) {
       Utilities::sendErrorResponse('Error in sending curl Request');
    }
    curl_close($ch);
    if (empty($content)) {
      $currentTimeInMillis = round(microtime(TRUE) * 1000);
      $currentTimeInMillis = number_format($currentTimeInMillis, 0, '', '');
    }
    return empty($content) ? $currentTimeInMillis : $content;
  }

  /**
   * Generates and outputs the SAML IDP metadata.
   */
  public function miniorangeSamlIdpMetadata() {
    self::generateMetadata();
  }

  /**
   * Generates SAML metadata and optionally provides it for download.
   *
   * @param bool $download
   *   Whether the metadata should be downloaded or just displayed.
   */
  public function generateMetadata($download = FALSE) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $site_url = $base_url . '/';
    $entity_id = $site_url . '?q=admin/config/people/miniorange_saml_idp/';
    $login_url = $site_url . 'initiatelogon';
    $logout_url = $site_url;
    define('DRUPAL_BASE_ROOT', dirname(__FILE__));
    $certificate_raw = $this->config->get('miniorange_saml_idp_x509_certificate');
    $certificate = preg_replace("/[\r\n]+/", "", $certificate_raw);
    $certificate = str_replace("-----BEGIN CERTIFICATE-----", "", $certificate);
    $certificate = str_replace("-----END CERTIFICATE-----", "", $certificate);
    $certificate = str_replace(" ", "", $certificate);
    if ($download === 'certificate') {
      header('Content-Disposition: attachment; filename="idp-certificate.crt"');
      echo $certificate_raw;
      exit;
    }

    if ($download) {
      header('Content-Disposition: attachment; filename="Metadata.xml"');
    }
    else {
      header('Content-Type: text/xml');
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="' . $entity_id . '">
    <md:IDPSSODescriptor WantAuthnRequestsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:KeyDescriptor use="signing">
            <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
                <ds:X509Data>
                    <ds:X509Certificate>' . $certificate . '</ds:X509Certificate>
                </ds:X509Data>
            </ds:KeyInfo>
        </md:KeyDescriptor>
        <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified</md:NameIDFormat>
        <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="' . $login_url . '"/>
        <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="' . $login_url . '"/>
    </md:IDPSSODescriptor>
</md:EntityDescriptor>';
    exit;
  }

  /**
   * Downloads the SAML IDP metadata as an XML file.
   */
  public function miniorangeSamlIdpMetadataDownload() {
    self::generateMetadata(TRUE);
  }

  /**
   * Downloads the SAML IDP certificate.
   */
  public function miniorangeSamlIdpCertificateDownload() {
    self::generateMetadata('certificate');
  }

  /**
   * Tests the configuration of the SAML IDP module.
   */
  public function testConfiguration() {
    $relayState = '/';
    $acs = \Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_acs_url");
    $sp_issuer = \Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_entity_id");
    if ($acs == '' || is_null($acs) || $sp_issuer == '' || is_null($sp_issuer)) {
      echo '<div style="font-family:Calibri;padding:0 3%;">';
      echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div>
                <div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Error: </strong>Please configure your Service Provider (SP) first and then click on Test Configuration.</p>
                <p><strong>Possible Cause: </strong> ACS URL or SP Entity ID not found.</p>

                </div>
                <div style="margin:3%;display:block;text-align:center;">';
      ?>
            <div style="margin:3%;display:block;text-align:center;"><input
                        style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"
                        type="button" value="Done" onClick="self.close();"></a></div>
            <?php
            // Save the session so things like messages get saved.
            exit;
    }
    self::miniorangeIdpAuthorizeUser($acs, $sp_issuer, $relayState);
  }

  /**
   * Authorizes the user for SAML login and sends a response to the SP.
   *
   * @param string $acs_url
   *   The Assertion Consumer Service URL.
   * @param string $audience
   *   The Service Provider entity ID.
   * @param string $relayState
   *   The relay state for the response.
   * @param string|null $inResponseTo
   *   The request ID to respond to (optional).
   */
  public function miniorangeIdpAuthorizeUser($acs_url, $audience, $relayState, $inResponseTo = NULL) {
    if (\Drupal::currentUser()->isAuthenticated()) {
      self::miniorangeIdpSendReponse($acs_url, $audience, $relayState, $inResponseTo);
    }
    else {
      $saml_response_params = [
        'moIdpsendResponse' => "true",
        "acs_url" => $acs_url,
        "audience" => $audience,
        "relayState" => $relayState,
        "inResponseTo" => $inResponseTo,
      ];
      $responsec = new Response();
      $cookie = Cookie::create("response_params", json_encode($saml_response_params), secure: TRUE, httpOnly: TRUE);
      $responsec->headers->setCookie($cookie);
      $responsec->sendHeaders();
      $responsec->sendContent();
      $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
      $redirect_url = $base_url . '/user/login';
      $response = new RedirectResponse($redirect_url);
      $response->send();
    }
  }

  /**
   * Sends the SAML response to the Service Provider.
   *
   * @param string $acs_url
   *   The ACS URL where the SAML response should be sent.
   * @param string $audience
   *   The audience value for the response.
   * @param string $relayState
   *   The relay state value.
   * @param string|null $inResponseTo
   *   The request ID to respond to (optional).
   */
  public static function miniorangeIdpSendReponse($acs_url, $audience, $relayState, $inResponseTo = NULL) {
    $user     = \Drupal::currentUser();
    $email    = $user->getEmail();
    $username = $user->getAccountName();
    if (!in_array('administrator', $user->getRoles())) {
      ob_end_clean();
      echo t('<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div>
				<div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Single Sign On not Allowed</strong> </p>
				<p>This is a trial module meant for Super User/Administrator use only.</p>
				<p>The Single Sign On feature for end users is available in the premium version of the module.</p>
				</div>
				<div style="margin:3%;display:block;text-align:center;">');
      exit;
    }

    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $issuer = $base_url . '/?q=admin/config/people/miniorange_saml_idp/';
    $name_id_attr = (\Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_nameid_attr_map") == '') ? 'emailAddress' : \Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_nameid_attr_map");
    $name_id_attr_format = \Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_nameid_format");
    $idp_assertion_signed = \Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_assertion_signed");
    $state = \Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_relay_state");
    if (!empty($state) && !is_null($state)) {
      $relayState = $state;
    }

    $saml_response_obj = new GenerateResponse($email, $username, $acs_url, $issuer, $audience, $inResponseTo, $name_id_attr, $name_id_attr_format, $idp_assertion_signed);
    $saml_response = $saml_response_obj->createSamlResponse();
    setcookie("response_params", "");
    self::sendResponse($saml_response, $relayState, $acs_url);
  }

  /**
   * Sends the SAML response to the Service Provider via a POST request.
   *
   * @param string $saml_response
   *   The SAML response to be sent.
   * @param string $ssoUrl
   *   The SSO URL.
   * @param string $acs_url
   *   The Assertion Consumer Service URL.
   */
  public static function sendResponse($saml_response, $ssoUrl, $acs_url) {
    $response = new RedirectResponse($acs_url);
    $request = \Drupal::request();

    $request->getSession()->save();
    $response->prepare($request);
    \Drupal::service('kernel')->terminate($request, $response);
    $saml_response = base64_encode($saml_response);
    ?>
        <form id="responseform" action="<?php echo Html::escape($acs_url); ?>" method="post">
            <input type="hidden" name="SAMLResponse" value="<?php echo Html::escape($saml_response); ?>"/>
            <input type="hidden" name="RelayState" value="<?php echo Html::escape($ssoUrl); ?>"/>
        </form>
        <script>
            setTimeout(function () {
                document.getElementById('responseform').submit();
            }, 100);
        </script>
        <?php
        exit;
  }

  /**
   * Handles a SAML login request by reading the request and processing it.
   * 
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function miniorangeSamlIdpLoginRequest() {
    $request = \Drupal::request();
    $samlRequest = $request->query->get('SAMLRequest') ?? $request->request->get('SAMLRequest');
    if (!empty($samlRequest)) {
        $get  = $request->query->all();
        $post = $request->request->all();
        self::readSamlRequest(array_merge($get, $post), $get);
    }
    return new Response();
  }

  /**
   * Reads and processes the SAML request, validating its contents.
   *
   * @param array $request
   *   The request array containing the SAMLRequest and RelayState.
   * @param array $get
   *   The GET parameters from the request.
   */
  public function readSamlRequest($request, $get) {
    $samlRequest = $request['SAMLRequest'];
    $relayState = '/';
    if (array_key_exists('RelayState', $request)) {
      $relayState = $request['RelayState'];
    }

    $samlRequest = base64_decode($samlRequest);
    if (array_key_exists('SAMLRequest', $get) && !empty($get['SAMLRequest'])) {
      $samlRequest = gzinflate($samlRequest);
    }

    libxml_use_internal_errors(TRUE);

    $document = new \DOMDocument();
    $result = $document->loadXML($samlRequest, LIBXML_NONET);

    libxml_clear_errors();
    libxml_use_internal_errors(FALSE);

    if (!$result) {
        throw new \InvalidArgumentException('Failed to parse SAMLRequest XML.');
    }
    
    $samlRequestXML = $document->firstChild;
    $authnRequest = new MiniorangeAuthnRequest($samlRequestXML);
    $errors = '';
    
    $isSigned = isset($request['Signature']);
    if (!$isSigned) {
    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    if ($xpath->query('.//ds:Signature', $document)->length > 0) {
        $isSigned = true;
      }
    }
    if ($isSigned) {
    $errors .= '<strong>INVALID_REQUEST: </strong>Signed requests are not supported. Please upgrade to premium for signature validation.<br/>';
    }

    if (strtotime($authnRequest->getIssueInstant()) > (time() + 60)) {
      $errors .= '<strong>INVALID_REQUEST: </strong>Request time is greater than the current time.<br/>';
    }
    if ($authnRequest->getVersion() !== '2.0') {
      $errors .= 'Module only support SAML 2.0! Please send a SAML 2.0 request.<br/>';
    }

    $acs_url                = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_acs_url');
    $sp_issuer              = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_entity_id');
    $acs_url_from_request   = $authnRequest->getAssertionConsumerServiceUrl();
    $sp_issuer_from_request = $authnRequest->getIssuer();
    if (empty($acs_url) || empty($sp_issuer)) {
      $errors .= '<strong>INVALID_SP: </strong>Service Provider is not configured. Please configure your Service Provider.<br/>';
    }
    else {
      if ((!is_null($acs_url_from_request)) && (strcmp($acs_url, $acs_url_from_request) !== 0)) {
        $errors .= '<strong>INVALID_ACS: </strong>Invalid ACS URL. Please check your Service Provider Configurations.<br/>';
      }
      if (strcmp($sp_issuer, $sp_issuer_from_request) !== 0) {
        $errors .= '<strong>INVALID_ISSUER: </strong>Invalid Issuer. Please check your configuration.<br/>';
      }
    }

    $inResponseTo = $authnRequest->getRequestId();
    if (empty($errors)) {
      $module_path = \Drupal::service('extension.list.module')->getPath('miniorange_saml_idp');
      ?>
            <div style="vertical-align:center;text-align:center;width:100%;font-size:25px;background-color:white;">
                <img src="<?php echo $module_path; ?>/images/loader_gif.gif"></img>
                <h3>PROCESSING...PLEASE WAIT!</h3>
            </div>
            <?php
            self::miniorangeIdpAuthorizeUser($acs_url, $sp_issuer_from_request, $relayState, $inResponseTo);
    }
    else {
      $errors = t($errors); 
      $errors = Markup::create($errors);
      echo sprintf($errors);
      exit;
    }
  }

  /**
   * Opens the modal form for requesting a demo license.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object containing the demo request form.
   */
  public function openDemoRequestForm() {
    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_saml_idp\Form\MiniorangeSamlIdpRequestDemo');
    $response->addCommand(new OpenModalDialogCommand('Request Trial License', $modal_form, ['width' => '50%']));
    return $response;
  }

  /**
   * Opens the modal form for contacting the miniOrange support team.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object containing the contact us form.
   */
  public function openContactUsForm() {
    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_saml_idp\Form\MiniorangeContactUs');
    $response->addCommand(new OpenModalDialogCommand('Contact Us', $modal_form, ['width' => '50%']));
    return $response;
  }

  /**
   * Opens the modal form for adding a new Service Provider (SP).
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object containing the form for adding a new SP.
   */
  public function addNewSp() {
    $response = new AjaxResponse();
    $form['premium_note'] = [
      '#type' => 'item',
      '#markup' => t('You can configure only one Service Provider in the free version of the module. Multiple Service Providers are supported in the ') . '<a  target="_blank" href="licensing">[Premium]</a>' . t(' version of the module') . '<br><a class="button button--primary button--small js-form-submit form-submit use-ajax" href="request_trial">Request 7-days Trial</a>',
    ];
    $response->addCommand(new OpenModalDialogCommand(t('Add New SP'), $form, ['width' => '40%']));
    return $response;
  }

}
