<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }
        });

        if (Schema::hasColumn('trainings', 'start_date') && !Schema::hasColumn('trainings', 'training_date')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->dateTime('training_date')->nullable()->after('description');
            });

            DB::table('trainings')->orderBy('id')->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->start_date) {
                        DB::table('trainings')->where('id', $row->id)->update([
                            'training_date' => $row->start_date.' 00:00:00',
                        ]);
                    }
                }
            });

            Schema::table('trainings', function (Blueprint $table) {
                $table->dropColumn('start_date');
            });
        }

        DB::table('trainings')->where('status', 'active')->update(['status' => 'published']);
        DB::table('trainings')->where('status', 'inactive')->update(['status' => 'draft']);

        DB::statement('ALTER TABLE trainings MODIFY status VARCHAR(20) NOT NULL DEFAULT \'draft\'');

        Schema::table('participation', function (Blueprint $table) {
            $table->unique(['user_id', 'training_id']);
        });
    }

    public function down(): void
    {
        Schema::table('participation', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'training_id']);
        });

        DB::statement('ALTER TABLE trainings MODIFY status VARCHAR(20) NOT NULL DEFAULT \'active\'');
        DB::table('trainings')->where('status', 'draft')->update(['status' => 'inactive']);
        DB::table('trainings')->where('status', 'published')->update(['status' => 'active']);
        DB::table('trainings')->where('status', 'archived')->update(['status' => 'inactive']);

        if (Schema::hasColumn('trainings', 'training_date') && !Schema::hasColumn('trainings', 'start_date')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->date('start_date')->nullable()->after('description');
            });
            DB::table('trainings')->whereNotNull('training_date')->orderBy('id')->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('trainings')->where('id', $row->id)->update([
                        'start_date' => substr($row->training_date, 0, 10),
                    ]);
                }
            });
            Schema::table('trainings', function (Blueprint $table) {
                $table->dropColumn('training_date');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
