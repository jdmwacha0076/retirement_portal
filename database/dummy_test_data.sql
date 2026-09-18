-- ============================================================================
-- Dummy / test data for the Activity Budget, Advance & Retirement module
-- ============================================================================
--
-- Assumes your `users` table already has rows with id = 1, 2, 3, 4 (per
-- your message). Nothing here touches the users table.
--
-- What this creates, across 4 activities that each sit at a different
-- point in the workflow (so you can exercise every screen/status):
--
--   1. Master data (activity_types, budget_categories, budget_components)
--      - SKIPPED per row if a row with the same name already exists, so
--        it's safe to run even if you already seeded these via the admin
--        screens. If you already have your own categories/components,
--        everything below still works - it just looks them up by name.
--
--   2. Four activities:
--      - Activity 1 - budget still Draft (never submitted)
--      - Activity 2 - budget Submitted then Assigned/Under Review
--      - Activity 3 - budget Approved, retirement in progress (Draft,
--        two of three lines filled in, one receipt attached)
--      - Activity 4 - budget Approved, retirement fully Approved and
--        Closed, with a reimbursement due and a reconciliation record
--
-- IMPORTANT - who's who:
--   User 1 is treated as the org-wide admin below (sees everything
--   regardless of the creator/coordinator/assignee columns). If that's
--   not accurate for your 4 users, it doesn't break anything - it just
--   means the "admin sees all" part of your visibility scoping won't
--   line up with these rows until you swap the id used for "admin"
--   below to whichever of your 4 users actually has role = 'admin'.
--
-- HOW TO RUN THIS:
--   Easiest with XAMPP: open phpMyAdmin (http://localhost/phpmyadmin),
--   select the retirement_portal database, go to the "Import" tab, and
--   choose this file. Or from a terminal:
--     mysql -u root -p retirement_portal < dummy_test_data.sql
--
-- This script was validated end-to-end against a throwaway MySQL/MariaDB
-- database built from your actual migrations before being handed to you -
-- every column name/type, FK, and enum value below matches your schema
-- exactly as of this session.
--
-- Re-running note: the four activity/retirement references below
-- (ACT/2026/00001..00004, RET/2026/00001..00002) are hard-coded and
-- UNIQUE. Running this script twice will fail on the second run with a
-- duplicate-key error on `reference` - that's expected, not a bug; it
-- means the dummy rows are already there. Delete them first (see the
-- cleanup block commented out at the very end) if you want to reseed.
-- ============================================================================

START TRANSACTION;

-- ── 1. Master data (guarded - only inserts if the name doesn't exist yet) ──

INSERT INTO activity_types (name, is_active, sort_order, created_at, updated_at)
SELECT 'Training', 1, 10, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM activity_types WHERE name = 'Training');

INSERT INTO activity_types (name, is_active, sort_order, created_at, updated_at)
SELECT 'Workshop', 1, 20, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM activity_types WHERE name = 'Workshop');

INSERT INTO activity_types (name, is_active, sort_order, created_at, updated_at)
SELECT 'International Travel', 1, 30, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM activity_types WHERE name = 'International Travel');

SELECT id INTO @type_training FROM activity_types WHERE name = 'Training' LIMIT 1;
SELECT id INTO @type_workshop FROM activity_types WHERE name = 'Workshop' LIMIT 1;
SELECT id INTO @type_travel   FROM activity_types WHERE name = 'International Travel' LIMIT 1;

INSERT INTO budget_categories (name, is_active, sort_order, created_at, updated_at)
SELECT 'Transport', 1, 10, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_categories WHERE name = 'Transport');

INSERT INTO budget_categories (name, is_active, sort_order, created_at, updated_at)
SELECT 'Accommodation & Meals', 1, 20, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_categories WHERE name = 'Accommodation & Meals');

INSERT INTO budget_categories (name, is_active, sort_order, created_at, updated_at)
SELECT 'Materials & Supplies', 1, 30, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_categories WHERE name = 'Materials & Supplies');

INSERT INTO budget_categories (name, is_active, sort_order, created_at, updated_at)
SELECT 'Visa & Travel Documents', 1, 40, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_categories WHERE name = 'Visa & Travel Documents');

