<?php

namespace Drupal\linkit\Plugin\Linkit\Matcher;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific linkit matchers for the node entity type.
 *
 * @Matcher(
 *   id = "entity:node",
 *   label = @Translation("Content"),
 *   target_entity = "node",
 *   provider = "node"
 * )
 */
class NodeMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summery = parent::getSummary();

    $summery[] = $this->t('Include unpublished: @include_unpublished', [
      '@include_unpublished' => $this->configuration['include_unpublished'] ? $this->t('Yes') : $this->t('No'),
    ]);

    return $summery;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'include_unpublished' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'module' => ['node'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['unpublished_nodes'] = [
      '#type' => 'details',
      '#title' => $this->t('Unpublished nodes'),
      '#open' => TRUE,
    ];

    $form['unpublished_nodes']['include_unpublished'] = [
      '#title' => $this->t('Include unpublished nodes'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['include_unpublished'],
      '#description' => $this->t('In order to see unpublished nodes, users must also have permissions to do so.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['include_unpublished'] = $form_state->getValue('include_unpublished');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($search_string) {
    $query = parent::buildEntityQuery($search_string);

    $no_access = !$this->currentUser->hasPermission('bypass node access') && !count($this->moduleHandler->getImplementations('node_grants'));
    if ($this->configuration['include_unpublished'] !== TRUE || $no_access) {
      $query->condition('status', NODE_PUBLISHED);
    }

    return $query;
  }

}
