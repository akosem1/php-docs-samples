<?php
/**
 * Copyright 2018 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Samples\Storage\Tests;

use Google\Cloud\Storage\StorageClient;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit Tests for BucketLockCommand.
 */
class BucketLockCommandTest extends \PHPUnit_Framework_TestCase
{
    protected static $hasCredentials;
    protected $commandTester;
    protected $storage;
    protected $bucket;
    protected $object;

    public static function setUpBeforeClass()
    {
        $path = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        self::$hasCredentials = $path && file_exists($path) &&
            filesize($path) > 0;
    }

    public function setUp()
    {
        $application = require __DIR__ . '/../storage.php';
        $this->commandTester = new CommandTester($application->get('bucket-lock'));
        $this->storage = new StorageClient();
        if (!self::$hasCredentials) {
            $this->markTestSkipped('No application credentials were found.');
        }

        $bucketName = 'php-bucket-lock-' . time();
        $this->bucket = $this->storage->createBucket($bucketName);
    }

    public function tearDown()
    {
        $this->object && $this->object->delete();
        $this->bucket->delete();
    }

    public function uploadObject()
    {
        $objectName = 'test-object-' . time();
        $file = tempnam(sys_get_temp_dir(), '/tests');
        file_put_contents($file, 'foo' . rand());
        $this->object = $this->bucket->upload($file, [
            'name' => $objectName
        ]);
        $this->object->reload();
    }

    public function testRetentionPolicyNoLock()
    {
        $retentionPeriod = 5;
        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                'retention-period' => $retentionPeriod,
                '--set-retention-policy' => true,
            ],
            ['interactive' => false]
        );
        $this->bucket->reload();

        $this->assertFalse($this->bucket->info()['retentionPolicy']['isLocked']);
        $this->assertNotNull($this->bucket->info()['retentionPolicy']['effectiveTime']);
        $this->assertEquals($this->bucket->info()['retentionPolicy']['retentionPeriod'], $retentionPeriod);

        $this->uploadObject();
        $this->assertNotNull($this->object->info()['retentionExpirationTime']);

        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                '--remove-retention-policy' => true,
            ],
            ['interactive' => false]
        );
        $this->bucket->reload();

        $this->assertNull($this->bucket->info()['retentionPolicy']);

        $outputString = <<<EOF
Bucket {$this->bucket->name()} retention period set for $retentionPeriod seconds
Removed bucket {$this->bucket->name()} retention policy

EOF;
        $this->expectOutputString($outputString);
        sleep($retentionPeriod);
    }

    public function testRetentionPolicyLock()
    {
        $retentionPeriod = 5;
        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                'retention-period' => $retentionPeriod,
                '--set-retention-policy' => true,
            ],
            ['interactive' => false]
        );
        $this->bucket->reload();

        $this->assertNull($this->bucket->info()['retentionPolicy']['isLocked']);

        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                '--lock-retention-policy' => true,
            ],
            ['interactive' => false]
        );
        $this->bucket->reload();

        $this->assertTrue($this->bucket->info()['retentionPolicy']['isLocked']);

        $outputString = <<<EOF
Bucket {$this->bucket->name()} retention period set for $retentionPeriod seconds
Bucket {$this->bucket->name()} retention policy locked

EOF;
        $this->expectOutputString($outputString);
    }

    public function testEnableDisableDefaultEventBasedHold()
    {
        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                '--enable-default-event-based-hold' => true,
            ],
            ['interactive' => false]
        );
        $this->bucket->reload();

        $this->assertTrue($this->bucket->info()['defaultEventBasedHold']);

        $this->uploadObject();
        $this->assertTrue($this->object->info()['eventBasedHold']);

        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                'object' => $this->object->name(),
                '--release-event-based-hold' => true,
            ],
            ['interactive' => false]
        );
        $this->object->reload();
        $this->assertFalse($this->object->info()['eventBasedHold']);

        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                '--disable-default-event-based-hold' => true,
            ],
            ['interactive' => false]
        );
        $this->bucket->reload();
        $this->assertFalse($this->bucket->info()['defaultEventBasedHold']);

        $outputString = <<<EOF
Default event based hold was enabled for {$this->bucket->name()}
Event based hold was released for {$this->object->name()}
Default event based hold was disabled for {$this->bucket->name()}

EOF;
        $this->expectOutputString($outputString);
    }

    public function testEnableDisableEventBasedHold()
    {
        $this->uploadObject();
        $this->assertNull($this->object->info()['eventBasedHold']);

        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                'object' => $this->object->name(),
                '--set-event-based-hold' => true,
            ],
            ['interactive' => false]
        );
        $this->object->reload();
        $this->assertTrue($this->object->info()['eventBasedHold']);

        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                'object' => $this->object->name(),
                '--release-event-based-hold' => true,
            ],
            ['interactive' => false]
        );
        $this->object->reload();
        $this->assertFalse($this->object->info()['eventBasedHold']);

        $outputString = <<<EOF
Event based hold was set for {$this->object->name()}
Event based hold was released for {$this->object->name()}

EOF;
        $this->expectOutputString($outputString);
    }

    public function testEnableDisableTemporaryHold()
    {
        $this->uploadObject();
        $this->assertNull($this->object->info()['temporaryHold']);

        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                'object' => $this->object->name(),
                '--set-temporary-hold' => true,
            ],
            ['interactive' => false]
        );
        $this->object->reload();
        $this->assertTrue($this->object->info()['temporaryHold']);

        $this->commandTester->execute(
            [
                'bucket' => $this->bucket->name(),
                'object' => $this->object->name(),
                '--release-temporary-hold' => true,
            ],
            ['interactive' => false]
        );
        $this->object->reload();
        $this->assertFalse($this->object->info()['temporaryHold']);

        $outputString = <<<EOF
Temporary hold was set for {$this->object->name()}
Temporary hold was released for {$this->object->name()}

EOF;
        $this->expectOutputString($outputString);
    }
}
