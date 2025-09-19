<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Notifications\ParticipantDrawResultNotification;
use App\Services\DrawService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RunDueDrawsCommand extends Command
{
    protected $signature = 'groups:run-due-draws {--dry-run : placeholder}';

    protected $description = 'placeholder';

    public function __construct()
    {
        parent::__construct();
        // Late binding of localized signature/description (Laravel doesn't localize these by default)
        $this->signature = 'groups:run-due-draws {--dry-run : ' . __('messages.console.run_due_draws.option_dry') . '}';
        $this->description = __('messages.console.run_due_draws.description');
    }

    public function handle(DrawService $drawService): int
    {
        $now = CarbonImmutable::now();
        $dry = $this->option('dry-run');

        $query = Group::query()
            ->whereNotNull('draw_at')
            ->where('draw_at', '<=', $now)
            ->where(function ($q) {
                $q->whereNull('has_draw')->orWhere('has_draw', false);
            });

        $groups = $query->get();
        if ($groups->isEmpty()) {
            $this->info(__('messages.console.run_due_draws.none_eligible'));
            return self::SUCCESS;
        }

        $summary = [
            'eligible' => $groups->count(),
            'executed' => 0,
            'skipped_participants' => 0,
            'failed_draw' => 0,
            'notified' => 0,
        ];

        foreach ($groups as $group) {
            // Recheck participants count
            $participantCount = $group->participants()->count();
            if ($participantCount < 2) {
                $summary['skipped_participants']++;
                $this->line(strtr(__('messages.console.run_due_draws.skip_insufficient'), [
                    ':id' => $group->id,
                    ':name' => $group->name,
                    ':count' => $participantCount,
                ]));
                continue;
            }

            if ($dry) {
                $this->line(strtr(__('messages.console.run_due_draws.dry_run'), [
                    ':id' => $group->id,
                    ':name' => $group->name,
                ]));
                continue;
            }

            // Protect against race: refresh + select for update inside transaction
            $result = DB::transaction(function () use ($group, $drawService) {
                $g = Group::where('id', $group->id)->lockForUpdate()->first();
                if (!$g || $g->has_draw) {
                    return ['skipped' => true, 'success' => false];
                }
                $run = $drawService->run($g);
                if (!($run['success'] ?? false)) {
                    return ['skipped' => false, 'success' => false];
                }
                return ['skipped' => false, 'success' => true, 'group' => $g];
            });

            if ($result['skipped']) {
                $this->line(strtr(__('messages.console.run_due_draws.race'), [
                    ':id' => $group->id,
                    ':name' => $group->name,
                ]));
                continue;
            }
            if (!$result['success']) {
                $summary['failed_draw']++;
                $this->error(strtr(__('messages.console.run_due_draws.fail'), [
                    ':id' => $group->id,
                    ':name' => $group->name,
                ]));
                continue;
            }

            $summary['executed']++;

            // Notify participants
            $participants = $group->participants()->get();
            Notification::send($participants, new ParticipantDrawResultNotification($group));
            $summary['notified'] += $participants->count();
            $this->info(strtr(__('messages.console.run_due_draws.ok'), [
                ':id' => $group->id,
                ':name' => $group->name,
                ':notified' => $participants->count(),
            ]));
        }

        $headers = __('messages.console.run_due_draws.table_headers');
        if (!is_array($headers)) {
            $headers = ['Eligible', 'Executed', 'Insufficient participants', 'Fails', 'Notified'];
        }
        $this->table($headers, [
            [
                $summary['eligible'],
                $summary['executed'],
                $summary['skipped_participants'],
                $summary['failed_draw'],
                $summary['notified'],
            ]
        ]);

        return self::SUCCESS;
    }
}
