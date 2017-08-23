<?php

namespace Drupal\Tests\linkit\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\linkit\Tests\ProfileCreationTrait;

/**
 * Tests creating, loading and deleting profiles.
 *
 * @group linkit
 */
class ProfileAdminTest extends LinkitBrowserTestBase {

  use ProfileCreationTrait;

  /**
   * Test the overview page.
   */
  public function testOverview() {
    // Verify that the profile collection page is not accessible for users
    // without permission.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('/admin/config/content/linkit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();

    // Login as an admin user and make sure the collection page is accessible.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/linkit');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure the 'Add profile' action link is present.
    $this->assertSession()->linkByHrefExists('/admin/config/content/linkit/add');

    // Create multiple profiles.
    $profiles = [];
    $profiles[] = $this->createProfile();
    $profiles[] = $this->createProfile();

    // Refresh the page.
    $this->drupalGet('/admin/config/content/linkit');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that there is an edit and a delete operation link for all
    // profiles.
    foreach ($profiles as $profile) {
      $this->assertSession()->linkByHrefExists('/admin/config/content/linkit/manage/' . $profile->id());
      $this->assertSession()->linkByHrefExists('/admin/config/content/linkit/manage/' . $profile->id() . '/delete');
    }
  }

  /**
   * Creates profile.
   */
  public function testProfileCreation() {
    $this->drupalLogin($this->adminUser);

    // Make sure the profile add page is accessible.
    $this->drupalGet('/admin/config/content/linkit/add');
    $this->assertSession()->statusCodeEquals(200);

    // Create a profile.
    $edit = [];
    $edit['label'] = Unicode::strtolower($this->randomMachineName());
    $edit['id'] = Unicode::strtolower($this->randomMachineName());
    $edit['description'] = $this->randomMachineName(16);
    $this->submitForm($edit, t('Save and manage matchers'));

    // Make sure that the new profile was saved properly.
    $this->assertSession()->responseContains(t('Created new profile %label.', ['%label' => $edit['label']]));
    $this->drupalGet('/admin/config/content/linkit');
    $this->assertSession()->pageTextContains($edit['label']);
  }

  /**
   * Updates a profile.
   */
  public function testProfileUpdate() {
    $this->drupalLogin($this->adminUser);

    // Create a profile.
    $profile = $this->createProfile();

    // Make sure the profile edit page is accessible.
    $this->drupalGet('/admin/config/content/linkit/manage/' . $profile->id());
    $this->assertSession()->statusCodeEquals(200);

    // Make sure the machine name field is disabled and that we have certain
    // elements presented.
    $this->assertSession()->elementNotExists('xpath', '//input[not(@disabled) and @name="id"]');
    $this->assertSession()->buttonExists('Update profile');
    $this->assertSession()->linkByHrefExists('/admin/config/content/linkit/manage/' . $profile->id() . '/delete');

    // Update the profile.
    $edit = [];
    $edit['label'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName(16);
    $this->submitForm($edit, t('Update profile'));

    // Make sure that the profile was updated properly.
    $this->assertSession()->responseContains(t('Updated profile %label.', ['%label' => $edit['label']]));
    $this->drupalGet('/admin/config/content/linkit');
    $this->assertSession()->pageTextContains($edit['label']);
  }

  /**
   * Delete a profile.
   */
  public function testProfileDelete() {
    $this->drupalLogin($this->adminUser);

    // Create a profile.
    $profile = $this->createProfile();

    $this->drupalGet('/admin/config/content/linkit/manage/' . $profile->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);

    // Delete the profile.
    $this->submitForm([], t('Delete'));

    // Make sure that the profile was deleted properly.
    $this->assertSession()->responseContains(t('The linkit profile %label has been deleted.', ['%label' => $profile->label()]));
    $this->drupalGet('/admin/config/content/linkit');
    $this->assertSession()->responseNotContains($profile->label());
  }

}
