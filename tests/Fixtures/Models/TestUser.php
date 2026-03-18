<?php

namespace Zak\Lists\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Zak\Lists\Tests\Fixtures\Factories\TestUserFactory;

/**
 * Тестовая модель пользователя для изоляции тестов от реального приложения.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $active
 */
class TestUser extends Authenticatable
{
    use HasFactory;

    protected $table = 'test_users';

    protected $guarded = [];

    protected static function newFactory(): TestUserFactory
    {
        return TestUserFactory::new();
    }

    /** Заглушка: все права разрешены для тестов. */
    public function can($abilities, $arguments = []): bool
    {
        return true;
    }
}
