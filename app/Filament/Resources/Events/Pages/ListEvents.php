<?php

namespace App\Filament\Resources\Events\Pages;

use App\Exceptions\RosterRuleViolation;
use App\Filament\Resources\Events\EventResource;
use App\Models\Branch;
use App\Services\EventGenerator;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate events')
                ->icon(Heroicon::OutlinedSparkles)
                ->modalDescription('Creates events from the active service schedule. Dates already covered are skipped. Up to 3 months at a time.')
                ->schema([
                    Select::make('branch_id')
                        ->label('Branch')
                        ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->visible(fn () => EventResource::isSuperAdmin()),
                    DatePicker::make('from')->required()->native(false)->minDate(fn () => today(config('app.timezone'))),
                    DatePicker::make('to')->required()->native(false)->minDate(fn () => today(config('app.timezone')))->afterOrEqual('from'),
                ])
                ->action(function (array $data): void {
                    $branchId = EventResource::isSuperAdmin() ? $data['branch_id'] : auth()->user()->branch_id;
                    $branch = Branch::query()->findOrFail($branchId);
                    $tz = config('app.timezone');

                    try {
                        $count = app(EventGenerator::class)->generate(
                            $branch,
                            CarbonImmutable::parse($data['from'], $tz),
                            CarbonImmutable::parse($data['to'], $tz),
                        );
                    } catch (RosterRuleViolation $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title("{$count} events created")->send();
                }),
            CreateAction::make(),
        ];
    }
}
