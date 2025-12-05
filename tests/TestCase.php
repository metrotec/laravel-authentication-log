<?php

namespace Rappasoft\LaravelAuthenticationLog\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Orchestra\Testbench\TestCase as Orchestra;
use Rappasoft\LaravelAuthenticationLog\Database\Factories\TestUserFactory;
use Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            if ($modelName === 'Rappasoft\\LaravelAuthenticationLog\\Tests\\TestUser') {
                return 'Rappasoft\\LaravelAuthenticationLog\\Database\\Factories\\TestUserFactory';
            }
            return 'Rappasoft\\LaravelAuthenticationLog\\Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelAuthenticationLogServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        
        // Run the package migration
        $migration = include __DIR__.'/../database/migrations/create_authentication_log_table.php.stub';
        $migration->up();
    }
}

class TestUser extends User
{
    use HasFactory;
    use Notifiable;
    use AuthenticationLoggable;
    
    protected $table = 'users';
    
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    protected static function newFactory()
    {
        return TestUserFactory::new();
    }
}
