# Database Schema Audit

This audit maps the current PHP workflow to the MySQL tables that are still actively used.

## Core Tables To Keep

- `admin`: admin login and password changes.
- `users`: patient/user registration, login, profile, and billing identity.
- `userlog`: patient login audit trail.
- `doctorspecialization`: doctor specialization master data.
- `doctors`: doctor login, profile, availability, appointment ownership.
- `doctorslog`: doctor login audit trail.
- `patients`: active patient registry for consultancy and admitted patients.
- `current_appointments`: active appointment workflow from booking through visit handling.
- `past_appointments`: archived appointment history for cancelled, completed, or transferred visits.
- `prescriptions`: structured prescription records and printable prescription PDFs.
- `appointment_transfers`: consultancy-to-admitted transfer history.
- `payment_transactions`: Razorpay and hospital-collected payment audit trail.
- `contact_queries`: active contact/helpdesk queries from user and doctor portals.
- `contact_query_history`: disposed/closed contact query history.
- `feedback_entries`: feedback submissions received by admin.

## Legacy Tables Removed From The Canonical Schema

- `appointment`: legacy single-table appointment model. The live workflow now uses `current_appointments` plus `past_appointments`.
- `tblpatient`: legacy patient registry. The live workflow now uses `patients`.
- `tblmedicalhistory`: unused legacy medical history table. Related pages are already disabled in the app.
- `tblcontactus`: unused legacy contact module. The current app uses `contact_queries` and `contact_query_history`.

## Dump Findings

The export file `C:/Users/Mahir Kachwala/Downloads/b10_41663109_HMS (1).sql` contains no `INSERT INTO` statements for:

- `appointment`
- `tblpatient`
- `tblmedicalhistory`
- `tblcontactus`

That means these tables are present in the dump structure but empty in the exported data.

## Code Cleanup Completed

The remaining active PHP screens that still referenced legacy tables were updated so the app now prefers the current schema:

- `admin/dashboard.php`
- `admin/patient-search.php`
- `admin/betweendates-detailsreports.php`
- `admin/edit-appointment.php`
- `doctor/check_availability.php`
- `doctor/edit-patient.php`
- `doctor/view-patient.php`

## Practical Presentation Summary

If you want to explain the database tomorrow, you can now present it as:

- user/admin/doctor accounts and logs
- patient registry
- active appointments
- archived appointment history
- prescriptions
- payment transactions
- contact queries and feedback
- appointment transfers for admitted cases
