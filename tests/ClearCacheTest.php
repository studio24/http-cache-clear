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
        $this->assertStringContainsString('3 response files deleted successfully', $output);
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
        $this->assertStringContainsString('3 response files deleted successfully', $output);
        $this->assertStringContainsString('3 metadata files deleted successfully', $output);

        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/en/6f/e4/5b8c54f33ab7cb33e79b82a24bbe051d9e5d643a2cb65769472495bd65a8'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/md/61/47/c582607430927bcccbcf0237778dae4d1da54cb796602542f398e74c6c77'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/en/2a/22/98c192104d32c638086c6be2c26841c781d6a03899a1af173fc9f9063da2'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/md/43/86/e2c40b00e0a41fe21a0b889594fd0ba9a94e8456cd8782fcf15e68721381'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/en/1c/b1/ec7f5ecd46f81cd80ad8e82df486f6358c8624b52bc6f5c03bb77e77dcd6'));
        $this->assertFalse($filesystem->exists($path . '/prod/http_cache/md/8d/a9/32deb346d769bfc27ea304d523152aba74eb4a092386b852e5b187e580ff'));

        $this->removeTestCacheFiles($path . '/prod/');
    }

    /**
     * Copy test HTTP cache files to new folder
     *
     * @param string $cacheFolder
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
