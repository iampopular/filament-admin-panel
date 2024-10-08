<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\City;
use App\Models\Country;
use App\Models\Employee;
use App\Models\State;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    TextInput::make('first_name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('middle_name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('last_name')
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
                    TextInput::make('address')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('zip_code')
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
                            function (Get $get){
                                return State::where('country_id', $get('country_id'))->pluck('name', 'id');
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
                                if(!empty($employee->id)){
                                    if($get('city_id')){
                                        $set('state_id', $employee->city->state->id);
                                    }
                                    if($get('state_id')){
                                        $set('country_id', $employee->city->state->country->id);
                                    }
                                }
                                return City::where('state_id', $get('state_id'))->pluck('name', 'id');   
                            }
                        )
                        ->native(false)
                        ->searchable(true)
                        ->preload(true)
                        ->required(),
                ])
                ->columns(3),
                Section::make('User Dates')
                ->description('')
                ->schema([
                    DatePicker::make('date_of_birth')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/y'),
                    DatePicker::make('date_of_hired')
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
                TextColumn::make('first_name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('middle_name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('address')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('zip_code')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('city.state.country.name')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('city.state.name')
                    ->label('State')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('city.name')
                    ->label('City')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_of_hired')
                    ->date()
                    ->sortable()
                    ->searchable()
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
                SelectFilter::make('Department')->relationship('department','name'),
                /*SelectFilter::make('Country')
                    ->relationship('city.state.country','name')
                    ->native(false)
                    ->searchable(true)
                    ->preload(true),
                SelectFilter::make('State')
                    ->relationship('city.state','name')
                    ->native(false)
                    ->searchable(true)
                    ->preload(true),
                SelectFilter::make('City')
                    ->relationship('city','name')
                    ->native(false)
                    ->searchable(true)
                    ->preload(true),*/
                Filter::make('Country')
                    ->form([
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
                            }),
                        Select::make('state_id')
                            ->label('State')
                            ->options(
                                function (Get $get){
                                    return State::where('country_id', $get('country_id'))->pluck('name', 'id');
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
                            ),
                        Select::make('city_id')
                            ->label('City')
                            ->options(
                                function (Get $get){
                                    return City::where('state_id', $get('state_id'))->pluck('name', 'id');   
                                }
                            )
                            ->native(false)
                            ->searchable(true)
                            ->preload(true)
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['city_id'],
                                fn (Builder $query, $city_id): Builder => $query->whereHas('city',fn($query) => $query->where('id',$city_id)),
                            )
                            ->when(
                                $data['state_id'],
                                fn (Builder $query, $state_id): Builder => $query->whereHas('city.state',fn($query) => $query->where('id',$state_id)),
                            )
                            ->when(
                                $data['country_id'],
                                fn (Builder $query, $country_id): Builder => $query->whereHas('city.state.country',fn($query) => $query->where('id',$country_id)),
                            );
                    })
                    ->indicateUsing(function (array $data): ?array {
                        $indicators = [];
                        if ($data['city_id'] || $data['state_id'] || $data['country_id']) {
                            if($data['country_id']){
                                $indicators[] =  'Country: ' . Country::where('id', $data['country_id'])->value('name');
                            }

                            if($data['state_id']){
                                $indicators[] =  'State: ' . State::where('id', $data['state_id'])->value('name');
                            }

                            if($data['city_id']){
                                $indicators[] =  'City: ' . City::where('id', $data['city_id'])->value('name');
                            } 
                        }
                        return $indicators;
                 
                        
                    })
                    
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
                ComponentsSection::make('Employee Info')
                ->schema([
                    TextEntry::make('first_name')->label('First Name'),
                    TextEntry::make('middle_name')->label('Middle Name'),
                    TextEntry::make('last_name')->label('Last Name'),
                ])->columns(2),
                ComponentsSection::make('Address Info')
                ->schema([
                    TextEntry::make('department.name')->label('Department'),
                    TextEntry::make('address')->label('Address'),
                    TextEntry::make('zip_code')->label('Zipcode'),
                    TextEntry::make('city.name')->label('City'),
                    TextEntry::make('city.state.name')->label('State'),
                    TextEntry::make('city.state.country.name')->label('Country'),
                ])->columns(2),
                ComponentsSection::make('Address Info')
                ->schema([
                    TextEntry::make('date_of_birth')->label('Date Of Birth'),
                    TextEntry::make('date_of_hired')->label('Date Of Hired')
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
