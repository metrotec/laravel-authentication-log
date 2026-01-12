---
title: Displaying the Log
weight: 3
---

You can set up your own views and paginate the logs using the user relationship as normal. Below are examples for different table implementations.

## Filament Table

If you're using [Filament](https://filamentphp.com), here's an example table component:

**Note:** This example uses the package's built-in `DeviceFingerprint` helper for parsing user agents. You can customize the display format as needed.

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthenticationLogResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class AuthenticationLogResource extends Resource
{
    protected static ?string $model = AuthenticationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_name')
                    ->label('Browser/Device')
                    ->searchable()
                    ->default('Unknown Device'),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('location->city', 'like', "%{$search}%")
                            ->orWhere('location->state', 'like', "%{$search}%")
                            ->orWhere('location->state_name', 'like', "%{$search}%")
                            ->orWhere('location->postal_code', 'like', "%{$search}%");
                    })
                    ->formatStateUsing(function ($state) {
                        if (!$state || ($state['default'] ?? false)) {
                            return '-';
                        }
                        return ($state['city'] ?? 'Unknown City') . ', ' . ($state['state'] ?? 'Unknown State');
                    }),
                Tables\Columns\TextColumn::make('device_name')
                    ->label('Device')
                    ->default('Unknown')
                    ->searchable(),
                Tables\Columns\IconColumn::make('login_successful')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_trusted')
                    ->label('Trusted')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_suspicious')
                    ->label('Suspicious')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('login_at')
                    ->label('Login At')
                    ->dateTime()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('logout_at')
                    ->label('Logout At')
                    ->dateTime()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable()
                    ->default('-'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('login_successful')
                    ->label('Login Status')
                    ->placeholder('All logins')
                    ->trueLabel('Successful only')
                    ->falseLabel('Failed only'),
                Tables\Filters\TernaryFilter::make('is_trusted')
                    ->label('Trusted Device')
                    ->placeholder('All devices')
                    ->trueLabel('Trusted only')
                    ->falseLabel('Untrusted only'),
                Tables\Filters\TernaryFilter::make('is_suspicious')
                    ->label('Suspicious Activity')
                    ->placeholder('All activities')
                    ->trueLabel('Suspicious only')
                    ->falseLabel('Normal only'),
                Tables\Filters\Filter::make('active_sessions')
                    ->label('Active Sessions')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('login_successful', true)
                        ->whereNull('logout_at')
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->modalContent(function (AuthenticationLog $record) {
                        return view('filament.resources.authentication-log.view', [
                            'record' => $record,
                        ]);
                    })
                    ->modalHeading('Authentication Log Details'),
            ])
            ->defaultSort('login_at', 'desc')
            ->poll('30s'); // Optional: auto-refresh every 30 seconds
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('authenticatable_type', auth()->user()->getMorphClass())
            ->where('authenticatable_id', auth()->id());
    }
}
```

For a standalone Filament table (not a resource), you can use:

```php
<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class AuthenticationLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static string $view = 'filament.pages.authentication-logs';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AuthenticationLog::query()
                    ->where('authenticatable_type', auth()->user()->getMorphClass())
                    ->where('authenticatable_id', auth()->id())
            )
            ->columns([
                // ... same columns as above
            ])
            ->filters([
                // ... same filters as above
            ])
            ->defaultSort('login_at', 'desc');
    }
}
```

### Displaying Authentication Logs on User Resource Pages

To show authentication logs as a relationship tab on your User resource page, create a RelationManager:

```php
<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class AuthenticationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'authentications';

    protected static ?string $recordTitleAttribute = 'ip_address';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_name')
                    ->label('Device')
                    ->searchable()
                    ->default('Unknown Device'),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->getStateUsing(function ($record) {
                        $location = $record->location;

                        if (!$location || !is_array($location)) {
                            return '-';
                        }

                        // Don't show default/fallback locations
                        if ($location['default'] ?? false) {
                            return '-';
                        }

                        $city = $location['city'] ?? null;
                        $state = $location['state'] ?? $location['state_name'] ?? null;

                        if (!$city && !$state) {
                            return '-';
                        }

                        return trim(($city ?? '') . ($city && $state ? ', ' : '') . ($state ?? '')) ?: '-';
                    })
                    ->searchable(false),
                Tables\Columns\IconColumn::make('login_successful')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_trusted')
                    ->label('Trusted')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_suspicious')
                    ->label('Suspicious')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('login_at')
                    ->label('Login At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('logout_at')
                    ->label('Logout At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('login_successful')
                    ->label('Login Status')
                    ->placeholder('All logins')
                    ->trueLabel('Successful only')
                    ->falseLabel('Failed only'),
                Tables\Filters\TernaryFilter::make('is_trusted')
                    ->label('Trusted Device')
                    ->placeholder('All devices')
                    ->trueLabel('Trusted only')
                    ->falseLabel('Untrusted only'),
                Tables\Filters\TernaryFilter::make('is_suspicious')
                    ->label('Suspicious Activity')
                    ->placeholder('All activities')
                    ->trueLabel('Suspicious only')
                    ->falseLabel('Normal only'),
                Tables\Filters\Filter::make('active_sessions')
                    ->label('Active Sessions')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('login_successful', true)
                        ->whereNull('logout_at')
                    ),
            ])
            ->defaultSort('login_at', 'desc');
    }
}
```

Then register it in your User resource:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\RelationManagers\AuthenticationLogsRelationManager;
use Filament\Resources\Resource;
use App\Models\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // ... other resource configuration

    public static function getRelations(): array
    {
        return [
            AuthenticationLogsRelationManager::class,
        ];
    }
}
```

