<?php

namespace Drupal\users_endpoint\Plugin\rest\resource;


use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Drupal\profile\Entity\Profile;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest\Plugin\rest\resource\RestResource;

/**
 * Return list of users with details
 * 
 * @RestResource(
 *  id = "users_api_resource",
 *  label = @Translation("Users API Resource"),
 *  uri_paths = {
 *      "canonical" = "/api/v1/users"
 *  }
 * )
 */
class UserApiResource extends ResourceBase implements ContainerFactoryPluginInterface
{
    protected $entityTypeManager;

    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EntityTypeManagerInterface $entity_type_manager,
        array $serializer_formats = [],
        $logger = NULL
    ) {
        parent::__construct(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $serializer_formats,
            $logger
        );
        $this->entityTypeManager = $entity_type_manager;
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity_type.manager'),
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('users_endpoint')
        );
    }


    public function get()
    {
        try {
            $output = [
                "status" => "success",
                "message" => "Fetch users",
                "count" => 0,
                "data" => []
            ];
            $users = $this->entityTypeManager->getStorage('user')->loadMultiple();

            foreach ($users as $user) {
                // if ($user->id() == 0 || !$user->isActive()) {
                //     continue;
                // }

                $profile = \Drupal::entityTypeManager()
                    ->getStorage('profile')
                    ->loadByProperties([
                        'uid' => $user->id(),
                        'type' => 'maklumat_pegawai'
                    ]);

                $profile = reset($profile);
                //Profile Exists
                if (!$profile && !is_object($profile)) {
                    continue;
                }

                $user_data = [
                    'uid' => $user->id(),
                    'id' => $user->getAccountName(),
                    'email' => $user->getEmail(),
                    'nama' => $user->get('field_nama_pegawai_v1')->value,
                    'status' => $user->isActive() ? 'active' : 'blocked',
                ];

                $user_data['nama_penuh'] = $profile->get('field_nama')->value ?? null;
                $user_data['tel_pejabat'] = $profile->get('field_no_tel_pejabat')->value ?? null;
                $user_data['tel_bimbit'] = $profile->get('field_no_telefon_bimbit')->value ?? null;
                $user_data['gred'] = $profile->get('field_gred_new')->value ?? null;
                $user_data['skim'] = $profile->get('field_skim')->value ?? null;
                $user_data['tarikh_lahir'] = $profile->get('field_tarikh_lahir')->value ?? null;
                $user_data['kedudukan_direktori'] = $profile->get('field_kedudukan_direktori_new')->value ?? null;
                if ($user_data['kedudukan_direktori'] == null) {
                    continue;
                }
                $user_data['jawatan'] = null;
                $user_data['bahagian'] = null;
                $user_data['cawangan_unit'] = null;

                //Reference Field(Taxonomy)
                $bahagian_term = $profile->get('field_bahagian')->entity;
                $cawangan_unit_term = $profile->get('field_cawangan_unit')->entity;
                $jawatan_term = $profile->get('field_jawatan')->entity;

                if ($bahagian_term) {
                    $user_data['bahagian'] = [
                        'term_id' => $profile->get('field_bahagian')->target_id,
                        'name' => $bahagian_term->label(),
                    ];
                } else {
                    continue;
                }
                if ($cawangan_unit_term) {
                    $user_data['cawangan_unit'] = [
                        'term_id' => $profile->get('field_cawangan_unit')->target_id,
                        'name' => $cawangan_unit_term->label(),
                    ];

                }
                if ($jawatan_term) {
                    $user_data['jawatan'] = [
                        'term_id' => $profile->get('field_jawatan')->target_id,
                        'name' => $jawatan_term->label(),
                    ];
                }
                $output['data'][] = $user_data;
                $output['count']++;
            }

            return new ResourceResponse($output);
        } catch (\Exception $ex) {
            $output = [
                'status' => "error",
                'message' => $ex->getMessage(),
                'data' => [],
            ];
            \Drupal::logger('User Endpoint API')->error('<pre>' . print_r($output, true) . '</pre>');
            return new ResourceResponse($output);
        }

    }
}