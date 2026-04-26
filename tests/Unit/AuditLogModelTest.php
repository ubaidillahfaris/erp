<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_it_has_correct_fillable_and_casts()
    {
        $log = new AuditLog;

        $this->assertEquals([
            'user_id', 'event', 'auditable_id', 'auditable_type',
            'old_values', 'new_values', 'url', 'ip_address', 'user_agent',
        ], $log->getFillable());

        $this->assertEquals('json', $log->getCasts()['old_values']);
        $this->assertEquals('json', $log->getCasts()['new_values']);
    }

    /** @test */
    public function test_it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $log = AuditLog::create([
            'user_id' => $user->id,
            'event' => 'created',
            'auditable_type' => 'App\Models\Produk',
            'auditable_id' => 1,
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    /** @test */
    public function test_it_can_morph_to_auditable()
    {
        $produk = Produk::factory()->create();
        $log = AuditLog::create([
            'event' => 'created',
            'auditable_type' => Produk::class,
            'auditable_id' => $produk->id,
        ]);

        $this->assertInstanceOf(Produk::class, $log->auditable);
        $this->assertEquals($produk->id, $log->auditable->id);
    }
}
