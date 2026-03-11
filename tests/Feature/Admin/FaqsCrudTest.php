<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_can_view_faqs_index_page(): void
    {
        Faq::factory()->count(3)->create();

        $response = $this->get(route('admin.faqs.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Faqs/Index')
            ->has('faqs', 3)
        );
    }

    public function test_can_create_faq(): void
    {
        $data = [
            'question' => 'Bagaimana cara mendaftar siswa baru?',
            'answer' => 'Pendaftaran siswa baru dapat dilakukan secara online melalui website sekolah atau datang langsung ke sekolah.',
            'category' => 'PPDB',
            'is_published' => true,
            'sort_order' => 1,
        ];

        $response = $this->post(route('admin.faqs.store'), $data);

        $response->assertRedirect(route('admin.faqs.index'));
        $response->assertSessionHas('success', 'FAQ berhasil ditambahkan.');
        $this->assertDatabaseHas('faqs', [
            'question' => 'Bagaimana cara mendaftar siswa baru?',
            'category' => 'PPDB',
        ]);
    }

    public function test_validation_requires_question_and_answer(): void
    {
        $data = [
            'question' => '',
            'answer' => '',
            'is_published' => true,
        ];

        $response = $this->post(route('admin.faqs.store'), $data);

        $response->assertSessionHasErrors(['question', 'answer']);
    }

    public function test_question_max_length_validation(): void
    {
        $data = [
            'question' => str_repeat('A', 256), // exceeds max of 255
            'answer' => 'Answer text',
            'is_published' => true,
        ];

        $response = $this->post(route('admin.faqs.store'), $data);

        $response->assertSessionHasErrors(['question']);
    }

    public function test_category_max_length_validation(): void
    {
        $data = [
            'question' => 'Question?',
            'answer' => 'Answer text',
            'category' => str_repeat('A', 256), // exceeds max of 255
            'is_published' => true,
        ];

        $response = $this->post(route('admin.faqs.store'), $data);

        $response->assertSessionHasErrors(['category']);
    }

    public function test_can_update_faq(): void
    {
        $faq = Faq::factory()->create([
            'question' => 'Old Question',
            'answer' => 'Old Answer',
        ]);

        $data = [
            'question' => 'Updated Question',
            'answer' => 'Updated Answer with more details',
            'category' => 'Updated Category',
            'is_published' => false,
            'sort_order' => 5,
        ];

        $response = $this->put(route('admin.faqs.update', $faq), $data);

        $response->assertRedirect(route('admin.faqs.index'));
        $response->assertSessionHas('success', 'FAQ berhasil diperbarui.');
        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'Updated Question',
            'answer' => 'Updated Answer with more details',
        ]);
    }

    public function test_can_delete_faq(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->delete(route('admin.faqs.destroy', $faq));

        $response->assertRedirect(route('admin.faqs.index'));
        $response->assertSessionHas('success', 'FAQ berhasil dihapus.');
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_can_create_unpublished_faq(): void
    {
        $data = [
            'question' => 'Draft Question?',
            'answer' => 'Draft answer',
            'is_published' => false,
        ];

        $this->post(route('admin.faqs.store'), $data);

        $this->assertDatabaseHas('faqs', [
            'question' => 'Draft Question?',
            'is_published' => false,
        ]);
    }

    public function test_can_set_sort_order(): void
    {
        $data = [
            'question' => 'Question with order',
            'answer' => 'Answer',
            'sort_order' => 10,
            'is_published' => true,
        ];

        $this->post(route('admin.faqs.store'), $data);

        $this->assertDatabaseHas('faqs', [
            'question' => 'Question with order',
            'sort_order' => 10,
        ]);
    }

    public function test_can_create_faq_without_category(): void
    {
        $data = [
            'question' => 'Question without category?',
            'answer' => 'Answer',
            'category' => null,
            'is_published' => true,
        ];

        $this->post(route('admin.faqs.store'), $data);

        $this->assertDatabaseHas('faqs', [
            'question' => 'Question without category?',
            'category' => null,
        ]);
    }

    public function test_answer_can_be_long_text(): void
    {
        $longAnswer = str_repeat('This is a detailed answer. ', 100);

        $data = [
            'question' => 'Complex Question?',
            'answer' => $longAnswer,
            'is_published' => true,
        ];

        $this->post(route('admin.faqs.store'), $data);

        $faq = Faq::where('question', 'Complex Question?')->first();
        $this->assertEquals(trim($longAnswer), $faq->answer);
    }

    public function test_can_create_faq_in_different_categories(): void
    {
        $categories = ['PPDB', 'Akademik', 'Kesiswaan', 'Keuangan'];

        foreach ($categories as $category) {
            $data = [
                'question' => "Question for {$category}?",
                'answer' => "Answer for {$category}",
                'category' => $category,
                'is_published' => true,
            ];
            $this->post(route('admin.faqs.store'), $data);
        }

        foreach ($categories as $category) {
            $this->assertDatabaseHas('faqs', ['category' => $category]);
        }
    }

    public function test_update_maintains_published_status(): void
    {
        $faq = Faq::factory()->create(['is_published' => true]);

        $data = [
            'question' => 'Updated Question',
            'answer' => 'Updated Answer',
            'is_published' => true,
        ];

        $this->put(route('admin.faqs.update', $faq), $data);

        $faq->refresh();
        $this->assertTrue($faq->is_published);
    }

    public function test_can_unpublish_faq(): void
    {
        $faq = Faq::factory()->create(['is_published' => true]);

        $data = [
            'question' => 'Question',
            'answer' => 'Answer',
            'is_published' => false,
        ];

        $this->put(route('admin.faqs.update', $faq), $data);

        $faq->refresh();
        $this->assertFalse($faq->is_published);
    }
}
