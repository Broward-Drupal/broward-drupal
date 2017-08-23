<?php

namespace Drupal\entity_share_server\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FilterAddForm.
 *
 * @package Drupal\entity_share_server\Form
 */
class FilterAddForm extends FilterBaseForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('Enter the machine name of the field / property you want to filter on. You can reference field / property of a referenced entity. Example: uid.name for the name of the author.'),
      '#required' => TRUE,
    ];

    $form['filter_id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#machine_name' => [
        'source' => ['path'],
        'exists' => [$this, 'filterExists'],
      ],
    ];

    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#options' => $this->getOperatorOptions(),
      '#empty_option' => $this->t('Select an operator'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'buildAjaxValueElement'],
        'wrapper' => 'value-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];
    // Container for the AJAX.
    $form['value_wrapper'] = [
      '#type' => 'container',
      // Force an id because otherwise default id is changed when using AJAX.
      '#attributes' => [
        'id' => 'value-wrapper',
      ],
    ];

    $this->buildValueElement($form, $form_state);

    $form['memberof'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent group'),
      '#options' => $this->getGroupOptions(),
      '#empty_option' => $this->t('Select a group'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_share_server\Entity\ChannelInterface $channel */
    $channel = $this->entity;
    $channel_filters = $channel->get('channel_filters');

    if (is_null($channel_filters)) {
      $channel_filters = [];
    }

    // Add the filter.
    $new_filter = [
      'path' => $form_state->getValue('path'),
      'operator' => $form_state->getValue('operator'),
    ];

    $value = $form_state->getValue('value');
    if (!is_null($value)) {
      $new_filter['value'] = explode(',', $value);
    }

    $memberof = $form_state->getValue('memberof');
    if (!empty($memberof)) {
      $new_filter['memberof'] = $memberof;
    }

    $channel_filters[$form_state->getValue('filter_id')] = $new_filter;
    $channel->set('channel_filters', $channel_filters);
    $channel->save();

    $form_state->setRedirectUrl($channel->toUrl('edit-form'));
  }

  /**
   * Helper function to generate filter form elements.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function buildValueElement(array &$form, FormStateInterface $form_state) {
    $selected_operator = $form_state->getValue('operator');

    // No operator selected.
    if (empty($selected_operator)) {
      return;
    }

    // Operators which do not require value.
    if (in_array($selected_operator, ['IS NULL', 'IS NOT NULL'])) {
      return;
    }

    $form['value_wrapper']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#required' => TRUE,
    ];

    // Add additional description for operator which handle or require multiple
    // values.
    if (in_array($selected_operator, ['IN', 'NOT IN', 'CONTAINS'])) {
      $form['value_wrapper']['value']['#description'] = $this->t('Separate the values with a comma. Example: <strong>admin,john,doe</strong>.');
    }
  }

}
