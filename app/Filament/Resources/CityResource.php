<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Filament\Forms\Components\Section as ComponentsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;


class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'System Management';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ComponentsSection::make('City Info')
                ->schema([
                    Select::make('country_id')
                        ->live()
                        ->label('Country')
                        ->dehydrated(false)
                        ->native(true)
                        ->searchable(true)
                        ->preload(true)
                        ->options(
                            Country::all()->pluck('name','id')
                        )
                        ->afterStateUpdated(function (Set $set) {
                            $set('state_id',null);
                        }),
                    Select::make('state_id')
                        ->required()
                        ->label('State')
                        ->native(false)
                        ->searchable(true)
                        ->preload(true)
                        ->options(
                            function (?City $city, Get $get, Set $set){
                                if(! empty($city) && empty($get('country_id'))){
                                    if($get('state_id')){
                                        $set('country_id', $city->state->country_id);
                                    }
                                }
                                return State::where('country_id', $get('country_id'))->pluck('name', 'id');
                            }
                        ),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('state.country.name')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('state.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('City')
                    ->sortable()
                    ->searchable(),
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
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('City Info')
                ->schema([
                    TextEntry::make('state.country.name')->label('Cotunry Name'),
                    TextEntry::make('state.name')->label('State Name'),
                    TextEntry::make('name')->label('City name'),
                ])->columns(2)
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'view' => Pages\ViewCity::route('/{record}'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
