# Phase 2 Checklist - Public Demo and Landing Page

## Current status
- Landing page rebranded with marketing copy and demo waitlist CTA.
- Email capture endpoint created (`POST /api/lead-capture`).
- CORS patched for live + demo frontends.
- Demo environment template added (`.env.demo.example`).
- Live environment template added (`.env.live.example`).

## Checklist
### 1) Public landing page (live + demo)
- [ ] Build frontend with correct API base URL
  - Live: `VITE_API_BASE_URL=https://api.vegrohr.invodtech.ltd`
  - Demo: `VITE_API_BASE_URL=https://api-demo.vegrohr.invodtechltd.com`
- [ ] Upload `dist/` to the correct webroot
- [ ] Verify landing page loads on both domains
- [ ] Verify waitlist form submits successfully

### 2) Lead capture
- [ ] Run migrations to create `lead_captures` table
- [ ] Test: submit the waitlist form and confirm DB entry
- [ ] Optional: add email notification to `info@invodtechltd.com` or `invodtech@gmail.com`

### 3) Demo environment (separate from live)
- [ ] Ensure demo API uses `TENANT_ENVIRONMENT=demo`
- [ ] Create demo database `vegro_hr_demo`
- [ ] Import/seed demo data
- [ ] Verify demo login works and data is isolated

### 4) Live environment
- [ ] Ensure live API uses `TENANT_ENVIRONMENT=production`
- [ ] Create live database `vegro_hr_live`
- [ ] Import/seed live data
- [ ] Verify live login and core flows

### 5) Hosting constraints (no cPanel terminal)
- [ ] Generate `APP_KEY` locally and paste into each `.env`
- [ ] Run migrations locally and export DB
- [ ] Import DB via phpMyAdmin (demo + live)
- [ ] Copy `storage/app/public` to `public/storage` manually
- [ ] Skip caches (`config:cache`, `route:cache`) if no terminal

### 6) Go-live verification
- [ ] Login works on demo + live
- [ ] CSV import/export works (employees, payroll, attendance)
- [ ] Reports/Dashboards load
- [ ] Lead capture form writes to DB

### 7) Marketing (LinkedIn)
- [ ] Post 1: HR leaders
- [ ] Post 2: Finance and payroll
- [ ] Post 3: Founders
- [ ] Post 4: MDs
- [ ] Link to landing page and waitlist
