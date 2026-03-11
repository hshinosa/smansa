<?php

namespace Tests\Feature;

use App\Models\AcademicCalendarContent;
use App\Models\Alumni;
use App\Models\Extracurricular;
use App\Models\Gallery;
use App\Models\Post;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_can_be_accessed()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('LandingPage')
            ->has('heroContent')
        );
    }

    public function test_visi_misi_page_can_be_accessed()
    {
        $response = $this->get('/visi-misi');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('VisiMisiPage')
        );
    }

    public function test_struktur_organisasi_page_can_be_accessed()
    {
        $response = $this->get('/struktur-organisasi');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('StrukturOrganisasiPage')
        );
    }

    public function test_profil_sekolah_page_can_be_accessed()
    {
        $response = $this->get('/profil-sekolah');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProfilSekolahPage')
        );
    }

    public function test_guru_staff_page_can_be_accessed()
    {
        $response = $this->get('/guru-staff');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('GuruStaffPage')
        );
    }

    public function test_program_sekolah_page_can_be_accessed()
    {
        $response = $this->get('/program');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProgramSekolahPage')
        );
    }

    public function test_kurikulum_page_can_be_accessed()
    {
        $response = $this->get('/akademik/kurikulum');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('KurikulumPage')
        );
    }

    public function test_ekstrakurikuler_page_can_be_accessed()
    {
        $response = $this->get('/akademik/ekstrakurikuler');
        $response->assertRedirect(route('akademik.organisasi_ekstrakurikuler'));
        $response = $this->get('/akademik/organisasi-ekstrakurikuler');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('EkstrakurikulerPage')
        );
    }

    public function test_program_studi_mipa_page_can_be_accessed()
    {
        $response = $this->get('/akademik/program-studi/mipa');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProgramMipaPage')
        );
    }

    public function test_program_studi_ips_page_can_be_accessed()
    {
        $response = $this->get('/akademik/program-studi/ips');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProgramIpsPage')
        );
    }

    public function test_program_studi_bahasa_page_can_be_accessed()
    {
        $response = $this->get('/akademik/program-studi/bahasa');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProgramBahasaPage')
        );
    }

    public function test_informasi_spmb_page_can_be_accessed()
    {
        $response = $this->get('/informasi-spmb');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('InformasiSpmbPage')
        );
    }

    public function test_alumni_page_can_be_accessed()
    {
        $response = $this->get('/alumni');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AlumniPage')
        );
    }

    public function test_galeri_page_can_be_accessed()
    {
        $response = $this->get('/galeri');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('GaleriPage')
        );
    }

    public function test_berita_pengumuman_page_can_be_accessed()
    {
        $response = $this->get('/berita-pengumuman');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('BeritaPengumumanPage')
        );
    }

    public function test_berita_detail_page_can_be_accessed()
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/berita/' . $post->slug);
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('BeritaDetailPage')
            ->where('post.id', $post->id)
        );
    }

    public function test_kontak_page_can_be_accessed()
    {
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('KontakPage')
        );
    }

    public function test_kalender_akademik_page_can_be_accessed()
    {
        $response = $this->get('/kalender-akademik');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AcademicCalendarPage')
        );
    }

    public function test_sitemap_can_be_accessed()
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
    }

    public function test_robots_txt_can_be_accessed()
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
        // Header assertions can be case-sensitive depending on implementation, allow flexibility or match exact
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    }
}
