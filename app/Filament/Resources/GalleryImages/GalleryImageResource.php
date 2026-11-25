<?php

namespace App\Filament\Resources\GalleryImages;

use App\Filament\Resources\GalleryImages\Pages\CreateGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\EditGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\ListGalleryImages;
use App\Filament\Support\ApprovalNotice;
use App\Filament\Support\PublishStatus;
use App\Models\Branch;
use App\Models\GalleryImage;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class GalleryImageResource extends Resource
{
    protected static ?string $model = GalleryImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Gallery';

    protected static ?string $modelLabel = 'gallery image';

    /** Staff see their own branch rows plus global rows (null branch_id), matching the policy view(). */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        if ($user === null || self::isSuperAdmin()) {
            return $query->visibleTo($user);
        }

        return $query->where(fn (Builder $q) => $q
            ->whereNull('gallery_images.branch_id')
            ->orWhere('gallery_images.branch_id', $user->branch_id));
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
    public static function guardPublishing(array $data, ?GalleryImage $record = null): array
    {
        return PublishGuard::apply($data, $record, ['path', 'alt_text', 'title']);
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
                    Section::make('Image')
                        ->description('Pick a clear, well-lit photo.')
                        ->icon(Heroicon::OutlinedPhoto)
                        ->schema([
                            FileUpload::make('path')
                                ->label('Image')
                                ->disk('public')
                                ->directory('gallery')
                                ->image()
                                ->imagePreviewHeight('220')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(4096)
                                ->helperText('JPG, PNG or WebP, up to 4 MB. On edit, leave empty to keep the current image.')
                                ->required(fn (string $operation) => $operation === 'create'),
                        ]),
                    Section::make('Details')
                        ->description('A short title and a description for people using screen readers.')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->columns(2)
                        ->schema([
                            TextInput::make('title')
                                ->helperText('Optional caption shown under the photo.')
                                ->maxLength(255)
                                ->prefixIcon(Heroicon::OutlinedTag),
                            TextInput::make('alt_text')
                                ->label('Alt text')
                                ->helperText('Describe the photo for people using screen readers.')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon(Heroicon::OutlinedChatBubbleBottomCenterText),
                        ]),
                    Section::make('Publishing')
                        ->description('Which church this photo belongs to, its order, and whether the public can see it.')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->columns(2)
                        ->schema([
                            Select::make('branch_id')
                                ->label('Branch')
                                ->placeholder('All churches')
                                ->helperText('All photos show on the shared homepage. Branch only controls who can manage it. Leave empty for a photo any branch can see.')
                                ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->prefixIcon(Heroicon::OutlinedBuildingLibrary)
                                ->visible(fn () => self::isSuperAdmin()),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefixIcon(Heroicon::OutlinedArrowsUpDown),
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
            ->icon(Heroicon::OutlinedPhoto)
            ->visible(fn (string $operation) => $operation === 'edit')
            ->columnSpan(['default' => 3, 'lg' => 1])
            ->columns(2)
            ->schema([
                ImageEntry::make('summary_image')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->imageHeight(160)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->state(fn (GalleryImage $record) => $record->url ? url($record->url) : null),
                TextEntry::make('summary_title')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->weight(FontWeight::Bold)
                    ->size(TextSize::Large)
                    ->state(fn (GalleryImage $record) => $record->title ?: 'Untitled'),
                TextEntry::make('summary_alt')
                    ->columnSpanFull()
                    ->hiddenLabel()
                    ->color('gray')
                    ->state(fn (GalleryImage $record) => $record->alt_text),
                TextEntry::make('summary_branch')
                    ->label('Branch')
                    ->columnSpanFull()
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->state(fn (GalleryImage $record) => $record->branch?->name ?? 'All churches'),
                PublishStatus::entry(),
                TextEntry::make('summary_updated')
                    ->label('Last updated')
                    ->state(fn (GalleryImage $record) => $record->updated_at)
                    ->since(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // A real table (not Split/Stack) so every column lines up under its header,
            // whatever actions a row has. Less important columns hide on small screens.
            ->columns([
                ImageColumn::make('path')
                    ->label('Photo')
                    ->state(fn (GalleryImage $record) => $record->url ? url($record->url) : null)
                    ->square()
                    ->imageSize(56)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                TextColumn::make('title')
                    ->label('Title')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (GalleryImage $record) => filled($record->alt_text) ? Str::limit($record->alt_text, 60) : null)
                    ->searchable(['title', 'alt_text'])
                    ->sortable()
                    ->placeholder('Untitled')
                    ->wrap(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->iconColor('gray')
                    ->placeholder('All churches')
                    ->visibleFrom('md'),
                PublishStatus::column()->visibleFrom('sm'),
            ])
            ->defaultSort('sort_order')
            // Reordering writes to every listed row, including global ones, so it is super-admin only.
            ->reorderable('sort_order', fn () => self::isSuperAdmin())
            ->striped()
            ->searchPlaceholder('Search title or description')
            ->paginated([10, 25, 50, 100])
            ->emptyStateIcon(Heroicon::OutlinedPhoto)
            ->emptyStateHeading('No photos yet')
            ->emptyStateDescription('Add a photo and it can show in the homepage gallery once approved.')
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => self::isSuperAdmin()),
                TernaryFilter::make('is_published')->label('Published'),
                Filter::make('pending_approval')
                    ->label('Pending approval')
                    ->query(fn (Builder $query) => $query->where('gallery_images.is_published', false))
                    ->visible(fn () => self::isSuperAdmin()),
            ])
            ->filtersTriggerAction(fn (Action $action) => $action->button()->label('Filters'))
            ->recordActions([
                // Greyed out (not hidden) on view-only rows so columns stay aligned.
                EditAction::make()->iconButton()->tooltip('Edit')->authorizationTooltip(),
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
            'index' => ListGalleryImages::route('/'),
            'create' => CreateGalleryImage::route('/create'),
            'edit' => EditGalleryImage::route('/{record}/edit'),
        ];
    }
}
