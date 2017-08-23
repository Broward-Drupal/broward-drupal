<?php

namespace Drupal\Tests\webform\Unit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Access\WebformAccess;

/**
 * @coversDefaultClass \Drupal\webform\Access\WebformAccess
 *
 * @group webform
 */
class WebformAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\user\Access\PermissionAccessCheck
   */
  public $accessCheck;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the check admin access.
   *
   * @covers ::checkAdminAccess
   */
  public function testCheckAdminAccess() {
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');

    $admin_account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $admin_account->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
          ['administer webform', TRUE],
          ['administer webform submission', TRUE],
      ]
      ));

    $submission_manager_account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $submission_manager_account->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
          ['access webform overview', TRUE],
          ['view any webform submission', TRUE],
      ]
      ));

    $node = $this->getMockBuilder('Drupal\node\NodeInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $webform_node = $this->getMockBuilder('Drupal\node\NodeInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $webform_node->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $webform_node->expects($this->any())
      ->method('hasField')
      ->will($this->returnValue(TRUE));
    $webform_node->webform = (object) ['entity' => TRUE];

    $webform = $this->getMock('Drupal\webform\WebformInterface');

    $email_webform = $this->getMock('Drupal\webform\WebformInterface');
    $handler = $this->getMock('\Drupal\webform\Plugin\WebformHandlerMessageInterface');
    $email_webform->expects($this->any())
      ->method('getHandlers')
      ->will($this->returnValue([$handler]));
    $email_webform->expects($this->any())
      ->method('access')
      ->with('submission_update_any')
      ->will($this->returnValue(TRUE));

    $webform_submission = $this->getMock('Drupal\webform\WebformSubmissionInterface');
    $webform_submission->expects($this->any())
      ->method('getWebform')
      ->will($this->returnValue($webform));
    $email_webform_submission = $this->getMock('Drupal\webform\WebformSubmissionInterface');
    $email_webform_submission->expects($this->any())
      ->method('getWebform')
      ->will($this->returnValue($email_webform));

    // Check submission access.
    $this->assertEquals(AccessResult::neutral(), WebformAccess::checkAdminAccess($account));
    $this->assertEquals(AccessResult::allowed(), WebformAccess::checkAdminAccess($admin_account));

    // Check submission access.
    $this->assertEquals(AccessResult::neutral(), WebformAccess::checkSubmissionAccess($account));
    $this->assertEquals(AccessResult::allowed(), WebformAccess::checkSubmissionAccess($submission_manager_account));

    // Check overview access.
    $this->assertEquals(AccessResult::neutral(), WebformAccess::checkOverviewAccess($account));
    $this->assertEquals(AccessResult::allowed(), WebformAccess::checkOverviewAccess($submission_manager_account));

    // Check email access.
    $this->assertEquals(AccessResult::forbidden(), WebformAccess::checkEmailAccess($webform_submission, $account));
    $this->assertEquals(AccessResult::allowed(), WebformAccess::checkEmailAccess($email_webform_submission, $submission_manager_account));

    // @todo Fix below access check which is looping through the node's fields.
    // Check entity results access.
    // $this->assertEquals(AccessResult::neutral(), WebformAccess::checkEntityResultsAccess($node, $account));
    // $this->assertEquals(AccessResult::allowed(), WebformAccess::checkEntityResultsAccess($webform_node, $submission_manager_account));
  }

}
