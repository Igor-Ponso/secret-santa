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
    protected $signature = 'groups:run-due-draws {--dry-run : Apenas mostra o que seria executado}';

    protected $description = 'Executa o sorteio automaticamente para grupos cuja data draw_at venceu e ainda não foram sorteados.';

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
            $this->info('Nenhum grupo elegível.');
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
                $this->line("[skip] Grupo #{$group->id} ({$group->name}) - participantes insuficientes ({$participantCount})");
                continue;
            }

            if ($dry) {
                $this->line("[dry-run] Sorteio seria executado para grupo #{$group->id} ({$group->name})");
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
                $this->line("[race] Grupo #{$group->id} já processado.");
                continue;
            }
            if (!$result['success']) {
                $summary['failed_draw']++;
                $this->error("[fail] Sorteio falhou para grupo #{$group->id} ({$group->name})");
                continue;
            }

            $summary['executed']++;

            // Notify participants
            $participants = $group->participants()->get();
            Notification::send($participants, new ParticipantDrawResultNotification($group));
            $summary['notified'] += $participants->count();
            $this->info("[ok] Grupo #{$group->id} ({$group->name}) sorteado. Notificados: {$participants->count()}");
        }

        $this->table(['Elegíveis', 'Executados', 'Sem participantes', 'Falhas', 'Notificações'], [
            [$summary['eligible'], $summary['executed'], $summary['skipped_participants'], $summary['failed_draw'], $summary['notified']]
        ]);

        return self::SUCCESS;
    }
}
