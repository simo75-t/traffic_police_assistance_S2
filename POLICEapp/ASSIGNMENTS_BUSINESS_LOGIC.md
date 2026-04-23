Business logic: Assignments-based dispatch (nearest-officer automatic assignments)

Goals
- Allow multiple assignments per officer and per report.
- Officers receive assignments (possibly many) without accept/reject flow.
- Officers mark assignments "completed" when they finish handling them.
- Keep report status and assignment status decoupled so multiple officers can work on same report if needed.

Key concepts
- Report: the citizen-submitted report (reports table). Has its own lifecycle: submitted -> dispatched -> in_progress -> closed.
- Assignment: a record that maps a report to an officer with assignment_status: assigned/in_progress/completed/rejected/expired.
- Notifications: payload MUST contain assignment_id (preferred) and report_id.

API Endpoints (recommended)
1) GET /officers/assignments
   - Returns list of assignments for the authenticated officer.
   - Each item includes: assignment_id, assignment_status, report { id, title, description, status, priority, image_url, location, reporter }, assigned_at, response_deadline, distance_km.

2) POST /officers/assignments/{assignmentId}/complete
   - Request body: { notes?: string }
   - Behavior:
     - Set assignments.status = 'completed', updated_at = now(), store notes.
     - Optionally set reports.status: business decision below.
     - Return 200 with assignment and report status.

3) (Optional) POST /officers/reports/{reportId}/respond - keep for other flows but not used for nearest-officer auto-assignment.

Business rules decisions (choose one and implement consistently)
A) Assignment-complete only affects the assignment (recommended when multiple officers may be assigned):
   - assignments.status -> 'completed'
   - report.status remains 'dispatched' or 'in_progress' and moved to 'closed' only by a separate report-closing workflow.
B) Assignment-complete also advances report to 'in_progress' or 'closed' (recommended when single-officer handling):
   - If report requires single officer, on first assignment completed set report.status = 'in_progress' or 'closed' as needed; guard with transaction and check current report.status.

Concurrency and idempotency
- complete endpoint must be idempotent: calling it twice should succeed but not duplicate effects.
- Use transactions when updating assignments and reports together.
- Use optimistic checks (WHERE status != 'completed') or update ... RETURNING to detect prior state.

Notifications
- When creating an assignment, send FCM to the officer(s) with payload: { assignment_id, report_id, title, priority }
- Notification deep-link uses assignment_id so client highlights the exact card.

Auditing
- Store notes, timestamps, and who completed the assignment.
- Keep an assignments_history table if you need full change log.

Data retention & cleanup
- Optionally archive old assignments older than X days.
- When report is deleted, assignments should cascade (REFERENCES reports(id) ON DELETE CASCADE).

Migration notes
- Add assignments table and indexes (see ASSIGNMENTS_MIGRATION_20260417.sql in repo root)
- Remove or relax uniqueness constraints that prevented many assignments per officer.

Testing
- Unit tests for assignment lifecycle.
- Integration test: create assignment, notify officer, call complete endpoint, verify DB state.

Implementation checklist for backend devs
- [ ] Add assignments table migration
- [ ] Implement GET /officers/assignments
- [ ] Implement POST /officers/assignments/{assignmentId}/complete
- [ ] Ensure notifications include assignment_id and report_id
- [ ] Add audit logging for completes
- [ ] Add automated tests
