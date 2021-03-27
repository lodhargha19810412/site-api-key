<?php

namespace Drupal\site_api_key\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Json controller.
 */
class json extends ControllerBase {

    /**
     * Keyvalue factory service.
     *
     * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
     */
    protected $keyValueFactory;

     /**
     * Entity type manager service.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs a new object.
     *
     * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterfac $keyValueFactory
     *   User email verification helper service.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterfac $entityTypeManager
     *   The time service.
     */
    public function __construct(KeyValueFactoryInterface $keyValueFactory, EntityTypeManagerInterface $entityTypeManager) {
        $this->keyValueFactory = $keyValueFactory;
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('keyvalue'),
            $container->get('entity_type.manager')
        );
    }

    /**
     * Function to return the selected page.
     *
     */
    private function getPage() {
        $siteSettings = $this->keyValueFactory->get('application.settings')->getAll();
        $siteApiKey = !empty($siteSettings['site_api_key']) ? $siteSettings['site_api_key'] : "";
        $storage = $this->entityTypeManager->getStorage('node');
        $idKey = $storage->getEntityType()->getKey('id');
        $typeKey = $storage->getEntityType()->getKey('bundle');
        $properties[$idKey] = $siteApiKey;
        $properties[$typeKey] = "page";
        $page = $storage->loadByProperties($properties);
        return $page;
    }
    
    /**
     * Access Callback to handle user's Email verification.
     *
     */
    public function access() {
        $page = $this->getPage();
        if (!empty($page)) {
            return AccessResult::allowed();
        }
        return AccessResult::neutral();
    }

    /**
     * Callback to handle user's Email verification.
     *
     */
    public function json() {
        $page = $this->getPage();
        $output = \Drupal::service("serializer")->serialize($page, 'json');
        $status = 200;
        $headers = [];
        $json = true;
        return new JsonResponse($output, $status, $headers, $json);
    }
}
