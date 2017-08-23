<?php

namespace Drupal\Tests\jsonapi\Unit\Routing\Param;

use Drupal\jsonapi\Routing\Param\Filter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\jsonapi\Routing\Param\Filter
 * @group jsonapi
 * @group jsonapi_params
 */
class FilterTest extends UnitTestCase {

  /**
   * @covers ::get
   * @covers ::expand
   * @covers ::expandItem
   * @covers ::validateItem
   * @dataProvider validFiltersDataProvider
   */
  public function testValidFilters($original, $expected) {
    $filter = new Filter($original);
    $this->assertEquals($expected, $filter->get());
  }

  /**
   * @covers ::get
   * @covers ::expand
   * @covers ::expandItem
   * @covers ::validateItem
   * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @dataProvider invalidFiltersDataProvider
   */
  public function testInvalidFilters($original) {
    $filter = new Filter($original);
    $filter->get();
  }

  /**
   * Data provider for testValidFilters().
   */
  public function validFiltersDataProvider() {
    return [
      // Test case:
      // filter[foo][value]=bar.
      [
        ['foo' => ['value' => 'bar']],
        ['foo' => ['condition' => ['path' => 'foo', 'value' => 'bar', 'operator' => '=']]],
      ],
      // Test case:
      // filter[0][path]=foo
      // filter[0][value]=bar.
      [
        [0 => ['path' => 'foo', 'value' => 'bar']],
        [0 => ['condition' => ['path' => 'foo', 'value' => 'bar', 'operator' => '=']]],
      ],
      // Test case:
      // filter[foo][value]=bar
      // filter[foo][operator]=>.
      [
        ['foo' => ['value' => 'bar', 'operator' => '>']],
        ['foo' => ['condition' => ['path' => 'foo', 'value' => 'bar', 'operator' => '>']]],
      ],
      // Test case:
      // filter[0][path]=foo
      // filter[0][value]=1
      // filter[0][operator]=>.
      [
        [0 => ['path' => 'foo', 'value' => '1', 'operator' => '>']],
        [0 => ['condition' => ['path' => 'foo', 'value' => '1', 'operator' => '>']]],
      ],
      // Test case:
      // filter[foo][value][]=1
      // filter[foo][value][]=2
      // filter[foo][value][]=3
      // filter[foo][operator]="NOT IN".
      [
        ['foo' => ['value' => ['1', '2', '3'], 'operator' => 'NOT IN']],
        ['foo' => ['condition' => ['path' => 'foo', 'value' => ['1', '2', '3'], 'operator' => 'NOT IN']]],
      ],
      // Test case:
      // filter[foo][value][]=1
      // filter[foo][value][]=10
      // filter[foo][operator]=BETWEEN.
      [
        ['foo' => ['value' => ['1', '10'], 'operator' => 'BETWEEN']],
        ['foo' => ['condition' => ['path' => 'foo', 'value' => ['1', '10'], 'operator' => 'BETWEEN']]],
      ],
      // Test case:
      // filter[0][condition][path]=foo
      // filter[0][condition][value]=1
      // filter[0][condition][operator]=>.
      [
        [0 => ['condition' => ['path' => 'foo', 'value' => '1', 'operator' => '>']]],
        [0 => ['condition' => ['path' => 'foo', 'value' => '1', 'operator' => '>']]],
      ],
      // Test case:
      // filter[0][path]=foo
      // filter[0][value][]=bar
      // filter[0][value][]=baz.
      [
        [0 => ['path' => 'foo', 'value' => ['bar', 'baz']]],
        [0 => ['condition' => ['path' => 'foo', 'value' => ['bar', 'baz'], 'operator' => '=']]],
      ],
      // Test case:
      // filter[0][path]=foo
      // filter[0][value][]=bar
      // filter[0][value][]=baz
      // filter[0][memberOf]=or-group
      // filter[or-group][group][conjunction]=OR.
      [
        [0 => ['path' => 'foo', 'value' => ['bar', 'baz'], 'memberOf' => 'or-group'], 'or-group' => ['group' => ['conjunction' => 'OR']]],
        [0 => ['condition' => ['path' => 'foo', 'value' => ['bar', 'baz'], 'operator' => '=', 'memberOf' => 'or-group']], 'or-group' => ['group' => ['conjunction' => 'OR']]],
      ],
      // Test case:
      // filter[0][path]=foo
      // filter[0][value]=bar
      // filter[1][condition][path]=baz
      // filter[1][condition][value]=zab
      // filter[1][condition][operator]=<>.
      [
        [
          0 => ['path' => 'foo', 'value' => 'bar'],
          1 => ['condition' => ['path' => 'baz', 'value' => 'zab', 'operator' => '<>']],
        ],
        [
          0 => ['condition' => ['path' => 'foo', 'value' => 'bar', 'operator' => '=']],
          1 => ['condition' => ['path' => 'baz', 'value' => 'zab', 'operator' => '<>']],
        ],
      ],
      // Test case:
      // filter[zero][path]=foo
      // filter[zero][value]=bar
      // filter[one][condition][path]=baz
      // filter[one][condition][value]=zab
      // filter[one][condition][operator]=<>.
      [
        [
          'zero' => ['path' => 'foo', 'value' => 'bar'],
          'one' => ['condition' => ['path' => 'baz', 'value' => 'zab', 'operator' => '<>']],
        ],
        [
          'zero' => ['condition' => ['path' => 'foo', 'value' => 'bar', 'operator' => '=']],
          'one' => ['condition' => ['path' => 'baz', 'value' => 'zab', 'operator' => '<>']],
        ],
      ],
      // Test case:
      // filter[and-group][group][conjunction]=AND
      // filter[or-group][group][conjunction]=OR
      // filter[or-group][group][memberOf]=and-group
      // filter[admin-filter][path]=uid.name
      // filter[admin-filter][value]=admin
      // filter[admin-filter][memberOf]=and-group
      // filter[sticky-filter][path]=sticky
      // filter[sticky-filter][value]=1
      // filter[sticky-filter][memberOf]=or-group
      // filter[promote-filter][path]=promote
      // filter[promote-filter][value]=1
      // filter[promote-filter][memberOf]=or-group.
      [
        [
          'and-group' => ['group' => ['conjunction' => 'AND']],
          'or-group' => ['group' => ['conjunction' => 'OR', 'memberOf' => 'and-group']],
          'admin-filter' => ['path' => 'uid.name', 'value' => 'admin', 'memberOf' => 'and-group'],
          'sticky-filter' => ['path' => 'sticky', 'value' => 1, 'memberOf' => 'or-group'],
          'promote-filter' => ['path' => 'promote', 'value' => 1, 'memberOf' => 'or-group'],
        ],
        [
          'and-group' => ['group' => ['conjunction' => 'AND']],
          'or-group' => ['group' => ['conjunction' => 'OR', 'memberOf' => 'and-group']],
          'admin-filter' => ['condition' => ['path' => 'uid.name', 'value' => 'admin', 'operator' => '=', 'memberOf' => 'and-group']],
          'sticky-filter' => ['condition' => ['path' => 'sticky', 'value' => 1, 'operator' => '=', 'memberOf' => 'or-group']],
          'promote-filter' => ['condition' => ['path' => 'promote', 'value' => 1, 'operator' => '=', 'memberOf' => 'or-group']],
        ],
      ],
      // Test case:
      // filter[and-group][group][conjunction]=AND
      // filter[or-group][group][conjunction]=OR
      // filter[or-group][group][memberOf]=and-group
      // filter[admin-filter][condition][path]=uid.name
      // filter[admin-filter][condition][value]=admin
      // filter[admin-filter][condition][memberOf]=and-group
      // filter[sticky-filter][condition][path]=sticky
      // filter[sticky-filter][condition][value]=1
      // filter[sticky-filter][condition][memberOf]=or-group
      // filter[promote-filter][condition][path]=promote
      // filter[promote-filter][condition][value]=1
      // filter[promote-filter][condition][memberOf]=or-group.
      [
        [
          'and-group' => ['group' => ['conjunction' => 'AND']],
          'or-group' => ['group' => ['conjunction' => 'OR', 'memberOf' => 'and-group']],
          'admin-filter' => ['condition' => ['path' => 'uid.name', 'value' => 'admin', 'memberOf' => 'and-group']],
          'sticky-filter' => ['condition' => ['path' => 'sticky', 'value' => 1, 'memberOf' => 'or-group']],
          'promote-filter' => ['condition' => ['path' => 'promote', 'value' => 1, 'memberOf' => 'or-group']],
        ],
        [
          'and-group' => ['group' => ['conjunction' => 'AND']],
          'or-group' => ['group' => ['conjunction' => 'OR', 'memberOf' => 'and-group']],
          'admin-filter' => ['condition' => ['path' => 'uid.name', 'value' => 'admin', 'memberOf' => 'and-group']],
          'sticky-filter' => ['condition' => ['path' => 'sticky', 'value' => 1, 'memberOf' => 'or-group']],
          'promote-filter' => ['condition' => ['path' => 'promote', 'value' => 1, 'memberOf' => 'or-group']],
        ],
      ],

      // filter[has-sticky][path]=sticky
      // filter[has-sticky[operator]='IS NOT NULL'
      [
        ['has-sticky' => ['condition' => ['path' => 'sticky', 'operator' => 'IS NOT NULL']]],
        ['has-sticky' => [
          'condition' => ['path' => 'sticky', 'operator' => 'IS NOT NULL', 'value' => NULL]
        ]],
      ]
    ];
  }

