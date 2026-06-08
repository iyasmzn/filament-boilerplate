<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(config('filament-shield.super_admin.name', 'super_admin'));

        $this->actingAs($this->admin);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_access_users_list_page(): void
    {
        $this->get('/admin/users')->assertOk();
    }

    public function test_admin_can_access_create_user_page(): void
    {
        $this->get('/admin/users/create')->assertOk();
    }

    public function test_admin_can_list_users(): void
    {
        $users = User::factory(3)->create();

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords($users);
    }

    public function test_admin_can_create_user(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(User::class, [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_create_user_validates_required_fields(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => null,
                'email' => 'not-an-email',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'email' => 'email',
            ]);
    }

    public function test_admin_can_edit_user(): void
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['name' => 'Updated Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_email_must_be_unique_on_create(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Another User',
                'email' => 'taken@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function test_password_not_required_on_edit(): void
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => null,
                'password_confirmation' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
