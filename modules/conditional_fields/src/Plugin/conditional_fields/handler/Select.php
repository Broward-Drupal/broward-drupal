<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for multiple select lists.
 *
 * Multiple select fields always require an array as value.
 * In addition, since our modified States API triggers a dependency only if all
 * reference values of type Array are selected, a different selector must be
 * added for each value of a set for OR, XOR and NOT evaluations.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_options_select",
 * )
 */
class Select extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    switch ($options['values_set']) {
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        return $this->widgetCase($field, $options);

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        if (isset($state[$options['state']][$options['selector']]['value'])) {
          $state[$options['state']][$options['selector']]['value'] = (array) $state[$options['state']][$options['selector']]['value'];
        }
        else {
          $state[$options['state']][$options['selector']]['value'] = [];
        }
        return $state;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $select_states[$options['state']][] = 'xor';

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        $regex = TRUE;
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        foreach ($options['values'] as $value) {
          $select_states[$options['state']][] = [
            $options['selector'] => [
              $options['condition'] => empty($regex) ? [$value] : $options['value'],
            ],
          ];
        }
        break;
    }

    $state = $select_states;
    return $state;
  }

  /**
   * Returns state in widget input case.
   */
  protected function widgetCase($field, $options) {
    $state = [];
    $key_column = $field['#key_column'];

    if (empty($key_column)) {
      return $state;
    }

    if (!empty($options['value_form'][0][$key_column]) && $options['field_cardinality'] == 1) {
      $state[$options['state']][$options['selector']] = [
        'value' => $options['value_form'][0][$key_column],
      ];
    }
    else {
      $values = array_column($options['value_form'], $key_column);
      $state[$options['state']][$options['selector']] = array('value' => $values);
    }

    return $state;
  }

}