The authentication logs will now appear as a tab on the User resource's view/edit pages, showing all authentication activity for that specific user.

## Livewire Tables

If you use my [Livewire Tables](https://github.com/rappasoft/laravel-livewire-tables) plugin, here is an example table:

**Note:** This example uses the package's built-in `device_name` field which is automatically generated from the user agent. You can customize the display format as needed.

```php
<?php

namespace App\Http\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog as Log;

class AuthenticationLog extends DataTableComponent
{
    public string $defaultSortColumn = 'login_at';
    public string $defaultSortDirection = 'desc';
    public string $tableName = 'authentication-log-table';

    public User $user;

    public function mount(User $user)
    {
        if (! auth()->user() || ! auth()->user()->isAdmin()) {
            $this->redirectRoute('frontend.index');
        }

        $this->user = $user;
    }

    public function columns(): array
    {
        return [
            Column::make('IP Address', 'ip_address')
                ->searchable(),
            Column::make('Device', 'device_name')
                ->searchable()
                ->format(fn($value, $row) => $row->device_name ?? 'Unknown Device'),
            Column::make('User Agent', 'user_agent')
                ->searchable()
                ->wrap(),
            Column::make('Location')
                ->searchable(function (Builder $query, $searchTerm) {
                    $query->orWhere('location->city', 'like', '%'.$searchTerm.'%')
                        ->orWhere('location->state', 'like', '%'.$searchTerm.'%')
                        ->orWhere('location->state_name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('location->postal_code', 'like', '%'.$searchTerm.'%');
                })
                ->format(fn ($value) => $value && $value['default'] === false ? $value['city'] . ', ' . $value['state'] : '-'),
            Column::make('Login At')
                ->sortable()
                ->format(fn($value) => $value ? timezone()->convertToLocal($value) : '-'),
            Column::make('Login Successful')
                ->sortable()
                ->format(fn($value) => $value === true ? 'Yes' : 'No'),
            Column::make('Logout At')
                ->sortable()
                ->format(fn($value) => $value ? timezone()->convertToLocal($value) : '-'),
            Column::make('Cleared By User')
                ->sortable()
                ->format(fn($value) => $value === true ? 'Yes' : 'No'),
            Column::make('Device')
                ->format(fn($value, $row) => $row->device_name ?? 'Unknown'),
            Column::make('Trusted')
                ->sortable()
                ->format(fn($value) => $value === true ? 'Yes' : 'No'),
            Column::make('Suspicious')
                ->sortable()
                ->format(fn($value) => $value === true ? 'Yes' : 'No'),
        ];
    }

    public function query(): Builder
    {
        return Log::query()
            ->where('authenticatable_type', User::class)
            ->where('authenticatable_id', $this->user->id);
    }
}
```

```html
<livewire:authentication-log :user="$user" />
```

Example:

![Example Log Table](https://imgur.com/B4DlN4W.png)
