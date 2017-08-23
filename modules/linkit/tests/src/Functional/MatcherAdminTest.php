<?php

namespace Drupal\Tests\linkit\Functional;

use Drupal\linkit\Entity\Profile;
use Drupal\linkit\Tests\ProfileCreationTrait;

/**
 * Tests adding, listing, updating and deleting matchers on a profile.
 *
 * @group linkit
 */
class MatcherAdminTest extends LinkitBrowserTestBase {

  use ProfileCreationTrait;

  /**
   * The attribute manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * The linkit profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    $this->linkitProfile = $this->createProfile();
  }

  /**
   * Test the overview page.
   */
  public function testOverview() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers');
    $this->assertSession()->pageTextContains(t('No matchers added.'));

    // Make sure the 'Add matcher' action link is present.
    $this->assertSession()->linkByHrefExists('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers/add');
  }

  /**
   * Test adding a matcher to a profile.
   */
  public function testAdd() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers/add');

    // Create matcher.
    $edit = [];
    $edit['plugin'] = 'dummy_matcher';
    $this->submitForm($edit, t('Save and continue'));

    // Reload the profile.
    $this->linkitProfile = Profile::load($this->linkitProfile->id());

    $matcher_ids = $this->linkitProfile->getMatchers()->getInstanceIds();
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->linkitProfile->getMatcher(current($matcher_ids));

    $this->assertSession()->responseContains(t('Added %label matcher.', ['%label' => $plugin->getLabel()]));
    $this->assertSession()->pageTextNotContains(t('No matchers added.'));
  }

  /**
   * Test adding a configurable attribute to a profile.
   */
  public function testAddConfigurable() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers/add');

    // Create configurable matcher.
    $edit = [];
    $edit['plugin'] = 'configurable_dummy_matcher';
    $this->submitForm($edit, t('Save and continue'));

    // Reload the profile.
    $this->linkitProfile = Profile::load($this->linkitProfile->id());

    $matcher_ids = $this->linkitProfile->getMatchers()->getInstanceIds();
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->linkitProfile->getMatcher(current($matcher_ids));

    $this->assertSession()->addressEquals('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers/' . $plugin->getUuid());
    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers');

    $this->assertSession()->pageTextNotContains(t('No matchers added.'));
  }

  /**
   * Test delete a matcher from a profile.
   */
  public function testDelete() {
    $this->drupalLogin($this->adminUser);

    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('dummy_matcher');

    $plugin_uuid = $this->linkitProfile->addMatcher($plugin->getConfiguration());
    $this->linkitProfile->save();

    // Try delete a matcher that is not attached to the profile.
    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers/doesntexists/delete');
    $this->assertSession()->statusCodeEquals('404');

    // Go to the delete page, but press cancel.
    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers/' . $plugin_uuid . '/delete');
    $this->clickLink(t('Cancel'));
    $this->assertSession()->addressEquals('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers');

    // Delete the matcher from the profile.
    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers/' . $plugin_uuid . '/delete');

    $this->submitForm([], t('Confirm'));
    $this->assertSession()->responseContains(t('The matcher %plugin has been deleted.', ['%plugin' => $plugin->getLabel()]));
    $this->assertSession()->addressEquals('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers');
    $this->assertSession()->pageTextContains(t('No matchers added.'));

    /** @var \Drupal\linkit\Entity\Profile $updated_profile */
    $updated_profile = Profile::load($this->linkitProfile->id());
    $this->assertFalse($updated_profile->getMatchers()->has($plugin_uuid), 'The user matcher is deleted from the profile');
  }

}
