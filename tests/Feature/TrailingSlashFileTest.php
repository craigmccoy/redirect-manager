<?php

namespace Tests\Feature;

use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrailingSlashFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_urls_ignore_add_trailing_slash_mode(): void
    {
        Redirect::factory()->domain()->preservePath()->addTrailingSlash()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        // File extensions should NOT get trailing slash added
        $response = $this->get('/document.pdf');
        $response->assertRedirect('https://newsite.com/document.pdf'); // Not /document.pdf/

        $response = $this->get('/image.jpg');
        $response->assertRedirect('https://newsite.com/image.jpg');

        $response = $this->get('/script.js');
        $response->assertRedirect('https://newsite.com/script.js');

        $response = $this->get('/style.css');
        $response->assertRedirect('https://newsite.com/style.css');
    }

    public function test_directory_urls_do_get_trailing_slash_added(): void
    {
        Redirect::factory()->domain()->preservePath()->addTrailingSlash()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        // Non-file paths SHOULD get trailing slash
        $response = $this->get('/about');
        $response->assertRedirect('https://newsite.com/about/');

        $response = $this->get('/products');
        $response->assertRedirect('https://newsite.com/products/');
    }

    public function test_file_urls_ignore_remove_trailing_slash_mode(): void
    {
        Redirect::factory()->domain()->preservePath()->removeTrailingSlash()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        // File extensions should be left alone (even though remove mode is on)
        $response = $this->get('/download.zip');
        $response->assertRedirect('https://newsite.com/download.zip');

        $response = $this->get('/data.json');
        $response->assertRedirect('https://newsite.com/data.json');
    }

    public function test_file_with_complex_path(): void
    {
        Redirect::factory()->domain()->preservePath()->addTrailingSlash()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        // File in subdirectory should not get trailing slash
        $response = $this->get('/downloads/reports/annual-report-2024.pdf');
        $response->assertRedirect('https://newsite.com/downloads/reports/annual-report-2024.pdf');
    }

    public function test_common_file_extensions(): void
    {
        Redirect::factory()->domain()->preservePath()->addTrailingSlash()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        $fileExtensions = [
            'pdf', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',
            'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip', 'tar', 'gz', 'rar',
            'mp3', 'mp4', 'avi', 'mov',
            'html', 'css', 'js', 'json', 'xml',
            'txt', 'csv', 'log',
        ];

        foreach ($fileExtensions as $ext) {
            $response = $this->get("/file.{$ext}");
            $response->assertRedirect("https://newsite.com/file.{$ext}");
        }
    }

    public function test_edge_case_multiple_dots_in_filename(): void
    {
        Redirect::factory()->domain()->preservePath()->addTrailingSlash()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        // File with multiple dots should still be detected as file
        $response = $this->get('/my.backup.file.tar.gz');
        $response->assertRedirect('https://newsite.com/my.backup.file.tar.gz');
    }

    public function test_path_without_extension_gets_trailing_slash(): void
    {
        Redirect::factory()->domain()->preservePath()->addTrailingSlash()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        // Paths that look like they could be directories get trailing slash
        $response = $this->get('/api/users');
        $response->assertRedirect('https://newsite.com/api/users/');

        $response = $this->get('/blog/my-post');
        $response->assertRedirect('https://newsite.com/blog/my-post/');
    }
}
