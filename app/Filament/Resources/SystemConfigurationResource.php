<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemConfigurationResource\Pages\CreateSystemConfiguration;
use App\Filament\Resources\SystemConfigurationResource\Pages\EditSystemConfiguration;
use App\Filament\Resources\SystemConfigurationResource\Pages\ListSystemConfigurations;
use App\Filament\Resources\SystemConfigurationResource\Schemas\SystemConfigurationForm;
use App\Filament\Resources\SystemConfigurationResource\Tables\SystemConfigurationsTable;
use App\Models\SystemConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SystemConfigurationResource extends Resource
{
    protected static ?string $model = SystemConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Configuraciones';

    protected static ?string $modelLabel = 'Configuración';

    protected static ?string $pluralModelLabel = 'Configuraciones del Sistema';

    protected static UnitEnum|string|null $navigationGroup = '⚙️ Administración';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Schema $schema): Schema
    {
        return SystemConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SystemConfigurationsTable::configure($table);
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
            'index' => ListSystemConfigurations::route('/'),
            'create' => CreateSystemConfiguration::route('/create'),
            'edit' => EditSystemConfiguration::route('/{record}/edit'),
        ];
    }
}
