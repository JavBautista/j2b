<?php

namespace App\Console\Commands;

use App\Models\CfdiTimbradoLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PurgeCfdiLogsCommand extends Command
{
    protected $signature = 'cfdi:purge-logs
                            {--days= : Override de retención (default: config cfdi-logging.retention_days)}
                            {--pretend : Solo mostrar cuántos rows se borrarían, sin ejecutar}';

    protected $description = 'Purga rows antiguos de cfdi_timbrado_logs según retención configurada';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('cfdi-logging.retention_days', 90));
        $cutoff = Carbon::now()->subDays($days);

        $count = CfdiTimbradoLog::where('created_at', '<', $cutoff)->count();

        $this->info("Retención: {$days} días (cutoff: {$cutoff->format('Y-m-d H:i:s')})");
        $this->info("Rows candidatos a purga: {$count}");

        if ($count === 0) {
            $this->info('Nada que purgar.');
            return self::SUCCESS;
        }

        if ($this->option('pretend')) {
            $this->warn('--pretend activo: no se borró nada.');
            return self::SUCCESS;
        }

        $deleted = CfdiTimbradoLog::where('created_at', '<', $cutoff)->delete();
        $this->info("Purgados {$deleted} rows.");

        return self::SUCCESS;
    }
}
