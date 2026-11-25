<?php

namespace App\Filament\Resources\JoinApplications;

use App\Actions\ApproveJoinApplication;
use App\Actions\RejectJoinApplication;
use App\Exceptions\JoinApplicationReviewException;
use App\Filament\Resources\JoinApplications\Pages\ListJoinApplications;
use App\Filament\Resources\JoinApplications\Pages\ViewJoinApplication;
use App\Models\Branch;
use App\Models\JoinApplication;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JoinApplicationResource extends Resource
{
    protected static ?string $model = JoinApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $navigationLabel = 'Join applications';

    protected static ?string $modelLabel = 'join application';

    protected static ?int $navigationSort = 20;

    /** Scoped in the query so list and view-by-URL are both branch limited (other branch = 404). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->visibleTo(auth()->user())
            ->with('branch');
    }

    protected static bool $isGloballySearchable = false;

    protected static function isSuperAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    /** Applications come only from the public form and are never edited or deleted. Gate::before would pass super-admin, so these do not use the policy. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /** Live count (no cache), same scope as the list. */
    public static function getNavigationBadge(): ?string
    {
        if (auth()->user() === null) {
            return null;
        }

        $count = static::getEloquentQuery()->pending()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function statusColor(?string $state): string
    {
        return match ($state) {
            JoinApplication::STATUS_APPROVED => 'success',
            JoinApplication::STATUS_REJECTED => 'danger',
            default => 'warning',
        };
    }

    // visible() is only UI; the service classes re-authorize server-side on every call.
    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Approve this application?')
            ->modalDescription('A member account is created and a set-password email is sent to the applicant.')
            ->modalSubmitActionLabel('Approve')
            ->visible(fn (JoinApplication $record) => $record->isPending() && (auth()->user()?->can('approve', $record) ?? false))
            ->action(function (JoinApplication $record) {
                self::review(fn () => app(ApproveJoinApplication::class)->handle($record, auth()->user()), 'Application approved', 'The set-password email is on its way.');
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reject this application?')
            ->modalDescription('The applicant is not emailed.')
            ->modalSubmitActionLabel('Reject')
            ->visible(fn (JoinApplication $record) => $record->isPending() && (auth()->user()?->can('reject', $record) ?? false))
            ->action(function (JoinApplication $record) {
                self::review(fn () => app(RejectJoinApplication::class)->handle($record, auth()->user()), 'Application rejected');
            });
    }

    private static function review(callable $run, string $successTitle, ?string $body = null): void
    {
        try {
            $run();
        } catch (JoinApplicationReviewException $e) {
            Notification::make()->danger()->title('Could not complete')->body($e->getMessage())->send();

            return;
        } catch (AuthorizationException) {
            Notification::make()->danger()->title('Not allowed')->send();

            return;
        }

        Notification::make()->success()->title($successTitle)->body($body)->send();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Applicant')
                ->icon(Heroicon::OutlinedUser)
                ->columns(2)
                ->schema([
                    TextEntry::make('full_name')->label('Full name')->weight(FontWeight::SemiBold),
                    TextEntry::make('age'),
                    TextEntry::make('contact_number')->label('Contact number')->copyable(),
                    TextEntry::make('email')->copyable(),
                    TextEntry::make('facebook_url')
                        ->label('Facebook')
                        ->placeholder('Not provided')
                        ->url(
                            fn (JoinApplication $record) => str_starts_with((string) $record->facebook_url, 'https://')
                                ? $record->facebook_url
                                : null,
                            shouldOpenInNewTab: true,
                        )
                        ->extraAttributes(['rel' => 'noopener noreferrer nofollow']),
                    TextEntry::make('branch.name')->label('Branch'),
                ]),
            Section::make('Application')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->schema([
                    TextEntry::make('interests')->badge(),
                    TextEntry::make('experience')->columnSpanFull(),
                    TextEntry::make('availability')->columnSpanFull(),
                    TextEntry::make('consented_at')->label('Consent given')->dateTime('M j, Y g:i A'),
                    TextEntry::make('created_at')->label('Submitted')->dateTime('M j, Y g:i A'),
                ]),
            Section::make('Review')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->columns(3)
                ->schema([
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Str::headline($state))
                        ->color(fn (string $state) => self::statusColor($state)),
                    TextEntry::make('reviewer.name')
                        ->label('Reviewed by')
                        ->visible(fn (JoinApplication $record) => $record->reviewed_by !== null),
                    TextEntry::make('reviewed_at')
                        ->label('Reviewed at')
                        ->dateTime('M j, Y g:i A')
                        ->visible(fn (JoinApplication $record) => $record->reviewed_at !== null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['full_name', 'email']),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->iconColor('gray'),
                TextColumn::make('interests')->badge()->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Str::headline($state))
                    ->color(fn (string $state) => self::statusColor($state)),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->dateTimeTooltip('M j, Y g:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder('Search name or email')
            ->emptyStateIcon(Heroicon::OutlinedInboxArrowDown)
            ->emptyStateHeading('No applications here')
            ->emptyStateDescription('New join applications will show up in this list.')
            ->filters([
                // Review queue first: pending by default; clear the filter to see history.
                SelectFilter::make('status')
                    ->options([
                        JoinApplication::STATUS_PENDING => 'Pending',
                        JoinApplication::STATUS_APPROVED => 'Approved',
                        JoinApplication::STATUS_REJECTED => 'Rejected',
                    ])
                    ->default(JoinApplication::STATUS_PENDING),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => self::isSuperAdmin()),
            ])
            ->recordActions([
                ViewAction::make(),
                self::approveAction(),
                self::rejectAction(),
            ])
            ->recordUrl(fn (JoinApplication $record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJoinApplications::route('/'),
            'view' => ViewJoinApplication::route('/{record}'),
        ];
    }
}
