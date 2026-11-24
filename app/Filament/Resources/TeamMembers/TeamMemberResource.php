<?php

namespace App\Filament\Resources\TeamMembers;

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Models\Branch;
use App\Models\TeamMember;
use App\Support\PublishGuard;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamMemberResource extends Resource
{
    protected static ?string $model = TeamMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Team';

    protected static ?string $modelLabel = 'team member';

    /** Staff see their own branch rows plus global rows (null branch_id), matching the policy view(). */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        if ($user === null || self::isSuperAdmin()) {
            return $query->visibleTo($user);
        }

        return $query->where(fn (Builder $q) => $q
            ->whereNull('team_members.branch_id')
            ->orWhere('team_members.branch_id', $user->branch_id));
    }

    /** Non-super-admins always save into their own branch, whatever the request says. */
    public static function forceBranch(array $data): array
    {
        if (! self::isSuperAdmin()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        return $data;
    }

    /** Server-side publish rule (see PublishGuard). */
    public static function guardPublishing(array $data, ?TeamMember $record = null): array
    {
        return PublishGuard::apply($data, $record, ['name', 'role_title', 'photo_path', 'is_full_time', 'is_featured']);
    }

    private static function isSuperAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('Branch')
                    ->placeholder('All churches')
                    ->helperText('Leave empty to show this person for all churches.')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn () => self::isSuperAdmin()),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('role_title')->label('Role / title')->maxLength(255),
                FileUpload::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->directory('team')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096)
                    ->helperText('JPG, PNG or WebP, up to 4 MB. On edit, leave empty to keep the current photo.'),
                Toggle::make('is_full_time')->label('Full-time'),
                Toggle::make('is_featured')->label('Featured on homepage'),
                TextInput::make('sort_order')->numeric()->minValue(0)->default(0),
                Toggle::make('is_published')
                    ->label('Published')
                    ->helperText('Visible on the public homepage. Only a super-admin can publish.')
                    ->default(true)
                    ->visible(fn () => self::isSuperAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->state(fn (TeamMember $record) => $record->photo_url ? url($record->photo_url) : null)
                    ->circular(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('role_title')->label('Role')->searchable()->placeholder('-'),
                TextColumn::make('branch.name')->label('Branch')->placeholder('All churches')->sortable(),
                IconColumn::make('is_full_time')->label('Full-time')->boolean(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                IconColumn::make('is_published')->label('Published')->boolean(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->defaultSort('sort_order')
            // Reordering writes to every listed row, including global ones, so it is super-admin only.
            ->reorderable('sort_order', fn () => self::isSuperAdmin())
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => self::isSuperAdmin()),
                TernaryFilter::make('is_published')->label('Published'),
                Filter::make('pending_approval')
                    ->label('Pending approval')
                    ->query(fn (Builder $query) => $query->where('team_members.is_published', false))
                    ->visible(fn () => self::isSuperAdmin()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamMembers::route('/'),
            'create' => CreateTeamMember::route('/create'),
            'edit' => EditTeamMember::route('/{record}/edit'),
        ];
    }
}
