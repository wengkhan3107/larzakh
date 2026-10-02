<?php

namespace Drupal\users_endpoint\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\rest\Plugin\rest\resource\RestResource;


/**
 * Provides a Custom API Resource.
 *
 * @RestResource(
 *   id = "users_api_resource",
 *   label = @Translation("Users API Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/test"
 *   }
 * )
 */
class UsersApiResource extends ResourceBase {

  /**
   * Responds to GET requests.
   */
  public function get() {
    $data = [
      'message' => 'Request received',
      'status' => 'success',
    ];
    return new ResourceResponse($data);
  }

}
