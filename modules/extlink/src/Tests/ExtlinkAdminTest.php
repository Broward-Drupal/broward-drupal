<?php

namespace Drupal\extlink\Tests;

/**
 * Testing of the External Links administration interface and functionality.
 *
 * @group Extlink Admin Tests
 */
class ExtlinkAdminTest extends ExtlinkTestBase {

  /**
   * Test access to the admin pages.
   */
  public function testAdminAccess() {
    $this->drupalLogin($this->normalUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->assertText(t('Access denied'), 'Normal users should not be able to access the External Links admin pages', 'External Links');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->assertNoText(t('Access denied'), 'Admin users should be able to access the External Links admin pages', 'External Links');
  }

}
