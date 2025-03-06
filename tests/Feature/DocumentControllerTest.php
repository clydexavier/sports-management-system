<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\Document;
use App\Models\IntramuralGame;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_documents_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        Document::factory()->count(3)->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/documents");

        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_index_documents_if_user()
    {
        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();
        Document::factory()->count(3)->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/intramurals/{$intrams->id}/documents");

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_store_document_if_admin()
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $data = [
            'name' => 'Test Document',
            'file' => $file,
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($admin)->postJson("/api/v1/intramurals/{$intrams->id}/documents/create", $data);

        $response->assertStatus(201)->assertJsonFragment(['name' => 'Test Document']);

        Storage::disk('local')->assertExists("documents/{$file->hashName()}");
    }

    public function test_store_document_if_user()
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $intrams = IntramuralGame::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $data = [
            'name' => 'Unauthorized Document',
            'file' => $file,
            'intrams_id' => $intrams->id,
        ];

        $response = $this->actingAs($user)->postJson("/api/v1/intramurals/{$intrams->id}/documents/create", $data);

        $response->assertStatus(403)
                 ->assertJson(['error' => 'unauthorized']);
    }

    public function test_show_document_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $document = Document::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/documents/{$document->id}");

        $response->assertStatus(200)->assertJson(['id' => $document->id, 'name' => $document->name]);
    }

    public function test_update_document_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $document = Document::factory()->create(['intrams_id' => $intrams->id]);

        $updateData = ['name' => 'Updated Document Name'];

        $response = $this->actingAs($admin)->patchJson("/api/v1/intramurals/{$intrams->id}/documents/{$document->id}/edit", $updateData);

        $response->assertStatus(200)->assertJson(['message' => 'Document updated successfully']);

        $this->assertDatabaseHas('documents', ['id' => $document->id, 'name' => 'Updated Document Name']);
    }

    public function test_delete_document_if_admin()
    {
        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        $document = Document::factory()->create(['intrams_id' => $intrams->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/intramurals/{$intrams->id}/documents/{$document->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_download_document_if_admin()
    {
        Storage::disk('local');

        $admin = User::factory()->admin()->create();
        $intrams = IntramuralGame::factory()->create();
        
        // Create a fake file
        $file = UploadedFile::fake()->create('downloadable.pdf', 200);
        $filePath = "documents/{$file->hashName()}"; // This generates a hashed filename

        // Store file in the fake local disk
        Storage::disk('local')->put($filePath, 'Dummy content');

        // Create a document entry pointing to the file
        $document = Document::factory()->create([
            'intrams_id' => $intrams->id,
            'file_path' => $filePath, // Store the hashed name
        ]);

        // Ensure the file exists before downloading
        Storage::disk('local')->assertExists($filePath);

        // Attempt to download the document
        $response = $this->actingAs($admin)->getJson("/api/v1/intramurals/{$intrams->id}/documents/{$document->id}/download");

        $response->dump();

        // Assert that the response returns the correct download header with the hashed filename
        $expectedFilename = basename($document->file_path);

        $response->assertStatus(200)
                ->assertHeader('Content-Disposition', "attachment; filename={$expectedFilename}");
    }


}
