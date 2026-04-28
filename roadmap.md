# Assignment LID — Development Roadmap

**Status:** Planning / Pre-Development  
**Target Launch:** Week 11 (v0.1.0 MVP)  
**Current Phase:** Phase 0 — Setup & Scaffolding

---

## Development Timeline

### Week 1: Setup & Scaffolding
**Status:** ✅ IN PROGRESS  
**Goal:** Create foundational structure and development environment

- [x] Create GitHub repository
- [x] Write comprehensive README.md
- [x] Define project scope and boundaries
- [ ] Create plugin directory structure
- [ ] Define version.php metadata
- [ ] Create db/install.xml schema
- [ ] Set up language strings skeleton
- [ ] Set up local Moodle testing environment
- [ ] Create basic plugin registration (visible in assignment settings)

### Week 2-3: Core Analysis Engine
**Status:** ⏳ NOT STARTED  
**Goal:** Build the heart of the plugin — LLM integration and analysis logic

- [ ] Implement gemini_client.php (reuse from local_lid)
- [ ] Create assignment-analyzer-prompt.md v1.0
- [ ] Implement prompt_builder.php:
  - [ ] Text submission extraction
  - [ ] PDF text extraction (pdftotext integration)
  - [ ] Rubric data parsing
  - [ ] Competency data fetching
  - [ ] Template substitution
- [ ] Implement analyzer.php (orchestrator):
  - [ ] Queue job creation logic
  - [ ] LLM API call wrapper
  - [ ] JSON response parsing
  - [ ] Database storage
- [ ] Create scheduled task process_queue.php
- [ ] Test: Single text submission end-to-end

**Deliverable:** Working analysis of one text submission with JSON output

### Week 4: Dashboard Views
**Status:** ⏳ NOT STARTED  
**Goal:** Create instructor-facing interfaces to view results

- [ ] Implement assignment-level dashboard
  - [ ] Renderable class (assignment_dashboard.php)
  - [ ] Mustache template
  - [ ] Summary statistics logic
  - [ ] Student list table
- [ ] Implement course-level dashboard
  - [ ] Renderable class (course_dashboard.php)
  - [ ] Competency progression timeline
  - [ ] Assignment summary table
- [ ] Implement student-level dashboard
  - [ ] Renderable class (student_dashboard.php)
  - [ ] Competency radar chart
  - [ ] Assignment timeline
- [ ] Create AMD module dashboard.js for interactivity
- [ ] Apply LID design system (styles.css)

**Deliverable:** Three functional dashboards with sample data

### Week 5: UI Integration
**Status:** ⏳ NOT STARTED  
**Goal:** Embed LID into Moodle's assignment grading workflow

- [ ] Add "LID Analysis" column to grading table
- [ ] Add analysis panel to single student view
- [ ] Add LID settings section to assignment form
- [ ] Implement "Analyze" button functionality
- [ ] Implement "Batch Analyze" action
- [ ] Add status indicators (analyzed/pending/failed)
- [ ] Test: Complete instructor workflow (create → enable → grade → analyze → view)

**Deliverable:** Seamless integration in grading interface

### Week 6: Rubric & Competency Integration
**Status:** ⏳ NOT STARTED  
**Goal:** Deep integration with Moodle's assessment frameworks

- [ ] Implement rubric_parser.php (support rubric + marking guide)
- [ ] Implement competency_mapper.php (fetch course competencies)
- [ ] Extend prompt to include rubric criteria in structured format
- [ ] Extend prompt to include competency definitions
- [ ] Update JSON output schema to match v1.0 spec
- [ ] Test: Analyze submission with both rubric and competencies enabled

**Deliverable:** Full rubric and competency analysis in JSON output

### Week 7: Error Handling & Edge Cases
**Status:** ⏳ NOT STARTED  
**Goal:** Robust handling of failures and invalid inputs

- [ ] Implement retry logic (max 3 attempts with backoff)
- [ ] Handle empty submission gracefully
- [ ] Handle PDF extraction failures
- [ ] Handle LLM API timeouts
- [ ] Handle invalid JSON responses
- [ ] Handle rate limit errors
- [ ] Create user-friendly error messages
- [ ] Test all error scenarios systematically

**Deliverable:** Plugin handles failures gracefully without data loss

### Week 8: GDPR & Privacy Compliance
**Status:** ⏳ NOT STARTED  
**Goal:** Full compliance with data protection regulations

- [ ] Implement privacy/provider.php
- [ ] Implement get_metadata() for data registry
- [ ] Implement export_user_data() for data export
- [ ] Implement delete_data_for_user() for right to deletion
- [ ] Test data export functionality
- [ ] Test user deletion removes analysis data
- [ ] Add privacy policy link to settings
- [ ] Document data flows and retention

**Deliverable:** GDPR-compliant data handling

### Week 9: Documentation & Release Prep
**Status:** ⏳ NOT STARTED  
**Goal:** Comprehensive documentation for users and developers

