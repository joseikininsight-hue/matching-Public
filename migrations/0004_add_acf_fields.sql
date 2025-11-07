-- Add missing ACF fields to grants table
ALTER TABLE grants ADD COLUMN url TEXT;
ALTER TABLE grants ADD COLUMN eligible_expenses TEXT;
ALTER TABLE grants ADD COLUMN required_documents TEXT;
ALTER TABLE grants ADD COLUMN adoption_rate TEXT;
ALTER TABLE grants ADD COLUMN difficulty_level TEXT;
ALTER TABLE grants ADD COLUMN area_notes TEXT;
ALTER TABLE grants ADD COLUMN subsidy_rate_detailed TEXT;

-- Create indexes for commonly queried fields
CREATE INDEX IF NOT EXISTS idx_grants_organization ON grants(organization);
CREATE INDEX IF NOT EXISTS idx_grants_url ON grants(url);
