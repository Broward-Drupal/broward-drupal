<?php

namespace Drupal\entity_share_client\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_share_client\Entity\RemoteInterface;
use Drupal\entity_share_client\Event\RelationshipFieldValueEvent;
use Drupal\file\FileInterface;
use Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class JsonapiHelper.
 *
 * @package Drupal\entity_share_client\Service
 */
class JsonapiHelper implements JsonapiHelperInterface {
  use StringTranslationTrait;

  /**
   * The JSON API serializer.
   *
   * @var \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
   */
  protected $jsonapiSerializer;

  /**
   * The resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * The bundle infos from the website.
   *
   * @var array
   */
  protected $bundleInfos;

  /**
   * The entity type definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityDefinitions;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The remote manager.
   *
   * @var \Drupal\entity_share_client\Service\RemoteManagerInterface
   */
  protected $remoteManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * A prepared HTTP client for file transfer.
   *
   * @var \GuzzleHttp\Client
   */
  protected $fileHttpClient;

  /**
   * A prepared HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The remote website on which to prepare the clients.
   *
   * @var \Drupal\entity_share_client\Entity\RemoteInterface
   */
  protected $remote;

  /**
   * JsonapiHelper constructor.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   A serializer.
   * @param \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer $jsonapi_serializer
   *   The JSON API serializer.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepository $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   The entity definition update manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\entity_share_client\Service\RemoteManagerInterface $remote_manager
   *   The remote manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(
    SerializerInterface $serializer,
    JsonApiDocumentTopLevelNormalizer $jsonapi_serializer,
    ResourceTypeRepository $resource_type_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDefinitionUpdateManagerInterface $entity_definition_update_manager,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    LanguageManagerInterface $language_manager,
    RemoteManagerInterface $remote_manager,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->jsonapiSerializer = $jsonapi_serializer;
    $this->jsonapiSerializer->setSerializer($serializer);
    $this->resourceTypeRepository = $resource_type_repository;
    $this->bundleInfos = $entity_type_bundle_info->getAllBundleInfo();
    $this->entityDefinitions = $entity_type_manager->getDefinitions();
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->languageManager = $language_manager;
    $this->remoteManager = $remote_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntitiesOptions(array $json_data) {
    $options = [];
    foreach ($this->prepareData($json_data) as $data) {
      $this->addOptionFromJson($options, $data);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function extractEntity(array $data) {
    // Format JSON as in
    // JsonApiDocumentTopLevelNormalizerTest::testDenormalize().
    $prepared_json = [
      'type' => $data['type'],
      'data' => [
        'attributes' => $data['attributes'],
      ],
    ];
    $parsed_type = explode('--', $data['type']);

    return $this->jsonapiSerializer->denormalize($prepared_json, JsonApiDocumentTopLevelNormalizer::class, 'api_json', [
      'resource_type' => $this->resourceTypeRepository->get(
        $parsed_type[0],
        $parsed_type[1]
      ),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateRelationships(ContentEntityInterface $entity, array $data) {
    if (isset($data['relationships'])) {
      // Reference fields.
      foreach ($data['relationships'] as $field_name => $field_data) {
        if ($this->relationshipHandleable($field_data)) {
          $field_values = [];

          if (isset($field_data['links']) && isset($field_data['links']['related'])) {
            $referenced_entities_response = $this->getHttpClient()->get($field_data['links']['related'])
              ->getBody()
              ->getContents();
            $referenced_entities_json = Json::decode($referenced_entities_response);

            if (!isset($referenced_entities_json['errors'])) {
              $referenced_entities_ids = $this->importEntityListData($referenced_entities_json['data']);

              $field = $entity->get($field_name);
              $main_property = $field->getItemDefinition()->getMainPropertyName();

              // Add field metadatas.
              foreach ($this->prepareData($field_data['data']) as $key => $field_value_data) {
                $field_value = [
                  $main_property => $referenced_entities_ids[$key],
                ];

                if (isset($field_value_data['meta'])) {
                  $field_value += $field_value_data['meta'];
                }

                // Allow to alter the field value with an event.
                $event = new RelationshipFieldValueEvent($field, $field_value);
                $this->eventDispatcher->dispatch(RelationshipFieldValueEvent::EVENT_NAME, $event);
                $field_values[] = $event->getFieldValue();
              }
            }
          }
          $entity->set($field_name, $field_values);
        }
      }

      // Save the entity once all the references have been updated.
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handlePhysicalFiles(ContentEntityInterface $entity, array &$data) {
    if ($entity instanceof FileInterface) {
      $stream_wrapper = $this->streamWrapperManager->getViaUri($data['attributes']['uri']);

      $uri_parts = explode('/', $data['attributes']['uri']);
      // Remove the last part of the URI as it is supposed to be the file name.
      array_pop($uri_parts);
      $directory_uri = implode('/', $uri_parts);

      // Create the destination folder.
      if (file_prepare_directory($directory_uri, FILE_CREATE_DIRECTORY)) {
        $remote_uri_real_path = $stream_wrapper->realpath();
        // TODO: Check the case of large files.
        // TODO: Transfer file only if necessary.
        $file_content = $this->getFileHttpClient()->get($data['attributes']['url'])
          ->getBody()
          ->getContents();
        file_put_contents($remote_uri_real_path, $file_content);
      }
      else {
        drupal_set_message($this->t('Impossible to write in the directory %directory', ['%directory' => $directory_uri]), 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRemote(RemoteInterface $remote) {
    $this->remote = $remote;
  }

  /**
   * {@inheritdoc}
   */
  public function importEntityListData(array $entity_list_data) {
    $imported_entity_ids = [];
    foreach ($this->prepareData($entity_list_data) as $entity_data) {
      $parsed_type = explode('--', $entity_data['type']);
      $entity_type = $this->entityDefinitionUpdateManager->getEntityType($parsed_type[0]);
      $entity_keys = $entity_type->getKeys();

      $this->prepareEntityData($entity_data, $entity_keys);

      $data_langcode = $entity_data['attributes'][$entity_keys['langcode']];

      // Prepare entity label.
      if (isset($entity_keys['label'])) {
        $entity_label = $entity_data['attributes'][$entity_keys['label']];
      }
      else {
        // Use the entity type if there is no label.
        $entity_label = $parsed_type[0];
      }

      if (!$this->dataLanguageExists($data_langcode, $entity_label)) {
        continue;
      }

      // Check if an entity already exists.
      $existing_entities = $this->entityTypeManager
        ->getStorage($parsed_type[0])
        ->loadByProperties(['uuid' => $entity_data['attributes'][$entity_keys['uuid']]]);

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->extractEntity($entity_data);

      // New entity.
      if (empty($existing_entities)) {
        $entity->save();
        $imported_entity_ids[] = $entity->id();
        $this->updateRelationships($entity, $entity_data);
        $this->handlePhysicalFiles($entity, $entity_data);
        // Change the entity "changed" time because it could have been altered
        // with relationship save by example.
        if (method_exists($entity, 'setChangedTime')) {
          $entity->setChangedTime($entity_data['attributes']['changed']);
        }
        $entity->save();
      }
      // Update the existing entity.
      else {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $existing_entity */
        $existing_entity = array_shift($existing_entities);
        $imported_entity_ids[] = $existing_entity->id();
        $has_translation = $existing_entity->hasTranslation($data_langcode);
        // Update the existing translation.
        if ($has_translation) {
          $existing_translation = $existing_entity->getTranslation($data_langcode);
          foreach ($entity_data['attributes'] as $field_name => $value) {
            $existing_translation->set(
              $field_name,
              $entity->get($field_name)->getValue()
            );
          }
          $existing_translation->save();
        }
        // Create the new translation.
        else {
          $translation = $entity->toArray();
          $existing_entity->addTranslation($data_langcode, $translation);
          $existing_entity->save();
          $existing_translation = $existing_entity->getTranslation($data_langcode);
        }
        $this->updateRelationships($existing_translation, $entity_data);
        $this->handlePhysicalFiles($existing_translation, $entity_data);
        // Change the entity "changed" time because it could have been altered
        // with relationship save by example.
        if (method_exists($existing_translation, 'setChangedTime')) {
          $existing_translation->setChangedTime($entity_data['attributes']['changed']);
        }
        $existing_translation->save();
      }
    }
    return $imported_entity_ids;
  }