- [ ] Write instructor guide (docs/USER_GUIDE.md)
- [ ] Write admin guide (docs/ADMIN_GUIDE.md)
- [ ] Write developer guide (docs/DEVELOPER_GUIDE.md)
- [ ] Write installation guide (docs/INSTALLATION.md)
- [ ] Create demo video (5-min walkthrough)
- [ ] Write blog post for learning-intelligence.dev
- [ ] Prepare Moodle plugins directory submission
- [ ] Create release notes

**Deliverable:** Complete documentation suite

### Week 10: Beta Testing
**Status:** ⏳ NOT STARTED  
**Goal:** Real-world validation with pilot users

- [ ] Deploy to lms.cucorn.com production
- [ ] Onboard 2-3 pilot instructors
- [ ] Monitor usage and collect feedback
- [ ] Iterate on prompt based on output quality feedback
- [ ] Fix bugs identified during testing
- [ ] Validate cost projections with real data
- [ ] Performance testing (30-100 student cohorts)

**Deliverable:** Validated, production-ready plugin

### Week 11: v0.1.0 Release
**Status:** ⏳ NOT STARTED  
**Goal:** Official MVP release

- [ ] Finalize all code
- [ ] Tag v0.1.0 in GitHub
- [ ] Create GitHub release with changelog
- [ ] Submit to Moodle plugins directory
- [ ] Publish blog post
- [ ] Announce in Moodle forums
- [ ] Announce in LID community
- [ ] Monitor initial adoption

**Deliverable:** Public release of Assignment LID v0.1.0

---

## Success Criteria (MVP Completion)

### Technical Validation
- [ ] Plugin installs cleanly on Moodle 5.1 and 4.5
- [ ] Analysis success rate >95%
- [ ] Queue processes ≥3 analyses/minute
- [ ] Average analysis time <30 seconds
- [ ] API cost <$0.005 per analysis
- [ ] Zero data loss in processing

### Usability Validation
- [ ] Instructors can analyze in <3 clicks
- [ ] Dashboard loads in <2 seconds (100-student cohort)
- [ ] Pilot instructors rate output "useful" or better (>80%)
- [ ] Zero GDPR complaints

### Adoption Validation (6 months post-release)
- [ ] 10+ Moodle sites install plugin
- [ ] 100+ analyses run across installations
- [ ] 5+ GitHub stars
- [ ] 2+ external contributors

---

## Future Phases (Post-v0.1.0)

### v0.2.0 — Enhanced Workflows (Q3 2026)
- Multi-file submission support
- Additional file types (.docx, .odt)
- Student-facing feedback view
- CSV export functionality
- "Accept suggested scores" workflow
- Auto-analysis on submission (opt-in)

### v0.3.0 — Ecosystem Integration (Q4 2026)
- xAPI statement generation
- LRS integration via Ralph
- Peer review analysis
- Student portfolio PDF export
- Simple/direct grading support
- Real-time streaming display

### v1.0.0 — Production-Grade (2027)
- Multi-language support
- Advanced analytics dashboard
- Instructor calibration tools
- Batch re-analysis workflows
- API for external integrations
- White-label deployment options

---

## Risk Management

### High-Priority Risks

**Risk:** LLM output quality inconsistent  
**Mitigation:** Extensive prompt engineering in Week 2-3; beta testing in Week 10

**Risk:** PDF extraction fails on certain file types  
**Mitigation:** Test multiple PDF generators; fallback to manual text copy; clear error messages

**Risk:** Queue processing too slow for large cohorts  
**Mitigation:** Optimize query patterns; consider parallel processing in v0.2.0

**Risk:** API costs exceed projections  
**Mitigation:** Cost tracking from day 1; alerts at admin level; clear communication with users

**Risk:** GDPR compliance gaps  
**Mitigation:** Privacy provider implementation in Week 8; external audit before release

### Medium-Priority Risks

**Risk:** Rubric parsing breaks on complex configurations  
**Mitigation:** Test all rubric types in Week 6; document unsupported edge cases

**Risk:** User adoption lower than expected  
**Mitigation:** Strong documentation; demo video; active community engagement

**Risk:** Moodle API changes break plugin  
**Mitigation:** Test on both 4.5 LTS and latest; CI/CD for Moodle upgrades

---

## Dependencies & Blockers

### External Dependencies
- Google AI Studio API availability (monitored)
- Moodle core assignment APIs (stable)
- pdftotext library availability (documented alternative: smalot/pdfparser)

### Internal Dependencies
- Local Moodle test environment (Week 1)
- Access to lms.cucorn.com staging (Week 2)
- Pilot instructors identified (Week 9)

### No Current Blockers ✅

---

## Communication Plan

### Weekly Status Updates
- Progress against phase goals
- Blockers and risks
- Next week's objectives
- Demo of completed features

### Milestone Demos
- End of Phase 1 (Week 3): Core analysis working
- End of Phase 2 (Week 4): Dashboards functional
- End of Phase 5 (Week 7): Error handling complete
- End of Phase 10 (Week 10): Beta deployment

### Release Announcement (Week 11)
- Blog post on learning-intelligence.dev
- Moodle forum announcement
- GitHub release notes
- Email to pilot users
- Social media (LinkedIn, Twitter)

---

**Last Updated:** 2026-04-27  
**Next Review:** Week 2 (upon completion of Phase 1)
