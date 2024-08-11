<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\City;
use App\Models\Country;
use App\Models\Employee;
use App\Models\State;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Employee Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Employee Name')
                ->description('')
                ->schema([
                    Forms\Components\TextInput::make('fist_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('middle_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('last_name')
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(3),
                Section::make('User Address')
                ->description('')
                ->schema([
                    Select::make('department_id')
                        ->relationship(name:'Department', titleAttribute:'name')
                        ->native(false)
                        ->searchable(true)
                        ->preload(true)
                        ->required(),
                    Forms\Components\TextInput::make('address')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('zip_code')
                        ->required()
                        ->maxLength(255),
                    Select::make('country_id')
                        ->label('Country')
                        ->options(Country::all()->pluck('name','id')->toArray())
                        ->live()
                        ->dehydrated(false)
                        ->native(false)
                        ->searchable(true)
                        ->preload(true)
                        ->afterStateUpdated(function (Set $set) {
                            $set('city_id',null);
                            $set('state_id',null);
                        })
                        ->required(),
                    Select::make('state_id')
                        ->label('State')
                        ->options(
                            function (?Employee $employee, Get $get, Set $set){
                                $country = Country::find($get('country_id'));
                                if(!$country){
                                    return [];
                                }
                                return $country->states->pluck('name','id');
                            }
                        )
                        ->live()
                        ->dehydrated(false)
                        ->native(false)
                        ->searchable(true)
                        ->preload(true)
                        ->afterStateUpdated(
                            function (Set $set) {
                                $set('city_id',null);
                            }
                        )
                        ->required(),
                    Select::make('city_id')
                        ->label('City')
                        ->options(
                            function (?Employee $employee, Get $get, Set $set){
                                if(!empty($employee->id) && (empty($get('state_id')) && empty($get('country_id'))) ){
                                    $set('country_id', $employee->city->state->country->id);
                                    $set('state_id', $employee->city->state->id);
                                }
                                $state = State::find($get('state_id'));
                                if(!$state){
                                    return [];
                                }
                                return $state->cities->pluck('name','id');
                                
                            }
                        )
                        ->live(debounce: '2s')
                        ->native(false)
                        ->searchable(true)
                        ->preload(true)
                        ->required(),
                ])
                ->columns(3),
                Section::make('User Dates')
                ->description('')
                ->schema([
                    Forms\Components\DatePicker::make('date_of_birth')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/y'),
                    Forms\Components\DatePicker::make('date_of_hired')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/y'),
                ])
                ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fist_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('middle_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('zip_code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('country.name')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('state.name')
                    ->label('State')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('City')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_hired')
                    ->date()
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
