<?php

namespace Controllers;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_employee_controller_show_action()
    {
        $employee = Employee::factory()->create();

        $response = $this->get(route('employees.show', $employee->id));

        // Проверяем, что ответ успешный (HTTP статус 200)
        $response->assertStatus(200);
        $response->assertViewHas('employee', $employee);
    }

    /** @test */
    public function test_employee_controller_index_action()
    {
        Employee::factory()->count(25)->create();

        $response = $this->get(route('employees.index'));

        // Проверяем, что ответ успешный
        $response->assertStatus(200);

        // Проверяем, что мы получили 10 элементов коллекции в ViewBag.
        // Хотя было создано больше чем 25
        // Это значит, что вернулась отпагинированная коллекция.

        $this->assertCount(10, $response->viewData('employees'));
    }

    /**
     * @test
     *
     */
    public function test_employee_controller_delete_action()
    {
        $employee = Employee::factory()->create();

        $response = $this->delete(route('employees.destroy', $employee->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Deleted');

        // Проверяем, что запись отсутствует.
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    /**
     * @test
     *
     */
    public function test_employee_controller_delete_action_with_invalid_request_data()
    {
        $nonExistsId = 6666;

        $response = $this->delete(route('employees.destroy', $nonExistsId));

        // Проверяем, что ответ возвращает статус 404 (Not Found)
        $response->assertStatus(404);
    }

    /** @test */
    public function test_store_action()
    {
        $company = Company::factory()->create();

        $data = [
            'first_name' => 'Denis',
            'last_name' => 'Samoilov',
            'company_id' => $company->id,
            'email' => 'admin@sex.com',
            'phone' => '1234567890',
        ];

        $response = $this->post(route('employees.store'), $data);

        // Проверяем, что сотрудник был создан
        $this->assertDatabaseHas('employees', $data);

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success', 'Created');
    }

    /** @test */
    public function test_store_action_with_invalid_request_data()
    {
        // Выполняем POST-запрос с пустыми данными
        $response = $this->post(route('employees.store'), []);

        // Проверяем, что произошла ошибка валидации
        $response->assertSessionHasErrors(['first_name', 'last_name', 'company_id']);
    }

    /** @test */
    public function test_update_action()
    {
        // Создаем компанию и сотрудника для тестирования обновления
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);

        // Данные для обновления сотрудника
        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'company_id' => $company->id,
            'email' => 'jane.doe@example.com',
            'phone' => '0987654321',
        ];

        // Выполняем PUT-запрос для обновления сотрудника
        $response = $this->put(route('employees.update', $employee), $data);

        // Проверяем, что данные сотрудника были обновлены
        $this->assertEquals('Jane', $employee->fresh()->first_name);

        // Проверяем редирект и сообщение об успехе
        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success', 'Updated');
    }

    /** @test */
    public function test_update_action_with_invalid_request_data()
    {
        $company = Company::factory()->create();

        $employee = Employee::factory()->create(['company_id' => $company->id]);

        // Выполняем PUT-запрос с пустыми данными
        $response = $this->put(route('employees.update', $employee->id), [
            'first_name' => '',
            'last_name' => '',
            'company_id' => '',
            'email' => null,
            'phone' => null,
        ]);

        // Проверяем, что произошла ошибка валидации
        $response->assertSessionHasErrors(['first_name', 'last_name', 'company_id']);
    }
}
