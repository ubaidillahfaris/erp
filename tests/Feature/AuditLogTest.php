<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);
    }

    public function test_creating_auditable_model_triggers_audit_log()
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Initial description'
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'auditable_id' => $category->id,
            'auditable_type' => Category::class,
            'user_id' => $this->user->id,
        ]);

        $log = AuditLog::first();
        $this->assertNotNull($log->new_values);
        $this->assertEquals('Test Category', $log->new_values['name']);
    }

    public function test_updating_auditable_model_triggers_audit_log_with_changes()
    {
        $category = Category::create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $category->update(['name' => 'New Name']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'auditable_id' => $category->id,
            'user_id' => $this->user->id,
        ]);

        $log = AuditLog::where('event', 'updated')->first();
        $this->assertNotNull($log->old_values);
        $this->assertNotNull($log->new_values);
        $this->assertEquals('Old Name', $log->old_values['name']);
        $this->assertEquals('New Name', $log->new_values['name']);
    }

    public function test_deleting_auditable_model_triggers_audit_log()
    {
        $category = Category::create([
            'name' => 'To Be Deleted',
            'slug' => 'to-be-deleted',
        ]);

        $categoryId = $category->id;
        $category->delete();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deleted',
            'auditable_id' => $categoryId,
            'user_id' => $this->user->id,
        ]);
    }
}
