# Expense Tracker - Improvement TODO

## Current Status: Implementing Improvements for Error-Free Add Expense & Overall Polish

### Phase 1: Security & Validation [✅ COMPLETED]
- [x] 1. Add CSRF token generation/validation
  - Updated config.php: Added csrf_token() helper
  - Updated dashboard.php: Included CSRF meta tag
  - Updated api.php: Validates CSRF on all POST
  - Updated script.js: Includes CSRF in all apiCall()

### Phase 2: Fix & Improve Add Expense [✅ COMPLETED]
- [x] 2. Server-side validation improvements (api.php)
  - Validates category belongs to user
  - Amount >= 0.01, valid Y-m-d date format
  - Specific error messages, DB error logging
- [x] 3. Client-side validation & UX (script.js)
  - Real-time form validation before submit
  - Loading spinner during submit
  - Better error display via toast

**Phase 1 & 2 COMPLETE. Add expense now fully validated, secure, with great UX.**

### Phase 3: UX Enhancements [TODO]
- [ ] 4. Loading states for all API calls (script.js, style.css) - PARTIAL (forms done)
- [ ] 5. Expense search & filter (script.js, dashboard.php)
- [ ] 6. Confirm dialogs for delete/edit - EXISTING
- [ ] 7. Pagination for expense list

### Phase 4: New Features [TODO]
- [ ] 8. Export expenses to CSV
- [ ] 9. Quick-add expense from dashboard
- [ ] 10. Dark mode toggle

**Progress: 3/14 complete**

### Phase 3: UX Enhancements [TODO]
- [ ] 4. Loading states for all API calls (script.js, style.css)
- [ ] 5. Expense search & filter (script.js, dashboard.php)
- [ ] 6. Confirm dialogs for delete/edit
- [ ] 7. Pagination for expense list

### Phase 4: New Features [TODO]
- [ ] 8. Export expenses to CSV
- [ ] 9. Quick-add expense from dashboard
- [ ] 10. Dark mode toggle

### Phase 5: Testing & Final Polish [TODO]
- [ ] 11. Full end-to-end testing
- [ ] 12. Error logging (PHP)
- [ ] 13. Performance optimizations
- [ ] 14. Update README.md

**Next Step: Phase 1 & 2 (CSRF + Add Expense fixes)**

Progress: 0/14 complete
