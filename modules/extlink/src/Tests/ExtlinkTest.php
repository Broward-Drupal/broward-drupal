<?php

namespace Drupal\extlink\Tests;

/**
 * Testing the basic functionality of External Links.
 *
 * @group Extlink
 */
class ExtlinkTest extends ExtlinkTestBase {

  /**
   * Checks to see if we can get the front page.
   */
  public function testExtlinkOnFrontPage() {
    // Get main page.
    $this->drupalGet('');
  }

}
