<?php

namespace Drupal\jsonapi\Routing\Param;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Component\Render\FormattableMarkup;

/**
 * @internal
 */
class Filter extends JsonApiParamBase {

  /**
   * {@inheritdoc}
   */
  const KEY_NAME = 'filter';

  /**
   * Key in the filter[<key>] parameter for conditions.
   *
   * @var string
   */
  const CONDITION_KEY = 'condition';

  /**
   * Key in the filter[<key>] parameter for groups.
   *
   * @var string
   */
  const GROUP_KEY = 'group';

  /**
   * Key in the filter[<id>][<key>] parameter for group membership.
   *
   * @var string
   */
  const MEMBER_KEY = 'memberOf';

  /**
   * The field key in the filter condition: filter[lorem][condition][<field>].
   *
   * @var string
   */
  const PATH_KEY = 'path';

  /**
   * The value key in the filter condition: filter[lorem][condition][<value>].
   *
   * @var string
   */
  const VALUE_KEY = 'value';

  /**
   * The operator key in the condition: filter[lorem][condition][<operator>].
   *
   * @var string
   */
  const OPERATOR_KEY = 'operator';

  /**
   * The conjunction key in the condition: filter[lorem][group][<conjunction>].
   *
   * @var string
   */
  const CONJUNCTION_KEY = 'conjunction';

  /**
   * {@inheritdoc}
   */
  protected function expand() {
    // We should always get an array for the filter.
    if (!is_array($this->original)) {
      throw new BadRequestHttpException('Incorrect value passed to the filter parameter.');
    }

    $expanded = [];
    foreach ($this->original as $filter_index => $filter_item) {
      $expanded_item = $this->expandItem($filter_index, $filter_item);
      $this->validateItem($filter_index, $expanded_item);
      $expanded[$filter_index] = $expanded_item;
    }
    return $expanded;
  }

  /**
   * Expands a filter item in case a shortcut was used.
   *
   * Possible cases for the conditions:
   *   1. filter[uuid][value]=1234.
   *   2. filter[0][condition][path]=uuid&filter[0][condition][value]=1234.
   *   3. filter[uuid][value]=1234&filter[uuid][path]=uuid&
   *      filter[uuid][operator]==&filter[uuid][memberOf]=my_group.
   *
   * @param string $filter_index
   *   The index.
   * @param mixed $filter_item
   *   The raw filter item.
   *
   * @return array
   *   The expanded filter item.
   */
  protected function expandItem($filter_index, $filter_item) {
    // Expand shorthand.
    if (isset($filter_item[static::VALUE_KEY])) {
      if (!isset($filter_item[static::PATH_KEY])) {
        $filter_item[static::PATH_KEY] = $filter_index;
      }
      $filter_item = [
        static::CONDITION_KEY => $filter_item,
      ];

      if (!isset($filter_item[static::CONDITION_KEY][static::OPERATOR_KEY])) {
        $filter_item[static::CONDITION_KEY][static::OPERATOR_KEY] = '=';
      }
    }
    // Expand IS (NOT) NULL items.
    elseif (
      isset($filter_item[static::CONDITION_KEY][static::OPERATOR_KEY]) &&
      in_array($filter_item[static::CONDITION_KEY][static::OPERATOR_KEY], ['IS NULL', 'IS NOT NULL'])
    ) {
      // This is not strictly necessary, but it simplifies validation.
      $filter_item[static::CONDITION_KEY][static::VALUE_KEY] = NULL;
    }

    return $filter_item;
  }

  /**
   * Makes sure every filter item contains valid data.
   *
   * @param string $filter_index
   *   The index.
   * @param mixed $filter_item
   *   The expanded filter item.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the filter is malformed.
   */
  protected function validateItem($filter_index, $filter_item) {
    // Make sure the current filter item is an array. So far we don't allow
    // filter queries like filter[nid]=1.
    if (!is_array($filter_item)) {
      $message = new FormattableMarkup(
        'Filter query for "@index" must be array.',
        ['@index' => $filter_index]
      );
      throw new BadRequestHttpException($message);
    }

    // We do not allow having both "condition" and "group" for the same filter
    // item. So for example this condition should fail:
    // - filter[id][condition][path]=nid
    // - filter[id][condition][value]=123
    // - filter[id][condition][operator]==
    // - filter[id][group][conjunction]=AND.
    $filter_keys = array_keys($filter_item);
    $allowed_filter_keys = [static::CONDITION_KEY, static::GROUP_KEY];
    if (count($filter_keys) > 1 || !in_array($filter_keys[0], $allowed_filter_keys)) {
      $message = new FormattableMarkup(
        'Filter query for "@index" should only contain either "@condkey" or "@groupkey" as a top-level key, but not both.',
        [
          '@index' => $filter_index,
          '@condkey' => static::CONDITION_KEY,
          '@groupkey' => static::GROUP_KEY,
        ]
      );
      throw new BadRequestHttpException($message);
    }

    // Handle full canonical form:
    // - filter[id][condition][path]=nid
    // - filter[id][condition][value]=123
    // - filter[id][condition][operator]==
    // - filter[id][condition][memberOf]=some-group.
    if (isset($filter_item[static::CONDITION_KEY])) {
      $this->validateConditionItem($filter_index, $filter_item);
    }
    // Validating filter groups. Example of group:
    // filter[and-group][group][conjunction]=AND
    // filter[and-group][group][memberOf]=another-group.
    elseif (isset($filter_item[static::GROUP_KEY])) {
      $this->validateGroupItem($filter_index, $filter_item);
    }

  }

