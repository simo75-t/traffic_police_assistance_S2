<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'fcm_token')) {
                $table->text('fcm_token')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('fcm_token');
            }
        });

        Schema::table('violation_types', function (Blueprint $table): void {
            if (! Schema::hasColumn('violation_types', 'severity_weight')) {
                $table->decimal('severity_weight', 5, 2)->default(1)->after('fine_amount');
            }

            if (! Schema::hasColumn('violation_types', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('severity_weight');
            }
        });

        if (! Schema::hasTable('areas')) {
            Schema::create('areas', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('city');
                $table->decimal('center_lat', 10, 7)->nullable();
                $table->decimal('center_lng', 10, 7)->nullable();
                $table->dateTime('created_at')->nullable();
            });
        }

        Schema::table('violation_locations', function (Blueprint $table): void {
            if (! Schema::hasColumn('violation_locations', 'area_id')) {
                $table->foreignId('area_id')->nullable()->after('id')->constrained('areas')->nullOnDelete();
            }

            if (! Schema::hasColumn('violation_locations', 'address')) {
                $table->string('address')->nullable()->after('area_id');
            }

            if (! Schema::hasColumn('violation_locations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('landmark');
            }

            if (! Schema::hasColumn('violation_locations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (! Schema::hasColumn('violation_locations', 'city')) {
                $table->string('city')->nullable()->after('longitude');
            }
        });

        if (! Schema::hasTable('report_locations')) {
            Schema::create('report_locations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
                $table->string('address')->nullable();
                $table->string('street_name')->nullable();
                $table->string('landmark')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('city')->nullable();
                $table->dateTime('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('citizen_reports')) {
            Schema::create('citizen_reports', function (Blueprint $table): void {
                $table->id();
                $table->string('reporter_name');
                $table->string('reporter_phone')->nullable();
                $table->string('reporter_email')->nullable();
                $table->foreignId('report_location_id')->nullable()->constrained('report_locations')->nullOnDelete();
                $table->string('title');
                $table->text('description');
                $table->string('image_path')->nullable();
                $table->string('status')->default('submitted');
                $table->string('priority')->default('medium');
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('accepted_at')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->unsignedInteger('dispatch_attempts_count')->default(0);
                $table->dateTime('last_dispatch_at')->nullable();
            });
        }

        Schema::table('violations', function (Blueprint $table): void {
            if (! Schema::hasColumn('violations', 'source_report_id')) {
                $table->foreignId('source_report_id')->nullable()->after('violation_location_id')->constrained('citizen_reports')->nullOnDelete();
            }

            if (! Schema::hasColumn('violations', 'plate_snapshot')) {
                $table->string('plate_snapshot')->nullable()->after('fine_amount');
            }

            if (! Schema::hasColumn('violations', 'owner_snapshot')) {
                $table->string('owner_snapshot')->nullable()->after('plate_snapshot');
            }

            if (! Schema::hasColumn('violations', 'data_source')) {
                $table->string('data_source')->nullable()->after('occurred_at');
            }

            if (! Schema::hasColumn('violations', 'is_synthetic')) {
                $table->boolean('is_synthetic')->default(false)->after('data_source');
            }

            if (! Schema::hasColumn('violations', 'severity_level')) {
                $table->string('severity_level')->nullable()->after('is_synthetic');
            }

            if (! Schema::hasColumn('violations', 'status')) {
                $table->string('status')->default('recorded')->after('severity_level');
            }
        });

        if (! Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('violation_id')->constrained('violations')->cascadeOnDelete();
                $table->string('file_path');
                $table->string('file_type');
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('recorded_at')->nullable();
            });
        }

        if (! Schema::hasTable('report_assignments')) {
            Schema::create('report_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('citizen_report_id')->constrained('citizen_reports')->cascadeOnDelete();
                $table->foreignId('officer_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('assignment_order')->default(1);
                $table->decimal('distance_km', 8, 2)->nullable();
                $table->string('assignment_status')->default('pending');
                $table->dateTime('assigned_at')->nullable();
                $table->dateTime('responded_at')->nullable();
                $table->dateTime('response_deadline')->nullable();
                $table->text('notes')->nullable();
            });
        }

        if (! Schema::hasTable('officers_live_locations')) {
            Schema::create('officers_live_locations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('officer_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->string('availability_status')->default('available');
                $table->dateTime('last_update_time')->nullable();
                $table->string('device_id')->nullable();
                $table->unsignedTinyInteger('battery_level')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('heatmap_analysis_cache')) {
            Schema::create('heatmap_analysis_cache', function (Blueprint $table): void {
                $table->id();
                $table->string('cache_key')->unique();
                $table->string('violation_type_id')->nullable();
                $table->string('time_bucket')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->unsignedInteger('grid_size')->nullable();
                $table->dateTime('generated_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->longText('result_json')->nullable();
                $table->dateTime('created_at')->nullable();
            });
        }

        Schema::table('appeals', function (Blueprint $table): void {
            if (! Schema::hasColumn('appeals', 'submitted_at')) {
                $table->dateTime('submitted_at')->nullable()->after('decision_note');
            }

            if (! Schema::hasColumn('appeals', 'decided_at')) {
                $table->dateTime('decided_at')->nullable()->after('submitted_at');
            }
        });

        DB::table('appeals')
            ->whereNull('submitted_at')
            ->update(['submitted_at' => DB::raw('created_at')]);

        DB::table('violations')
            ->whereNull('plate_snapshot')
            ->update(['plate_snapshot' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(vehicle_snapshot, '$[0]'))")]);
    }

    public function down(): void
    {
        Schema::table('appeals', function (Blueprint $table): void {
            if (Schema::hasColumn('appeals', 'decided_at')) {
                $table->dropColumn('decided_at');
            }

            if (Schema::hasColumn('appeals', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
        });

        Schema::dropIfExists('heatmap_analysis_cache');
        Schema::dropIfExists('officers_live_locations');
        Schema::dropIfExists('report_assignments');
        Schema::dropIfExists('attachments');

        Schema::table('violations', function (Blueprint $table): void {
            $columns = [
                'status',
                'severity_level',
                'is_synthetic',
                'data_source',
                'owner_snapshot',
                'plate_snapshot',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('violations', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('violations', 'source_report_id')) {
                $table->dropConstrainedForeignId('source_report_id');
            }
        });

        Schema::dropIfExists('citizen_reports');
        Schema::dropIfExists('report_locations');

        Schema::table('violation_locations', function (Blueprint $table): void {
            foreach (['city', 'longitude', 'latitude', 'address'] as $column) {
                if (Schema::hasColumn('violation_locations', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('violation_locations', 'area_id')) {
                $table->dropConstrainedForeignId('area_id');
            }
        });

        Schema::dropIfExists('areas');

        Schema::table('violation_types', function (Blueprint $table): void {
            foreach (['is_active', 'severity_weight'] as $column) {
                if (Schema::hasColumn('violation_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            foreach (['last_seen_at', 'fcm_token', 'phone'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