SELECT id INTO @cat_transport FROM budget_categories WHERE name = 'Transport' LIMIT 1;
SELECT id INTO @cat_accommodation FROM budget_categories WHERE name = 'Accommodation & Meals' LIMIT 1;
SELECT id INTO @cat_materials FROM budget_categories WHERE name = 'Materials & Supplies' LIMIT 1;
SELECT id INTO @cat_visa FROM budget_categories WHERE name = 'Visa & Travel Documents' LIMIT 1;

INSERT INTO budget_components (budget_category_id, name, is_active, sort_order, created_at, updated_at)
SELECT @cat_transport, 'Airport Transfer', 1, 10, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_components WHERE budget_category_id = @cat_transport AND name = 'Airport Transfer');

INSERT INTO budget_components (budget_category_id, name, is_active, sort_order, created_at, updated_at)
SELECT @cat_transport, 'Local Transport', 1, 20, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_components WHERE budget_category_id = @cat_transport AND name = 'Local Transport');

INSERT INTO budget_components (budget_category_id, name, is_active, sort_order, created_at, updated_at)
SELECT @cat_accommodation, 'Hotel Accommodation', 1, 10, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_components WHERE budget_category_id = @cat_accommodation AND name = 'Hotel Accommodation');

INSERT INTO budget_components (budget_category_id, name, is_active, sort_order, created_at, updated_at)
SELECT @cat_accommodation, 'Per Diem / Meals', 1, 20, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_components WHERE budget_category_id = @cat_accommodation AND name = 'Per Diem / Meals');

INSERT INTO budget_components (budget_category_id, name, is_active, sort_order, created_at, updated_at)
SELECT @cat_materials, 'Training Materials', 1, 10, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_components WHERE budget_category_id = @cat_materials AND name = 'Training Materials');

INSERT INTO budget_components (budget_category_id, name, is_active, sort_order, created_at, updated_at)
SELECT @cat_materials, 'Stationery', 1, 20, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_components WHERE budget_category_id = @cat_materials AND name = 'Stationery');

INSERT INTO budget_components (budget_category_id, name, is_active, sort_order, created_at, updated_at)
SELECT @cat_visa, 'Visa Fees', 1, 10, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM budget_components WHERE budget_category_id = @cat_visa AND name = 'Visa Fees');

SELECT id INTO @comp_airport   FROM budget_components WHERE budget_category_id = @cat_transport AND name = 'Airport Transfer' LIMIT 1;
SELECT id INTO @comp_local     FROM budget_components WHERE budget_category_id = @cat_transport AND name = 'Local Transport' LIMIT 1;
SELECT id INTO @comp_hotel     FROM budget_components WHERE budget_category_id = @cat_accommodation AND name = 'Hotel Accommodation' LIMIT 1;
SELECT id INTO @comp_perdiem   FROM budget_components WHERE budget_category_id = @cat_accommodation AND name = 'Per Diem / Meals' LIMIT 1;
SELECT id INTO @comp_materials FROM budget_components WHERE budget_category_id = @cat_materials AND name = 'Training Materials' LIMIT 1;
SELECT id INTO @comp_stationery FROM budget_components WHERE budget_category_id = @cat_materials AND name = 'Stationery' LIMIT 1;
SELECT id INTO @comp_visa      FROM budget_components WHERE budget_category_id = @cat_visa AND name = 'Visa Fees' LIMIT 1;

-- Keep the app's own reference generator (ReferenceGenerator::next(),
-- counter_key 'activity' / 'activity_retirement') from re-issuing any of
-- the ACT/2026/0000x or RET/2026/0000x references used below.
INSERT INTO reference_counters (counter_key, year, last_sequence, created_at, updated_at)
VALUES ('activity', 2026, 4, NOW(), NOW())
ON DUPLICATE KEY UPDATE last_sequence = GREATEST(last_sequence, 4);

INSERT INTO reference_counters (counter_key, year, last_sequence, created_at, updated_at)
VALUES ('activity_retirement', 2026, 2, NOW(), NOW())
ON DUPLICATE KEY UPDATE last_sequence = GREATEST(last_sequence, 2);


-- ============================================================================
-- ACTIVITY 1 - Community Health Training Workshop
-- Budget: Draft (never submitted). Created by user 2, coordinated by user 3.
-- ============================================================================

