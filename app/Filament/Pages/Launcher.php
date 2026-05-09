<?php

namespace App\Filament\Pages;

use App\Models\Application;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Launcher extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'fas-th';

    protected string $view = 'filament.pages.launcher';

    protected static ?string $title = 'App Portal';

    protected static ?string $slug = 'launcher';

    public function table(Table $table): Table
    {
        return $table
            ->query(Application::query()->active())
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
                '2xl' => 4,
            ])
            ->columns([
                Stack::make([
                    \Filament\Tables\Columns\ImageColumn::make('icon_url')
                        ->label('')
                        ->circular()
                        ->defaultImageUrl(asset('images/app-placeholder.png'))
                        ->extraAttributes(['class' => 'mb-4']),
                    TextColumn::make('name')
                        ->weight('bold')
                        ->size('lg')
                        ->searchable(),
                    TextColumn::make('category')
                        ->badge()
                        ->color('gray')
                        ->extraAttributes(['class' => 'mt-2']),
                    TextColumn::make('description')
                        ->limit(100)
                        ->color('gray')
                        ->wrap()
                        ->extraAttributes(['class' => 'mt-2 text-sm']),
                ]),
            ])
            ->actions([
                Action::make('open_app')
                    ->label('Apri App')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn(Application $record) => $record->url)
                    ->openUrlInNewTab()
                    ->button(),
                Action::make('open_management')
                    ->label('Gestione')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->url(fn(Application $record) => $record->management_url)
                    ->openUrlInNewTab()
                    ->visible(fn(Application $record) => !empty($record->management_url))
                    ->iconButton(),
            ])
            ->actionsAlignment('justify-between')
            ->headerActions([])
            ->bulkActions([])
            ->defaultSort('sort_order');
    }
}
