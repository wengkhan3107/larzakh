<?php

namespace Drupal\users_endpoint\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Profile List REST API Resource.
 *
 * @RestResource(
 *   id = "profile_list_resource",
 *   label = @Translation("Profile List Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/profiles"
 *   }
 * )
 */
class ProfileListResource extends ResourceBase implements ContainerFactoryPluginInterface
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
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
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
            $profiles = $this->entityTypeManager
                ->getStorage('profile')
                ->loadByProperties(['type' => 'maklumat_pegawai']);

            $data = [];

            foreach ($profiles as $profile) {
                $user = $profile->getOwner();
                $uid = $user ? $user->id() : null;

                $data[] = [
                    'uid' => $uid,
                    'profile' => [
                        'nama' => $profile->get('field_bahagian')?->value ?? null,
                        'tel_pejabat' => $profile->get('field_no_tel_pejabat')?->value ?? null,
                        'tel_bimbit' => $profile->get('field_no_telefon_bimbit')?->value ?? null,
                        'jawatan' => $profile->get('field_jawatan')?->value ?? null,
                        'cawangan_unit' => $profile->get('field_cawangan_unit')?->value ?? null,
                        'gred' => $profile->get('field_gred_new')?->value ?? null,
                        'skim' => $profile->get('field_skim')?->value ?? null,
                        'kedudukan_direktori' => $profile->get('field_kedudukan_direktori_new')?->value ?? null,
                    ],
                ];
            }

            return new ResourceResponse([
                'status' => 'success',
                'message' => 'Main profile list retrieved successfully.',
                'count' => count($data),
                'data' => $data,
            ]);

        } catch (\Throwable $e) {
            \Drupal::logger('users_endpoint')->error($e->getMessage());

            return new ResourceResponse([
                'status' => 'error',
                'message' => 'Failed to fetch main profiles.',
                'error' => $e->getMessage(),
            ]);
        }
    }

}