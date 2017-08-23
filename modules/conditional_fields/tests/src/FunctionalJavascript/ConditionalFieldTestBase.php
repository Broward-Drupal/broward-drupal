<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Base setup for ConditionalField tests.
 */
abstract class ConditionalFieldTestBase extends JavascriptTestBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Access controller.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessControlHandler;

  /**
   * Path to create screenshot.
   *
   * @var string
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'conditional_fields',
    'node',
    'datetime',
    'field_ui',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
      $this->drupalCreateContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);
    }
    $this->checkScreenshotPathExist();
  }

  /**
   * Check does screenshot path exist and create if it's necessary.
   */
  private function checkScreenshotPathExist() {
    if (file_exists($this->screenshotPath)) {
      return;
    }
    mkdir($this->screenshotPath, 0777, TRUE);
  }

  /**
   * Waits and asserts that a given element is visible.
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  protected function waitUntilVisible($selector, $timeout = 1000, $message = '') {
    $condition = "jQuery('{$selector}').is(':visible');";
    $this->assertJsCondition($condition, $timeout, $message);
  }

  /**
   * Waits and asserts that a given element is hidden (invisible).
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  protected function waitUntilHidden($selector, $timeout = 1000, $message = '') {
    $condition = "jQuery('{$selector}').is(':hidden');";
    $this->assertJsCondition($condition, $timeout, $message);
  }

  /**
   * Helper to change Field value with Javascript.
   */
  protected function changeField($selector, $value = '') {
    $this->getSession()
      ->executeScript("jQuery('" . $selector . "').val('" . $value . "').trigger('keyup').trigger('change');");
  }

  /**
   * Helper to change selection with Javascript.
   */
  protected function changeSelect($selector, $value = '') {
    $this->getSession()
      ->executeScript("jQuery('" . $selector . "').val('" . $value . "').trigger('click').trigger('change');");
  }

  /**
   * Create basic fields' dependency.
   *
   * @param string $dependent
   *   Machine name of dependent field.
   * @param string $dependee
   *   Machine name of dependee field.
   * @param string $state
   *   Dependent field state.
   * @param string $condition
   *   Condition value.
   */
  protected function createCondition($dependent, $dependee, $state, $condition) {
    $edit = [
      'table[add_new_dependency][dependent][]' => $dependent,
      'table[add_new_dependency][dependee]' => $dependee,
      'table[add_new_dependency][state]' => $state,
      'table[add_new_dependency][condition]' => $condition,
    ];
    $this->submitForm($edit, 'Add dependency');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Base steps for all javascript tests.
   */
  protected function baseTestSteps() {
    $admin_account = $this->drupalCreateUser([
      'view conditional fields',
      'edit conditional fields',
      'delete conditional fields',
      'administer nodes',
      'create article content',
      'administer content types',
    ]);
    $this->drupalLogin($admin_account);

    // Visit a ConditionalFields configuration page that requires login.
    $this->drupalGet('admin/structure/conditional_fields');
    $this->assertSession()->statusCodeEquals(200);

    // Configuration page contains the `Content` entity type.
    $this->assertSession()->pageTextContains('Content');

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->drupalGet('admin/structure/conditional_fields/node');
    $this->assertSession()->statusCodeEquals(200);

    // Configuration page contains the `Article` bundle of Content entity type.
    $this->assertSession()->pageTextContains('Article');

    // Visit a ConditionalFields configuration page for Article CT.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->statusCodeEquals(200);
  }

}
