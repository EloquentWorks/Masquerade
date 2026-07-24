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
        // Get the table name from the configuration, defaulting to 'masquerade_logs'
        $tableName = config('masquerade.logging.table_name', 'masquerade_logs');

        // Add new columns to the masquerade logs table
        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            // Add the category column as a nullable string after guard
            if (! Schema::hasColumn($tableName, 'category')) {
                $table->string('category')->nullable()->index()->after('guard');
            }

            // Add the ability column as a nullable string after category
            if (! Schema::hasColumn($tableName, 'ability')) {
                $table->string('ability')->nullable()->index()->after('category');
            }

            // Add the ended_reason column as a nullable string after ability
            if (! Schema::hasColumn($tableName, 'ended_reason')) {
                $table->string('ended_reason')->nullable()->index()->after('ability');
            }

            // Add the extension_count column as an unsigned small integer after ended_reason
            if (! Schema::hasColumn($tableName, 'extension_count')) {
                $table->unsignedSmallInteger('extension_count')->default(0)->after('ended_reason');
            }

            // Add the risk_score column as an unsigned small integer after extension_count
            if (! Schema::hasColumn($tableName, 'risk_score')) {
                $table->unsignedSmallInteger('risk_score')->default(0)->index()->after('extension_count');
            }

            // Add the risk_flags column as a JSON column after risk_score
            if (! Schema::hasColumn($tableName, 'risk_flags')) {
                $table->json('risk_flags')->nullable()->after('risk_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Get the table name from the configuration, defaulting to 'masquerade_logs'
        $tableName = config('masquerade.logging.table_name', 'masquerade_logs');

        // Drop the columns added in the up() method
        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            foreach ([
                'risk_flags',
                'risk_score',
                'extension_count',
                'ended_reason',
                'ability',
                'category',
            ] as $column) {
                // Drop the column if it exists
                if (Schema::hasColumn($tableName, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
