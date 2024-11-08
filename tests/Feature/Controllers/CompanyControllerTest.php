<?php

namespace Controllers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_company_controller_show_action()
    {
        $company = Company::factory()->create();

        $response = $this->get(route('companies.show', $company->id));

        // Проверяем, что ответ успешный (HTTP статус 200)
        $response->assertStatus(200);
        $response->assertViewHas('company', $company);
    }

    /** @test */
    public function test_company_controller_index_action()
    {
        Company::factory()->count(25)->create();

        $response = $this->get(route('companies.index'));

        // Проверяем, что ответ успешный
        $response->assertStatus(200);

        // Проверяем, что мы получили 10 элементов коллекции в ViewBag.
        // Хотя было создано больше чем 25
        // Это значит, что вернулась отпагинированная коллекция.
        $this->assertCount(10, $response->viewData('companies'));
    }

    /**
     * @test
     *
     */
    public function test_company_controller_delete_action()
    {
        $company = Company::factory()->create();

        $response = $this->delete(route('companies.destroy', $company->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Deleted');

        // Проверяем, что запись отсутствует.
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    /**
     * @test
     *
     */
    public function test_company_controller_delete_action_with_invalid_request_data()
    {
        $nonExistsId = 6666;

        $response = $this->delete(route('companies.destroy', $nonExistsId));

        // Проверяем, что ответ возвращает статус 404 (Not Found)
        $response->assertStatus(404);
    }

    /** @test */
    public function test_company_controller_store_action()
    {
        $data = [
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'logo' => null,
            'website' => 'https://example.com',
        ];

        $response = $this->post(route('companies.store'), $data);

        // Проверяем, что компания была создана
        $this->assertDatabaseHas('companies', $data);

        $response->assertRedirect(route('companies.index'));
        $response->assertSessionHas('success', 'Created');
    }

    /** @test */
    public function test_company_controller_store_action_with_invalid_request_data()
    {
        $response = $this->post(route('companies.store'), []);

        // Проверяем, что произошла ошибка валидации
        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function test_company_controller_update_action()
    {
        $company = Company::factory()->create();

        $data = [
            'name' => 'Updated Company',
            'email' => 'updated@example.com',
            'logo' => null,
            'website' => 'https://updated-example.com',
        ];

        $response = $this->put(route('companies.update', $company), $data);

        // Проверяем, что данные компании были обновлены
        $this->assertDatabaseHas('companies', $data);

        $response->assertRedirect(route('companies.index'));
        $response->assertSessionHas('success', 'Updated');
    }

    /** @test */
    public function test_company_controller_update_action_with_invalid_request_data()
    {
        $company = Company::factory()->create();

        $response = $this->put(route('companies.update', $company), [
            'name' => '',
            'email' => '',
            'website' => '',
        ]);

        // Проверяем, что произошла ошибка валидации
        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function test_company_controller_store_action_with_valid_logo()
    {
        Storage::fake('public');

        // Создаем временный файл изображения с размерами 200x200
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $data = [
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'logo' => $file,
            'website' => 'https://example.com',
        ];

        $response = $this->post(route('companies.store'), $data);

        // Проверяем, что компания была создана
        $this->assertCount(1, Company::all());
        $this->assertEquals('Test Company', Company::first()->name);

        // Проверяем, что файл логотипа был загружен
        Storage::disk('public')->assertExists(Company::first()->logo);

        // Проверяем, что произошел редирект
        $response->assertRedirect(route('companies.index'));
        $response->assertSessionHas('success', 'Created');
    }

    /** @test */
    public function test_company_controller_store_action_with_invalid_logo()
    {
        Storage::fake('public');

        // Создаем временный файл изображения с размерами 50x50
        $file = UploadedFile::fake()->image('logo.png', 50, 50);

        $response = $this->post(route('companies.store'), [
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'logo' => $file,
            'website' => 'https://example.com',
        ]);

        // Проверяем, что файл логотипа НЕ был загружен
        Storage::disk('public')->assertMissing($file);

        // Проверяем, что произошла ошибка валидации
        $response->assertSessionHasErrors(['logo']);
    }

    /** @test */
    public function test_company_controller_update_action_with_valid_logo()
    {
        Storage::fake('public');

        $company = Company::factory()->create();

        // Создаем временный файл изображения с размерами 200x200
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $data = [
            'name' => 'Updated Company',
            'email' => 'updated@example.com',
            'logo' => $file,
            'website' => 'https://updated-example.com',
        ];

        $response = $this->put(route('companies.update', $company), $data);

        // Проверяем, что компания была обновлена
        $this->assertEquals('Updated Company', $company->fresh()->name);

        // Проверяем, что файл логотипа был загружен
        Storage::disk('public')->assertExists($company->fresh()->logo);

        // Проверяем, что произошел редирект
        $response->assertRedirect(route('companies.index'));
        $response->assertSessionHas('success', 'Updated');
    }

    /** @test */
    public function test_company_controller_update_action_with_invalid_logo()
    {
        Storage::fake('public');

        $company = Company::factory()->create();

        // Создаем временный файл изображения с размерами 50x50 (слишком маленький)
        $smallFile = UploadedFile::fake()->image('logo.png', 50, 50);

        $dataSmall = [
            'name' => 'Updated Company',
            'email' => 'updated@example.com',
            'logo' => $smallFile,
            'website' => 'https://updated-example.com',
        ];

        $responseSmall = $this->put(route('companies.update', $company), $dataSmall);

        // Проверяем, что файл логотипа НЕ был загружен
        Storage::disk('public')->assertMissing($company->fresh()->logo);

        // Проверяем, что произошла ошибка валидации на размер логотипа
        $responseSmall->assertSessionHasErrors(['logo']);
    }

    /** @test
     * Проверяем что статика логотипов доступна всем.
     * */
    public function test_can_access_company_logo_from_public_directory()
    {
        Storage::fake('public');

        $company = Company::factory()->create();

        $logoPath = UploadedFile::fake()->image('logo.png', 200, 200)->store('logos', 'public');

        $company->logo = $logoPath;
        $company->save();

        // Проверяем, что файл существует в фейковой публичной директории.
        $this->assertTrue(Storage::disk('public')->exists($company->logo));
    }

    /** @test */
    public function test_logo_stored_in_correct_directory()
    {
        Storage::fake('public');

        $company = Company::factory()->create();

        $logoPath = UploadedFile::fake()->image('logo.png', 200, 200)->store('logos', 'public');

        $company->logo = $logoPath;
        $company->save();

        Storage::disk('public')->assertExists($company->logo);

        // Проверяем, что файл находится именно в директории 'logos'
        $this->assertStringStartsWith('logos/', $company->logo);
    }
}
