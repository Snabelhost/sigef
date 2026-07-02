<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageDeliveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filesystems.disks.public.url', '/media');
        Storage::fake('public', ['url' => '/media']);
    }

    public function test_media_route_serves_a_public_file_without_a_symlink(): void
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        Storage::disk('public')->put('candidates/photos/avatar.png', $contents);

        $response = $this->get('/media/candidates/photos/avatar.png');

        $response
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertHeader('x-content-type-options', 'nosniff');

        $cacheControl = (string) $response->headers->get('cache-control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=604800', $cacheControl);
        $this->assertStringContainsString('immutable', $cacheControl);
        $this->assertStringStartsWith('inline;', (string) $response->headers->get('content-disposition'));
        $this->assertNotEmpty($response->headers->get('etag'));
    }

    public function test_legacy_storage_route_remains_available(): void
    {
        Storage::disk('public')->put('documents/example.txt', 'SIGEF');

        $response = $this->get('/storage/documents/example.txt');

        $response->assertOk();
        $this->assertSame('text/plain; charset=utf-8', strtolower((string) $response->headers->get('content-type')));
    }

    public function test_missing_and_unsafe_public_files_are_not_served(): void
    {
        $this->get('/media/candidates/photos/missing.jpg')->assertNotFound();
        $this->get('/media/candidates/..%2F..%2F.env')->assertNotFound();
    }

    public function test_storage_audit_command_runs_without_public_storage_tables(): void
    {
        $this->artisan('app:storage-audit')->assertExitCode(0);
    }
}
