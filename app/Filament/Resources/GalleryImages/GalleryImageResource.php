<?php

namespace App\Filament\Resources\GalleryImages;

use App\Filament\Resources\GalleryImages\Pages\CreateGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\EditGalleryImage;
use App\Filament\Resources\GalleryImages\Pages\ListGalleryImages;
use App\Models\Branch;
use App\Models\GalleryImage;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->helperText('Leave empty to show this image for all churches.')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn () => self::isSuperAdmin()),
                FileUpload::make('path')
                    ->label('Image')
                    ->disk('public')
                    ->directory('gallery')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096)
                    ->helperText('JPG, PNG or WebP, up to 4 MB. On edit, leave empty to keep the current image.')
                    ->required(fn (string $operation) => $operation === 'create'),
                TextInput::make('alt_text')
                    ->label('Alt text')
                    ->helperText('Describe the photo for people using screen readers.')
                    ->required()
                    ->maxLength(255),
                TextInput::make('title')->maxLength(255),
                TextInput::make('sort_order')->numeric()->minValue(0)->default(0),
                Toggle::make('is_published')->label('Published')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('Image')
                    ->state(fn (GalleryImage $record) => $record->url ? url($record->url) : null)
                    ->square(),
                TextColumn::make('title')->searchable()->sortable()->placeholder('-'),
                TextColumn::make('alt_text')->label('Alt text')->searchable()->limit(50),
                TextColumn::make('branch.name')->label('Branch')->placeholder('All churches')->sortable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->defaultSort('sort_order')
            // Reordering writes to every listed row, including global ones, so it is super-admin only.
            ->reorderable('sort_order', fn () => self::isSuperAdmin())
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
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
            'index' => ListGalleryImages::route('/'),
            'create' => CreateGalleryImage::route('/create'),
            'edit' => EditGalleryImage::route('/{record}/edit'),
        ];
    }
}