INSERT INTO activities (
    reference, activity_type_id, title, purpose, program, location, country, venue,
    start_date, end_date, participant_count, coordinator_id, description,
    currency, status, created_by, created_at, updated_at
) VALUES (
    'ACT/2026/00001', @type_training, 'Community Health Training Workshop',
    'Refresher training for community health workers on updated referral protocols.',
    'LIFT Platform', 'Dodoma', 'Tanzania', 'Dodoma Regional Hall',
    '2026-10-06', '2026-10-08', 40, 3,
    'Three-day refresher covering the updated referral protocols and digital reporting workflow.',
    'TZS', 'draft', 2, '2026-08-20 09:00:00', '2026-08-20 09:00:00'
);
SET @activity1 = LAST_INSERT_ID();

INSERT INTO activity_budgets (
    activity_id, version, is_current, budget_code, status, created_by, currency,
    total_cash, total_invoice, total_overall, requested_advance_amount,
    created_at, updated_at
) VALUES (
    @activity1, 1, 1, 'CHW-2026-01', 'draft', 2, 'TZS',
    250000.00, 2250000.00, 2500000.00, 2500000.00,
    '2026-08-20 09:10:00', '2026-08-20 09:10:00'
);
SET @budget1 = LAST_INSERT_ID();

INSERT INTO activity_budget_items (
    activity_budget_id, budget_category_id, budget_component_id, item_code, description,
    qty, unit, unit_cost, frequency, total, payment_mode, cash_amount, invoice_amount,
    sort_order, created_at, updated_at
) VALUES
(@budget1, @cat_materials, @comp_materials, 'A1', 'Training materials and handouts',
    50.00, 'set', 5000.00, 1, 250000.00, 'cash', 250000.00, 0.00, 10, '2026-08-20 09:10:00', '2026-08-20 09:10:00'),
(@budget1, @cat_accommodation, @comp_perdiem, 'B1', 'Participant meals (3 days)',
    50.00, 'person', 15000.00, 3, 2250000.00, 'invoice', 0.00, 2250000.00, 20, '2026-08-20 09:10:00', '2026-08-20 09:10:00');

INSERT INTO activity_history (historyable_type, historyable_id, action, performed_by, from_status, to_status, comment, created_at)
VALUES ('App\\Models\\ActivityBudget', @budget1, 'created', 2, NULL, 'draft', 'Budget draft started.', '2026-08-20 09:10:00');


-- ============================================================================
-- ACTIVITY 2 - Regional Data Quality Workshop
-- Budget: Submitted, then Assigned to user 3, now Under Review.
-- Created by user 2, coordinated by user 3.
-- ============================================================================

INSERT INTO activities (
    reference, activity_type_id, title, purpose, program, location, country, venue,
    start_date, end_date, participant_count, coordinator_id, description,
    currency, status, created_by, created_at, updated_at
) VALUES (
    'ACT/2026/00002', @type_workshop, 'Regional Data Quality Workshop',
    'Workshop for regional M&E focal points on the updated data quality checklist.',
    'NNL Knowledge Base', 'Arusha', 'Tanzania', 'Arusha Conference Centre',
    '2026-10-20', '2026-10-21', 25, 3,
    'Two-day workshop on data quality standards ahead of the Q4 reporting cycle.',
    'USD', 'active', 2, '2026-08-25 10:00:00', '2026-08-27 14:00:00'
);
SET @activity2 = LAST_INSERT_ID();

INSERT INTO activity_budgets (
    activity_id, version, is_current, budget_code, status, created_by, current_assignee_id,
    currency, total_cash, total_invoice, total_overall, requested_advance_amount,
    submitted_at, created_at, updated_at
) VALUES (
    @activity2, 1, 1, 'RDQ-2026-01', 'under_review', 2, 3,
    'USD', 800.00, 200.00, 1000.00, 1000.00,
    '2026-08-27 08:00:00', '2026-08-25 10:05:00', '2026-08-27 14:00:00'
);
SET @budget2 = LAST_INSERT_ID();

INSERT INTO activity_budget_items (
    activity_budget_id, budget_category_id, budget_component_id, item_code, description,
    qty, unit, unit_cost, frequency, total, payment_mode, cash_amount, invoice_amount,
    sort_order, created_at, updated_at
) VALUES
(@budget2, @cat_transport, @comp_local, 'A1', 'Local transport for facilitators',
    4.00, 'trip', 40.00, 5, 800.00, 'cash', 800.00, 0.00, 10, '2026-08-25 10:05:00', '2026-08-25 10:05:00'),
(@budget2, @cat_visa, @comp_visa, 'B1', 'Visa fees for regional facilitators',
    2.00, 'person', 100.00, 1, 200.00, 'invoice', 0.00, 200.00, 20, '2026-08-25 10:05:00', '2026-08-25 10:05:00');

