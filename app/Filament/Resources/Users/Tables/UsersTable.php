<?php

namespace App\Filament\Resources\Users\Tables;

use App\Services\UserActivityService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'secondary',
                    })
                    ->searchable(),
                TextColumn::make('online_status')
                    ->label('Online')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        try {
                            $userActivityService = app(UserActivityService::class);
                            return $userActivityService->isUserOnline($record->id) ? 'Online' : 'Offline';
                        } catch (\Exception $e) {
                            return 'Unknown';
                        }
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Online' => 'success',
                        'Offline' => 'secondary',
                        default => 'warning',
                    }),
                TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->getStateUsing(function ($record) {
                        try {
                            $userActivityService = app(UserActivityService::class);
                            $activity = $userActivityService->getUserActivity($record->id);
                            return $activity ? $activity['last_seen']->diffForHumans() : 'Never';
                        } catch (\Exception $e) {
                            return 'Error';
                        }
                    })
                    ->sortable(false),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->poll('30s'); // Auto-refresh every 30 seconds to update online status
    }
}
