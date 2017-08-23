<?php

namespace Drupal\twitter_block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests if the twitter block is available.
 *
 * @group twitter_block
 */
class TwitterBlockTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system_test', 'block', 'twitter_block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and login user.
    $admin_user = $this->drupalCreateUser(array(
      'administer blocks', 'administer site configuration',
      'access administration pages',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the twitter block can be placed and works.
   */
  public function testTwitterBlock() {
    // Test availability of the twitter block in the admin "Place blocks" list.
    \Drupal::service('theme_handler')->install(['bartik', 'seven', 'stark']);
    $theme_settings = $this->config('system.theme');
    foreach (['bartik', 'seven', 'stark'] as $theme) {
      $this->drupalGet('admin/structure/block/list/' . $theme);
      // Configure and save the block.
      $this->drupalPlaceBlock('twitter_block', array(
        'username' => 'drupal',
        'width' => 180,
        'height' => 200,
        'region' => 'content',
        'theme' => $theme,
      ));
      // Set the default theme and ensure the block is placed.
      $theme_settings->set('default', $theme)->save();
      $this->drupalGet('');
      $this->assertText('Tweets by @drupal', 'Twitter block found');
    }
  }

}