INSERT INTO activity_assignments (assignmentable_type, assignmentable_id, assigned_from, assigned_to, assigned_by, comment, status_at_assignment, created_at)
VALUES ('App\\Models\\ActivityBudget', @budget2, NULL, 3, 2, 'Please review the transport and visa figures.', 'submitted', '2026-08-27 08:05:00');

INSERT INTO activity_history (historyable_type, historyable_id, action, performed_by, from_status, to_status, comment, created_at) VALUES
('App\\Models\\ActivityBudget', @budget2, 'submitted', 2, 'draft', 'submitted', NULL, '2026-08-27 08:00:00'),
('App\\Models\\ActivityBudget', @budget2, 'assigned', 2, 'submitted', 'assigned', 'Please review the transport and visa figures.', '2026-08-27 08:05:00'),
('App\\Models\\ActivityBudget', @budget2, 'review_started', 3, 'assigned', 'under_review', NULL, '2026-08-27 14:00:00');


-- ============================================================================
-- ACTIVITY 3 - International Conference Travel - Nairobi
-- Budget: Approved. Retirement: Draft, in progress (2 of 3 lines filled in,
-- one receipt attached to the airport-transfer line).
-- Created by user 3, coordinated by user 4, budget approved by user 4.
-- ============================================================================

INSERT INTO activities (
    reference, activity_type_id, title, purpose, program, location, country, venue,
    start_date, end_date, participant_count, coordinator_id, description,
    currency, status, created_by, created_at, updated_at
) VALUES (
    'ACT/2026/00003', @type_travel, 'International Conference Travel - Nairobi',
    'Staff travel to represent Praxis at the East Africa Health Systems Conference.',
    NULL, 'Nairobi', 'Kenya', 'Kenyatta International Convention Centre',
    '2026-08-18', '2026-08-22', 1, 4,
    'Single delegate attending on behalf of the LIFT programme team.',
    'USD', 'active', 3, '2026-08-10 08:00:00', '2026-08-15 11:00:00'
);
SET @activity3 = LAST_INSERT_ID();

INSERT INTO activity_budgets (
    activity_id, version, is_current, budget_code, status, created_by,
    currency, total_cash, total_invoice, total_overall, requested_advance_amount,
    approved_by, approved_at, approval_comment, submitted_at, created_at, updated_at
) VALUES (
    @activity3, 1, 1, 'NBO-CONF-2026', 'approved', 3,
    'USD', 290.00, 600.00, 890.00, 890.00,
    4, '2026-08-15 11:00:00', 'Approved as submitted.', '2026-08-12 09:00:00',
    '2026-08-10 08:05:00', '2026-08-15 11:00:00'
);
SET @budget3 = LAST_INSERT_ID();

INSERT INTO activity_budget_items (
    activity_budget_id, budget_category_id, budget_component_id, item_code, description,
    qty, unit, unit_cost, frequency, total, payment_mode, cash_amount, invoice_amount,
    sort_order, created_at, updated_at
) VALUES
(@budget3, @cat_transport, @comp_airport, 'A1', 'Airport transfer - Nairobi',
    1.00, 'trip', 120.00, 2, 240.00, 'cash', 240.00, 0.00, 10, '2026-08-10 08:05:00', '2026-08-10 08:05:00'),
(@budget3, @cat_accommodation, @comp_hotel, 'B1', 'Hotel accommodation (4 nights)',
    1.00, 'night', 150.00, 4, 600.00, 'invoice', 0.00, 600.00, 20, '2026-08-10 08:05:00', '2026-08-10 08:05:00'),
(@budget3, @cat_visa, @comp_visa, 'C1', 'Kenya visa fee',
    1.00, 'person', 50.00, 1, 50.00, 'cash', 50.00, 0.00, 30, '2026-08-10 08:05:00', '2026-08-10 08:05:00');
SET @b3_item_airport = (SELECT id FROM activity_budget_items WHERE activity_budget_id = @budget3 AND item_code = 'A1');
SET @b3_item_hotel   = (SELECT id FROM activity_budget_items WHERE activity_budget_id = @budget3 AND item_code = 'B1');
SET @b3_item_visa    = (SELECT id FROM activity_budget_items WHERE activity_budget_id = @budget3 AND item_code = 'C1');

