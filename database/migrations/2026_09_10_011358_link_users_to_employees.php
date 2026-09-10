<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give every user account exactly one employee, enforced by the database.
 *
 * The legacy Windows system had three separate id spaces linked by employee
 * number: `users.id` was an arbitrary surrogate, `users.num` held the employee
 * number, and `time_sheet.revised_by` held that number too. To import
 * `revised_by` directly, this application forced `users.id = users.number =
 * employees.id` — three id spaces pretending to be one, with nothing enforcing
 * it. Two employee rows have already drifted (id 6718 carries number 6716),
 * which is enough to break every assumption resting on it.
 *
 * The link now lives on `users` because the rule is directional: every user is
 * an employee, but most employees have no login. NOT NULL and UNIQUE make that
 * rule the database's job rather than a convention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('id');
        });

        $this->backfillFromExistingData();

        $unlinked = DB::table('users')->whereNull('employee_id')->count();

        if ($unlinked > 0) {
            $numbers = DB::table('users')->whereNull('employee_id')->pluck('number')->implode(', ');

            throw new RuntimeException(
                "Cannot link {$unlinked} user(s) to an employee (numbers: {$numbers}). "
                .'Create or correct the matching employee records, then run this migration again. '
                .'No user is linked to a guessed employee.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
            $table->unique('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
        });

        // Superseded by users.employee_id, which can enforce the rule.
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        // The number belongs to the employee; a copy on the user is what drifted.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'number')) {
                $table->dropColumn('number');
            }
        });

        /*
         * Restore the surrogate key.
         *
         * An earlier migration made `users.id` manual so it could be set to the
         * employee number. With the link now explicit, the id goes back to
         * being a meaningless auto-incrementing surrogate — which is what let
         * the two id spaces drift apart in the first place.
         */
        // Other tables hold foreign keys to users.id, so the checks come off
        // for the column change itself. No data moves; only the column's
        // auto-increment flag changes.
        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Link existing users on the best available evidence, never a guess.
     *
     * Employee number is the business identity and is tried first; the legacy
     * id aliasing is only a fallback for users whose number matches nothing.
     */
    private function backfillFromExistingData(): void
    {
        if (! Schema::hasColumn('users', 'number')) {
            return;
        }

        DB::statement('
            UPDATE users u
            JOIN employees e ON e.number = u.number
            SET u.employee_id = e.id
            WHERE u.employee_id IS NULL
        ');

        // MySQL cannot subquery the table being updated, so the "not already
        // taken" check is expressed as an anti-join.
        DB::statement('
            UPDATE users u
            JOIN employees e ON e.id = u.id
            LEFT JOIN users taken ON taken.employee_id = e.id
            SET u.employee_id = e.id
            WHERE u.employee_id IS NULL
              AND taken.id IS NULL
        ');
    }

    public function down(): void
    {
        // Restored with its foreign key, not just the column: the migration that
        // rolls back after this one drops `employees_user_id_foreign` by name, and
        // a down() that leaves the schema half-restored breaks the one behind it.
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
        });

        DB::statement('UPDATE employees e JOIN users u ON u.employee_id = e.id SET e.user_id = u.id');

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('number')->nullable();
        });

        DB::statement('UPDATE users u JOIN employees e ON e.id = u.employee_id SET u.number = e.number');

        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL');
        Schema::enableForeignKeyConstraints();

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
