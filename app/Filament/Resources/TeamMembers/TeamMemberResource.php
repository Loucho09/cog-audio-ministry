<?php

namespace App\Filament\Resources\TeamMembers;

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Filament\Support\ApprovalNotice;
use App\Filament\Support\PublishStatus;
use App\Models\Branch;
use App\Models\TeamMember;
use App\Support\PublishGuard;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
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
            ->columns(3)
            ->components([
                ApprovalNotice::note(),
                Group::make([
                    Section::make('Profile')
                        ->description('Who they are and how they look on the site.')
                        ->icon(Heroicon::OutlinedIdentification)
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon(Heroicon::OutlinedUser),
                            TextInput::make('role_title')
                                ->label('Role / title')
                                ->maxLength(255)
                                ->prefixIcon(Heroicon::OutlinedBriefcase),
                            FileUpload::make('photo_path')
                                ->label('Photo')
                                ->columnSpanFull()
                                ->disk('public')
                                ->directory('team')
                                ->image()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->imageResizeMode('cover')
                                ->imageResizeTargetWidth('400')
                                ->imageResizeTargetHeight('400')
                                ->maxSize(4096)
                                ->helperText('JPG, PNG or WebP, up to 4 MB. On edit, leave empty to keep the current photo.'),
                        ]),
                    Section::make('Display')
                        ->description('How this person appears in the team section.')
                        ->icon(Heroicon::OutlinedSparkles)
                        ->columns(2)
                        ->schema([
                            Toggle::make('is_full_time')
                                ->label('Full-time')
                                ->helperText('Full-time members are listed first.'),
                            Toggle::make('is_featured')
                                ->label('Featured on homepage')
                                ->helperText('Shown in Meet the Team when that section is switched on.'),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefixIcon(Heroicon::OutlinedArrowsUpDown),
                        ]),
                    Section::make('Access and publishing')
                        ->description('Which church this person belongs to, and whether the public can see them.')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->columns(2)
                        ->visible(fn () => self::isSuperAdmin())
                        ->schema([
                            Select::make('branch_id')
                                ->label('Branch')
                                ->placeholder('All churches')
                                ->helperText('Leave empty to show this person for all churches.')
                                ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->prefixIcon(Heroicon::OutlinedBuildingLibrary)
                                ->visible(fn () => self::isSuperAdmin()),
                            Toggle::make('is_published')
                                ->label('Published')
                                ->helperText('Visible on the public homepage. Only a super-admin can publish.')
                                ->default(true)
                                ->visible(fn () => self::isSuperAdmin()),
                        ]),
                ])->columnSpan(fn (string $operation) => $operation === 'edit' ? ['default' => 3, 'lg' => 2] : 'full'),
                self::summary(),
            ]);
    }

    /** Read-only side card on the edit page. */
    protected static function summary(): Section
    {
        return Section::make('Summary')
            ->icon(Heroicon::OutlinedUserCircle)
            ->visible(fn (string $operation) => $operation === 'edit')
            ->columnSpan(['default' => 3, 'lg' => 1])
            ->columns(2)
            ->schema([
                ImageEntry::make('summary_photo')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->circular()
                    ->imageSize(72)
                    ->state(fn (TeamMember $record) => $record->photo_url ? url($record->photo_url) : null),
                TextEntry::make('summary_name')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->weight(FontWeight::Bold)
                    ->size(TextSize::Large)
                    ->state(fn (TeamMember $record) => $record->name),
                TextEntry::make('summary_role')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->color('gray')
                    ->state(fn (TeamMember $record) => $record->role_title ?: 'No role title yet'),
                TextEntry::make('summary_branch')
                    ->label('Branch')
                    ->columnSpanFull()
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->state(fn (TeamMember $record) => $record->branch?->name ?? 'All churches'),
                PublishStatus::entry(),
                TextEntry::make('summary_updated')
                    ->label('Last updated')
                    ->state(fn (TeamMember $record) => $record->updated_at)
                    ->since(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Split/Stack reflow each row to the screen width, so the table never scrolls sideways.
                Split::make([
                    ImageColumn::make('photo_path')
                        ->label('Photo')
                        ->state(fn (TeamMember $record) => $record->photo_url ? url($record->photo_url) : null)
                        ->circular()
                        ->imageSize(44)
                        ->grow(false),
                    Stack::make([
                        TextColumn::make('name')
                            ->weight(FontWeight::SemiBold)
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('role_title')
                            ->label('Role')
                            ->color('gray')
                            ->size(TextSize::Small)
                            ->searchable()
                            ->placeholder('No role title yet'),
                        TextColumn::make('global_note')
                            ->label('Access')
                            ->badge()
                            ->color('gray')
                            ->size(TextSize::ExtraSmall)
                            ->state(fn (TeamMember $record) => ApprovalNotice::globalBadgeState($record->branch_id)),
                    ]),
                    TextColumn::make('branch.name')
                        ->label('Branch')
                        ->icon(Heroicon::OutlinedBuildingLibrary)
                        ->iconColor('gray')
                        ->size(TextSize::Small)
                        ->placeholder('All churches')
                        ->visibleFrom('lg'),
                    Stack::make([
                        TextColumn::make('work_type')
                            ->label('Type')
                            ->badge()
                            ->state(fn (TeamMember $record) => $record->is_full_time ? 'Full-time' : 'Part-time')
                            ->color(fn (string $state) => $state === 'Full-time' ? 'primary' : 'gray')
                            ->icon(fn (string $state) => $state === 'Full-time' ? Heroicon::OutlinedClock : Heroicon::OutlinedCalendar),
                        TextColumn::make('featured_badge')
                            ->label('Featured')
                            ->badge()
                            ->state(fn (TeamMember $record) => $record->is_featured ? 'Featured' : null)
                            ->color('info')
                            ->icon(Heroicon::OutlinedStar),
                        PublishStatus::column(),
                    ])->space(1)->grow(false),
                ])->from('md'),
            ])
            ->defaultSort('sort_order')
            // Reordering writes to every listed row, including global ones, so it is super-admin only.
            ->reorderable('sort_order', fn () => self::isSuperAdmin())
            ->striped()
            ->searchPlaceholder('Search name or role')
            ->paginated([10, 25, 50, 100])
            ->emptyStateIcon(Heroicon::OutlinedUserGroup)
            ->emptyStateHeading('No team members yet')
            ->emptyStateDescription('People you add here can be shown in the Meet the Team section.')
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
            ->filtersTriggerAction(fn (Action $action) => $action->button()->label('Filters'))
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edit'),
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
