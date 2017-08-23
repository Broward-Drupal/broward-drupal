<?php

namespace Drupal\entity_share_client\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entity_share_client\Service\JsonapiHelperInterface;
use Drupal\entity_share_client\Service\RemoteManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form controller to pull entities.
 */
class PullForm extends FormBase {

  /**
   * The remote websites known from the website.
   *
   * @var \Drupal\entity_share_client\Entity\RemoteInterface[]
   */
  protected $remoteWebsites;

  /**
   * An array of channel infos as returned by entity_share_server entry point.
   *
   * @var array
   */
  protected $channelsInfos;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The remote manager.
   *
   * @var \Drupal\entity_share_client\Service\RemoteManagerInterface
   */
  protected $remoteManager;

  /**
   * The jsonapi helper.
   *
   * @var \Drupal\entity_share_client\Service\JsonapiHelperInterface
   */
  protected $jsonapiHelper;

  /**
   * Query string parameters ($_GET).
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $query;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   The entity definition update manager.
   * @param \Drupal\entity_share_client\Service\RemoteManagerInterface $remote_manager
   *   The remote manager service.
   * @param \Drupal\entity_share_client\Service\JsonapiHelperInterface $jsonapi_helper
   *   The jsonapi helper service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
      EntityTypeManagerInterface $entity_type_manager,
      EntityDefinitionUpdateManagerInterface $entity_definition_update_manager,
      RemoteManagerInterface $remote_manager,
      JsonapiHelperInterface $jsonapi_helper,
      RequestStack $request_stack,
      LanguageManagerInterface $language_manager
    ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->remoteWebsites = $entity_type_manager
      ->getStorage('remote')
      ->loadMultiple();
    $this->remoteManager = $remote_manager;
    $this->jsonapiHelper = $jsonapi_helper;
    $this->query = $request_stack->getCurrentRequest()->query;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.definition_update_manager'),
      $container->get('entity_share_client.remote_manager'),
      $container->get('entity_share_client.jsonapi_helper'),
      $container->get('request_stack'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_share_client_pull_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['remote'] = [
      '#type' => 'select',
      '#title' => $this->t('Remote website'),
      '#options' => $this->prepareRemoteOptions(),
      '#default_value' => $this->query->get('remote'),
      '#empty_value' => '',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'buildAjaxChannelSelect'],
        'wrapper' => 'channel-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];

    // Container for the AJAX.
    $form['channel_wrapper'] = [
      '#type' => 'container',
      // Force an id because otherwise default id is changed when using AJAX.
      '#attributes' => [
        'id' => 'channel-wrapper',
      ],
    ];
    $this->buildChannelSelect($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ensure at least one entity is selected.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateSelectedEntities(array &$form, FormStateInterface $form_state) {
    $selected_entities = $form_state->getValue('entities');
    if (!is_null($selected_entities)) {
      $selected_entities = array_filter($selected_entities);
      if (empty($selected_entities)) {
        $form_state->setErrorByName('entities', $this->t('You must select at least one entity.'));
      }
    }
  }

  /**
   * Form submission handler for the 'synchronize' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function synchronizeSelectedEntities(array &$form, FormStateInterface $form_state) {
    $selected_entities = $form_state->getValue('entities');
    $selected_entities = array_filter($selected_entities);
    $selected_remote = $form_state->getValue('remote');
    $selected_channel = $form_state->getValue('channel');

    $form_state->setRedirect('entity_share_client.admin_content_pull_form', [], [
      'query' => [
        'remote' => $selected_remote,
        'channel' => $selected_channel,
      ],
    ]);

    // Add the selected UUIDs to the URL.
    // We do not handle offset or limit as we provide a maximum of 50 UUIDs.
    $url = $this->channelsInfos[$selected_channel]['url'];
    $parsed_url = UrlHelper::parse($url);
    $query = $parsed_url['query'];
    $query['filter']['uuid-filter'] = [
      'condition' => [
        'path' => 'uuid',
        'operator' => 'IN',
        'value' => array_values($selected_entities),
      ],
    ];
    $query = UrlHelper::buildQuery($query);
    $prepared_url = $parsed_url['path'] . '?' . $query;

    $selected_remote = $this->remoteWebsites[$selected_remote];
    $http_client = $this->remoteManager->prepareJsonApiClient($selected_remote);
    $json_response = $http_client->get($prepared_url)
      ->getBody()
      ->getContents();
    $json = Json::decode($json_response);

    if (!isset($json['errors'])) {
      $batch = [
        'title' => $this->t('Synchronize entities'),
        'operations' => [
          [
            '\Drupal\entity_share_client\JsonapiBatchHelper::importEntityListBatch',
            [$selected_remote, $this->jsonapiHelper->prepareData($json['data'])],
          ],
        ],
        'finished' => '\Drupal\entity_share_client\JsonapiBatchHelper::importEntityListBatchBatchFinished',
      ];

      batch_set($batch);
    }
  }

  /**
   * Helper function.
   *
   * @return string[]
   *   An array of remote websites.
   */
  protected function prepareRemoteOptions() {
    $options = [];
    foreach ($this->remoteWebsites as $id => $remote_website) {
      $options[$id] = $remote_website->label();
    }
    return $options;
  }

