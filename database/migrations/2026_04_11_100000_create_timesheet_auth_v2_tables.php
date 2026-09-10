<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scope_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('context')->default('general')->index();
            $table->enum('match_type', ['global', 'location', 'department', 'center', 'employee'])->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('center_id')->nullable()->index();
            $table->json('target_employee_ids')->nullable();
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        Schema::create('scope_policy_actors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('actor_employee_id');
            $table->boolean('can_fill')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_revise')->default(false);
            $table->string('role_hint')->nullable();
            $table->timestamps();

            $table->unique(['policy_id', 'actor_employee_id'], 'scope_policy_actor_unique');

            $table->foreign('policy_id')->references('id')->on('scope_policies')->cascadeOnDelete();
            $table->foreign('actor_employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('context')->default('time_sheet')->index();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->json('applies_to')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id');
            $table->unsignedInteger('step_order');
            $table->string('step_key');
            $table->string('required_role')->nullable();
            $table->boolean('can_fill')->default(false);
            $table->boolean('can_approve')->default(true);
            $table->unsignedInteger('depends_on_step_order')->nullable();
            $table->timestamps();

            $table->unique(['flow_id', 'step_order'], 'approval_flow_step_order_unique');
            $table->unique(['flow_id', 'step_key'], 'approval_flow_step_key_unique');

            $table->foreign('flow_id')->references('id')->on('approval_flows')->cascadeOnDelete();
        });

        Schema::create('timesheet_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('flow_id');
            $table->unsignedInteger('step_order');
            $table->string('step_key');
            $table->unsignedBigInteger('approved_by_employee_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year', 'step_order'], 'timesheet_approval_step_unique');
            $table->index(['month', 'year', 'step_key'], 'timesheet_approval_step_lookup');

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('flow_id')->references('id')->on('approval_flows')->cascadeOnDelete();
            $table->foreign('approved_by_employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_approval_steps');
        Schema::dropIfExists('approval_flow_steps');
        Schema::dropIfExists('approval_flows');
        Schema::dropIfExists('scope_policy_actors');
        Schema::dropIfExists('scope_policies');
    }
};
