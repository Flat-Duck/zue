<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The personnel export identifies organisational units by a code and an Arabic
 * name. Those belong on the units themselves rather than being copied onto
 * every employee row, so each employee keeps its existing foreign keys and the
 * detail lives in one place.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['centers', 'administrations', 'departments', 'locations'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->string('code')->nullable()->after('name');
                $blueprint->string('arabic_name')->nullable()->after('code');

                $blueprint->index('code', "{$table}_code_index");
            });
        }

        // These were text copies of the same information on the employee row.
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'cost_center',
                'hr_administration',
                'hr_department',
                'department_code',
                'location_code',
                'hr_location',
            ]);
        });
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex("{$table}_code_index");
                $blueprint->dropColumn(['code', 'arabic_name']);
            });
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('cost_center')->nullable();
            $table->string('hr_administration')->nullable();
            $table->string('hr_department')->nullable();
            $table->string('department_code')->nullable();
            $table->string('location_code')->nullable();
            $table->string('hr_location')->nullable();
        });
    }
};
