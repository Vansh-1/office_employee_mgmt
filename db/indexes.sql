-- Recommended indexes for performance
-- Attendance queries by date range and employee
CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(date);
CREATE INDEX IF NOT EXISTS idx_attendance_emp_date ON attendance(emp_id, date);

-- Payslips lookups
CREATE INDEX IF NOT EXISTS idx_payslips_emp_year_month ON payslips(employee_id, year, month);

-- Announcements reads
CREATE INDEX IF NOT EXISTS idx_announcement_reads_emp ON announcement_reads(employee_id, read_at);
CREATE INDEX IF NOT EXISTS idx_announcements_created ON announcements(created_at);

-- Employees common lookups
CREATE INDEX IF NOT EXISTS idx_employees_dept ON employees(department_id);

-- Leave applications management
CREATE INDEX IF NOT EXISTS idx_leave_applications_created ON leave_applications(created_at);
CREATE INDEX IF NOT EXISTS idx_leave_applications_status ON leave_applications(status);
