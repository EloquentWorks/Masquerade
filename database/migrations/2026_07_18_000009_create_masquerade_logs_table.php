<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void Returns nothing.
     */
    public function up(): void
    {
        // Create the masquerade logs table with the specified columns and indexes.
        Schema::create(config('masquerade.logging.table_name', 'masquerade_logs'), function (Blueprint $table): void {
            // Define the columns for the masquerade logs table.
            $table->id();

            // Use UUID for the masquerade session identifier and index it for faster lookups.
            $table->uuid('masquerade_uuid')->index();

            // Store the action performed (e.g., 'start', 'stop') and index it for efficient querying.
            $table->string('action')->index();

            // Store the authentication guard used for the masquerade session, allowing null values and indexing it for faster lookups.
            $table->string('guard')->nullable()->index();

            // Define polymorphic relationships for the impersonator and target models, allowing null values.
            $table->nullableMorphs('impersonator');
            $table->nullableMorphs('target');

            // Store the reason for starting the masquerade session, allowing null values.
            $table->text('reason')->nullable();

            // Store the IP address of the user performing the masquerade action, allowing null values.
            $table->ipAddress('ip_address')->nullable();

            // Store the user agent string of the user performing the masquerade action, allowing null values.
            $table->text('user_agent')->nullable();

            // Store additional metadata associated with the masquerade session in JSON format, allowing null values.
            $table->json('metadata')->nullable();

            // Store the timestamps for when the masquerade session started and ended, allowing null values.
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Add the default created_at and updated_at timestamps for the log entry.
            $table->timestamps();

            // Define indexes to optimize queries based on action, created_at, masquerade_uuid, impersonator, and target.
            $table->index(['action', 'created_at']);
            $table->index(['masquerade_uuid', 'action']);
            $table->index(['impersonator_type', 'impersonator_id', 'created_at'], 'masquerade_impersonator_created_index');
            $table->index(['target_type', 'target_id', 'created_at'], 'masquerade_target_created_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        // Drop the masquerade logs table if it exists, using the configured table name or defaulting to 'masquerade_logs'.
        Schema::dropIfExists(config('masquerade.logging.table_name', 'masquerade_logs'));
    }
};
