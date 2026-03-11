<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessagesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_can_view_contact_messages_index_page(): void
    {
        ContactMessage::factory()->count(12)->create();

        $response = $this->get(route('admin.contact-messages.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/ContactMessages/Index')
            ->has('messages')
            ->has('messages.data', 10) // Paginated to 10 per page
        );
    }

    public function test_can_view_contact_message_show_page(): void
    {
        $message = ContactMessage::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Pertanyaan PPDB',
            'message' => 'Apakah pendaftaran sudah dibuka?',
            'is_read' => false,
        ]);

        $response = $this->get(route('admin.contact-messages.show', $message));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/ContactMessages/Show')
            ->has('message')
            ->where('message.name', 'John Doe')
            ->where('message.is_read', true) // Should be marked as read
        );
    }

    public function test_showing_message_marks_it_as_read(): void
    {
        $message = ContactMessage::factory()->create(['is_read' => false]);

        $this->get(route('admin.contact-messages.show', $message));

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);
    }

    public function test_can_delete_contact_message(): void
    {
        $message = ContactMessage::factory()->create();

        $response = $this->delete(route('admin.contact-messages.destroy', $message));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pesan berhasil dihapus.');
        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }

    public function test_pagination_works_correctly(): void
    {
        ContactMessage::factory()->count(25)->create();

        $response = $this->get(route('admin.contact-messages.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('messages')
            ->where('messages.per_page', 10)
            ->where('messages.total', 25)
            ->has('messages.data', 10) // Only 10 items per page
        );
    }

    public function test_messages_ordered_by_latest(): void
    {
        $latest = ContactMessage::factory()->create(['created_at' => now()->addHour()]);
        $earlier = ContactMessage::factory()->create(['created_at' => now()->subHour()]);

        $response = $this->get(route('admin.contact-messages.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('messages.data.0') // First item should be the latest
            ->where('messages.data.0.id', $latest->id)
        );
    }

    public function test_can_view_multiple_messages(): void
    {
        $messages = ContactMessage::factory()->count(5)->create();

        $response = $this->get(route('admin.contact-messages.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/ContactMessages/Index')
            ->has('messages.data', 5)
        );
    }

    public function test_message_data_is_displayed_correctly(): void
    {
        $message = ContactMessage::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message content',
            'is_read' => false,
        ]);

        $response = $this->get(route('admin.contact-messages.show', $message));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('message')
            ->where('message.name', 'Test User')
            ->where('message.email', 'test@example.com')
            ->where('message.subject', 'Test Subject')
            ->where('message.message', 'Test message content')
        );
    }

    public function test_can_view_read_message(): void
    {
        $message = ContactMessage::factory()->create(['is_read' => true]);

        $response = $this->get(route('admin.contact-messages.show', $message));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('message')
            ->where('message.is_read', true)
        );
    }

    public function test_viewing_already_read_message_still_works(): void
    {
        $message = ContactMessage::factory()->create(['is_read' => true]);

        $response = $this->get(route('admin.contact-messages.show', $message));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('message')
            ->where('message.is_read', true)
        );
    }

    public function test_deleting_nonexistent_message_returns_error(): void
    {
        $response = $this->delete(route('admin.contact-messages.destroy', 999999));

        // This should result in a ModelNotFoundException (404)
        $response->assertStatus(404);
    }

    public function test_message_attributes_validation(): void
    {
        $message = ContactMessage::factory()->create([
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test Message',
            'is_read' => true,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->get(route('admin.contact-messages.show', $message));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('message')
            ->where('message.name', 'Test Name')
            ->where('message.email', 'test@example.com')
            ->where('message.subject', 'Test Subject')
            ->where('message.message', 'Test Message')
            ->where('message.is_read', true)
        );
    }

    public function test_pagination_links_exist(): void
    {
        ContactMessage::factory()->count(15)->create();

        $response = $this->get(route('admin.contact-messages.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('messages')
            ->has('messages.links') // Check that pagination links exist
            ->where('messages.total', 15)
            ->where('messages.per_page', 10)
            ->where('messages.current_page', 1)
            ->where('messages.last_page', 2)
        );
    }

    public function test_message_created_at_is_displayed(): void
    {
        $message = ContactMessage::factory()->create(['created_at' => now()->subDays(3)]);

        $response = $this->get(route('admin.contact-messages.show', $message));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('message')
            ->has('message.created_at')
        );
    }
}
