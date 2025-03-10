<?php

namespace Drupal\Tests\drupalai\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Lorem Ipsum module.
 * @group loremipsum
 */
class DrupalAITests extends BrowserTestBase {

  /**
   * Modules to install
   *
   * @var array
   */
  protected static $modules = array('drupalai');
  protected $defaultTheme = 'stark';

  // A simple user
  private $user;

  // Perform initial setup tasks that run before every test method.
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->DrupalCreateUser(array(
      'administer site configuration',
      'generate drupalai',
    ));
  }

  /**
   * Tests that the Lorem ipsum page can be reached.
   */
  public function testDrupalAIPageExists() {
    // Login
    $this->drupalLogin($this->user);

    // Generator test:
    $this->drupalGet('drupalai/generate/4/20');
    $this->assertSession()->statusCodeEquals(200);
  }
}