  /**
   * Helper function to add an option.
   *
   * @param array $options
   *   The array of options for the tableselect form type element.
   * @param array $data
   *   An array of data.
   * @param int $level
   *   The level of indentation.
   */
  protected function addOptionFromJson(array &$options, array $data, $level = 0) {
    // Format JSON as in
    // JsonApiDocumentTopLevelNormalizerTest::testDenormalize().
    $prepared_json = [
      'type' => $data['type'],
      'data' => [
        'attributes' => $data['attributes'],
      ],
    ];
    $parsed_type = explode('--', $data['type']);

    $entity = $this->jsonapiSerializer->denormalize($prepared_json, JsonApiDocumentTopLevelNormalizer::class, 'api_json', [
      'resource_type' => $this->resourceTypeRepository->get(
        $parsed_type[0],
        $parsed_type[1]
      ),
    ]);

    $this->addOption($options, $entity, $parsed_type[0], $parsed_type[1], $level);
  }

  /**
   * Helper function to add an option.
   *
   * @param array $options
   *   The array of options for the tableselect form type element.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An unserialized entity.
   * @param string $entity_type_id
   *   The entity type ID of the entity.
   * @param string $bundle_id
   *   The bundle id of the entity.
   * @param int $level
   *   The level of indentation.
   */
  protected function addOption(array &$options, ContentEntityInterface $entity, $entity_type_id, $bundle_id, $level = 0) {
    $indentation = '';
    for ($i = 1; $i <= $level; $i++) {
      $indentation .= '<div class="indentation">&nbsp;</div>';
    }

    $label = new FormattableMarkup($indentation . '@label', [
      '@label' => $entity->label(),
    ]);

    $status_info = $this->getStatusInfo($entity, $entity_type_id);

    $options[$entity->uuid()] = [
      'label' => $label,
      'type' => $entity->getEntityType()->getLabel(),
      'bundle' => $this->bundleInfos[$entity_type_id][$bundle_id]['label'],
      'language' => $this->getEntityLanguageLabel($entity),
      'status' => $status_info['label'],
      '#attributes' => [
        'class' => [
          $status_info['class'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareData(array $data) {
    if ($this->isNumericArray($data)) {
      return $data;
    }
    else {
      return [$data];
    }
  }

  /**
   * Check if a array is numeric.
   *
   * @param array $array
   *   The array to check.
   *
   * @return bool
   *   TRUE if the array is numeric. FALSE in case of associative array.
   */
  protected function isNumericArray(array $array) {
    foreach ($array as $a => $b) {
      if (!is_int($a)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Check if a relationship is handleable.
   *
   * Filter on fields:
   * - with data only
   * - not targeting config entities or users.
   *
   * @param array $field_data
   *   The array of relationship field data.
   *
   * @return bool
   *   TRUE if the relationship is handleable.
   */
  protected function relationshipHandleable(array $field_data) {
    // Check empty field.
    if ($field_data['data'] == NULL) {
      return FALSE;
    }

    if ($this->isNumericArray($field_data['data'])) {
      $type = $field_data['data'][0]['type'];
    }
    else {
      $type = $field_data['data']['type'];
    }

    $type_data = explode('--', $type);
    $entity_type_id = $type_data[0];

    // User.
    if ($entity_type_id == 'user') {
      return FALSE;
    }
    // Unknown entity type.
    elseif (!isset($this->entityDefinitions[$entity_type_id])) {
      drupal_set_message($this->t('There is a reference to an unknown entity type %entity_type.', ['%entity_type' => $entity_type_id]), 'warning');
      return FALSE;
    }
    // Config entity type.
    elseif ($this->entityDefinitions[$entity_type_id]->getGroup() == 'configuration') {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Helper function to get the language from an extracted entity.
   *
   * We can't use $entity->language() because if the entity is in a language not
   * enabled, it is the site default language that is returned.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An unserialized entity.
   *
   * @return string
   *   The language of the entity.
   */
  protected function getEntityLanguageLabel(ContentEntityInterface $entity) {
    $langcode = $entity->get('langcode')->value;
    $language = $this->languageManager->getLanguage($langcode);
    // Check if the entity is in an enabled language.
    if (is_null($language)) {
      $language_list = LanguageManager::getStandardLanguageList();
      if (isset($language_list[$langcode])) {
        $entity_language = $language_list[$langcode][0] . ' ' . $this->t('(not enabled)', [], ['context' => 'language']);
      }
      else {
        $entity_language = $this->t('Entity in an unsupported language.');
      }
    }
    else {
      $entity_language = $language->getName();
    }

    return $entity_language;
  }

  /**
   * Helper function to get the File Http Client.
   *
   * @return \GuzzleHttp\Client
   *   A HTTP client to retrieve files.
   */
  protected function getFileHttpClient() {
    if (!$this->fileHttpClient) {
      $this->fileHttpClient = $this->remoteManager->prepareClient($this->remote);
    }

    return $this->fileHttpClient;
  }

  /**
   * Helper function to get the Http Client.
   *
   * @return \GuzzleHttp\Client
   *   A HTTP client to request JSONAPI endpoints.
   */
  protected function getHttpClient() {
    if (!$this->httpClient) {
      $this->httpClient = $this->remoteManager->prepareJsonApiClient($this->remote);
    }

    return $this->httpClient;
  }

  /**
   * Prepare the data array before extracting the entity.
   *
   * Used to remove some data.
   *
   * @param array $data
   *   An array of data.
   * @param array $entity_keys
   *   An array of entity keys.
   */
  protected function prepareEntityData(array &$data, array $entity_keys) {
    // Removes some ids.
    unset($data['attributes'][$entity_keys['id']]);
    if (isset($entity_keys['revision']) && !empty($entity_keys['revision'])) {
      unset($data['attributes'][$entity_keys['revision']]);
    }

    // Remove the default_langcode boolean to be able to import content not
    // necessarily in the default language.
    // TODO: Handle content_translation_source?
    unset($data['attributes'][$entity_keys['default_langcode']]);

    // To avoid side effects and as currently JSONAPI send null for the path
    // we remove the path attribute.
    if (isset($data['attributes']['path'])) {
      unset($data['attributes']['path']);
    }
  }

  /**
   * Check if we try to import an entity in a disabled language.
   *
   * @param string $langcode
   *   The langcode of the language to check.
   * @param string $entity_label
   *   The entity label.
   *
   * @return bool
   *   FALSE if the data is not in an enabled language.
   */
  protected function dataLanguageExists($langcode, $entity_label) {
    if (is_null($this->languageManager->getLanguage($langcode))) {
      drupal_set_message($this->t('Trying to import an entity (%entity_label) in a disabled language.', [
        '%entity_label' => $entity_label,
      ]), 'error');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check if an entity already exists or not and compare revision timestamp.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The distant entity to check.
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   Returns an array of info:
   *     - class: to add a class on a row.
   *     - label: the label to display.
   */
  protected function getStatusInfo(ContentEntityInterface $entity, $entity_type_id) {
    $status_info = [
      'label' => $this->t('Undefined'),
      'class' => 'entity-share-undefined',
    ];

    // Check if an entity already exists.
    $existing_entities = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->loadByProperties(['uuid' => $entity->uuid()]);

    if (empty($existing_entities)) {
      $status_info = [
        'label' => $this->t('New entity'),
        'class' => 'entity-share-new',
      ];
    }
    // An entity already exists.
    // Check if the entity type has a changed date.
    elseif (method_exists($entity, 'getChangedTime')) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $existing_entity */
      $existing_entity = array_shift($existing_entities);
      $entity_language_id = $entity->language()->getId();

      // Entity has the translation.
      if ($existing_entity->hasTranslation($entity_language_id)) {
        $existing_translation = $existing_entity->getTranslation($entity_language_id);
        $entity_changed_time = $entity->getChangedTime();
        $existing_entity_changed_time = $existing_translation->getChangedTime();

        // Existing entity.
        if ($entity_changed_time != $existing_entity_changed_time) {
          $status_info = [
            'label' => $this->t('Entities not synchronized'),
            'class' => 'entity-share-changed',
          ];
        }
        else {
          $status_info = [
            'label' => $this->t('Entities synchronized'),
            'class' => 'entity-share-up-to-date',
          ];
        }
      }
      else {
        $status_info = [
          'label' => $this->t('New translation'),
          'class' => 'entity-share-new',
        ];
      }
    }

    return $status_info;
  }

}
