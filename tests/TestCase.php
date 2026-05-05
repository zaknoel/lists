<?php

namespace Zak\Lists\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Yajra\DataTables\DataTablesServiceProvider;
use Zak\Lists\ListsServiceProvider;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();

        // Загружаем переводы пакета в default namespace, чтобы __('lists.*') работал в тестах
        $this->app->make('translation.loader')->addPath(realpath(__DIR__.'/../resources/lang'));

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Zak\\Lists\\Tests\\Fixtures\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            DataTablesServiceProvider::class,
            ListsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('session.driver', 'array');
        config()->set('auth.providers.users.model', TestUser::class);
        config()->set('lists.path', __DIR__.'/Fixtures/Lists/');
        config()->set('lists.layout', 'layouts.app');

        // Регистрируем тестовый layout чтобы виды пакета могли его расширить
        $app['view']->addLocation(__DIR__.'/Fixtures/views');
    }

    protected function createTestTables(): void
    {
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->default('hashed');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('_user_list_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('_list_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('list');
            $table->string('filename');
            $table->string('status')->default('pending');
            $table->string('filepath')->nullable();
            $table->string('disk')->default('local');
            $table->text('error_message')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
        });
    }
}
