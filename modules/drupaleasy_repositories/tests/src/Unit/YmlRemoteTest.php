<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Unit;

use Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote;
use Drupal\Tests\UnitTestCase;

/**
 * Unit Test for YmlRemote Plugin.
 */
final class YmlRemoteTest extends UnitTestCase {

  /**
   * The .yml Remote plugin.
   *
   * @var \Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote
   */
  protected YmlRemote $ymlRemote;

  /**
   * Mock MessengerInterface Object.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * Mock KeyRepository Object.
   *
   * @var \Drupal\key\KeyRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyRepository;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Upon adding the GitHub Plugin, we added dependencies to that plugin.
    // We need to fake complicated depencendies here for messenger & key.
    // Mocking creates a fake dependency service.
    // Mock the Messenger Service.
    $messenger = $this->getMockBuilder('\Drupal\Core\Messenger\MessengerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Mock the Key Repository Service.
    $keyRepository = $this->getMockBuilder('\Drupal\key\KeyRepositoryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->ymlRemote = new YmlRemote([], 'yml_remote', [], $messenger, $keyRepository);
  }

  /**
   * Test that the help test methods returns proper text.
   *
   * @return void
   *   Nothing is returned.
   *
   * @covers ::validateHelpText
   * @test
   */
  public function testValidateHelpText(): void {
    self::assertEquals('https://anything.anything/anything/anything.yml (or "http")', $this->ymlRemote->validateHelpText(), 'Help text does not match.');
  }

  /**
   * Data providor for testValidate().
   *
   * @return array<int, array<int, bool|string>>
   *   Array of test strings and results.
   */
  public function validateProvider(): array {
    return [
     [
       'A test string',
       FALSE,
     ],
     [
       'http://www.mysite.com/anything.yml',
       TRUE,
     ],
     [
       'https://www.mysite.com/anything.yml',
       TRUE,
     ],
     [
       'https://www.mysite.com/anything.yaml',
       TRUE,
     ],
     [
       '/var/www/html/anything.yaml',
       FALSE,
     ],
     [
       'https://www.mysite.com/some%20directory/anything.yml',
       TRUE,
     ],
     [
       'https://www.my-site.com/some%20directory/anything.yaml',
       TRUE,
     ],
     [
       'https://localhost/some%20directory/anything.yaml',
       TRUE,
     ],
     [
       'https://dev.www.mysite.com/anything.yml',
       TRUE,
     ],
     [
       'https://dev.www.mysite.com/some directory/anything.yml',
       FALSE,
     ],
     [
       'https://dev.www.mysite.com/some directory/anything.yaml',
       FALSE,
     ],
    ];
  }

  /**
   * Test that the URL validator works.
   *
   * @dataProvider validateProvider
   *
   * @covers ::validate
   * @test
   */
  public function testValidate(string $testString, bool $expected): void {
    self::assertEquals($expected, $this->ymlRemote->validate($testString), "Validation of {$testString} does not return {$expected}.");
  }

  /**
   * Test that a repo can be read properly and mapped to a common format.
   *
   * @covers ::getRepo
   * @test
   */
  public function testGetRepo(): void {
    $repo = $this->ymlRemote->getRepo(__DIR__ . '/../../assets/batman-repo.yml');
    $repo = reset($repo);
    self::assertEquals('The Batman repository', $repo['label'], "Label does not match {$repo['label']}.");
    self::assertEquals('This is where Batman keeps all his crime-fighting code.', $repo['description'], 'Description does not match.');
    self::assertEquals('yml_remote', $repo['source'], "The source does not match.");
    self::assertEquals('6', $repo['num_open_issues'], 'Number of open issues does not match.');
  }

}
