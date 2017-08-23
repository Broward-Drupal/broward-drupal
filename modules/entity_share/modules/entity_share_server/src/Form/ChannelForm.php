<?php

namespace Drupal\entity_share_server\Form;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ChannelForm.
 *
 * @package Drupal\entity_share_server\Form
 */
class ChannelForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * The bundle infos from the website.
   *
   * @var array
   */
  protected $bundleInfos;

  /**
   * The entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ChannelForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityTypeRepositoryInterface $entity_type_repository,
    RendererInterface $renderer
  ) {
    $this->bundleInfos = $entity_type_bundle_info->getAllBundleInfo();
    $this->entityTypeRepository = $entity_type_repository;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.repository'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_share_server\Entity\ChannelInterface $channel */
    $channel = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $channel->label(),
      '#description' => $this->t('Label for the channel.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $channel->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_share_server\Entity\Channel::load',
      ],
      '#disabled' => !$channel->isNew(),
    ];

    // Keep only content entity type without user.
    $entity_type_options = $this->entityTypeRepository->getEntityTypeLabels(TRUE);
    unset($entity_type_options['Content']['user']);

    $form['channel_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $entity_type_options['Content'],
      '#empty_value' => '',
      '#default_value' => $channel->get('channel_entity_type'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'buildAjaxBundleSelect'],
        'wrapper' => 'bundle-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];

    // Container for the AJAX.
    $form['bundle_wrapper'] = [
      '#type' => 'container',
      // Force an id because otherwise default id is changed when using AJAX.
      '#attributes' => [
        'id' => 'bundle-wrapper',
      ],
    ];
    $this->buildBundleSelect($form, $form_state);

    $this->buildLanguageSelect($form, $form_state);

    $this->buildGroupsTable($form, $form_state);

    $this->buildFiltersTable($form, $form_state);

    $this->buildSortsTable($form, $form_state);

    $authorized_users = $channel->get('authorized_users');
    $form['authorized_users'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Authorized users'),
      '#options' => $this->getAuthorizedUsersOptions(),
      '#default_value' => !is_null($authorized_users) ? $authorized_users : [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_share_server\Entity\ChannelInterface $channel */
    $channel = $this->entity;

    $authorized_users = array_filter($form_state->getValue('authorized_users'));
    $channel->set('authorized_users', $authorized_users);

    // Sorts order.
    $channel_sorts = $channel->get('channel_sorts');
    if (is_null($channel_sorts)) {
      $channel_sorts = [];
    }
    $sorts = $form_state->getValue('sort_table');
    if (!is_null($sorts)) {
      foreach ($sorts as $sort_id => $sort) {
        $channel_sorts[$sort_id]['weight'] = $sort['weight'];
      }
    }
    $channel->set('channel_sorts', $channel_sorts);

    $status = $channel->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Channel.', [
          '%label' => $channel->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Channel.', [
          '%label' => $channel->label(),
        ]));
    }
    $form_state->setRedirectUrl($channel->toUrl('collection'));
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
  public static function buildAjaxBundleSelect(array $form, FormStateInterface $form_state) {
    // We just need to return the relevant part of the form here.
    return $form['bundle_wrapper'];
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
  public static function buildAjaxLanguageSelect(array $form, FormStateInterface $form_state) {
    // We just need to return the relevant part of the form here.
    return $form['bundle_wrapper']['language_wrapper'];
  }

  /**
   * Helper function to generate bundle select.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function buildBundleSelect(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_share_server\Entity\ChannelInterface $channel */
    $channel = $this->entity;
    $channel_entity_type = $channel->get('channel_entity_type');
    $selected_entity_type = $form_state->getValue('channel_entity_type');

    // No entity type selected and the channel does not have any.
    if (empty($selected_entity_type) && $channel_entity_type == '') {
      return;
    }

    if (!empty($selected_entity_type)) {
      $entity_type = $selected_entity_type;
    }
    else {
      $entity_type = $channel_entity_type;
    }

    $form['bundle_wrapper']['channel_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $this->getBundleOptions($entity_type),
      '#empty_value' => '',
      '#default_value' => $channel->get('channel_bundle'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'buildAjaxLanguageSelect'],
        'wrapper' => 'language-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];
    // Container for the AJAX.
    $form['bundle_wrapper']['language_wrapper'] = [
      '#type' => 'container',
      // Force an id because otherwise default id is changed when using AJAX.
      '#attributes' => [
        'id' => 'language-wrapper',
      ],
    ];
  }

  /**
   * Helper function to generate language select.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function buildLanguageSelect(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_share_server\Entity\ChannelInterface $channel */
    $channel = $this->entity;
    $channel_entity_type = $channel->get('channel_entity_type');
    $channel_bundle = $channel->get('channel_bundle');
    $selected_entity_type = $form_state->getValue('channel_entity_type');
    $selected_bundle = $form_state->getValue('channel_bundle');

    // No bundle selected and the channel does not have any.
    if (empty($selected_bundle) && $channel_bundle == '') {
      return;
    }

    if (!empty($selected_entity_type) && !empty($selected_bundle)) {
      $entity_type = $selected_entity_type;
      $bundle = $selected_bundle;
    }
    else {
      $entity_type = $channel_entity_type;
      $bundle = $channel_bundle;
    }

    // Check if the bundle is translatable.
    if (isset($this->bundleInfos[$entity_type][$bundle]['translatable']) && $this->bundleInfos[$entity_type][$bundle]['translatable']) {
      $form['bundle_wrapper']['language_wrapper']['channel_langcode'] = [
        '#type' => 'language_select',
        '#title' => $this->t('Language'),
        '#languages' => LanguageInterface::STATE_ALL,
        '#default_value' => $channel->get('channel_langcode'),
      ];
    }
    else {
      $form['bundle_wrapper']['language_wrapper']['channel_langcode'] = [
        '#type' => 'value',
        '#value' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ];
    }
  }

  /**
   * Helper function to generate group form elements.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function buildGroupsTable(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_share_server\Entity\ChannelInterface $channel */
    $channel = $this->entity;
    $channel_groups = $channel->get('channel_groups');
    if (is_null($channel_groups)) {
      $channel_groups = [];
    }

    $form['channel_groups'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Groups'),
    ];

    if ($channel->isNew()) {
      $form['channel_groups']['group_message'] = [
        '#markup' => $this->t("It will be possible to add groups after the channel's creation."),
      ];
    }
    else {
      $form['channel_groups']['group_actions'] = [
        '#type' => 'actions',
        '#weight' => -5,
      ];
      $form['channel_groups']['group_actions']['group_add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add a new group'),
        '#url' => Url::fromRoute('entity_share_server.group_add_form', [
          'channel' => $channel->id(),
        ]),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ];

      $header = [
        'id' => ['data' => $this->t('ID')],
        'conjunction' => ['data' => $this->t('Conjunction')],
        'memberof' => ['data' => $this->t('Parent group')],
        'operations' => ['data' => $this->t('Operations')],
      ];

      $rows = [];
      foreach ($channel_groups as $group_id => $group) {
        $operations = [
          '#type' => 'dropbutton',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('entity_share_server.group_edit_form', [
                'channel' => $channel->id(),
                'group' => $group_id,
              ]),
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity_share_server.group_delete_form', [
                'channel' => $channel->id(),
                'group' => $group_id,
              ]),
            ],
          ],
        ];

        $row = [
          'id' => $group_id,
          'conjunction' => $group['conjunction'],
          'memberof' => isset($group['memberof']) ? $group['memberof'] : '',
          'operations' => $this->renderer->render($operations),
        ];

        $rows[] = $row;
      }

      $form['channel_groups']['group_table'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('There is currently no group for this channel.'),
      ];
    }
  }

  /**
   * Helper function to generate filter form elements.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function buildFiltersTable(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_share_server\Entity\ChannelInterface $channel */
    $channel = $this->entity;
    $channel_filters = $channel->get('channel_filters');
    if (is_null($channel_filters)) {
      $channel_filters = [];
    }

    $form['channel_filters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filters'),
    ];

    if ($channel->isNew()) {
      $form['channel_filters']['filter_message'] = [
        '#markup' => $this->t("It will be possible to add filters after the channel's creation."),
      ];
    }
    else {
      $form['channel_filters']['filter_actions'] = [
        '#type' => 'actions',
        '#weight' => -5,
      ];
      $form['channel_filters']['filter_actions']['filter_add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add a new filter'),
        '#url' => Url::fromRoute('entity_share_server.filter_add_form', [
          'channel' => $channel->id(),
        ]),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ];

      $header = [
        'id' => ['data' => $this->t('ID')],
        'path' => ['data' => $this->t('Path')],
        'operator' => ['data' => $this->t('Operator')],
        'value' => ['data' => $this->t('Value')],
        'group' => ['data' => $this->t('Group')],
        'operations' => ['data' => $this->t('Operations')],
      ];

      $rows = [];
      foreach ($channel_filters as $filter_id => $filter) {
        $operations = [
          '#type' => 'dropbutton',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('entity_share_server.filter_edit_form', [
                'channel' => $channel->id(),
                'filter' => $filter_id,
              ]),
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity_share_server.filter_delete_form', [
                'channel' => $channel->id(),
                'filter' => $filter_id,
              ]),
            ],
          ],
        ];

        $row = [
          'id' => $filter_id,
          'path' => $filter['path'],
          'operator' => $filter['operator'],
          'value' => '',
          'filter' => isset($filter['memberof']) ? $filter['memberof'] : '',
          'operations' => $this->renderer->render($operations),
        ];

        if (isset($filter['value'])) {
          $value = [
            '#theme' => 'item_list',
            '#items' => $filter['value'],
          ];
          $row['value'] = $this->renderer->render($value);
        }

        $rows[] = $row;
      }

      $form['channel_filters']['filter_table'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('There is currently no filter for this channel.'),
      ];
    }
  }

  /**
   * Helper function to generate sort form elements.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function buildSortsTable(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_share_server\Entity\ChannelInterface $channel */
    $channel = $this->entity;
    $channel_sorts = $channel->get('channel_sorts');
    if (is_null($channel_sorts)) {
      $channel_sorts = [];
    }

    $form['channel_sorts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('sorts'),
    ];

    if ($channel->isNew()) {
      $form['channel_sorts']['sort_message'] = [
        '#markup' => $this->t("It will be possible to add sorts after the channel's creation."),
      ];
    }
    else {
      $form['channel_sorts']['sort_actions'] = [
        '#type' => 'actions',
        '#weight' => -5,
      ];
      $form['channel_sorts']['sort_actions']['sort_add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add a new sort'),
        '#url' => Url::fromRoute('entity_share_server.sort_add_form', [
          'channel' => $channel->id(),
        ]),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ];

      $header = [
        'id' => ['data' => $this->t('ID')],
        'path' => ['data' => $this->t('Path')],
        'direction' => ['data' => $this->t('Direction')],
        'weight' => ['data' => $this->t('Weight')],
        'operations' => ['data' => $this->t('Operations')],
      ];

      $form['channel_sorts']['sort_table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#empty' => $this->t('There is currently no sort for this channel.'),
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'weight',
          ],
        ],
      ];

      uasort($channel_sorts, [SortArray::class, 'sortByWeightElement']);
      foreach ($channel_sorts as $sort_id => $sort) {
        $row = [
          '#attributes' => [
            'class' => [
              'draggable',
            ],
          ],
          '#weight' => 'weight',
          'id' => [
            '#markup' => $sort_id,
          ],
          'path' => [
            '#markup' => $sort['path'],
          ],
          'direction' => [
            '#markup' => $sort['direction'],
          ],
          'weight' => [
            '#type' => 'weight',
            '#title' => t('Weight'),
            '#title_display' => 'invisible',
            '#default_value' => $sort['weight'],
            '#attributes' => ['class' => ['weight']],
          ],
          'operations' => [
            '#type' => 'dropbutton',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('entity_share_server.sort_edit_form', [
                  'channel' => $channel->id(),
                  'sort' => $sort_id,
                ]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('entity_share_server.sort_delete_form', [
                  'channel' => $channel->id(),
                  'sort' => $sort_id,
                ]),
              ],
            ],
          ],
        ];

        $form['channel_sorts']['sort_table'][$sort_id] = $row;
      }
    }
  }

  /**
   * Helper function to get the bundle options.
   *
   * @param string $selected_entity_type
   *   The entity type.
   *
   * @return array
   *   An array of options.
   */
  protected function getBundleOptions($selected_entity_type) {
    $options = [];
    foreach ($this->bundleInfos[$selected_entity_type] as $bundle_id => $bundle_info) {
      $options[$bundle_id] = $bundle_info['label'];
    }
    return $options;
  }

  /**
   * Helper function.
   *
   * Get users with permission entity_share_server_access_channels.
   *
   * @return array
   *   An array of options.
   */
  protected function getAuthorizedUsersOptions() {
    $authorized_users = [];
    $authorized_roles = [];
    $users = [];

    // Filter on roles having access to the channel list.
    /** @var \Drupal\user\RoleInterface[] $roles */
    $roles = $this->entityTypeManager
      ->getStorage('user_role')
      ->loadMultiple();
    foreach ($roles as $role) {
      if ($role->hasPermission('entity_share_server_access_channels')) {
        $authorized_roles[] = $role->id();
      }
    }

    if (!empty($authorized_roles)) {
      $users = $this->entityTypeManager
        ->getStorage('user')
        ->loadByProperties(['roles' => $authorized_roles]);
    }

    foreach ($users as $user) {
      $authorized_users[$user->uuid()] = $user->label();
    }

    return $authorized_users;
  }

}
