<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Leitor,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Administrador,
        ]);
    }

    /**
     * Indicate that the user is an editor.
     */
    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Editor,
        ]);
    }

    /**
     * Indicate that the user is an author.
     */
    public function autor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Autor,
        ]);
    }

    /**
     * Indicate that the user is a reader.
     */
    public function leitor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Leitor,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inativo(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
