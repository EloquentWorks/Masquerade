<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Create the masquerade logs table with the specified columns and indexes
        Schema::create(config('masquerade.logging.table_name', 'masquerade_logs'), function (Blueprint $table): void {
            
            $table->id();
            $table->uuid('masquerade_uuid')->index();
            
            // The action column represents the type of action performed during the masquerade session (e.g., "start", "end", "extend").
            $table->string('action')->index();

            // The guard column represents the authentication guard used during the masquerade session (e.g., "web", "api").
            $table->string('guard')->nullable()->index();

            // The category column represents the category of the masquerade session (e.g., "admin", "user").
            $table->string('category')->nullable()->index();
            
            // The ability column represents the specific ability or permission that was blocked during the masquerade session.
            $table->string('ability')->nullable()->index();

            // The ended_reason column represents the reason why the masquerade session ended (e.g., "timeout", "manual_end").
            $table->string('ended_reason')->nullable()->index();

            // The extension_count column represents the number of times the masquerade session was extended.
            $table->unsignedSmallInteger('extension_count')->default(0);

            // The risk_score column represents the calculated risk score for the masquerade session, which
            // can be used to assess the potential security risk associated with the session.
            $table->unsignedSmallInteger('risk_score')->default(0)->index();

            // The risk_flags column represents an array of risk flags associated with the masquerade session,
            // which can be used to identify specific security concerns or anomalies.
            $table->json('risk_flags')->nullable();

            // The impersonator and target columns represent the polymorphic relationships to the
            // impersonator and target models involved in the masquerade session.
            $table->nullableMorphs('impersonator');
            $table->nullableMorphs('target');

            // The reason column represents an optional reason provided for the masquerade session, which can
            // be used for auditing or logging purposes.
            $table->text('reason')->nullable();

            // The ip_address column represents the IP address from which the masquerade session was initiated.
            $table->ipAddress('ip_address')->nullable();

            // The user_agent column represents the user agent string of the client initiating the masquerade session.
            $table->text('user_agent')->nullable();

            // The metadata column represents additional metadata associated with the masquerade session, which can
            // be used to store custom information relevant to the session.
            $table->json('metadata')->nullable();

            // The started_at and ended_at columns represent the timestamps for when the masquerade session started and ended, respectively.
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // The timestamps columns represent the created_at and updated_at timestamps for the masquerade log entry.
            $table->timestamps();

            // Define indexes for efficient querying based on common access patterns
            $table->index(['action', 'created_at']);
            $table->index(['masquerade_uuid', 'action']);
            $table->index(['category', 'created_at']);
            $table->index(['ability', 'created_at']);
            $table->index(['impersonator_type', 'impersonator_id', 'created_at'], 'masquerade_impersonator_created_index');
            $table->index(['target_type', 'target_id', 'created_at'], 'masquerade_target_created_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Drop the masquerade logs table if it exists
        Schema::dropIfExists(config('masquerade.logging.table_name', 'masquerade_logs'));
    }
};