INSERT INTO activity_history (historyable_type, historyable_id, action, performed_by, from_status, to_status, comment, created_at) VALUES
('App\\Models\\ActivityBudget', @budget3, 'submitted', 3, 'draft', 'submitted', NULL, '2026-08-12 09:00:00'),
('App\\Models\\ActivityBudget', @budget3, 'approved', 4, 'submitted', 'approved', 'Approved as submitted.', '2026-08-15 11:00:00');

INSERT INTO activity_retirements (
    reference, activity_budget_id, status, created_by,
    total_actual_cash, total_actual_invoice, total_actual_overall, total_advanced,
    unspent_advance_amount, reimbursement_due_amount, created_at, updated_at
) VALUES (
    'RET/2026/00001', @budget3, 'draft', 3,
    260.00, 600.00, 860.00, 890.00,
    30.00, 0.00, '2026-09-01 09:00:00', '2026-09-03 10:00:00'
);
SET @retirement3 = LAST_INSERT_ID();

INSERT INTO activity_retirement_items (
    activity_retirement_id, activity_budget_item_id, actual_qty, actual_unit_cost, actual_frequency,
    actual_cash_amount, actual_invoice_amount, actual_total, actual_payment_mode,
    variance_justification, created_at, updated_at
) VALUES
(@retirement3, @b3_item_airport, 1.00, 130.00, 2, 260.00, 0.00, 260.00, 'cash',
    'Taxi fare increased due to surge pricing on arrival.', '2026-09-01 09:00:00', '2026-09-03 10:00:00'),
(@retirement3, @b3_item_hotel, 1.00, 150.00, 4, 0.00, 600.00, 600.00, 'invoice',
    NULL, '2026-09-01 09:00:00', '2026-09-03 10:00:00'),
(@retirement3, @b3_item_visa, NULL, NULL, NULL, 0.00, 0.00, 0.00, NULL,
    NULL, '2026-09-01 09:00:00', '2026-09-01 09:00:00');
SET @r3_item_airport = (SELECT id FROM activity_retirement_items WHERE activity_retirement_id = @retirement3 AND activity_budget_item_id = @b3_item_airport);

-- Note: original_filename/stored_path below are illustrative only - no
-- actual file exists on disk at that path, so the "Download" button on
-- this dummy row will 404. Re-upload a real file over it from the UI if
-- you need a working download for testing.
INSERT INTO retirement_documents (
    activity_retirement_id, activity_retirement_item_id, document_type, vendor,
    document_amount, currency, original_filename, stored_path, uploaded_by, created_at, updated_at
) VALUES (
    @retirement3, @r3_item_airport, 'receipt', 'Nairobi Taxi Co.',
    260.00, 'USD', 'taxi-receipt.jpg', 'retirement-documents/dummy/taxi-receipt.jpg', 3, '2026-09-01 09:15:00', '2026-09-01 09:15:00'
);

INSERT INTO activity_history (historyable_type, historyable_id, action, performed_by, from_status, to_status, comment, created_at) VALUES
('App\\Models\\ActivityRetirement', @retirement3, 'started', 3, NULL, 'draft', 'Retirement started against the approved advance.', '2026-09-01 09:00:00');


-- ============================================================================
-- ACTIVITY 4 - LIFT Platform Refresher Training
-- Budget: Approved. Retirement: Approved and Closed, fully reconciled -
-- actual spend came in over the advance, so a reimbursement is due.
-- Created by user 4, coordinated by user 1, approved by user 2, closed by user 1.
-- ============================================================================

INSERT INTO activities (
    reference, activity_type_id, title, purpose, program, location, country, venue,
    start_date, end_date, participant_count, coordinator_id, description,
    currency, status, created_by, created_at, updated_at
) VALUES (
    'ACT/2026/00004', @type_training, 'LIFT Platform Refresher Training',
    'Refresher training for LIFT platform focal points on the updated reporting workflow.',
    'LIFT Platform', 'Dodoma', 'Tanzania', 'Praxis Training Centre',
    '2026-07-14', '2026-07-15', 20, 1,
    'Two-day refresher for existing LIFT focal points, ahead of the platform update rollout.',
    'TZS', 'completed', 4, '2026-07-01 08:00:00', '2026-07-25 16:00:00'
);
SET @activity4 = LAST_INSERT_ID();

