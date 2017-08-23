<?php

namespace Drupal\config_filter\Tests;

use Drupal\config_filter\Config\FilteredStorage;
use Drupal\config_filter\Config\ReadOnlyStorage;
use Drupal\config_filter\Config\StorageFilterInterface;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\Core\Config\Storage\CachedStorageTest;
use Prophecy\Argument;

/**
 * Tests StorageWrapper operations using the CachedStorage.
 *
 * @group config_filter
 */
class FilteredStorageTest extends CachedStorageTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // The storage is a wrapper with a transparent filter.
    // So all inherited tests should still pass.
    $this->storage = new FilteredStorage($this->storage, [new TransparentFilter()]);
  }

  /**
   * Test that the storage is set on the filters.
   */
  public function testSettingStorages() {
    $filterReflection = new \ReflectionClass(FilteredStorage::class);
    $filtersProperty = $filterReflection->getProperty('filters');
    $filtersProperty->setAccessible(TRUE);

    /** @var \Drupal\config_filter\Tests\TransparentFilter[] $filters */
    $filters = $filtersProperty->getValue($this->storage);
    foreach ($filters as $filter) {
      // Test that the source storage is a ReadonlyStorage and wraps the cached
      // storage from the inherited test.
      $readonly = $filter->getPrivateSourceStorage();
      $this->assertInstanceOf(ReadOnlyStorage::class, $readonly);
      $readonlyReflection = new \ReflectionClass(ReadOnlyStorage::class);
      $storageProperty = $readonlyReflection->getProperty('storage');
      $storageProperty->setAccessible(TRUE);
      $source = $storageProperty->getValue($readonly);
      $this->assertInstanceOf(CachedStorage::class, $source);

      // Assert that the filter gets the storage.
      $this->assertEquals($this->storage, $filter->getPrivateFilteredStorage());
    }
  }

  /**
   * Test the read methods invokes the correct filter methods.
   *
   * @dataProvider readFilterProvider
   */
  public function testReadFilter($name, $storageMethod, $filterMethod, $data, $expected) {
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $source->$storageMethod($name)->willReturn($data);
    $interim = $this->randomArray();
    $filterA->$filterMethod($name, $data)->willReturn($interim);
    $filterB->$filterMethod($name, $interim)->willReturn($expected);

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertEquals($expected, $storage->$storageMethod($name));
  }

  /**
   * Data provider for testReadFilter.
   */
  public function readFilterProvider() {
    // @codingStandardsIgnoreStart
    return [
      [$this->randomString(), 'exists', 'filterExists', TRUE, TRUE],
      [$this->randomString(), 'exists', 'filterExists', TRUE, FALSE],
      [$this->randomString(), 'exists', 'filterExists', FALSE, TRUE],
      [$this->randomString(), 'exists', 'filterExists', FALSE, FALSE],

      [$this->randomString(), 'read', 'filterRead', $this->randomArray(), $this->randomArray()],
      [$this->randomString(), 'read', 'filterRead', NULL, $this->randomArray()],
      [$this->randomString(), 'read', 'filterRead', $this->randomArray(), NULL],

      [
        [$this->randomString(), $this->randomString()],
        'readMultiple',
        'filterReadMultiple',
        [$this->randomArray(), $this->randomArray()],
        [$this->randomArray(), $this->randomArray()],
      ],
      [
        [$this->randomString(), $this->randomString()],
        'readMultiple',
        'filterReadMultiple',
        [$this->randomArray(), FALSE],
        [$this->randomArray(), $this->randomArray()],
      ],

      [
        $this->randomString(),
        'listAll',
        'filterListAll',
        ['a' . $this->randomString(), 'b' . $this->randomString()],
        ['a' . $this->randomString(), 'b' . $this->randomString()],
      ],
    ];
    // @codingStandardsIgnoreEnd
  }

  /**
   * Test the write method invokes the filterWrite in filters.
   *
   * @dataProvider writeFilterProvider
   */
  public function testWriteFilter($interim, $expected, $exists = TRUE) {
    $name = $this->randomString();
    $data = $this->randomArray();
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $filterA->filterWrite($name, $data)->willReturn($interim);
    $interim = is_array($interim) ? $interim : [];
    $filterB->filterWrite($name, $interim)->willReturn($expected);

    if ($expected) {
      $source->write($name, $expected)->willReturn(TRUE);
    }
    else {
      $source->write(Argument::any())->shouldNotBeCalled();
      $source->exists($name)->willReturn($exists);
      if ($exists) {
        $filterA->filterWriteEmptyIsDelete($name)->willReturn(TRUE);
        $source->delete($name)->willReturn(TRUE);
      }
    }

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertTrue($storage->write($name, $data));
  }

  /**
   * Data provider for testWriteFilter.
   */
  public function writeFilterProvider() {
    return [
      [$this->randomArray(), $this->randomArray()],
      [NULL, $this->randomArray()],
      [[], $this->randomArray()],
      [$this->randomArray(), NULL, FALSE],
      [$this->randomArray(), [], FALSE],
      [$this->randomArray(), NULL, TRUE],
    ];
  }

  /**
   * Test the delete method invokes the filterDelete in filters.
   *
   * @dataProvider deleteFilterProvider
   */
  public function testDeleteFilter($interim, $expected) {
    $name = $this->randomString();
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $filterA->filterDelete($name, TRUE)->willReturn($interim);
    $filterB->filterDelete($name, $interim)->willReturn($expected);

    if ($expected) {
      $source->delete($name)->willReturn(TRUE);
    }
    else {
      $source->delete(Argument::any())->shouldNotBeCalled();
    }

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertEquals($expected, $storage->delete($name));
  }

  /**
   * Data provider for testDeleteFilter.
   */
  public function deleteFilterProvider() {
    return [
      [TRUE, TRUE],
      [FALSE, TRUE],
      [TRUE, FALSE],
      [FALSE, FALSE],
    ];
  }

  /**
   * Test the rename method invokes the filterRename in filters.
   *
   * @dataProvider renameFilterProvider
   */
  public function testRenameFilter($interim, $expected) {
    $name = $this->randomString();
    $name2 = $this->randomString();
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $filterA->filterRename($name, $name2, TRUE)->willReturn($interim);
    $filterB->filterRename($name, $name2, $interim)->willReturn($expected);

    if ($expected) {
      $source->rename($name, $name2)->willReturn(TRUE);
    }
    else {
      $source->rename(Argument::any())->shouldNotBeCalled();
    }

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertEquals($expected, $storage->rename($name, $name2));
  }

  /**
   * Data provider for testRenameFilter.
   */
  public function renameFilterProvider() {
    return [
      [TRUE, TRUE],
      [FALSE, TRUE],
      [TRUE, FALSE],
      [FALSE, FALSE],
    ];
  }

  /**
   * Test the deleteAll method invokes the filterDeleteAll in filters.
   *
   * @dataProvider deleteAllFilterProvider
   */
  public function testDeleteAllFilter($interim, $expected) {
    $name = $this->randomString();
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $filterA->filterDeleteAll($name, TRUE)->willReturn($interim);
    $filterB->filterDeleteAll($name, $interim)->willReturn($expected);

    if ($expected) {
      $source->deleteAll($name)->willReturn(TRUE);
    }
    else {
      $source->deleteAll(Argument::any())->shouldNotBeCalled();
      $all = [$this->randomString(), $this->randomString()];
      $source->listAll($name)->willReturn($all);

      foreach ($all as $item) {
        $filterA->filterDelete($item, TRUE)->willReturn(TRUE);
        $filterB->filterDelete($item, TRUE)->willReturn(FALSE);
      }
    }

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertTrue($storage->deleteAll($name));
  }

  /**
   * Data provider for testDeleteAllFilter.
   */
  public function deleteAllFilterProvider() {
    return [
      [TRUE, TRUE],
      [FALSE, TRUE],
      [TRUE, FALSE],
      [FALSE, FALSE],
    ];
  }

  /**
   * Prophesize a StorageFilter.
   */
  protected function prophesizeFilter() {
    $filter = $this->prophesize(StorageFilterInterface::class);
    $filter->setSourceStorage(Argument::type(ReadOnlyStorage::class))->shouldBeCalledTimes(1);
    $filter->setFilteredStorage(Argument::type(FilteredStorage::class))->shouldBeCalledTimes(1);
    return $filter;
  }

  /**
   * Create a random array.
   */
  protected function randomArray($size = 4) {
    return (array) $this->randomObject($size);
  }

}
