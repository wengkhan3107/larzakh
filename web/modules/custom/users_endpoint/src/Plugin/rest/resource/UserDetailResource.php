<?php

namespace Drupal\users_endpoint\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\rest\Plugin\ResourceBase;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Drupal\profile\Entity\Profile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\rest\resource\RestResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * used uid to get the user details
 * 
 * @RestResource(
 *  id="user_detail_resource",
 *  label = @Translation("User Detail Resource"),
 *  uri_paths = {
 *      "canonical" = "/api/v1/user/{uid}"
 *  }
 * )
 */
class UserDetailResource extends ResourceBase
{

    protected $entityManager;

    public function __construct()
    {
    }

    public function get($uid)
    {
        try {
            $output = [
                'status' => 'success',
                'message' => 'Fetching a user + details',
                'total' => 0,
                'data' => []
            ];

            if ($uid == null || $uid == '') {
                throw new Exception('UID is required');
            }

            $user = User::load($uid);
            if (!$user || $user->isAnonymous()) {
                throw new NotFoundHttpException('user not found');
            }

            // $profile = Profile::loadByUser($user, 'main');

            $output['data'][] = [
                'uid' => $user->id(),
                'id' => $user->getAccountName(),
                'email' => $user->getEmail(),
                'status' => $user->isActive() ? 'active' : 'blocked',
            ];
            // if ($profile) {
            //     $data = array_merge($data, [
            //         "nama" => $profile->get('field_bahagian')->value,
            //         "tel_pejabat" => $profile->get('field_no_tel_pejabat')->value,
            //         "tel_bimbit" => $profile->get('field_no_telefon_bimbit')->value,
            //         "jawatan" => $profile->get('field_jawatan')->value,
            //         "cawangan_unit" => $profile->get('field_cawangan_unit')->value,
            //         "gred" => $profile->get('field_gred_new')->value,
            //         "skim" => $profile->get('field_skim')->value,
            //         "kedudukan_direktori" => $profile->get('field_kedudukan_direktori_new')->value,
            //     ]);
            // }
            return new ResourceResponse($output);

        } catch (\Throwable $th) {
            $output['status'] = 'error';
            $output['message'] = 'Failed to fetch user details';
            $output['error'] = $th->getMessage();
        }

    }
}