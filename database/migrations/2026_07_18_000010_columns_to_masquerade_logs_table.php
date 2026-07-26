<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the table name for masquerade logs from the configuration, defaulting to 'masquerade_logs'
        $tableName = config(
            'masquerade.logging.table_name',
            'masquerade_logs'
        );

        // Add new columns to the masquerade logs table if they do not already exist
        if (! Schema::hasTable($tableName)) {
            return;
        }

        // Add the 'category' column if it does not exist
        if (! Schema::hasColumn($tableName, 'category')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('category')->nullable();
            });
        }

        // Add the 'ability' column if it does not exist
        if (! Schema::hasColumn($tableName, 'ability')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('ability')->nullable();
            });
        }

        // Add the 'ended_reason' column if it does not exist
        if (! Schema::hasColumn($tableName, 'ended_reason')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('ended_reason')->nullable();
            });
        }

        // Add the 'extension_count' column if it does not exist
        if (! Schema::hasColumn($tableName, 'extension_count')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedSmallInteger('extension_count')->default(0);
            });
        }

        // Add the 'risk_score' column if it does not exist
        if (! Schema::hasColumn($tableName, 'risk_score')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedSmallInteger('risk_score')->default(0);
            });
        }

        // Add the 'risk_flags' column if it does not exist
        if (! Schema::hasColumn($tableName, 'risk_flags')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->json('risk_flags')->nullable();
            });
        }

        // Add indexes to the new columns for better query performance
        if (! Schema::hasIndex(
            $tableName,
            'masquerade_category_created_index'
        )) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->index(
                    ['category', 'created_at'],
                    'masquerade_category_created_index'
                );
            });
        }

        // Add an index for the 'ability' and 'created_at' columns if it does not exist
        if (! Schema::hasIndex(
            $tableName,
            'masquerade_ability_created_index'
        )) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->index(
                    ['ability', 'created_at'],
                    'masquerade_ability_created_index'
                );
            });
        }

        // Add an index for the 'ended_reason' column if it does not exist
        if (! Schema::hasIndex(
            $tableName,
            'masquerade_ended_reason_index'
        )) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->index(
                    'ended_reason',
                    'masquerade_ended_reason_index'
                );
            });
        }

        // Add an index for the 'risk_score' column if it does not exist
        if (! Schema::hasIndex(
            $tableName,
            'masquerade_risk_score_index'
        )) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->index(
                    'risk_score',
                    'masquerade_risk_score_index'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the table name for masquerade logs from the configuration, defaulting to 'masquerade_logs'
        $tableName = config(
            'masquerade.logging.table_name',
            'masquerade_logs'
        );

        // Drop the indexes and columns added in the 'up' method if they exist
        if (! Schema::hasTable($tableName)) {
            return;
        }

        // Drop the indexes if they exist
        $indexes = [
            'masquerade_category_created_index',
            'masquerade_ability_created_index',
            'masquerade_ended_reason_index',
            'masquerade_risk_score_index',
        ];

        // Drop the indexes if they exist
        foreach ($indexes as $index) {
            if (Schema::hasIndex($tableName, $index)) {
                Schema::table(
                    $tableName,
                    function (Blueprint $table) use ($index): void {
                        $table->dropIndex($index);
                    }
                );
            }
        }

        // Drop the columns if they exist
        $columns = [
            'risk_flags',
            'risk_score',
            'extension_count',
            'ended_reason',
            'ability',
            'category',
        ];

        // Drop the columns if they exist
        foreach ($columns as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                Schema::table(
                    $tableName,
                    function (Blueprint $table) use ($column): void {
                        $table->dropColumn($column);
                    }
                );
            }
        }
    }
};