INSERT INTO activity_budgets (
    activity_id, version, is_current, budget_code, status, created_by,
    currency, total_cash, total_invoice, total_overall, requested_advance_amount,
    approved_by, approved_at, approval_comment, submitted_at, created_at, updated_at
) VALUES (
    @activity4, 1, 1, 'LIFT-REF-2026-01', 'approved', 4,
    'TZS', 240000.00, 240000.00, 480000.00, 480000.00,
    2, '2026-07-05 15:00:00', 'Approved as submitted.', '2026-07-03 09:00:00',
    '2026-07-01 08:05:00', '2026-07-05 15:00:00'
);
SET @budget4 = LAST_INSERT_ID();

INSERT INTO activity_budget_items (
    activity_budget_id, budget_category_id, budget_component_id, item_code, description,
    qty, unit, unit_cost, frequency, total, payment_mode, cash_amount, invoice_amount,
    sort_order, created_at, updated_at
) VALUES
(@budget4, @cat_materials, @comp_materials, 'A1', 'LIFT refresher training materials',
    30.00, 'set', 8000.00, 1, 240000.00, 'cash', 240000.00, 0.00, 10, '2026-07-01 08:05:00', '2026-07-01 08:05:00'),
(@budget4, @cat_accommodation, @comp_perdiem, 'B1', 'Facilitator per diem (2 days)',
    2.00, 'person', 60000.00, 2, 240000.00, 'invoice', 0.00, 240000.00, 20, '2026-07-01 08:05:00', '2026-07-01 08:05:00');
SET @b4_item_materials = (SELECT id FROM activity_budget_items WHERE activity_budget_id = @budget4 AND item_code = 'A1');
SET @b4_item_perdiem   = (SELECT id FROM activity_budget_items WHERE activity_budget_id = @budget4 AND item_code = 'B1');

INSERT INTO activity_history (historyable_type, historyable_id, action, performed_by, from_status, to_status, comment, created_at) VALUES
('App\\Models\\ActivityBudget', @budget4, 'submitted', 4, 'draft', 'submitted', NULL, '2026-07-03 09:00:00'),
('App\\Models\\ActivityBudget', @budget4, 'approved', 2, 'submitted', 'approved', 'Approved as submitted.', '2026-07-05 15:00:00');

INSERT INTO activity_retirements (
    reference, activity_budget_id, status, created_by,
    submitted_at, approved_by, approved_at,
    total_actual_cash, total_actual_invoice, total_actual_overall, total_advanced,
    unspent_advance_amount, reimbursement_due_amount,
    closed_by, closed_at, created_at, updated_at
) VALUES (
    'RET/2026/00002', @budget4, 'closed', 4,
    '2026-07-20 10:00:00', 2, '2026-07-22 09:00:00',
    234000.00, 260000.00, 494000.00, 480000.00,
    0.00, 14000.00,
    1, '2026-07-25 16:00:00', '2026-07-18 10:00:00', '2026-07-25 16:00:00'
);
SET @retirement4 = LAST_INSERT_ID();

INSERT INTO activity_retirement_items (
    activity_retirement_id, activity_budget_item_id, actual_qty, actual_unit_cost, actual_frequency,
    actual_cash_amount, actual_invoice_amount, actual_total, actual_payment_mode,
    variance_justification, created_at, updated_at
) VALUES
(@retirement4, @b4_item_materials, 30.00, 7800.00, 1, 234000.00, 0.00, 234000.00, 'cash',
    NULL, '2026-07-18 10:00:00', '2026-07-20 09:00:00'),
(@retirement4, @b4_item_perdiem, 2.00, 65000.00, 2, 0.00, 260000.00, 260000.00, 'invoice',
    'Per diem rate revised upward per the updated policy circular effective July.', '2026-07-18 10:00:00', '2026-07-20 09:00:00');
SET @r4_item_perdiem = (SELECT id FROM activity_retirement_items WHERE activity_retirement_id = @retirement4 AND activity_budget_item_id = @b4_item_perdiem);

-- Same caveat as Activity 3's receipt: these stored_path values are
-- illustrative placeholders, not real files on disk.
INSERT INTO retirement_documents (
    activity_retirement_id, activity_retirement_item_id, document_type,
    original_filename, stored_path, uploaded_by, created_at, updated_at
) VALUES (
    @retirement4, NULL, 'attendance_sheet',
    'attendance-sheet.pdf', 'retirement-documents/dummy/attendance-sheet.pdf', 4, '2026-07-18 10:10:00', '2026-07-18 10:10:00'
);

