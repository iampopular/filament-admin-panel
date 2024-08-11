<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Filament\Resources\CityResource\RelationManagers;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\Label;

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
                Select::make('country_id')
                    ->live()
                    ->label('Country')
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
                    ->placeholder(fn (Forms\Get $get): string => empty($get('country_id')) ? 'First select country' : 'Select an option')
                    ->options(
                        function (?City $city, Get $get, Set $set){
                            if(!empty($city->id) && empty($get('country_id')) ){
                                $set('country_id', $city->state->country_id);
                                $set('state_id', $city->state_id);
                            }

                            return State::where('country_id', $get('country_id'))->pluck('name', 'id');
                        }
                    ),
                    
                /*Select::make('state_id')
                    ->relationship(name:'State', titleAttribute:'name')
                    ->native(false)
                    ->searchable(true)
                    ->preload(true)
                    ->required(),*/
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('state.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('City')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
