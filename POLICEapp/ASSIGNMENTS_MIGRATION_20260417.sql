-- Migration: Add assignments table and update reports handling
-- DB: PostgreSQL

BEGIN;

-- 1. Create assignments table to allow multiple assignments per officer and per report
CREATE TABLE IF NOT EXISTS assignments (
  id BIGSERIAL PRIMARY KEY,
  report_id BIGINT NOT NULL REFERENCES reports(id) ON DELETE CASCADE,
  officer_id BIGINT NOT NULL REFERENCES officers(id) ON DELETE SET NULL,
  assignment_status VARCHAR(32) NOT NULL DEFAULT 'assigned', -- assigned, in_progress, completed, rejected, expired
  assigned_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  response_deadline TIMESTAMP WITH TIME ZONE NULL,
  notes TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. Indexes for common queries
CREATE INDEX IF NOT EXISTS idx_assignments_officer_id ON assignments(officer_id);
CREATE INDEX IF NOT EXISTS idx_assignments_report_id ON assignments(report_id);
CREATE INDEX IF NOT EXISTS idx_assignments_status ON assignments(assignment_status);

-- 3. Ensure reports table has a status column that can be updated independently
ALTER TABLE reports
  ADD COLUMN IF NOT EXISTS status VARCHAR(32) DEFAULT 'submitted'; -- submitted, dispatched, in_progress, closed

-- 4. Optional: if previous schema stored one "assigned_officer_id" on reports and had a unique constraint,
-- drop that constraint so multiple assignments are possible (adjust names to your schema):
-- ALTER TABLE reports DROP CONSTRAINT IF EXISTS reports_assigned_officer_id_key;
-- ALTER TABLE reports DROP COLUMN IF EXISTS assigned_officer_id;

-- 5. Trigger to update updated_at on assignments
CREATE OR REPLACE FUNCTION fn_update_updated_at() RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_assignments_update_updated_at
BEFORE UPDATE ON assignments
FOR EACH ROW
EXECUTE FUNCTION fn_update_updated_at();

COMMIT;
