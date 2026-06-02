<?php

use App\Models\Order;
use App\Models\Transporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('can assign a transporter and tracking code to an order', function () {
    $order = Order::factory()->create();
    $transporter = Transporter::factory()->create(['name' => 'GLS', 'tracking_url_template' => 'https://gls-group.eu/track/{tracking_code}']);

    $order->update([
        'transporter_id' => $transporter->id,
        'tracking_code' => '123456789',
    ]);

    expect($order->transporter->name)->toBe('GLS');
    expect($order->tracking_code)->toBe('123456789');
});

it('can attach an invoice pdf to an order', function () {
    Storage::fake('public');
    $order = Order::factory()->create();

    $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

    $order->addMedia($file)->toMediaCollection('invoices');

    expect($order->getMedia('invoices')->count())->toBe(1);
    expect($order->getFirstMedia('invoices')->file_name)->toBe('invoice.pdf');
});
