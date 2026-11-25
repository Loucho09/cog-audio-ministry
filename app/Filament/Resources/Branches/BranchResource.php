<?php

namespace App\Filament\Resources\Branches;

use App\Filament\Resources\Branches\Pages\CreateBranch;
use App\Filament\Resources\Branches\Pages\EditBranch;
use App\Filament\Resources\Branches\Pages\ListBranches;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Models\TeamMember;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Branches';

    protected static ?string $modelLabel = 'branch';

    /** Staff only ever see their own branch; super-admin sees all. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user?->hasRole('super-admin') ? $query : $query->whereKey($user?->branch_id ?? 0);
    }

    /** Returns a reason why the branch cannot be deleted, or null when it can. */
    public static function deleteBlocker(Branch $branch): ?string
    {
        if ($branch->is_main) {
            return 'The main branch cannot be deleted. Make another branch the main one first.';
        }
        if ($branch->users()->exists()) {
            return "{$branch->name} still has users. Move or remove them first.";
        }
        if ($branch->services()->exists()) {
            return "{$branch->name} still has services. Remove them first.";
        }
        if ($branch->events()->exists()) {
            return "{$branch->name} still has events. Remove them first.";
        }
        // nullOnDelete would turn these into "all churches" records, so block instead.
        if (GalleryImage::where('branch_id', $branch->id)->exists()
            || TeamMember::where('branch_id', $branch->id)->exists()) {
            return "{$branch->name} still has gallery photos or team members. Move or remove them first.";
        }

        return null;
    }

    /** Saves a branch; a branch marked main becomes the only main branch (atomic). */
    public static function persist(?Branch $record, array $data): Branch
    {
        return DB::transaction(function () use ($record, $data) {
            $isMain = (bool) ($data['is_main'] ?? false);
            unset($data['is_main']);

            $branch = $record ?? new Branch;
            $branch->fill($data);
            $branch->is_main = $isMain;
            $branch->save();

            if ($isMain) {
                Branch::query()->whereKeyNot($branch->getKey())->where('is_main', true)->update(['is_main' => false]);
            }

            return $branch;
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Details')
                        ->description('The branch name and the short name used in its web address.')
                        ->icon(Heroicon::OutlinedBuildingOffice2)
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon(Heroicon::OutlinedBuildingLibrary)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, ?Model $record, ?string $state) {
                                    if (! $record && blank($get('slug'))) {
                                        $set('slug', Str::slug((string) $state));
                                    }
                                }),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->alphaDash()
                                ->unique(ignoreRecord: true)
                                ->prefixIcon(Heroicon::OutlinedLink),
                            Toggle::make('is_main')
                                ->label('Main branch')
                                ->columnSpanFull()
                                ->helperText('Only one branch can be the main one.')
                                ->rules([
                                    fn (?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                        if ($record?->is_main && ! $value
                                            && ! Branch::query()->whereKeyNot($record->getKey())->where('is_main', true)->exists()) {
                                            $fail('There must be one main branch. Mark another branch as main instead.');
                                        }
                                    },
                                ]),
                        ]),
                    Section::make('Contact')
                        ->description('Where people can find and reach this branch (all optional).')
                        ->icon(Heroicon::OutlinedPhone)
                        ->columns(2)
                        ->schema([
                            TextInput::make('address')
                                ->maxLength(255)
                                ->columnSpanFull()
                                ->prefixIcon(Heroicon::OutlinedMapPin),
                            TextInput::make('contact_number')
                                ->maxLength(255)
                                ->prefixIcon(Heroicon::OutlinedPhone),
                            TextInput::make('email')
                                ->email()
                                ->maxLength(255)
                                ->prefixIcon(Heroicon::OutlinedEnvelope),
                            TextInput::make('facebook_url')
                                ->label('Facebook URL')
                                ->url()
                                ->rule('starts_with:https://')
                                ->maxLength(255)
                                ->columnSpanFull()
                                ->prefixIcon(Heroicon::OutlinedGlobeAlt),
                        ]),
                ])->columnSpan(fn (string $operation) => $operation === 'edit' ? ['default' => 3, 'lg' => 2] : 'full'),
                self::summary(),
            ]);
    }

    /** Read-only side card on the edit page. */
    protected static function summary(): Section
    {
        return Section::make('Summary')
            ->icon(Heroicon::OutlinedBuildingOffice2)
            ->visible(fn (string $operation) => $operation === 'edit')
            ->columnSpan(['default' => 3, 'lg' => 1])
            ->columns(2)
            ->schema([
                TextEntry::make('summary_name')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->weight(FontWeight::Bold)
                    ->size(TextSize::Large)
                    ->state(fn (Branch $record) => $record->name),
                TextEntry::make('summary_main')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->badge()
                    ->color('primary')
                    ->icon(Heroicon::OutlinedStar)
                    ->state(fn (Branch $record) => $record->is_main ? 'Main branch' : null),
                TextEntry::make('summary_members')
                    ->label('Members')
                    ->icon(Heroicon::OutlinedUsers)
                    ->state(fn (Branch $record) => $record->users()->count()),
                TextEntry::make('summary_services')
                    ->label('Services')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->state(fn (Branch $record) => $record->services()->count()),
                TextEntry::make('summary_created')
                    ->label('Created')
                    ->state(fn (Branch $record) => $record->created_at)
                    ->date('M j, Y'),
                TextEntry::make('summary_updated')
                    ->label('Last updated')
                    ->state(fn (Branch $record) => $record->updated_at)
                    ->since(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['users', 'services']))
            ->columns([
                // Split/Stack reflow each row to the screen width, so the table never scrolls sideways.
                Split::make([
                    Stack::make([
                        TextColumn::make('name')
                            ->weight(FontWeight::SemiBold)
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('main_badge')
                            ->label('Main')
                            ->badge()
                            ->color('primary')
                            ->icon(Heroicon::OutlinedStar)
                            ->state(fn (Branch $record) => $record->is_main ? 'Main' : null),
                        TextColumn::make('slug')
                            ->color('gray')
                            ->size(TextSize::Small),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('address')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->iconColor('gray')
                            ->size(TextSize::Small)
                            ->placeholder('No address yet')
                            ->wrap(),
                        TextColumn::make('contact_number')
                            ->label('Contact')
                            ->icon(Heroicon::OutlinedPhone)
                            ->iconColor('gray')
                            ->size(TextSize::Small)
                            ->copyable()
                            ->copyMessage('Number copied'),
                        TextColumn::make('email')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->iconColor('gray')
                            ->size(TextSize::Small)
                            ->copyable()
                            ->copyMessage('Email copied'),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('users_count')
                            ->counts('users')
                            ->badge()
                            ->color('gray')
                            ->icon(Heroicon::OutlinedUsers)
                            ->formatStateUsing(fn ($state) => $state.' '.str('member')->plural((int) $state)),
                        TextColumn::make('services_count')
                            ->counts('services')
                            ->badge()
                            ->color('gray')
                            ->icon(Heroicon::OutlinedRectangleStack)
                            ->formatStateUsing(fn ($state) => $state.' '.str('service')->plural((int) $state)),
                    ])->space(1)->grow(false)->visibleFrom('lg'),
                ])->from('md'),
            ])
            ->defaultSort('name')
            ->striped()
            ->searchPlaceholder('Search branches')
            ->paginated([10, 25, 50, 100])
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice2)
            ->emptyStateHeading('No branches yet')
            ->emptyStateDescription('Add a branch and it can start receiving members and applications.')
            ->filtersTriggerAction(fn (Action $action) => $action->button()->label('Filters'))
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edit'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete')
                        ->before(function (DeleteBulkAction $action, $records) {
                            foreach ($records as $record) {
                                if ($reason = self::deleteBlocker($record)) {
                                    Notification::make()->danger()->title('Cannot delete')->body($reason)->send();
                                    $action->cancel();
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}
