<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Mongo\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PruneAuditLogsCommand extends Command
{
    protected $signature = 'audit:prune {--days= : Override retention days}';

    protected $description = 'Prune old audit and activity log records.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('audit.retention_days', 180));
        $threshold = now()->subDays($days);

        AuditLog::query()->where('occurred_at', '<', $threshold)->delete();

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')->where('created_at', '<', $threshold)->delete();
        }

        $this->info("Audit logs older than {$days} days were pruned.");

        return self::SUCCESS;
    }
}
