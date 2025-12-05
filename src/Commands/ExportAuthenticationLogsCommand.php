<?php

namespace Rappasoft\LaravelAuthenticationLog\Commands;

use Illuminate\Console\Command;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class ExportAuthenticationLogsCommand extends Command
{
    public $signature = 'authentication-log:export 
                        {--format=csv : Export format (csv or json)}
                        {--user= : Filter by user ID}
                        {--days= : Export logs from last N days}
                        {--output= : Output file path}';

    public $description = 'Export authentication logs to CSV or JSON';

    public function handle(): int
    {
        $format = $this->option('format');
        $days = $this->option('days');
        $userId = $this->option('user');
        $output = $this->option('output') ?? "authentication-logs-{$format}-" . now()->format('Y-m-d-His') . ".{$format}";

        $query = AuthenticationLog::query();

        if ($days) {
            $query->recent((int) $days);
        }

        if ($userId) {
            $query->where('authenticatable_id', $userId);
        }

        $logs = $query->with('authenticatable')->get();

        if ($logs->isEmpty()) {
            $this->warn('No logs found to export.');

            return self::FAILURE;
        }

        $this->info("Exporting {$logs->count()} logs...");

        if ($format === 'csv') {
            $this->exportCsv($logs, $output);
        } else {
            $this->exportJson($logs, $output);
        }

        $this->info("Exported to: {$output}");

        return self::SUCCESS;
    }

    protected function exportCsv($logs, string $output): void
    {
        $file = fopen($output, 'w');

        // Headers
        fputcsv($file, [
            'ID',
            'User Type',
            'User ID',
            'User Email',
            'IP Address',
            'User Agent',
            'Device ID',
            'Device Name',
            'Is Trusted',
            'Login At',
            'Login Successful',
            'Logout At',
            'Is Suspicious',
            'Suspicious Reason',
            'Location',
        ]);

        foreach ($logs as $log) {
            fputcsv($file, [
                $log->id,
                $log->authenticatable_type,
                $log->authenticatable_id,
                $log->authenticatable->email ?? 'N/A',
                $log->ip_address,
                $log->user_agent,
                $log->device_id,
                $log->device_name,
                $log->is_trusted ? 'Yes' : 'No',
                $log->login_at?->toDateTimeString(),
                $log->login_successful ? 'Yes' : 'No',
                $log->logout_at?->toDateTimeString(),
                $log->is_suspicious ? 'Yes' : 'No',
                $log->suspicious_reason,
                $log->location ? json_encode($log->location) : null,
            ]);
        }

        fclose($file);
    }

    protected function exportJson($logs, string $output): void
    {
        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user' => [
                    'type' => $log->authenticatable_type,
                    'id' => $log->authenticatable_id,
                    'email' => $log->authenticatable->email ?? null,
                ],
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'device' => [
                    'id' => $log->device_id,
                    'name' => $log->device_name,
                    'is_trusted' => $log->is_trusted,
                ],
                'login_at' => $log->login_at?->toIso8601String(),
                'login_successful' => $log->login_successful,
                'logout_at' => $log->logout_at?->toIso8601String(),
                'is_suspicious' => $log->is_suspicious,
                'suspicious_reason' => $log->suspicious_reason,
                'location' => $log->location,
            ];
        })->toArray();

        file_put_contents($output, json_encode($data, JSON_PRETTY_PRINT));
    }
}