  /**
   * Makes sure a condition in a filter item contains valid data.
   *
   * @param string $filter_index
   *   The index.
   * @param mixed $filter_item
   *   The expanded filter item.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the filter is malformed.
   */
  protected function validateConditionItem($filter_index, $filter_item) {
    // List of allowed keys for adding a new condition to the query.
    $expected_keys = [
      static::PATH_KEY,
      static::VALUE_KEY,
      static::OPERATOR_KEY,
      static::MEMBER_KEY,
    ];

    // Get keys sent by the client.
    $item_keys = array_keys($filter_item[static::CONDITION_KEY]);

    // If the client sent any keys outside of allowed, we should return an
    // error to indicate that the desired set of params is not going to work.
    $unexpected_keys = array_diff($item_keys, $expected_keys);
    if (!empty($unexpected_keys)) {
      $message = new FormattableMarkup(
        'Filter query for "@index" contains not expected arguments: @invalid_args. Valid arguments: @valid_args',
        [
          '@index' => $filter_index,
          '@invalid_args' => implode(',', $unexpected_keys),
          '@valid_args' => implode(',', $expected_keys),
        ]
      );
      throw new BadRequestHttpException($message);
    }

    // It is required to set the next keys for full canonical filter
    // representation.
    $mandatory_keys = [static::PATH_KEY, static::VALUE_KEY];

    // If any mandatory key is missing - report back to the client.
    $missing_mandatory_keys = array_diff($mandatory_keys, $item_keys);
    if (!empty($missing_mandatory_keys)) {
      $message = new FormattableMarkup(
        'Filter query for "@index" missing mandatory params: @missing_params.',
        [
          '@index' => $filter_index,
          '@missing_params' => implode(',', $missing_mandatory_keys),
        ]
      );
      throw new BadRequestHttpException($message);
    }
  }

  /**
   * Makes sure a condition in a filter item contains valid data.
   *
   * @param string $filter_index
   *   The index.
   * @param mixed $filter_item
   *   The expanded filter item.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the filter is malformed.
   */
  protected function validateGroupItem($filter_index, $filter_item) {
    // List of allowed keys for adding a new filter group.
    $expected_keys = [static::CONJUNCTION_KEY, static::MEMBER_KEY];

    // If the client sent any keys outside of allowed, we should return an
    // error to indicate that the desired set of params is not going to work.
    $item_keys = array_keys($filter_item[static::GROUP_KEY]);
    $unexpected_keys = array_diff($item_keys, $expected_keys);
    if (!empty($unexpected_keys)) {
      $message = new FormattableMarkup(
        'Filter query for "@index" contains unexpected arguments: @invalid_args. Valid arguments: @valid_args',
        [
          '@index' => $filter_index,
          '@invalid_args' => implode(',', $unexpected_keys),
          '@valid_args' => implode(',', $expected_keys),
        ]
      );
      throw new BadRequestHttpException($message);
    }

    // If any mandatory key is missing - report back to the client.
    $mandatory_keys = [static::CONJUNCTION_KEY];
    $missing_mandatory_keys = array_diff($mandatory_keys, $item_keys);
    if (!empty($missing_mandatory_keys)) {
      $message = new FormattableMarkup(
        'Filter query for "@index" missing mandatory params: @missing_params.',
        [
          '@index' => $filter_index,
          '@missing_params' => implode(',', $missing_mandatory_keys),
        ]
      );
      throw new BadRequestHttpException($message);
    }

    // Make sure the conjunction value is correct.
    $allowed_conjunction = ['AND', 'OR', 'and', 'or'];
    if (!in_array($filter_item[static::GROUP_KEY][static::CONJUNCTION_KEY], $allowed_conjunction)) {
      $message = new FormattableMarkup(
        'Filter query for "@index" contains invalid conjunction operator. Allowed values: AND, OR.',
        ['@index' => $filter_index]
      );
      throw new BadRequestHttpException($message);
    }

    // We do not allow having anything other than "group" in the filter query.
    // So for example this condition should fail:
    // - filter[][group][conjunction]=AND
    // - filter[][nid][value]=123.
    if (count($filter_item) > 1) {
      $message = new FormattableMarkup(
        'Filter query for "@index" should contain only "@key" as a top-level key.',
        ['@index' => $filter_index, '@key' => static::GROUP_KEY]);
      throw new BadRequestHttpException($message);
    }
  }

}
