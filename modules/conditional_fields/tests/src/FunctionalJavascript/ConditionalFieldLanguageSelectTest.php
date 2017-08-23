<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields Language Select Plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldLanguageSelectTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'conditional_fields',
    'language',
    'node',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/language_select/';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'langcode';

  /**
   * Jquery selector of field in a document.
   *
   * @var string
   */
  protected $fieldSelector;

  /**
   * The field to use in this test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The default language code.
   *
   * @var string
   */
  protected $defaultLanguage;

  /**
   * An array with Not specified and Not applicable language codes.
   *
   * @var array
   */
  protected $langcodes = ['und', 'zxx'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fieldSelector = "[name=\"{$this->fieldName}[0][value]\"]";

    // Get the default language which will trigger the dependency.
    $this->defaultLanguage = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Enable language selector on node creation page.
    ContentLanguageSettings::loadByEntityTypeBundle('node', 'article')
      ->setLanguageAlterable(TRUE)
      ->setDefaultLangcode($this->defaultLanguage)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-language-select-add-filed-conditions.png');

    // Set up conditions.
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      $this->fieldSelector => $this->defaultLanguage,
      '[name="grouping"]' => 'AND',
      '[name="state"]' => 'visible',
      '[name="effect"]' => 'show',
    ];
    foreach ($data as $selector => $value) {
      $this->changeField($selector, $value);
    }
    $this->getSession()->wait(1000, '!jQuery.active');
    $this->getSession()->executeScript("jQuery('#conditional-field-edit-form').submit();");
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot($this->screenshotPath . '02-language-select-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-language-select-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-language-select-body-visible-when-controlled-field-has-default-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, $this->langcodes[0]);
    $this->createScreenshot($this->screenshotPath . '05-language-select-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, $this->defaultLanguage);
    $this->createScreenshot($this->screenshotPath . '06-language-select-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, $this->langcodes[1]);
    $this->createScreenshot($this->screenshotPath . '07-language-select-body-invisible-when-controlled-field-has-wrong-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    // TODO: Implement testVisibleValueRegExp() method.
    $this->markTestIncomplete();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    // TODO: Implement testVisibleValueAnd() method.
    $this->markTestIncomplete();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    // TODO: Implement testVisibleValueOr() method.
    $this->markTestIncomplete();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    // TODO: Implement testVisibleValueNot() method.
    $this->markTestIncomplete();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    // TODO: Implement testVisibleValueXor() method.
    $this->markTestIncomplete();
  }

}
