<?php

namespace EloquentWorks\Masquerade\Commands;

use EloquentWorks\Masquerade\Models\MasqueradeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Command to export masquerade audit logs to CSV or JSON.
 */
final class ExportMasqueradeLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'masquerade:export
        {--from= : Start date for the export}
        {--to= : End date for the export}
        {--format=csv : Export format: csv or json}
        {--path= : Output path. Defaults to storage/app/masquerade-logs-*}
        {--uuid= : Export a single masquerade UUID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export masquerade audit logs to CSV or JSON.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = strtolower((string) $this->option('format'));

        // Validate the format option
        if (! in_array($format, ['csv', 'json'], true)) {
            $this->components->error('The --format option must be csv or json.');

            return self::FAILURE;
        }

        // Get the configured masquerade log model class
        $modelClass = config('masquerade.logging.model', MasqueradeLog::class);

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            $this->components->error('The configured masquerade log model does not exist.');

            return self::FAILURE;
        }

        /** @var class-string<MasqueradeLog> $modelClass */
        $query = $modelClass::query()->oldest('created_at');

        $from = $this->option('from');
        $to = $this->option('to');
        $uuid = $this->option('uuid');

        // Apply filters based on the provided options
        if (is_string($from) && $from !== '') {
            $query->where('created_at', '>=', Carbon::parse($from));
        }

        // Apply the 'to' filter if provided
        if (is_string($to) && $to !== '') {
            $query->where('created_at', '<=', Carbon::parse($to));
        }

        // Apply the 'uuid' filter if provided
        if (is_string($uuid) && $uuid !== '') {
            $query->where('masquerade_uuid', $uuid);
        }

        // Fetch the logs and transform them into an array suitable for export
        $rows = $query->get()->map(fn (MasqueradeLog $log): array => $log->toExportArray())->values()->all();

        // Determine the output path for the export file
        $path = $this->option('path');
        $path = is_string($path) && $path !== ''
            ? $path
            : storage_path('app/masquerade-logs-'.now()->format('Ymd-His').'.'.$format);

        // Ensure the export directory exists or create it
        $directory = dirname($path);

        // Ensure the export directory exists or create it
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create export directory [{$directory}].");
        }

        // Export the logs in the specified format
        if ($format === 'json') {
            file_put_contents($path, json_encode($rows, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $handle = fopen($path, 'w');

            // Check if the file handle was successfully created
            if ($handle === false) {
                throw new RuntimeException("Unable to open export path [{$path}].");
            }

            // Determine the CSV headers based on the first row of data or use default headers if no rows exist
            $headers = array_keys($rows[0] ?? [
                'id' => null,
                'masquerade_uuid' => null,
                'action' => null,
                'guard' => null,
                'category' => null,
                'ability' => null,
                'ended_reason' => null,
                'extension_count' => null,
                'risk_score' => null,
                'risk_flags' => null,
                'impersonator_type' => null,
                'impersonator_id' => null,
                'target_type' => null,
                'target_id' => null,
                'reason' => null,
                'ip_address' => null,
                'user_agent' => null,
                'metadata' => null,
                'started_at' => null,
                'ended_at' => null,
                'created_at' => null,
            ]);

            // Write the CSV headers
            fputcsv($handle, $headers);

            // Write each row of data to the CSV file
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }

        // Provide feedback to the user about the export operation
        $this->components->success('Exported '.count($rows)." masquerade log(s) to {$path}.");

        return self::SUCCESS;
    }
}
