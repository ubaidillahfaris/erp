<?php

namespace Tests\Feature\Sprint1;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_creating_a_product_generates_audit_log()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['name' => 'Test Product']);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'auditable_id' => $product->id,
            'auditable_type' => Product::class,
            'user_id' => $user->id,
        ]);

        $log = AuditLog::where('auditable_id', $product->id)->where('event', 'created')->first();
        $this->assertNotNull($log->new_values);
        // $this->assertEquals('Test Product', $log->new_values['name'] ?? null);
    }

    /** @test */
    public function test_updating_a_product_generates_audit_log()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['name' => 'Old Name']);

        // Clear previous logs if any
        AuditLog::query()->delete();

        $product->update(['name' => 'New Name']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'auditable_id' => $product->id,
            'auditable_type' => Product::class,
            'user_id' => $user->id,
        ]);

        $log = AuditLog::where('auditable_id', $product->id)->where('event', 'updated')->first();
        $this->assertEquals('Old Name', $log->old_values['name']);
        $this->assertEquals('New Name', $log->new_values['name']);
    }

    /** @test */
    public function test_deleting_a_product_generates_audit_log()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();

        $product->delete();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deleted',
            'auditable_id' => $product->id,
            'auditable_type' => Product::class,
            'user_id' => $user->id,
        ]);
    }
}