INSERT INTO retirement_documents (
    activity_retirement_id, activity_retirement_item_id, document_type, vendor,
    receipt_number, document_amount, currency, original_filename, stored_path, uploaded_by, created_at, updated_at
) VALUES (
    @retirement4, @r4_item_perdiem, 'invoice', 'Grand Hotel Dodoma',
    'INV-2291', 260000.00, 'TZS', 'per-diem-invoice.pdf', 'retirement-documents/dummy/per-diem-invoice.pdf', 4, '2026-07-18 10:15:00', '2026-07-18 10:15:00'
);

INSERT INTO activity_reconciliations (
    activity_retirement_id, type, amount, currency, reference, transaction_date, notes, recorded_by, created_at, updated_at
) VALUES (
    @retirement4, 'reimbursement', 14000.00, 'TZS', 'REIMB-2026-0001', '2026-07-25',
    'Reimbursement paid to the creator for the per-diem rate increase.', 1, '2026-07-25 16:00:00', '2026-07-25 16:00:00'
);

INSERT INTO activity_history (historyable_type, historyable_id, action, performed_by, from_status, to_status, comment, created_at) VALUES
('App\\Models\\ActivityRetirement', @retirement4, 'started', 4, NULL, 'draft', 'Retirement started against the approved advance.', '2026-07-18 10:00:00'),
('App\\Models\\ActivityRetirement', @retirement4, 'submitted', 4, 'draft', 'submitted', NULL, '2026-07-20 10:00:00'),
('App\\Models\\ActivityRetirement', @retirement4, 'approved', 2, 'submitted', 'approved', 'Approved - reimbursement due to the creator.', '2026-07-22 09:00:00'),
('App\\Models\\ActivityRetirement', @retirement4, 'closed', 1, 'reconciliation_pending', 'closed', 'Reimbursement paid and reconciled.', '2026-07-25 16:00:00');


COMMIT;

-- ============================================================================
-- CLEANUP - uncomment and run this block to remove everything this script
-- added (master data is left alone, since it may be shared with real data
-- you've since created on top of it).
-- ============================================================================
-- DELETE FROM activity_reconciliations WHERE activity_retirement_id IN (SELECT id FROM activity_retirements WHERE reference IN ('RET/2026/00001','RET/2026/00002'));
-- DELETE FROM retirement_documents WHERE activity_retirement_id IN (SELECT id FROM activity_retirements WHERE reference IN ('RET/2026/00001','RET/2026/00002'));
-- DELETE FROM activity_retirement_items WHERE activity_retirement_id IN (SELECT id FROM activity_retirements WHERE reference IN ('RET/2026/00001','RET/2026/00002'));
-- DELETE FROM activity_history WHERE historyable_type = 'App\\Models\\ActivityRetirement' AND historyable_id IN (SELECT id FROM activity_retirements WHERE reference IN ('RET/2026/00001','RET/2026/00002'));
-- DELETE FROM activity_retirements WHERE reference IN ('RET/2026/00001','RET/2026/00002');
-- DELETE FROM activity_history WHERE historyable_type = 'App\\Models\\ActivityBudget' AND historyable_id IN (SELECT id FROM activity_budgets WHERE activity_id IN (SELECT id FROM activities WHERE reference IN ('ACT/2026/00001','ACT/2026/00002','ACT/2026/00003','ACT/2026/00004')));
-- DELETE FROM activity_assignments WHERE assignmentable_type = 'App\\Models\\ActivityBudget' AND assignmentable_id IN (SELECT id FROM activity_budgets WHERE activity_id IN (SELECT id FROM activities WHERE reference IN ('ACT/2026/00001','ACT/2026/00002','ACT/2026/00003','ACT/2026/00004')));
-- DELETE FROM activity_budget_items WHERE activity_budget_id IN (SELECT id FROM activity_budgets WHERE activity_id IN (SELECT id FROM activities WHERE reference IN ('ACT/2026/00001','ACT/2026/00002','ACT/2026/00003','ACT/2026/00004')));
-- DELETE FROM activity_budgets WHERE activity_id IN (SELECT id FROM activities WHERE reference IN ('ACT/2026/00001','ACT/2026/00002','ACT/2026/00003','ACT/2026/00004'));
-- DELETE FROM activities WHERE reference IN ('ACT/2026/00001','ACT/2026/00002','ACT/2026/00003','ACT/2026/00004');
