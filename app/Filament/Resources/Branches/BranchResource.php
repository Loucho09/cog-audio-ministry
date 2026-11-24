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
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
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
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?Model $record, ?string $state) {
                        if (! $record && blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')->required()->maxLength(255)->alphaDash()->unique(ignoreRecord: true),
                TextInput::make('address')->maxLength(255),
                TextInput::make('contact_number')->maxLength(255),
                TextInput::make('email')->email()->maxLength(255),
                TextInput::make('facebook_url')->label('Facebook URL')->url()->maxLength(255),
                Toggle::make('is_main')
                    ->label('Main branch')
                    ->rules([
                        fn (?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            if ($record?->is_main && ! $value
                                && ! Branch::query()->whereKeyNot($record->getKey())->where('is_main', true)->exists()) {
                                $fail('There must be one main branch. Mark another branch as main instead.');
                            }
                        },
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug'),
                TextColumn::make('email')->placeholder('-'),
                IconColumn::make('is_main')->label('Main')->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
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
