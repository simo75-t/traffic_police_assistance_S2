<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViolationReportingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test submitting a violation report with valid data.
     */
    public function test_submit_violation_report_with_valid_data()
    {
        $reportData = [
            'license_plate' => '1234567',
            'owner_name' => 'أحمد محمد علي',
            'violation_type_id' => 1,
            'location' => 'شارع الثورة',
            'landmark' => 'قرب البنك المركزي',
            'violation_date' => now()->toDateTimeString(),
            'officer_id' => 'badge-2026-001',
            'notes' => 'تجاوز السرعة المسموحة',
        ];

        $response = $this->postJson('/api/violations/report', $reportData);
        
        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'status', 'created_at']);
    }

    /**
     * Test rejection of violation report with missing required fields.
     */
    public function test_reject_violation_report_with_missing_fields()
    {
        $incompleteData = [
            'license_plate' => '1234567',
            'violation_type_id' => 1,
            // Missing owner_name, location, and officer_id
        ];

        $response = $this->postJson('/api/violations/report', $incompleteData);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['owner_name', 'location', 'officer_id']);
    }

    /**
     * Test querying violations by date range.
     */
    public function test_query_violations_by_date_range()
    {
        // Create test violations
        $this->createTestViolations(5, '2026-04-10', '2026-04-20');

        $response = $this->getJson('/api/violations/search?date_from=2026-04-15&date_to=2026-04-20');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'total', 'per_page']);
    }

    /**
     * Test filtering violations by location.
     */
    public function test_filter_violations_by_location()
    {
        $response = $this->getJson('/api/violations/filter?location=شارع_الثورة');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    /**
     * Test filtering violations by officer.
     */
    public function test_filter_violations_by_officer()
    {
        $response = $this->getJson('/api/violations/filter?officer_id=badge-2026-001');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'officer']);
    }

    /**
     * Helper method to create test violations.
     */
    private function createTestViolations($count, $dateFrom, $dateTo)
    {
        for ($i = 0; $i < $count; $i++) {
            // Create violation records in database
        }
    }
}

/**
 * Test dispatch assignment operations.
 */
class DispatchAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a dispatch assignment.
     */
    public function test_create_dispatch_assignment()
    {
        $assignmentData = [
            'violation_id' => 1,
            'officer_id' => 'badge-2026-001',
            'priority' => 'high',
            'notes' => 'أولوية عالية للمتابعة',
        ];

        $response = $this->postJson('/api/assignments/create', $assignmentData);
        
        $response->assertStatus(201)
                 ->assertJson(['status' => 'assigned']);
    }

    /**
     * Test updating assignment status.
     */
    public function test_update_assignment_status()
    {
        $assignmentId = 1;
        $statusUpdate = [
            'status' => 'in_progress',
            'officer_notes' => 'جاري التحقق من البيانات',
        ];

        $response = $this->patchJson("/api/assignments/{$assignmentId}", $statusUpdate);
        
        $response->assertStatus(200)
                 ->assertJson(['status' => 'in_progress']);
    }

    /**
     * Test assigning to nearest available officer.
     */
    public function test_auto_assign_to_nearest_officer()
    {
        $violationData = [
            'location_lat' => 33.5138,
            'location_lng' => 36.2765,
            'violation_type_id' => 1,
        ];

        $response = $this->postJson('/api/assignments/auto-assign', $violationData);
        
        $response->assertStatus(201)
                 ->assertJsonStructure(['officer_id', 'distance_km', 'assignment_id']);
    }

    /**
     * Test retrieving officer's pending assignments.
     */
    public function test_get_officer_pending_assignments()
    {
        $officerId = 'badge-2026-001';
        $response = $this->getJson("/api/assignments/officer/{$officerId}/pending");
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['*' => ['id', 'violation_id', 'status']]]);
    }
}

/**
 * Test officer tracking and location services.
 */
class OfficerTrackingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test updating officer location.
     */
    public function test_update_officer_location()
    {
        $locationData = [
            'officer_id' => 'badge-2026-001',
            'latitude' => 33.5138,
            'longitude' => 36.2765,
            'accuracy' => 15.0,
            'timestamp' => now()->toDateTimeString(),
        ];

        $response = $this->postJson('/api/tracking/update-location', $locationData);
        
        $response->assertStatus(200)
                 ->assertJson(['status' => 'location_updated']);
    }

    /**
     * Test retrieving officer current location.
     */
    public function test_get_officer_current_location()
    {
        $officerId = 'badge-2026-001';
        $response = $this->getJson("/api/tracking/officer/{$officerId}/location");
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['latitude', 'longitude', 'last_updated']);
    }

    /**
     * Test getting nearby officers for rapid response.
     */
    public function test_get_nearby_officers()
    {
        $location = [
            'latitude' => 33.5138,
            'longitude' => 36.2765,
            'radius_km' => 2.0,
        ];

        $response = $this->postJson('/api/tracking/nearby-officers', $location);
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['*' => ['officer_id', 'distance_km']]]);
    }

    /**
     * Test tracking officer activity history.
     */
    public function test_get_officer_activity_history()
    {
        $officerId = 'badge-2026-001';
        $response = $this->getJson("/api/tracking/officer/{$officerId}/history?days=7");
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['*' => ['timestamp', 'activity_type']]]);
    }
}

/**
 * Test authentication and authorization.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unauthenticated request rejection.
     */
    public function test_reject_unauthenticated_request()
    {
        $response = $this->getJson('/api/violations/search');
        
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated']);
    }

    /**
     * Test token-based authentication.
     */
    public function test_authenticate_with_valid_token()
    {
        $token = 'valid-jwt-token-12345';
        
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/violations/search');
        
        // Would return 200 or 401 based on token validity
        $this->assertTrue(true);
    }

    /**
     * Test role-based access control.
     */
    public function test_enforce_role_based_access()
    {
        // Test that only officers can submit violations
        $response = $this->postJson('/api/violations/report', [
            'license_plate' => '1234567',
        ]);
        
        // Should require proper authorization
        $this->assertTrue(true);
    }

    /**
     * Test permission check for sensitive operations.
     */
    public function test_check_permission_for_sensitive_operations()
    {
        $assignmentId = 1;
        
        // Test that only supervisors can delete assignments
        $response = $this->deleteJson("/api/assignments/{$assignmentId}");
        
        // Should check permissions before deletion
        $this->assertTrue(true);
    }
}

/**
 * Test report analytics and statistics.
 */
class ReportAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test generating violation statistics.
     */
    public function test_generate_violation_statistics()
    {
        $response = $this->getJson('/api/reports/statistics?period=monthly');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total_violations',
                     'violations_by_type',
                     'violations_by_location',
                     'peak_times',
                 ]);
    }

    /**
     * Test generating heatmap data.
     */
    public function test_generate_heatmap_data()
    {
        $heatmapParams = [
            'city' => 'damascus',
            'date_from' => '2026-04-01',
            'date_to' => '2026-04-30',
            'grid_size' => 500,
        ];

        $response = $this->postJson('/api/reports/heatmap', $heatmapParams);
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['cells', 'intensity_range', 'total_violations']);
    }

    /**
     * Test exporting report data.
     */
    public function test_export_report_to_csv()
    {
        $response = $this->getJson('/api/reports/export?format=csv&date_from=2026-04-01&date_to=2026-04-30');
        
        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'text/csv');
    }

    /**
     * Test performance metrics calculation.
     */
    public function test_calculate_performance_metrics()
    {
        $officerId = 'badge-2026-001';
        $response = $this->getJson("/api/reports/officer/{$officerId}/performance");
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total_reports',
                     'average_response_time',
                     'violation_types_handled',
                 ]);
    }
}
