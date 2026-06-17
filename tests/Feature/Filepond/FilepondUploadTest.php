<?php

declare(strict_types=1);

namespace Tests\Feature\Filepond;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilepondUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_process_requires_authentication(): void
    {
        $this->post(route('college.filepond.process'), [
            'purpose' => 'school_logo',
        ])->assertRedirect();
    }

    public function test_process_stores_image_under_user_tmp_prefix(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'username' => 'admin1',
            'type' => 'admin',
        ]);

        $file = UploadedFile::fake()->image('logo.png', 80, 80);

        $response = $this->actingAs($user)->post(route('college.filepond.process'), [
            'filepond' => $file,
            'purpose' => 'school_logo',
        ]);

        $response->assertOk();
        $path = trim($response->getContent());
        $this->assertStringStartsWith('filepond-tmp/'.$user->id.'/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_revert_deletes_pending_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'username' => 'admin2',
            'type' => 'admin',
        ]);

        $path = 'filepond-tmp/'.$user->id.'/revert-me.png';
        Storage::disk('local')->put($path, 'fake');

        $this->actingAs($user)->call(
            'DELETE',
            route('college.filepond.revert'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $path,
        )->assertOk();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_process_teacher_import_accepts_csv(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'username' => 'admin3',
            'type' => 'admin',
        ]);

        $file = UploadedFile::fake()->create('teachers.csv', 2, 'text/csv');

        $response = $this->actingAs($user)->post(route('college.filepond.process'), [
            'filepond' => $file,
            'purpose' => 'teacher_import',
        ]);

        $response->assertOk();
        $path = trim($response->getContent());
        $this->assertStringStartsWith('filepond-tmp/'.$user->id.'/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_process_teacher_profile_photo_accepts_png(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'username' => 'tphoto1',
            'type' => 'teacher',
        ]);

        $file = UploadedFile::fake()->image('pic.png', 80, 80);

        $response = $this->actingAs($user)->post(route('college.filepond.process'), [
            'filepond' => $file,
            'purpose' => 'teacher_profile_photo',
        ]);

        $response->assertOk();
        $path = trim($response->getContent());
        $this->assertStringStartsWith('filepond-tmp/'.$user->id.'/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_process_teacher_course_material_accepts_pdf(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'username' => 'tmat1',
            'type' => 'teacher',
        ]);

        $file = UploadedFile::fake()->create('slides.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)->post(route('college.filepond.process'), [
            'filepond' => $file,
            'purpose' => 'teacher_course_material',
        ]);

        $response->assertOk();
        $path = trim($response->getContent());
        $this->assertStringStartsWith('filepond-tmp/'.$user->id.'/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_process_teacher_cv_accepts_pdf(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'username' => 'tcv1',
            'type' => 'teacher',
        ]);

        $file = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('college.filepond.process'), [
            'filepond' => $file,
            'purpose' => 'teacher_cv',
        ]);

        $response->assertOk();
        $path = trim($response->getContent());
        $this->assertStringStartsWith('filepond-tmp/'.$user->id.'/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_process_teacher_import_rejects_non_spreadsheet(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'username' => 'admin4',
            'type' => 'admin',
        ]);

        $file = UploadedFile::fake()->image('photo.png', 10, 10);

        $this->actingAs($user)
            ->post(route('college.filepond.process'), [
                'filepond' => $file,
                'purpose' => 'teacher_import',
            ])
            ->assertInvalid(['filepond']);
    }
}
