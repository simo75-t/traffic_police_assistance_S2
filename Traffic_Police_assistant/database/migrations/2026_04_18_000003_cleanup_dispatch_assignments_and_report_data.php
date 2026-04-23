<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_assignments') || ! Schema::hasTable('citizen_reports')) {
            return;
        }

        $reports = DB::table('citizen_reports')->get()->keyBy('id');
        $assignments = DB::table('report_assignments')
            ->orderBy('citizen_report_id')
            ->orderByDesc('assignment_order')
            ->orderByDesc('id')
            ->get();

        $latestAssignmentByReport = [];

        foreach ($assignments as $assignment) {
            $report = $reports->get($assignment->citizen_report_id);
            if (! $report) {
                continue;
            }

            $normalizedAssignmentStatus = $report->status === 'closed' ? 'completed' : 'assigned';

            DB::table('report_assignments')
                ->where('id', $assignment->id)
                ->update([
                    'assignment_status' => $normalizedAssignmentStatus,
                    'responded_at' => $assignment->responded_at
                        ?? ($report->status === 'closed'
                            ? ($report->closed_at ?? $assignment->assigned_at)
                            : ($report->accepted_at ?? $assignment->assigned_at)),
                ]);

            if (! array_key_exists($assignment->citizen_report_id, $latestAssignmentByReport)) {
                $latestAssignmentByReport[$assignment->citizen_report_id] = $assignment;
            }
        }

        foreach ($reports as $report) {
            $latestAssignment = $latestAssignmentByReport[$report->id] ?? null;
            $normalizedReportStatus = $report->status;
            $assignedOfficerId = null;
            $acceptedAt = $report->accepted_at;
            $lastDispatchAt = $report->last_dispatch_at;

            if ($latestAssignment) {
                if ($normalizedReportStatus === 'submitted') {
                    $normalizedReportStatus = 'dispatched';
                }

                if (in_array($normalizedReportStatus, ['dispatched', 'in_progress', 'closed'], true)) {
                    $assignedOfficerId = $latestAssignment->officer_id;
                    $lastDispatchAt = $lastDispatchAt ?? $latestAssignment->assigned_at;
                }

                if (in_array($normalizedReportStatus, ['in_progress', 'closed'], true)) {
                    $acceptedAt = $acceptedAt ?? $latestAssignment->assigned_at;
                }
            }

            if (! $latestAssignment && $normalizedReportStatus === 'submitted') {
                $assignedOfficerId = null;
                $acceptedAt = null;
                $lastDispatchAt = null;
            }

            DB::table('citizen_reports')
                ->where('id', $report->id)
                ->update([
                    'status' => $normalizedReportStatus,
                    'assigned_officer_id' => $assignedOfficerId,
                    'accepted_at' => $acceptedAt,
                    'last_dispatch_at' => $lastDispatchAt,
                ]);
        }

        if (Schema::hasColumn('report_assignments', 'response_deadline')) {
            Schema::table('report_assignments', function (Blueprint $table): void {
                $table->dropColumn('response_deadline');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('report_assignments') && ! Schema::hasColumn('report_assignments', 'response_deadline')) {
            Schema::table('report_assignments', function (Blueprint $table): void {
                $table->dateTime('response_deadline')->nullable()->after('responded_at');
            });
        }
    }
};
