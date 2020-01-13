<?php
declare(strict_types=1);

namespace Studio24\HttpCacheClear\Test;

use PHPUnit\Framework\TestCase;
use Studio24\HttpCacheClear\ClearHttpCacheCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearCacheTest extends TestCase
{

    public function testGetContentResponseDigest(): void
    {
        $clear = new ClearHttpCacheCommand();

        $valid = [0 => [1 => ['x-content-digest' => [0 => 'en1cb1ec7f5ecd46f81cd80ad8e82df486f6358c8624b52bc6f5c03bb77e77dcd6']]]];
        $invalid = ['x-content-digest' => 'abc123'];

        $this->assertEquals('en/1c/b1/ec7f5ecd46f81cd80ad8e82df486f6358c8624b52bc6f5c03bb77e77dcd6', $clear->getLinkedResponseFileFromMetadata($valid));
        $this->assertNull($clear->getLinkedResponseFileFromMetadata($invalid));
    }

    public function testClearCacheDryRun(): void
    {
        $path = __DIR__ . '/template/tmp/cache';
        $this->copyTestCacheFiles($path . '/prod/');

        $command = new ClearHttpCacheCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--dry-run' => true,
            '--path' => $path
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Dry run mode', $output);
        $this->assertStringContainsString('http_cache/en/6f/e4/5b8c54f33ab7cb33e79b82a24bbe051d9e5d643a2cb65769472495bd65a8', $output);
        $this->assertStringContainsString('http_cache/md/61/47/c582607430927bcccbcf0237778dae4d1da54cb796602542f398e74c6c77', $output);
        $this->assertStringContainsString('4 response files deleted successfully', $output);
        $this->assertStringContainsString('3 metadata files deleted successfully', $output);

        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/en/6f/e4/5b8c54f33ab7cb33e79b82a24bbe051d9e5d643a2cb65769472495bd65a8'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/md/61/47/c582607430927bcccbcf0237778dae4d1da54cb796602542f398e74c6c77'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/en/2a/22/98c192104d32c638086c6be2c26841c781d6a03899a1af173fc9f9063da2'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/md/43/86/e2c40b00e0a41fe21a0b889594fd0ba9a94e8456cd8782fcf15e68721381'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/en/1c/b1/ec7f5ecd46f81cd80ad8e82df486f6358c8624b52bc6f5c03bb77e77dcd6'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/md/8d/a9/32deb346d769bfc27ea304d523152aba74eb4a092386b852e5b187e580ff'));

        $this->removeTestCacheFiles($path . '/prod/');
    }

    public function testClearCache(): void
    {
        $path = __DIR__ . '/template/tmp/cache';
        $this->copyTestCacheFiles($path . '/prod/');

        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/en/6f/e4/5b8c54f33ab7cb33e79b82a24bbe051d9e5d643a2cb65769472495bd65a8'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/md/61/47/c582607430927bcccbcf0237778dae4d1da54cb796602542f398e74c6c77'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/en/2a/22/98c192104d32c638086c6be2c26841c781d6a03899a1af173fc9f9063da2'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/md/43/86/e2c40b00e0a41fe21a0b889594fd0ba9a94e8456cd8782fcf15e68721381'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/en/1c/b1/ec7f5ecd46f81cd80ad8e82df486f6358c8624b52bc6f5c03bb77e77dcd6'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/md/8d/a9/32deb346d769bfc27ea304d523152aba74eb4a092386b852e5b187e580ff'));

        $command = new ClearHttpCacheCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--path' => $path
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringNotContainsString('Dry run mode', $output);
        $this->assertStringContainsString('http_cache/en/6f/e4/5b8c54f33ab7cb33e79b82a24bbe051d9e5d643a2cb65769472495bd65a8', $output);
        $this->assertStringContainsString('http_cache/md/61/47/c582607430927bcccbcf0237778dae4d1da54cb796602542f398e74c6c77', $output);
        $this->assertStringContainsString('4 response files deleted successfully', $output);
        $this->assertStringContainsString('3 metadata files deleted successfully', $output);

        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/en/6f/e4/5b8c54f33ab7cb33e79b82a24bbe051d9e5d643a2cb65769472495bd65a8'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/md/61/47/c582607430927bcccbcf0237778dae4d1da54cb796602542f398e74c6c77'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/en/2a/22/98c192104d32c638086c6be2c26841c781d6a03899a1af173fc9f9063da2'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/md/43/86/e2c40b00e0a41fe21a0b889594fd0ba9a94e8456cd8782fcf15e68721381'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/en/1c/b1/ec7f5ecd46f81cd80ad8e82df486f6358c8624b52bc6f5c03bb77e77dcd6'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/md/8d/a9/32deb346d769bfc27ea304d523152aba74eb4a092386b852e5b187e580ff'));

        // Should not have been deleted
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/en/ab/cd/6ef6e2a907f671744071b8c3a17a5328e2d2dd5c56d152dbabc88e143289'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/md/ab/cd/fe63e76b0515cefee47044454d1a6452cd54b18c71a0aaaffec5956143ca'));

        // Test orphan response file
        $this->assertStringContainsString('http_cache/en/cd/ef/17a5328e2d2dd5c56d156ef6e2a907f671744071b8c3a2dbabc88e143289', $output);
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/en/cd/ef/17a5328e2d2dd5c56d156ef6e2a907f671744071b8c3a2dbabc88e143289'));

        $this->removeTestCacheFiles($path . '/prod/');
    }

    public function testClear1hrCache(): void
    {
        $path = __DIR__ . '/template/tmp/cache';
        $this->copyTestCacheFiles($path . '/prod/');

        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/en/ab/cd/6ef6e2a907f671744071b8c3a17a5328e2d2dd5c56d152dbabc88e143289'));
        $this->assertTrue($filesystem->exists($path . '/prod/http_cache/md/ab/cd/fe63e76b0515cefee47044454d1a6452cd54b18c71a0aaaffec5956143ca'));

        $command = new ClearHttpCacheCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--path' => $path,
            '--expiry' => 1
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('http_cache/en/ab/cd/6ef6e2a907f671744071b8c3a17a5328e2d2dd5c56d152dbabc88e143289', $output);
        $this->assertStringContainsString('http_cache/md/ab/cd/fe63e76b0515cefee47044454d1a6452cd54b18c71a0aaaffec5956143ca', $output);
        $this->assertStringContainsString('5 response files deleted successfully', $output);
        $this->assertStringContainsString('4 metadata files deleted successfully', $output);

        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/en/ab/cd/6ef6e2a907f671744071b8c3a17a5328e2d2dd5c56d152dbabc88e143289'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/md/ab/cd/fe63e76b0515cefee47044454d1a6452cd54b18c71a0aaaffec5956143ca'));

        $this->removeTestCacheFiles($path . '/prod/');
    }

    /**
     * Copy test HTTP cache files to new folder
     *
     * @param string $cacheFolder
     * @throws \Exception
     */
    public function copyTestCacheFiles(string $cacheFolder)
    {
        $source = __DIR__ . '/template/http_cache';
        $cacheFolder = rtrim($cacheFolder, '/') . '/http_cache';

        $filesystem = new Filesystem();
        if (!$filesystem->exists($cacheFolder)) {
            $filesystem->mkdir($cacheFolder);
        }

        $filesystem->mirror($source, $cacheFolder);

        // Make a copy of HTTP Cache files, make it 2 hours old
        $response = file_get_contents($source . '/en/6f/e4/5b8c54f33ab7cb33e79b82a24bbe051d9e5d643a2cb65769472495bd65a8');
        $metadata = unserialize(file_get_contents($source . '/md/61/47/c582607430927bcccbcf0237778dae4d1da54cb796602542f398e74c6c77'));
        if (!$metadata) {
            throw new \Exception('Cannot unserialize HTTP Cache data files');
        }

        $date = new \DateTime();
        $date->modify('-2 hour');
        $metadata[0][1]['date'][0] = $date->format('D, j M Y H:i:s e');
        $metadata[0][1]['x-content-digest'][0] = 'enabcd6ef6e2a907f671744071b8c3a17a5328e2d2dd5c56d152dbabc88e143289';

        $filesystem->dumpFile($cacheFolder . '/en/ab/cd/6ef6e2a907f671744071b8c3a17a5328e2d2dd5c56d152dbabc88e143289', $response);
        $filesystem->dumpFile($cacheFolder . '/md/ab/cd/fe63e76b0515cefee47044454d1a6452cd54b18c71a0aaaffec5956143ca', serialize($metadata));

        // Copy response file without metadata file
        $filesystem->dumpFile($cacheFolder . '/en/cd/ef/17a5328e2d2dd5c56d156ef6e2a907f671744071b8c3a2dbabc88e143289', $response);
    }

    /**
     * Delete new cache folder and child files
     *
     * @param string $cacheFolder
     */
    public function removeTestCacheFiles(string $cacheFolder)
    {
        $filesystem = new Filesystem();
        $filesystem->remove($cacheFolder);
    }
}
