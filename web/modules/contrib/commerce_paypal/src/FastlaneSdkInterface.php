<?php

namespace Drupal\commerce_paypal;

use Psr\Http\Message\ResponseInterface;

interface FastlaneSdkInterface extends SdkInterface {

  /**
   * Gets an oauth token.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function getOauthToken(): ResponseInterface;

}
