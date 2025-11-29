<?php

declare(strict_types=1);

use App\Models\RequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

test('request_item model has correct media collections', function () {
    $requestItem = new RequestItem;

    $collections = $requestItem->getRegisteredMediaCollections();

    expect($collections)->toHaveCount(1);
    expect($collections->pluck('name'))->toContain('request_item_settlement_attachments');
});

test('request_item can add media to request_item_settlement_attachments collection', function () {
    $requestItem = RequestItem::factory()->create();
    $file = UploadedFile::fake()->image('receipt.jpg');

    $media = $requestItem
        ->addMedia($file)
        ->toMediaCollection('request_item_settlement_attachments');

    expect($media)->toBeInstanceOf(Media::class);
    expect($media->collection_name)->toBe('request_item_settlement_attachments');
    expect($media->disk)->toBe('public');
    expect($media->file_name)->toBe('receipt.jpg');
});

test('request_item can add multiple media files to request_item_settlement_attachments collection', function () {
    $requestItem = RequestItem::factory()->create();

    $file1 = UploadedFile::fake()->image('receipt1.jpg');
    $file2 = UploadedFile::fake()->image('receipt2.png');

    $media1 = $requestItem
        ->addMedia($file1)
        ->toMediaCollection('request_item_settlement_attachments');

    $media2 = $requestItem
        ->addMedia($file2)
        ->toMediaCollection('request_item_settlement_attachments');

    expect($requestItem->getMedia('request_item_settlement_attachments'))->toHaveCount(2);
    expect($requestItem->getMedia('request_item_settlement_attachments')->pluck('file_name'))->toContain('receipt1.jpg', 'receipt2.png');
});

test('request_item media conversions are generated', function () {
    $requestItem = RequestItem::factory()->create();
    $file = UploadedFile::fake()->image('receipt.jpg');

    $media = $requestItem
        ->addMedia($file)
        ->toMediaCollection('request_item_settlement_attachments');

    // Check if conversion URLs exist
    expect($media->hasGeneratedConversion('thumb'))->toBeTrue();
    expect($media->hasGeneratedConversion('medium'))->toBeTrue();
    expect($media->getUrl('thumb'))->toBeString();
    expect($media->getUrl('medium'))->toBeString();
});

test('request_item can store and retrieve PDF files', function () {
    $requestItem = RequestItem::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

    $media = $requestItem
        ->addMedia($file)
        ->toMediaCollection('request_item_settlement_attachments');

    expect($media->mime_type)->toBe('application/pdf');
    expect($media->file_name)->toBe('document.pdf');
});

test('request_item can clear media from collection', function () {
    $requestItem = RequestItem::factory()->create();
    $file = UploadedFile::fake()->image('receipt.jpg');

    $requestItem
        ->addMedia($file)
        ->toMediaCollection('request_item_settlement_attachments');

    expect($requestItem->getMedia('request_item_settlement_attachments'))->toHaveCount(1);

    $requestItem->clearMediaCollection('request_item_settlement_attachments');

    expect($requestItem->getMedia('request_item_settlement_attachments'))->toHaveCount(0);
});

test('request_item media files are stored on public disk', function () {
    Storage::fake('public');

    $requestItem = RequestItem::factory()->create();
    $file = UploadedFile::fake()->image('receipt.jpg');

    $media = $requestItem
        ->addMedia($file)
        ->toMediaCollection('request_item_settlement_attachments');

    expect(Storage::disk('public')->exists($media->getPath()))->toBeTrue();
    expect($media->disk)->toBe('public');
});

test('request_item media accepts only allowed file types', function () {
    $requestItem = new RequestItem;
    $collections = $requestItem->getRegisteredMediaCollections();

    $attachmentsCollection = $collections->firstWhere('name', 'request_item_settlement_attachments');

    expect($attachmentsCollection->acceptsMimeTypes)->toBeArray();
    expect($attachmentsCollection->acceptsMimeTypes)->toContain(
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf'
    );
});