  /**
   * Data provider for testInvalidFilters().
   */
  public function invalidFiltersDataProvider() {
    return [
      // Filter suppors only arrays.
      // # Test case:
      // filter[foo]=bar
      // # Reason to fail:
      // "bar" is a string and not an array.
      [
        ['foo' => 'bar'],
      ],
      // Filter supports only certain keys.
      // # Test case:
      // filter[foo][nid]=1
      // # Reason to fail:
      // Filter expects "group", "condition" or "value" key in the filter root.
      [
       ['foo' => ['nid' => 1]],
      ],
      // Shorthand filter supports only allowed list of params.
      // # Test case:
      // filter[foo][value]=1
      // filter[foo][bar]=baz
      // # Reason to fail:
      // "bar" is not expected key for filtering.
      [
        ['foo' => ['value' => 1, 'bar' => 'baz']],
      ],
      // Shorthand filter supports only allowed list of params.
      // # Test case:
      // filter[foo][value]=1
      // filter[foo][group]=bar
      // # Reason to fail:
      // "group" is a legacy and not supported anymore group key.
      [
        ['foo' => ['value' => 1, 'group' => 'bar']],
      ],
      // Full canonical filter has mandatory params.
      // # Test case:
      // filter[foo][condition][value]=1
      // # Reason to fail:
      // Missing mandatory "path" key.
      [
        ['foo' => ['condition' => ['value' => 1]]],
      ],
      // Full canonical filter has mandatory params.
      // # Test case:
      // filter[foo][condition][path]=nid
      // # Reason to fail:
      // Missing mandatory "value" key.
      [
        ['foo' => ['condition' => ['path' => 'nid']]],
      ],
      // Full canonical filter supports only allowed list of params.
      // # Test case:
      // filter[foo][condition][value]=1
      // filter[foo][condition][path]=nid
      // filter[foo][condition][bar]=baz.
      // # Reason to fail:
      // "bar" is not expected filtering key.
      [
        ['foo' => ['condition' => ['value' => 1, 'path' => 'nid', 'bar' => 'baz']]],
      ],
      // Full canonical filter allows only one top level key "condition".
      // # Test case:
      // filter[foo][condition][value]=1
      // filter[foo][condition][path]=nid.
      // filter[foo][value]=baz.
      // # Reason to fail:
      // "value" => "bar" is not expected next to the filter[condition] query.
      [
        ['foo' => ['condition' => ['value' => 1, 'path' => 'nid'], ['value' => 'baz']]],
      ],
      // Group query supports only allowed list of params.
      // # Test case:
      // filter[foo][group][conjunction]=AND
      // filter[foo][group][bar]=baz
      // # Reason to fail:
      // "bar" is not supported group key.
      [
        ['foo' => ['group' => ['conjunction' => 'AND', 'bar' => 'baz']]],
      ],
      // Group query supports only allowed list of params.
      // # Test case:
      // filter[foo][group][conjunction]=AND
      // filter[foo][group][group]=bar
      // # Reason to fail:
      // "group" is a legacy and not supported anymore group key.
      [
        ['foo' => ['group' => ['conjunction' => 'AND', 'group' => 'bar']]],
      ],
      // Group query supports only allowed list of params.
      // # Test case:
      // filter[foo][group][bar]=baz
      // filter[foo][group][conjunction]=AND
      // # Reason to fail:
      // "bar" is not supported group key.
      [
        ['foo' => ['group' => ['bar' => 'baz', 'conjunction' => 'AND']]],
      ],
      // Group query has mandatory field "conjunction".
      // # Test case:
      // filter[foo][group][memberOf]=bar
      // # Reason to fail:
      // "conjunction" key is missing.
      [
        ['foo' => ['group' => ['memberOf' => 'bar']]],
      ],
      // Group query has only certain correct values for "conjunction" key.
      // # Test case:
      // filter[foo][group][conjunction]=NOR
      // # Reason to fail:
      // "conjunction" key has wrong value.
      [
        ['foo' => ['group' => ['conjunction' => 'NOR']]],
      ],
      // Group query allows only one top level key "group".
      // # Test case:
      // filter[foo][group][conjunction]=AND
      // filter[foo][group][memberOf]=bar
      // filter[foo][value]=baz.
      // # Reason to fail:
      // "value" => "bar" is not expected next to the filter[condition] query.
      [
        ['foo' => ['group' => ['conjunction' => 'AND', 'memberOf' => 'bar'], ['value' => 'baz']]],
      ],
    ];
  }

}