  /**
   * Helper function.
   *
   * @return string[]
   *   An array of remote channels.
   */
  protected function getChannelOptions() {
    $options = [];
    foreach ($this->channelsInfos as $channel_id => $channel_infos) {
      $options[$channel_id] = $channel_infos['label'];
    }
    return $options;
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Subform.
   */
  public static function buildAjaxChannelSelect(array $form, FormStateInterface $form_state) {
    // We just need to return the relevant part of the form here.
    return $form['channel_wrapper'];
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Subform.
   */
  public static function buildAjaxEntitiesSelectTable(array $form, FormStateInterface $form_state) {
    // We just need to return the relevant part of the form here.
    return $form['channel_wrapper']['entities_wrapper'];
  }

  /**
   * Helper function to generate channel select.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function buildChannelSelect(array &$form, FormStateInterface $form_state) {
    $selected_remote = $form_state->getValue('remote');

    // No remote selected.
    if (empty($selected_remote)) {
      $triggering_element = $form_state->getTriggeringElement();
      $get_remote = $this->query->get('remote');
      // If it is not an ajax trigger, check if it is in the GET parameters.
      if (!is_array($triggering_element) && !is_null($get_remote) && isset($this->remoteWebsites[$get_remote])) {
        $selected_remote = $get_remote;
      }
      else {
        return;
      }
    }

    $selected_remote = $this->remoteWebsites[$selected_remote];
    $this->channelsInfos = $this->remoteManager->getChannelsInfos($selected_remote);

    $form['channel_wrapper']['channel'] = [
      '#type' => 'select',
      '#title' => $this->t('Channel'),
      '#options' => $this->getChannelOptions(),
      '#default_value' => $this->query->get('channel'),
      '#empty_value' => '',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'buildAjaxEntitiesSelectTable'],
        'wrapper' => 'entities-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];
    // Container for the AJAX.
    $form['channel_wrapper']['entities_wrapper'] = [
      '#type' => 'container',
      // Force an id because otherwise default id is changed when using AJAX.
      '#attributes' => [
        'id' => 'entities-wrapper',
      ],
    ];
    $this->buildEntitiesSelectTable($form, $form_state);
  }

  /**
   * Helper function to generate entities table.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function buildEntitiesSelectTable(array &$form, FormStateInterface $form_state) {
    $selected_remote = $form_state->getValue('remote');
    $selected_channel = $form_state->getValue('channel');
    $offset = '0';

    // No remote selected.
    if (empty($selected_remote) || empty($selected_channel) || !isset($this->channelsInfos[$selected_channel])) {
      $triggering_element = $form_state->getTriggeringElement();
      $get_remote = $this->query->get('remote');
      $get_channel = $this->query->get('channel');
      $get_offset = $this->query->get('offset');
      // If it is not an ajax trigger, check if it is in the GET parameters.
      if (
        !is_array($triggering_element) &&
        !is_null($get_remote) &&
        isset($this->remoteWebsites[$get_remote]) &&
        !is_null($get_channel) &&
        isset($this->channelsInfos[$get_channel])
      ) {
        $selected_remote = $get_remote;
        $selected_channel = $get_channel;
        if (!is_null($get_offset) && is_int((int) $get_offset)) {
          $offset = $get_offset;
        }
      }
      else {
        return;
      }
    }

    $selected_remote = $this->remoteWebsites[$selected_remote];
    $http_client = $this->remoteManager->prepareJsonApiClient($selected_remote);

    // Add offset to the selected channel.
    $parsed_url = UrlHelper::parse($this->channelsInfos[$selected_channel]['url']);
    $parsed_url['query']['page']['offset'] = $offset;
    $query = UrlHelper::buildQuery($parsed_url['query']);
    $prepared_url = $parsed_url['path'] . '?' . $query;

    $json_response = $http_client->get($prepared_url)
      ->getBody()
      ->getContents();
    $json = Json::decode($json_response);

    // Store the JSONAPI links to use its in the pager submit handlers.
    $storage = $form_state->getStorage();
    $storage['links'] = $json['links'];
    $form_state->setStorage($storage);

    // Pager.
    $form['channel_wrapper']['entities_wrapper']['pager'] = [
      '#type' => 'actions',
      '#weight' => -10,
    ];
    if (isset($json['links']['first'])) {
      $form['channel_wrapper']['entities_wrapper']['pager']['first'] = [
        '#type' => 'submit',
        '#value' => $this->t('First'),
        '#submit' => ['::firstPage'],
      ];
    }
    if (isset($json['links']['prev'])) {
      $form['channel_wrapper']['entities_wrapper']['pager']['prev'] = [
        '#type' => 'submit',
        '#value' => $this->t('Previous'),
        '#submit' => ['::prevPage'],
      ];
    }
    if (isset($json['links']['next'])) {
      $form['channel_wrapper']['entities_wrapper']['pager']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#submit' => ['::nextPage'],
      ];
    }
    if (isset($json['links']['last'])) {
      $form['channel_wrapper']['entities_wrapper']['pager']['last'] = [
        '#type' => 'submit',
        '#value' => $this->t('Last'),
        '#submit' => ['::lastPage'],
      ];
    }

    $form['channel_wrapper']['entities_wrapper']['actions_top']['#type'] = 'actions';
    $form['channel_wrapper']['entities_wrapper']['actions_top']['#weight'] = -1;
    $form['channel_wrapper']['entities_wrapper']['actions_top']['synchronize'] = [
      '#type' => 'submit',
      '#value' => $this->t('Synchronize entities'),
      '#button_type' => 'primary',
      '#validate' => ['::validateSelectedEntities'],
      '#submit' => ['::synchronizeSelectedEntities'],
    ];

    // Table to select entities.
    $header = [
      'label' => $this->t('Label'),
      'type' => $this->t('Type'),
      'bundle' => $this->t('Bundle'),
      'language' => $this->t('Language'),
      'status' => $this->t('Status'),
    ];

    $form['channel_wrapper']['entities_wrapper']['entities'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $this->jsonapiHelper->buildEntitiesOptions($json['data']),
      '#empty' => $this->t('No entities to be pulled have been found.'),
      '#attached' => [
        'library' => [
          'entity_share_client/admin',
        ],
      ],
    ];

    $form['channel_wrapper']['entities_wrapper']['actions_bottom']['#type'] = 'actions';
    $form['channel_wrapper']['entities_wrapper']['actions_bottom']['synchronize'] = [
      '#type' => 'submit',
      '#value' => $this->t('Synchronize entities'),
      '#button_type' => 'primary',
      '#validate' => ['::validateSelectedEntities'],
      '#submit' => ['::synchronizeSelectedEntities'],
    ];
  }

  /**
   * Form submission handler to go to the first pager page.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function firstPage(array &$form, FormStateInterface $form_state) {
    $this->pagerRedirect($form_state, 'first');
  }

  /**
   * Form submission handler to go to the previous pager page.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function prevPage(array &$form, FormStateInterface $form_state) {
    $this->pagerRedirect($form_state, 'prev');
  }

  /**
   * Form submission handler to go to the next pager page.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function nextPage(array &$form, FormStateInterface $form_state) {
    $this->pagerRedirect($form_state, 'next');
  }

  /**
   * Form submission handler to go to the last pager page.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function lastPage(array &$form, FormStateInterface $form_state) {
    $this->pagerRedirect($form_state, 'last');
  }

  /**
   * Helper function to redirect with the form to right page to handle pager.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $link_name
   *   The link name. Possibles values: first, prev, next, last.
   */
  protected function pagerRedirect(FormStateInterface $form_state, $link_name) {
    $storage = $form_state->getStorage();
    if (isset($storage['links'][$link_name])) {
      $selected_remote = $form_state->getValue('remote');
      $selected_channel = $form_state->getValue('channel');

      $parsed_url = UrlHelper::parse($storage['links'][$link_name]);
      if (isset($parsed_url['query']['page']) && isset($parsed_url['query']['page']['offset'])) {
        $form_state->setRedirect('entity_share_client.admin_content_pull_form', [], [
          'query' => [
            'remote' => $selected_remote,
            'channel' => $selected_channel,
            'offset' => $parsed_url['query']['page']['offset'],
          ],
        ]);
      }
    }
  }

}
