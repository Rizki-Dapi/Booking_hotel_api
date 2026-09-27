<?php

use App\Models\Room;
use App\Models\RoomType;
use App\Services\MidtransService;

beforeEach(function () {
    // Mock Midtrans SECARA GLOBAL untuk semua test di file ini - tidak
    // ada satupun test booking yang boleh benar-benar manggil API
    // Midtrans sungguhan (lambat, butuh internet, hasilnya tidak
    // konsisten antar run).
    $this->mock(MidtransService::class, function ($mock) {
        $mock->shouldReceive('createSnapToken')->andReturn('fake-snap-token-123');
    });

    $this->roomType = RoomType::factory()->create(['price_per_night' => 500000]);
    $this->rooms = Room::factory()->count(3)->create(['room_type_id' => $this->roomType->id]);
    $this->client = createUserWithRole('client');
});

it('creates a booking and auto-assigns an available room', function () {
    $response = actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->addDay()->toDateString(),
        'check_out_date' => now()->addDays(3)->toDateString(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.snap_token', 'fake-snap-token-123')
        ->assertJsonPath('data.booking.status', 'pending');

    expect($this->rooms->pluck('id')->all())
        ->toContain($response->json('data.booking.room.id'));
});

it('assigns a different room to each booking, then rejects once the type is fully booked', function () {
    $checkIn = now()->addDay()->toDateString();
    $checkOut = now()->addDays(3)->toDateString();
    $assignedRoomIds = [];

    // 3 kamar tersedia - 3 booking pertama harus dapat kamar BEDA-BEDA.
    foreach (range(1, 3) as $i) {
        $response = actingAsApi($this->client)->postJson('/api/bookings', [
            'room_type_id' => $this->roomType->id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
        ]);

        $response->assertCreated();
        $assignedRoomIds[] = $response->json('data.booking.room.id');
    }

    expect($assignedRoomIds)->toHaveCount(3)
        ->and(array_unique($assignedRoomIds))->toHaveCount(3);

    // Booking ke-4, tanggal sama - semua kamar tipe ini sudah penuh.
    actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => $checkIn,
        'check_out_date' => $checkOut,
    ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'All rooms of this type are fully booked for the selected dates.');
});

it('allows a new booking that starts exactly when another ends on the same room type', function () {
    actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->addDay()->toDateString(),
        'check_out_date' => now()->addDays(3)->toDateString(),
    ])->assertCreated();

    // Semua 3 kamar dipenuhi dulu di rentang yang sama, supaya booking
    // berikutnya TERPAKSA butuh salah satu kamar yang baru saja dipakai -
    // membuktikan checkout=checkin hari yang sama tidak dianggap bentrok.
    actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->addDay()->toDateString(),
        'check_out_date' => now()->addDays(3)->toDateString(),
    ])->assertCreated();

    actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->addDay()->toDateString(),
        'check_out_date' => now()->addDays(3)->toDateString(),
    ])->assertCreated();

    // Sekarang semua kamar "sibuk" sampai hari ke-3. Booking baru yang
    // MULAI persis di hari ke-3 harus tetap berhasil.
    actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->addDays(3)->toDateString(),
        'check_out_date' => now()->addDays(5)->toDateString(),
    ])->assertCreated();
});

it('rejects a partially overlapping date range once all rooms are taken', function () {
    foreach ($this->rooms as $room) {
        actingAsApi($this->client)->postJson('/api/bookings', [
            'room_type_id' => $this->roomType->id,
            'check_in_date' => now()->addDays(1)->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
        ])->assertCreated();
    }

    // Overlap SEBAGIAN (hari ke-2 s/d ke-5) dengan rentang di atas
    // (hari ke-1 s/d ke-3) - harus tetap ditolak, bukan cuma exact match.
    actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->addDays(2)->toDateString(),
        'check_out_date' => now()->addDays(5)->toDateString(),
    ])->assertStatus(409);
});

it('calculates total_price correctly based on number of nights', function () {
    actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->addDay()->toDateString(),
        'check_out_date' => now()->addDays(4)->toDateString(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.booking.total_price', 1500000);
});

it('rejects unauthenticated requests', function () {
    $this->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->addDay()->toDateString(),
        'check_out_date' => now()->addDays(3)->toDateString(),
    ])->assertUnauthorized();
});

it('rejects admin from creating a booking - client-only route', function () {
    $admin = createUserWithRole('admin');

    actingAsApi($admin)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->addDay()->toDateString(),
        'check_out_date' => now()->addDays(3)->toDateString(),
    ])->assertForbidden();
});

it('validates required fields', function () {
    $response = actingAsApi($this->client)->postJson('/api/bookings', []);

    // BUKAN assertJsonValidationErrors() bawaan Laravel - itu expect key
    // 'errors', sedangkan response kita taruh detailnya di key 'message'
    // (lihat catatan di chat soal ini). Assertion manual di bawah cocok
    // ke bentuk response kita yang sebenarnya.
    $response->assertStatus(422);
    expect($response->json('message'))
        ->toHaveKeys(['room_type_id', 'check_in_date', 'check_out_date']);
});

it('rejects a check_in_date in the past', function () {
    $response = actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => $this->roomType->id,
        'check_in_date' => now()->subDay()->toDateString(),
        'check_out_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toHaveKey('check_in_date');
});

it('rejects a non-existent room type at the validation layer, before it reaches the Service', function () {
    // Judulnya sengaja ditulis begini (bukan "returns 404") - exists:room_types,id
    // di StoreBookingRequest sudah menolak duluan di 422, request TIDAK
    // PERNAH sampai ke BookingService::createBooking() sama sekali.
    // ModelNotFoundException (yang jadi 404) di Service itu untuk kasus
    // lain yang tidak lewat validasi ini.
    actingAsApi($this->client)->postJson('/api/bookings', [
        'room_type_id' => 999999,
        'check_in_date' => now()->addDay()->toDateString(),
        'check_out_date' => now()->addDays(3)->toDateString(),
    ])->assertStatus(422);
});
