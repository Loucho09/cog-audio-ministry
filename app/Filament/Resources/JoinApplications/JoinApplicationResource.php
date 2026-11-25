<?php

namespace App\Filament\Resources\JoinApplications;

use App\Actions\ApproveJoinApplication;
use App\Actions\RejectJoinApplication;
use App\Exceptions\JoinApplicationReviewException;
use App\Filament\Resources\JoinApplications\Pages\ListJoinApplications;
use App\Filament\Resources\JoinApplications\Pages\ViewJoinApplication;
use App\Filament\Resources\Users\UserResource;
use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
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

    protected static ?string $breadcrumb = 'Join applications';

    protected static ?string $recordTitleAttribute = 'full_name';

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

    public static function statusIcon(?string $state): Heroicon
    {
        return match ($state) {
            JoinApplication::STATUS_APPROVED => Heroicon::OutlinedCheckCircle,
            JoinApplication::STATUS_REJECTED => Heroicon::OutlinedXCircle,
            default => Heroicon::OutlinedClock,
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
            ->modalIcon(Heroicon::OutlinedUserPlus)
            ->modalIconColor('success')
            ->modalHeading(fn (JoinApplication $record) => "Approve {$record->full_name}?")
            ->modalDescription(fn (JoinApplication $record) => "This creates a member account for {$record->full_name} in {$record->branch?->name} and emails them a link to set their password. The link works for 3 days.")
            ->modalSubmitActionLabel('Approve and send email')
            ->visible(fn (JoinApplication $record) => $record->isPending() && (auth()->user()?->can('approve', $record) ?? false))
            ->action(function (JoinApplication $record) {
                self::review(
                    fn () => app(ApproveJoinApplication::class)->handle($record, auth()->user()),
                    "{$record->full_name} approved",
                    "A set-password email is on its way to {$record->email}.",
                    "Couldn't approve",
                );
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedXCircle)
            ->modalIconColor('danger')
            ->modalHeading(fn (JoinApplication $record) => "Reject {$record->full_name}?")
            ->modalDescription(fn (JoinApplication $record) => "{$record->full_name} will not be emailed. This can't be undone.")
            ->modalSubmitActionLabel('Reject')
            ->visible(fn (JoinApplication $record) => $record->isPending() && (auth()->user()?->can('reject', $record) ?? false))
            ->action(function (JoinApplication $record) {
                self::review(
                    fn () => app(RejectJoinApplication::class)->handle($record, auth()->user()),
                    "{$record->full_name}'s application was rejected",
                    null,
                    "Couldn't reject",
                );
            });
    }

    private static function review(callable $run, string $successTitle, ?string $body, string $failTitle): void
    {
        try {
            $run();
        } catch (JoinApplicationReviewException $e) {
            Notification::make()->danger()->title($failTitle)->body($e->getMessage())->send();

            return;
        } catch (AuthorizationException) {
            Notification::make()->danger()->title("You're not allowed to do this")->send();

            return;
        }

        Notification::make()->success()->title($successTitle)->body($body)->send();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Contact details')
                        ->description('How to reach the applicant.')
                        ->icon(Heroicon::OutlinedIdentification)
                        ->columns(2)
                        ->schema([
                            TextEntry::make('contact_number')
                                ->label('Contact number')
                                ->icon(Heroicon::OutlinedPhone)
                                ->copyable()
                                ->copyMessage('Number copied'),
                            TextEntry::make('email')
                                ->icon(Heroicon::OutlinedEnvelope)
                                ->copyable()
                                ->copyMessage('Email copied'),
                            TextEntry::make('facebook_url')
                                ->label('Facebook')
                                ->icon(Heroicon::OutlinedLink)
                                ->placeholder('Not provided')
                                ->url(
                                    fn (JoinApplication $record) => str_starts_with((string) $record->facebook_url, 'https://')
                                        ? $record->facebook_url
                                        : null,
                                    shouldOpenInNewTab: true,
                                )
                                ->extraAttributes(['rel' => 'noopener noreferrer nofollow']),
                            TextEntry::make('age')
                                ->icon(Heroicon::OutlinedCake),
                        ]),
                    Section::make('Application')
                        ->description('When they applied and agreed to be contacted.')
                        ->icon(Heroicon::OutlinedClipboardDocumentList)
                        ->columns(2)
                        ->schema([
                            TextEntry::make('created_at')
                                ->label('Submitted')
                                ->dateTime('M j, Y g:i A')
                                ->tooltip(fn (JoinApplication $record) => $record->created_at?->diffForHumans()),
                            TextEntry::make('consented_at')
                                ->label('Consent given')
                                ->dateTime('M j, Y g:i A'),
                        ]),
                    Section::make('Review')
                        ->description('What was decided, and by whom.')
                        ->icon(Heroicon::OutlinedCheckBadge)
                        ->columns(2)
                        ->schema([
                            TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => Str::headline($state))
                                ->color(fn (string $state) => self::statusColor($state))
                                ->icon(fn (string $state) => self::statusIcon($state)),
                            TextEntry::make('waiting')
                                ->hiddenLabel()
                                ->color('gray')
                                ->state('Waiting for review')
                                ->visible(fn (JoinApplication $record) => $record->isPending()),
                            TextEntry::make('reviewer.name')
                                ->label('Reviewed by')
                                ->icon(Heroicon::OutlinedUser)
                                ->visible(fn (JoinApplication $record) => $record->reviewed_by !== null),
                            TextEntry::make('reviewed_at')
                                ->label('Reviewed at')
                                ->dateTime('M j, Y g:i A')
                                ->visible(fn (JoinApplication $record) => $record->reviewed_at !== null),
                            TextEntry::make('member_account')
                                ->label('Member account')
                                ->icon(Heroicon::OutlinedUserCircle)
                                ->state(fn (JoinApplication $record) => $record->user?->name)
                                ->url(fn (JoinApplication $record) => $record->user && UserResource::canEdit($record->user)
                                    ? UserResource::getUrl('edit', ['record' => $record->user])
                                    : null)
                                ->visible(fn (JoinApplication $record) => $record->user_id !== null
                                    && $record->user !== null
                                    && (auth()->user()?->can('view', $record->user) ?? false)),
                        ]),
                ])->columnSpan(['default' => 3, 'lg' => 2]),
                self::applicantSummary(),
            ]);
    }

    /** Read-only side card on the view page: who this is at a glance. */
    protected static function applicantSummary(): Section
    {
        return Section::make('Applicant')
            ->icon(Heroicon::OutlinedUserCircle)
            ->columnSpan(['default' => 3, 'lg' => 1])
            ->schema([
                TextEntry::make('summary_name')
                    ->hiddenLabel()
                    ->weight(FontWeight::Bold)
                    ->size(TextSize::Large)
                    ->state(fn (JoinApplication $record) => $record->full_name),
                TextEntry::make('summary_email')
                    ->hiddenLabel()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->copyable()
                    ->state(fn (JoinApplication $record) => $record->email),
                TextEntry::make('summary_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (JoinApplication $record) => $record->status)
                    ->formatStateUsing(fn (string $state) => Str::headline($state))
                    ->color(fn (string $state) => self::statusColor($state))
                    ->icon(fn (string $state) => self::statusIcon($state)),
                TextEntry::make('summary_branch')
                    ->label('Branch')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->state(fn (JoinApplication $record) => $record->branch?->name ?? 'No branch'),
                TextEntry::make('summary_applied')
                    ->label('Applied')
                    ->state(fn (JoinApplication $record) => $record->created_at)
                    ->since()
                    ->dateTimeTooltip('M j, Y g:i A'),
            ]);
    }

    /** True when a user already uses the applicant's email (admins should reject, not approve). */
    public static function emailHasAccount(JoinApplication $record): bool
    {
        return $record->isPending() && User::query()->where('email', $record->email)->exists();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Split/Stack reflow each row to the screen width, so the table never scrolls sideways.
                Split::make([
                    Stack::make([
                        TextColumn::make('full_name')
                            ->label('Name')
                            ->weight(FontWeight::SemiBold)
                            ->searchable(['full_name', 'email']),
                        TextColumn::make('email')
                            ->label('Email')
                            ->color('gray')
                            ->size(TextSize::Small)
                            ->wrap(),
                        TextColumn::make('account_exists')
                            ->label('Account')
                            ->badge()
                            ->color('danger')
                            ->state(fn (JoinApplication $record) => self::emailHasAccount($record) ? 'Account exists' : null)
                            ->tooltip('This email already belongs to a user. Reject instead of approving.'),
                    ]),
                    Stack::make([
                        TextColumn::make('branch.name')
                            ->label('Branch')
                            ->icon(Heroicon::OutlinedBuildingLibrary)
                            ->iconColor('gray')
                            ->size(TextSize::Small),
                    ]),
                    Stack::make([
                        TextColumn::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => Str::headline($state))
                            ->color(fn (string $state) => self::statusColor($state))
                            ->icon(fn (string $state) => self::statusIcon($state)),
                        TextColumn::make('created_at')
                            ->label('Submitted')
                            ->since()
                            ->prefix('Submitted ')
                            ->dateTimeTooltip('M j, Y g:i A')
                            ->color('gray')
                            ->size(TextSize::Small)
                            ->sortable(),
                    ])->space(1),
                ])->from('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder('Search by name or email')
            ->emptyStateIcon(fn ($livewire) => match ($livewire->activeTab ?? null) {
                'pending' => Heroicon::OutlinedCheckCircle,
                'approved' => Heroicon::OutlinedUserPlus,
                default => Heroicon::OutlinedInboxArrowDown,
            })
            ->emptyStateHeading(fn ($livewire) => match ($livewire->activeTab ?? null) {
                'pending' => 'All caught up',
                'approved' => 'No approved applications yet',
                'rejected' => 'No rejected applications',
                default => 'No applications yet',
            })
            ->emptyStateDescription(fn ($livewire) => match ($livewire->activeTab ?? null) {
                'pending' => 'No applications are waiting for review.',
                'approved' => 'Applicants you approve will show up here.',
                'rejected' => 'Applications you reject will show up here.',
                default => 'New join applications will show up in this list.',
            })
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn () => self::isSuperAdmin()),
            ])
            ->filtersTriggerAction(fn (Action $action) => $action->button()->label('Filters'))
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('View'),
                ActionGroup::make([
                    self::approveAction(),
                    self::rejectAction(),
                ])->tooltip('Review'),
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
