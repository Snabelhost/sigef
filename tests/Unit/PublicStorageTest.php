<?php

namespace Tests\Unit;

use App\Support\PublicStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filesystems.disks.public.url', '/media');
        Storage::fake('public', ['url' => '/media']);
    }

    public function test_it_normalizes_legacy_public_file_paths(): void
    {
        $expected = 'candidates/photos/avatar.jpg';

        $this->assertSame($expected, PublicStorage::normalizePath($expected));
        $this->assertSame($expected, PublicStorage::normalizePath('/storage/'.$expected));
        $this->assertSame($expected, PublicStorage::normalizePath('public\\storage\\'.$expected));
        $this->assertSame($expected, PublicStorage::normalizePath('https://old.example/storage/'.$expected));
        $this->assertSame($expected, PublicStorage::normalizePath('/media/'.$expected.'?v=2'));
    }

    public function test_it_rejects_unsafe_paths(): void
    {
        $this->assertNull(PublicStorage::normalizePath('../.env'));
        $this->assertNull(PublicStorage::normalizePath('candidates/../.env'));
        $this->assertNull(PublicStorage::normalizePath("photo.jpg\0.php"));
    }

    public function test_it_builds_same_origin_urls_and_can_require_an_existing_file(): void
    {
        Storage::disk('public')->put('candidates/photos/avatar.jpg', 'photo');

        $this->assertSame('/media/candidates/photos/avatar.jpg', PublicStorage::url('/storage/candidates/photos/avatar.jpg'));
        $this->assertSame('candidates/photos/avatar.jpg', PublicStorage::existingPath('/storage/candidates/photos/avatar.jpg'));
        $this->assertSame('https://cdn.example/avatar.jpg', PublicStorage::existingDisplayValue('https://cdn.example/avatar.jpg'));
        $this->assertNull(PublicStorage::url('candidates/photos/missing.jpg', requireExisting: true));
        $this->assertSame('https://cdn.example/avatar.jpg', PublicStorage::url('https://cdn.example/avatar.jpg', requireExisting: true));
    }
}
