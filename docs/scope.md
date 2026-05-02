# Assignment LID Plugin — Instructional Scope Document
**Plugin Name:** `assignment_lid`  
**Epic:** Learning Intelligence Dashboard (LID)  
**Version:** 0.1.0 (Initial Development)  
**Target Moodle:** 4.5+ (Testing: 5.1 and 4.5)  
**Created:** 2026-04-27  
**Status:** Planning / Pre-Development

---

## Executive Summary

The `assignment_lid` plugin extends the LID ecosystem into Moodle's assignment workflow, providing instructors with AI-powered analysis of student written submissions. It evaluates submissions against course competencies and advanced grading methods (rubrics, marking guides), generating structured assessment reports that surface learning depth, competency evidence, and formative feedback insights.

**Key Differentiator:** Unlike `local_lid` (which analyzes forum discourse), `assignment_lid` analyzes **individual written artifacts** — essays, reports, reflections, case analyses — against **instructor-defined assessment criteria** and **competency frameworks**, providing both summative scoring insights and formative learning intelligence.

---

## Project Context & Relationship to LID Ecosystem

### LID Ecosystem Architecture (Current State)

```
Learning Intelligence Dashboard Ecosystem
│
├── Browser-Based Tools
│   ├── learning-intelligence-dashboard-v2.html (Portfolio aggregator)
│   └── ai-course-dev-roi-dashboard.html (Course design ROI)
│
├── Moodle Plugins
│   ├── local_lid (Forum participation analysis) ← DEPLOYED (v0.6.0)
│   └── assignment_lid (Assignment submission analysis) ← THIS PROJECT
│
├── Infrastructure Layer
│   ├── Gemini 2.5 Flash (Google AI Studio endpoint)
│   ├── LRS integration (Ralph via xAPI) — planned
│   └── Keycloak SSO (auth.cucorn.com) — in progress
│
└── Analysis Prompts (Markdown-based LLM instructions)
    ├── session-analyzer-prompt.md (Browser dashboard)
    ├── forum-analyzer-prompt.md (local_lid) — implicit in scoring logic
    └── assignment-analyzer-prompt.md (assignment_lid) ← TO BE CREATED
```

### How `assignment_lid` Extends the LID Vision

| Dimension | `local_lid` (Forum) | `assignment_lid` (Assignment) |
|---|---|---|
| **Unit of Analysis** | Student's participation across forum threads | Individual written submission |
| **Assessment Context** | Peer discourse quality, thread advancement, critical thinking | Depth of understanding, competency demonstration, rubric alignment |
| **Data Source** | Forum posts (multi-turn, peer-interactive) | Assignment text box or PDF upload |
| **Grading Integration** | Descriptive only (no grade assignment) | Optional: Suggested rubric scores, competency attainment levels |
| **Primary Use Case** | Formative feedback on discourse skills | Both formative (learning evidence) and summative (grading assistance) |
| **Instructor Workflow** | Batch analysis of all students in forum | On-demand or batch analysis per assignment |

**Shared DNA:**
- Same LLM backend (Gemini 2.5 Flash via Google AI Studio)
- Same queue-based processing architecture
- Same competency framework integration approach
- Same three-tier dashboard pattern (activity-level, course-level, student-level)
- Same audit trail and data retention principles

---

## Problem Statement

### Current Pain Points in Assignment Grading
1. **Rubric scoring is time-intensive** — Instructors spend significant time mapping student work to rubric criteria, especially for large cohorts
2. **Competency evidence is implicit** — Student submissions may demonstrate course competencies, but extracting and documenting this evidence manually is prohibitive
3. **Formative feedback bottleneck** — Detailed developmental feedback (beyond grades) is rarely provided at scale
4. **Cognitive load management** — Reading 30+ essays requires sustained attention; instructors risk inconsistency or fatigue-driven drift
5. **No structured learning intelligence capture** — Rich evidence of student thinking remains locked in unstructured text, unusable for portfolio or transcript enhancement

### What `assignment_lid` Solves
- **Rubric alignment analysis** — LLM evaluates submission against each rubric criterion, suggests scores with evidence excerpts
- **Competency attainment mapping** — Identifies which course competencies are demonstrated in the submission and at what depth (Bloom's level)
- **Formative insight generation** — Surfaces patterns: conceptual gaps, strength areas, next-step recommendations
- **Cognitive offload** — Pre-analysis reduces instructor reading burden; they review AI insights rather than starting from blank slate
- **Portfolio evidence extraction** — Structured JSON output feeds back into LID dashboard ecosystem for longitudinal competency tracking

---

## Plugin Scope & Boundaries

### In Scope (v0.1.0 — MVP)
- [x] Analysis of **text box submissions** (online text assignment type)
- [x] Analysis of **PDF submissions** (file upload assignment type, single PDF only)
- [x] Integration with **Moodle advanced grading methods** (rubric, marking guide — read-only initially)
- [x] Integration with **course competencies** (competency framework analysis)
- [x] **Three-tier dashboard**:
  - Assignment-level view (all students for one assignment)
  - Course-level view (all assignments in course with LID enabled)
  - Student-level view (all LID-analyzed assignments for one student)
- [x] **Queue-based processing** (same architecture as `local_lid`)
- [x] **Analysis results storage** in dedicated table (`mdl_assignsubmission_lid_analysis`)
- [x] **Instructor-facing UI** integrated into assignment grading workflow
- [x] **API cost tracking** per analysis (input/output/thought tokens, cost in USD)
- [x] **Audit trail** for all analyses (timestamp, Moodle userid, submission version)
- [x] **Re-analysis capability** (instructor can trigger re-run on updated submission)

### Explicitly Out of Scope (v0.1.0)
- [ ] Automatic grade assignment (LLM output is advisory only; instructor retains final grading authority)
- [ ] Multi-file submissions (only single PDF or text box; no .docx, .zip, etc.)
- [ ] Plagiarism detection or AI-writing detection (not LID's purpose)
- [ ] Peer review integration (future phase)
- [ ] Integration with Simple or Direct grading (advanced grading only for MVP)
- [ ] Real-time streaming analysis display (batch processing only)
- [ ] Student-facing dashboard (instructor-only in v0.1.0)

### Future Phases (v0.2.0+)
- Support for additional file types (.docx, .odt)
- Multi-file submission support (combine all files into single analysis context)
- Suggested grade assignment with instructor override workflow
- Student-facing feedback view (LLM-generated formative comments)
- Peer review integration (analyze peer feedback quality)
- Grading workflow shortcuts (e.g., "Accept all suggested scores" button)
- Integration with Moodle gradebook directly (not just advanced grading)
- xAPI statement generation for LRS integration

---

## Technical Architecture

### Plugin Type & Structure
**Plugin Type:** `assignsubmission_lid`  
**Reasoning:** Assignment submission plugins integrate directly into the assignment grading interface and can add fields to the submission form.

**Alternative Considered:** `local_lid_assignment` (local plugin with assignment hooks)  
**Rejected Because:** Submission plugins have tighter integration with grading workflow UI; local plugins require more manual hook wiring.

### File Structure
```
moodle/mod/assign/submission/lid/
├── version.php                          # Plugin metadata
├── lang/
│   └── en/
│       └── assignsubmission_lid.php     # Language strings
├── db/
│   ├── install.xml                      # Schema definition
│   ├── upgrade.php                      # Schema migrations
│   └── access.php                       # Capability definitions
├── classes/
│   ├── privacy/                         # GDPR compliance provider
│   │   └── provider.php
│   ├── task/
│   │   └── process_queue.php            # Scheduled task (queue processor)
│   ├── analyzer.php                     # Core analysis orchestrator
│   ├── prompt_builder.php               # Constructs LLM prompt from submission + rubric + competencies
│   ├── gemini_client.php                # API client (reuse from local_lid with abstraction)
│   ├── rubric_parser.php                # Extracts rubric/marking guide data
│   ├── competency_mapper.php            # Fetches course competencies
│   └── output/
│       ├── assignment_dashboard.php     # Renderable: assignment-level view
│       ├── course_dashboard.php         # Renderable: course-level aggregation
│       └── student_dashboard.php        # Renderable: student-level view
├── templates/
│   ├── assignment_dashboard.mustache
│   ├── course_dashboard.mustache
│   ├── student_dashboard.mustache
│   └── analysis_result_card.mustache    # Reusable component
├── amd/src/
│   └── dashboard.js                     # AMD module for dashboard interactivity
├── styles.css                           # Dashboard styling
├── settings.php                         # Admin settings (API key, model selection)
├── lib.php                              # Plugin callbacks and hooks
├── locallib.php                         # Internal helper functions
├── view.php                             # Dashboard entry point
├── process_queue_cli.php                # CLI script for manual queue processing
└── prompts/
    └── assignment-analyzer-prompt.md    # LLM prompt template (markdown)
```

### Database Schema

#### Table: `mdl_assignsubmission_lid_queue`
Queue for pending analyses (same pattern as `local_lid`).

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT | Primary key |
| `assignmentid` | BIGINT | FK to `mdl_assign.id` |
| `submissionid` | BIGINT | FK to `mdl_assign_submission.id` |
| `userid` | BIGINT | FK to `mdl_user.id` (student being analyzed) |
| `status` | VARCHAR(20) | `pending`, `processing`, `completed`, `failed` |
| `priority` | INT | Processing priority (default 0) |
| `attempt` | INT | Retry counter (max 3) |
| `claimed_at` | BIGINT | Timestamp when job claimed by processor |
| `claimed_by` | VARCHAR(255) | Processor instance identifier |
| `created_at` | BIGINT | Queue entry creation timestamp |
| `processed_at` | BIGINT | Completion timestamp |
| `error_message` | TEXT | Error details if `status = failed` |

**Indexes:**
- `assignmentid, userid` (composite, unique per submission version)
- `status, priority, created_at` (queue processing optimization)
- `submissionid` (FK integrity)

#### Table: `mdl_assignsubmission_lid_analysis`
Stores analysis results (one row per analyzed submission).

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT | Primary key |
| `assignmentid` | BIGINT | FK to `mdl_assign.id` |
| `submissionid` | BIGINT | FK to `mdl_assign_submission.id` |
| `userid` | BIGINT | FK to `mdl_user.id` (student) |
| `submission_version` | INT | Submission attempt number (from `mdl_assign_submission.attemptnumber`) |
| `analysis_json` | LONGTEXT | Full JSON output from LLM |
| `analyzed_at` | BIGINT | Timestamp of analysis |
| `analyzed_by_userid` | BIGINT | FK to `mdl_user.id` (instructor who triggered analysis) |
| `api_cost_usd` | DECIMAL(10,6) | API cost for this analysis |
| `input_tokens` | INT | Input token count |
| `output_tokens` | INT | Output token count |
| `thought_tokens` | INT | Thinking token count (Gemini 2.5) |
| `processing_time_ms` | INT | Analysis duration |
| `model_version` | VARCHAR(50) | LLM model identifier (e.g., `gemini-2.5-flash`) |

**Indexes:**
- `assignmentid, userid, submission_version` (composite, unique — enables re-analysis on new submission versions)
- `submissionid` (FK integrity)
- `analyzed_at` (temporal queries)

**Design Decision — Versioning vs. Overwrite:**
Unlike `local_lid` (which had an upsert-overwrite issue), `assignment_lid` uses `submission_version` to support multiple analyses of the same student's work as they resubmit. Each submission attempt gets a new row. Instructors can compare analyses across attempts.

#### Table: `mdl_assignsubmission_lid_rubric_scores`
Suggested rubric scores (optional denormalization for query performance).

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT | Primary key |
| `analysisid` | BIGINT | FK to `mdl_assignsubmission_lid_analysis.id` |
| `criterion_id` | BIGINT | FK to rubric criterion (depends on grading method) |
| `suggested_score` | DECIMAL(10,2) | LLM-suggested score for this criterion |
| `evidence_excerpt` | TEXT | Text excerpt supporting the score |
| `confidence` | VARCHAR(20) | `high`, `medium`, `low` |

**Design Note:** This table is optional for MVP. If rubric scores are stored in `analysis_json` only, we can defer this denormalization until query performance requires it.

#### Table: `mdl_assignsubmission_lid_competency_map`
Competency demonstration evidence (optional denormalization).

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT | Primary key |
| `analysisid` | BIGINT | FK to `mdl_assignsubmission_lid_analysis.id` |
| `competencyid` | BIGINT | FK to `mdl_competency.id` |
| `bloom_level` | INT | 1-6 (Remember to Create) |
| `bloom_label` | VARCHAR(50) | `Remember`, `Understand`, `Apply`, etc. |
| `evidence_excerpt` | TEXT | Supporting text from submission |
| `confidence` | VARCHAR(20) | `high`, `medium`, `low` |

**Design Note:** Also optional for MVP; can live in `analysis_json` initially.

---

## LLM Integration Architecture

### Prompt Design Philosophy
The `assignment-analyzer-prompt.md` must:
1. **Accept variable-length submission text** (500–5000 words typical; up to ~15,000 words for extended essays)
2. **Accept rubric/marking guide criteria** as structured input (JSON or markdown table format)
3. **Accept course competencies** with their descriptions and framework hierarchy
4. **Produce deterministic, parseable JSON output** (same schema reliability as `local_lid`)
5. **Balance depth with token cost** — avoid unnecessary elaboration; focus on evidence-based scoring

### Prompt Structure (Draft Outline)
```markdown
# Assignment Analysis Prompt

You are an expert educational assessor analyzing a student's written assignment submission.

## Your Task
Analyze the submission against:
1. The provided rubric or marking guide criteria
2. The course competencies (if provided)
3. Bloom's taxonomy cognitive levels
4. Writing quality and coherence

## Input Context
- **Submission Text:** [FULL_SUBMISSION_TEXT]
- **Assignment Instructions:** [ASSIGNMENT_DESCRIPTION]
- **Rubric/Marking Guide:** [RUBRIC_JSON_OR_MARKDOWN]
- **Course Competencies:** [COMPETENCIES_JSON]
- **Student Metadata:** [STUDENT_ID, SUBMISSION_ATTEMPT_NUMBER]

## Output Requirements
Return a JSON object with the following structure:
{
  "schema_version": "1.0",
  "submission_analysis": {
    "overall_quality_score": 0-100,
    "cognitive_depth_score": 0-100,
    "coherence_score": 0-100,
    "evidence_quality_score": 0-100
  },
  "rubric_evaluation": [
    {
      "criterion_name": "",
      "criterion_id": "",
      "suggested_score": 0,
      "max_score": 0,
      "evidence_excerpt": "",
      "strengths": [],
      "areas_for_growth": [],
      "confidence": "high|medium|low"
    }
  ],
  "competency_demonstration": [
    {
      "competency_name": "",
      "competency_id": "",
      "bloom_level": 1-6,
      "bloom_label": "Remember|Understand|Apply|Analyze|Evaluate|Create",
      "evidence_excerpt": "",
      "depth_rating": "emerging|developing|proficient|advanced",
      "confidence": "high|medium|low"
    }
  ],
  "formative_feedback": {
    "key_strengths": [],
    "development_priorities": [],
    "next_steps": []
  },
  "meta": {
    "analysis_timestamp": "",
    "model_version": "",
    "confidence_overall": "high|medium|low"
  }
}

## Scoring Calibration
[Same calibration principles as local_lid — evidence-based, avoiding inflation, explicit uncertainty handling]

## Important Constraints
- Use only evidence from the submission text; do not infer unstated knowledge
- If rubric criteria are ambiguous, flag this in the output
- If competencies are not demonstrated, mark as "not evidenced" rather than low score
- Excerpt length: 50-150 words per evidence citation
```

### Prompt File Location
`/moodle/mod/assign/submission/lid/prompts/assignment-analyzer-prompt.md`

**Rationale:** Keep prompt versioned inside the plugin directory, not externalized. This ensures prompt evolution is tracked alongside code changes.

---

## Processing Workflow

### Trigger Points (When Analysis Runs)
1. **Manual trigger:** Instructor clicks "Analyze with LID" button in grading interface
2. **Batch trigger:** Instructor selects multiple students and chooses "Batch Analyze" from action menu
3. **Automatic on submission:** (Optional future feature) Auto-queue on student submission if enabled in assignment settings

### Queue Processing Flow (Mirrors `local_lid`)
```
┌─────────────────────────────────────────────────────────┐
│ 1. Instructor Action                                    │
│    - Clicks "Analyze" on student submission             │
│    - Or selects batch action                            │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 2. Queue Entry Creation                                 │
│    - Check: Is there already a completed analysis       │
│      for this submission_version?                       │
│      - Yes → Show existing result, offer re-analysis    │
│      - No → Insert into mdl_assignsubmission_lid_queue  │
│    - Set status = 'pending', priority = 0               │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 3. Scheduled Task (Cron or CLI)                         │
│    - Runs every 1-5 minutes (configurable)              │
│    - Phase 0: Cleanup stale claims (same as local_lid)  │
│    - Phase 1: Claim next pending job                    │
│      - UPDATE queue SET status='processing',            │
│        claimed_at=NOW(), claimed_by=INSTANCE_ID         │
│        WHERE status='pending' ORDER BY priority DESC,   │
│        created_at ASC LIMIT 1                           │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 4. Submission Content Retrieval                         │
│    - Fetch submission from mdl_assign_submission         │
│    - If onlinetext → extract from DB                    │
│    - If PDF → read file from Moodle file storage        │
│      - Use pdftotext or similar for extraction          │
│    - Validate: Is content empty? → Mark failed          │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 5. Context Assembly (prompt_builder.php)                │
│    - Load assignment instructions                       │
│    - Load rubric/marking guide (if exists)              │
│    - Load course competencies (if enabled)              │
│    - Load assignment-analyzer-prompt.md template        │
│    - Substitute placeholders with actual data           │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 6. LLM API Call (gemini_client.php)                     │
│    - POST to Gemini 2.5 Flash endpoint                  │
│    - Include full prompt + submission                   │
│    - Set max_tokens = 16384 (validated limit)           │
│    - Capture response JSON                              │
│    - Extract token counts from response headers/body    │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 7. Response Validation & Storage                        │
│    - Validate JSON schema (assignment_analysis v1.0)    │
│    - Calculate API cost (input + output + thought)      │
│    - INSERT into mdl_assignsubmission_lid_analysis      │
│      - Store full analysis_json                         │
│      - Store token counts, cost, processing time        │
│    - UPDATE queue SET status='completed',               │
│      processed_at=NOW()                                 │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 8. UI Update (If Instructor Is Waiting)                 │
│    - If viewing grading page → Show success notification│
│    - Render analysis result card                        │
│    - Offer "View Full Analysis" link to dashboard       │
└─────────────────────────────────────────────────────────┘
```

### Error Handling
- **PDF extraction failure** → Mark as failed, log error, notify instructor
- **Empty submission** → Skip analysis, mark as failed with clear message
- **LLM API timeout** (>60s) → Retry up to 3 times with exponential backoff
- **Invalid JSON response** → Log raw response, mark as failed, alert admin
- **Rate limit hit** → Re-queue with `status='pending'` and increment `attempt`

---

## Dashboard Views & UI Integration

### View 1: Assignment-Level Dashboard
**URL:** `/mod/assign/submission/lid/view.php?id={assignmentid}&view=assignment`

**Purpose:** Instructor sees analysis results for all students in one assignment.

**Components:**
- **Header:** Assignment name, course name, total submissions, analyzed count, pending count
- **Summary Stats:**
  - Average rubric scores per criterion (if rubrics used)
  - Competency demonstration heatmap (students × competencies grid)
  - Bloom's level distribution (pie chart or stacked bar)
  - API cost summary (total spend for this assignment)
- **Student List Table:**
  | Student Name | Submission Status | Analysis Status | Overall Score | Top Competencies | Actions |
  |---|---|---|---|---|---|
  | Alice Smith | Submitted | Analyzed | 78/100 | Critical Thinking, Research | View / Re-analyze |
  | Bob Jones | Submitted | Pending | — | — | Analyze Now |
  | Carol Lee | Not Submitted | — | — | — | — |
  
- **Bulk Actions:**
  - "Analyze All Unanalyzed" button
  - "Export Results (CSV)" button
  - "View Cost Projection" link

**Design Inspiration:** Mirror `local_lid` forum-level dashboard structure.

---

### View 2: Course-Level Dashboard
**URL:** `/mod/assign/submission/lid/view.php?id={courseid}&view=course`

**Purpose:** Instructor sees aggregated LID analysis across all assignments in the course.

**Components:**
- **Header:** Course name, total assignments with LID enabled, total analyses run, total API cost
- **Competency Progression Timeline:**
  - X-axis: Assignments (chronological)
  - Y-axis: Competency demonstration depth (avg Bloom's level per competency)
  - Lines: One per competency, showing progression across assignments
- **Assignment Summary Table:**
  | Assignment Name | Students | Analyzed | Avg Quality Score | Top Competency | Cost |
  |---|---|---|---|---|---|
  | Essay 1: Research Methods | 25 | 25 | 76 | Research Design | $3.20 |
  | Essay 2: Data Analysis | 25 | 18 | 82 | Statistical Reasoning | $2.88 |
  
- **Filters:**
  - By competency
  - By date range
  - By assignment type

---

### View 3: Student-Level Dashboard
**URL:** `/mod/assign/submission/lid/view.php?userid={userid}&courseid={courseid}`

**Purpose:** Instructor (or future: student) sees all LID analyses for one student across all assignments in the course.

**Components:**
- **Header:** Student name, course name, total submissions analyzed, competency growth trend
- **Competency Radar Chart:** (Same as LID browser dashboard)
  - Axes: Course competencies
  - Values: Max Bloom's level demonstrated per competency across all assignments
- **Assignment Timeline:**
  - Cards for each analyzed assignment, chronological
  - Shows: Assignment name, submission date, overall score, key competencies, formative feedback summary
- **Competency Evidence Portfolio:**
  - Searchable/filterable list of evidence excerpts grouped by competency
  - "Export Portfolio (PDF)" button

---

### Integration Points in Moodle UI

#### 1. Grading Interface (Assignment Grading Table)
**Location:** `/mod/assign/view.php?id={cmid}&action=grading`

**Addition:** New column "LID Analysis" with:
- Icon indicator: ✓ (analyzed) | ⏳ (pending) | ∅ (not queued)
- Button: "Analyze" or "View Analysis"
- Tooltip: Last analyzed date, cost

**Implementation:** Hook into `assign_grading_table` renderer, add custom column via plugin callback.

---

#### 2. Single Student Grading View
**Location:** `/mod/assign/view.php?id={cmid}&rownum={rownum}&action=grade`

**Addition:** New panel "Learning Intelligence Analysis" (collapsible) below submission text:
- If not analyzed: "Analyze this submission with LID" button
- If analyzed: Compact analysis card showing:
  - Overall quality score (0-100)
  - Top 3 competencies demonstrated
  - Key strengths (bullet list, 3 items)
  - Development priorities (bullet list, 3 items)
  - "View Full Analysis" link (opens dashboard in new tab)
  - "Re-analyze" button (if submission updated since last analysis)

**Implementation:** Plugin callback in `assign_submission_plugin` interface, override `view_summary()`.

---

#### 3. Assignment Settings Page
**Location:** `/mod/assign/mod_form.php`

**Addition:** New section "Learning Intelligence Dashboard (LID)" with:
- Checkbox: "Enable LID analysis for this assignment"
- Dropdown: "Auto-analyze on submission" (Yes / No) — disabled in v0.1.0, greyed out with "Coming in v0.2.0" note
- Checkbox: "Include competency analysis" (requires course competencies enabled)
- Checkbox: "Generate rubric score suggestions" (requires advanced grading method)

**Implementation:** Extend `mod_form.php` via plugin's `mod_form_definition_after_data()` callback.

---

## Settings & Configuration

### Admin Settings (`/admin/settings.php?section=assignsubmission_lid`)

| Setting | Type | Default | Description |
|---|---|---|
| `assignsubmission_lid/apikey` | Text (encrypted) | (empty) | Google AI Studio API key |
| `assignsubmission_lid/endpoint` | Text | `https://generativelanguage.googleapis.com/v1beta/openai/chat/completions` | LLM API endpoint |
| `assignsubmission_lid/model` | Dropdown | `gemini-2.5-flash` | Model selection |
| `assignsubmission_lid/max_tokens` | Int | 16384 | Max output tokens |
| `assignsubmission_lid/queue_interval` | Int | 300 | Queue processing interval (seconds) |
| `assignsubmission_lid/max_retries` | Int | 3 | Max retry attempts for failed jobs |
| `assignsubmission_lid/stale_claim_seconds` | Int | 600 | Time before a claimed job is considered stale |
| `assignsubmission_lid/enable_cost_tracking` | Checkbox | Yes | Track and display API costs |
| `assignsubmission_lid/cost_per_1m_input_tokens` | Decimal | 0.075 | Cost per 1M input tokens (USD) |
| `assignsubmission_lid/cost_per_1m_output_tokens` | Decimal | 0.30 | Cost per 1M output tokens (USD) |
| `assignsubmission_lid/cost_per_1m_thought_tokens` | Decimal | 0.30 | Cost per 1M thinking tokens (USD) |

**Design Decision:** Reuse API key from `local_lid` if both plugins are installed (shared config). Add capability to override per-plugin if needed.

---

## Capabilities & Permissions

### Defined Capabilities

| Capability | Name | Context | Risk |
|---|---|---|---|
| `assignsubmission/lid:analyze` | Analyze student submissions | Course, Module | Low |
| `assignsubmission/lid:viewreports` | View LID dashboards | Course, Module | Low |
| `assignsubmission/lid:viewcosts` | View API cost data | Course, Module | Low |
| `assignsubmission/lid:managesettings` | Configure LID for assignment | Module | Medium |

### Default Role Assignments
- **Manager, Course Creator, Editing Teacher:** All capabilities
- **Non-editing Teacher:** `analyze`, `viewreports` (but NOT `viewcosts` by default)
- **Student:** None (student-facing features in future phase)

---

## Testing Strategy

### Test Environments
- **Primary:** Moodle 5.1 (lms.cucorn.com staging instance)
- **Secondary:** Moodle 4.5 LTS (separate test instance or Docker container)

### Test Scenarios

#### Functional Testing
1. **Basic Analysis — Text Submission**
   - Create assignment with online text submission
   - Submit 500-word essay as student
   - Trigger LID analysis as instructor
   - Verify JSON output structure
   - Verify dashboard rendering

2. **Basic Analysis — PDF Submission**
   - Create assignment with file upload
   - Submit 3-page PDF as student
   - Trigger LID analysis
   - Verify PDF text extraction
   - Verify analysis completes

3. **Rubric Integration — Rubric Type**
   - Create assignment with rubric (4 criteria, 5 levels each)
   - Submit work
   - Analyze
   - Verify rubric criteria appear in analysis JSON
   - Verify suggested scores match rubric scale

4. **Rubric Integration — Marking Guide Type**
   - Create assignment with marking guide
   - Submit work
   - Analyze
   - Verify marking guide criteria appear in analysis JSON

5. **Competency Integration**
   - Enable course competencies (minimum 3)
   - Link competencies to assignment
   - Submit work
   - Analyze
   - Verify competency demonstration data in analysis JSON
   - Verify competency radar chart renders

6. **Queue Processing**
   - Queue 10 analyses
   - Run scheduled task manually: `php admin/cli/scheduled_task.php --execute='\assignsubmission_lid\task\process_queue'`
   - Verify all 10 complete within expected time
   - Check for duplicate processing (ensure no race conditions)

7. **Re-analysis on Updated Submission**
   - Analyze initial submission (v1)
   - Student resubmits (v2)
   - Trigger re-analysis
   - Verify both analyses are retained in DB (keyed by `submission_version`)
   - Verify dashboard shows both results

8. **Batch Analysis**
   - Select 5 students from grading table
   - Click "Batch Analyze"
   - Verify all 5 queue entries created
   - Verify all 5 complete

9. **Error Handling — Empty Submission**
   - Submit blank text box
   - Trigger analysis
   - Verify status = 'failed' with clear error message
   - Verify instructor sees "Cannot analyze empty submission"

10. **Error Handling — Corrupted PDF**
    - Upload corrupted or image-only PDF (no extractable text)
    - Trigger analysis
    - Verify graceful failure
    - Verify error message: "PDF text extraction failed"

#### Cost Tracking Validation
- Run 10 analyses
- Sum `api_cost_usd` from `mdl_assignsubmission_lid_analysis`
- Cross-check against Google AI Studio usage dashboard
- Verify token counts match (input, output, thought)

#### Performance Testing
- **Small cohort:** 30 students, 1500-word submissions → Measure total processing time
- **Large cohort:** 100 students, 1000-word submissions → Measure queue throughput
- **Target:** Process 100 submissions in under 30 minutes (avg 18 seconds per analysis)

#### GDPR Compliance Testing
- Verify `privacy/provider.php` correctly exports user data
- Verify user deletion removes analysis records
- Verify data retention policy respects Moodle's retention settings

---

## Data Privacy & GDPR Compliance

### Data Sent to LLM
- **Student submission text** (essay, report, etc.)
- **Student Moodle userid** (integer) — included in prompt context for traceability
- **Assignment instructions** (course content)
- **Rubric/marking guide criteria** (assessment metadata)
- **Course competencies** (competency framework metadata)

### PII Considerations
- **Student names are NOT sent** (only userid)
- **Submission text may contain implicit PII** (student writes "As a single parent, I..." → this is user-generated, not plugin-injected)
- **Instructor must be aware:** LLM provider (Google) processes submission content under their terms of service

### GDPR Compliance Checklist
- [x] Privacy provider implemented (`privacy/provider.php`)
- [x] User data export function (exports all analyses for a user as JSON)
- [x] User data deletion function (deletes analysis records on user deletion)
- [x] Consent mechanism: Instructor action (clicking "Analyze") is implicit consent to process student work with LLM
- [x] Transparency: Plugin settings page includes link to Google AI Studio privacy policy
- [ ] Future: Student consent checkbox in assignment settings ("Students must consent to LID analysis before submission")

---

## Cost Modeling & Sustainability

### Estimated Cost per Analysis (Based on `local_lid` Data)

**Assumptions:**
- Avg submission length: 1500 words (~2000 tokens input from submission text)
- Prompt overhead: ~1500 tokens (rubric + competencies + instructions)
- Total input: ~3500 tokens per analysis
- Output: ~2500 tokens (structured JSON response)
- Thought tokens: ~5000 tokens (Gemini 2.5 Flash extended thinking)

**Cost Calculation (Gemini 2.5 Flash Pricing):**
- Input: 3,500 tokens × $0.075 / 1M = $0.0002625
- Output: 2,500 tokens × $0.30 / 1M = $0.00075
- Thought: 5,000 tokens × $0.30 / 1M = $0.0015
- **Total per analysis: ~$0.0025 USD**

**Cohort Scaling:**
| Cohort Size | Analyses per Term | Cost per Term |
|---|---|---|
| 30 students | 30 × 3 assignments = 90 | $0.23 |
| 100 students | 100 × 3 = 300 | $0.75 |
| 500 students | 500 × 3 = 1500 | $3.75 |

**Sustainability:** At current Gemini pricing, cost per student per term is negligible (<$0.01). Budget risk is minimal even for large deployments.

---

## Development Phases & Milestones

### Phase 0: Setup & Scaffolding (Week 1)
- [ ] Create plugin directory structure
- [ ] Define `version.php`, `db/install.xml`, `lang/en/assignsubmission_lid.php`
- [ ] Implement basic plugin registration (shows in assignment settings)
- [ ] Set up local testing environment (Moodle 5.1 instance with sample course)
- [ ] Create GitHub repo (private initially; public after v0.1.0 release)

### Phase 1: Core Analysis Engine (Week 2-3)
- [ ] Implement `gemini_client.php` (reuse/adapt from `local_lid`)
- [ ] Create `assignment-analyzer-prompt.md` (v1.0 draft)
- [ ] Implement `prompt_builder.php`:
  - [ ] Submission text extraction (onlinetext)
  - [ ] PDF text extraction (file upload)
  - [ ] Rubric data parsing
  - [ ] Competency data fetching
  - [ ] Prompt template substitution
- [ ] Implement `analyzer.php` (orchestrator):
  - [ ] Queue job creation
  - [ ] LLM API call
  - [ ] Response parsing and validation
  - [ ] Database storage
- [ ] Create scheduled task `process_queue.php`
- [ ] Test: Analyze single text submission successfully

### Phase 2: Dashboard Views (Week 4)
- [ ] Implement assignment-level dashboard (`assignment_dashboard.php` + template)
- [ ] Implement course-level dashboard (`course_dashboard.php` + template)
- [ ] Implement student-level dashboard (`student_dashboard.php` + template)
- [ ] Create AMD module `dashboard.js` for interactivity (expand/collapse, filtering)
- [ ] Style dashboards (`styles.css`) — reuse LID design language
- [ ] Test: View dashboards with sample data

### Phase 3: UI Integration (Week 5)
- [ ] Add "LID Analysis" column to assignment grading table
- [ ] Add analysis panel to single student grading view
- [ ] Add LID settings section to assignment settings form
- [ ] Test: Full instructor workflow (create assignment → enable LID → grade → analyze → view results)

### Phase 4: Rubric & Competency Integration (Week 6)
- [ ] Implement `rubric_parser.php` (support rubric and marking guide)
- [ ] Implement `competency_mapper.php` (fetch course competencies)
- [ ] Extend prompt to include rubric and competency data
- [ ] Test: Analyze submission with rubric + competencies, verify JSON output includes both

### Phase 5: Error Handling & Edge Cases (Week 7)
- [ ] Implement retry logic (max 3 attempts)
- [ ] Handle empty submissions gracefully
- [ ] Handle PDF extraction failures
- [ ] Handle LLM API timeouts and rate limits
- [ ] Test: All error scenarios, verify user-friendly error messages

### Phase 6: GDPR & Privacy Compliance (Week 8)
- [ ] Implement `privacy/provider.php`
- [ ] Test data export (request user data, verify analysis JSON included)
- [ ] Test data deletion (delete user, verify analyses removed)
- [ ] Add privacy policy link to settings page

### Phase 7: Documentation & Release Prep (Week 9)
- [ ] Write user documentation (instructor guide)
- [ ] Write admin documentation (installation, configuration)
- [ ] Create demo video (5-minute walkthrough)
- [ ] Write blog post (publish on learning-intelligence.dev)
- [ ] Prepare for Moodle plugins directory submission

### Phase 8: Beta Testing (Week 10)
- [ ] Deploy to lms.cucorn.com production
- [ ] Onboard 2-3 pilot instructors
- [ ] Collect feedback (usability, output quality, cost)
- [ ] Iterate on prompt based on feedback
- [ ] Fix bugs identified in beta

### Phase 9: v0.1.0 Release (Week 11)
- [ ] Finalize plugin code
- [ ] Tag v0.1.0 in GitHub
- [ ] Submit to Moodle plugins directory
- [ ] Publish blog post and demo video
- [ ] Announce in Moodle forums and LID community

---

## Open Questions & Decision Points

### Q1: Should we support automated grading (LLM assigns final grade)?
**Options:**
- A) No — LLM output is purely advisory; instructor always assigns final grade manually
- B) Yes, but opt-in — Instructor can click "Accept suggested scores" to auto-populate rubric
- C) Yes, with review — LLM assigns grade, but flagged for instructor review before publishing

**Recommendation for v0.1.0:** Option A (advisory only). Option B for v0.2.0 if user demand is strong.

**Rationale:** Academic integrity and instructor autonomy require human-in-the-loop for final grading. LID should augment, not replace, professional judgment.

---

### Q2: Should students see LID analysis results?
**Options:**
- A) No — instructor-only tool (current scope for v0.1.0)
- B) Yes — students see formative feedback generated by LID (no scores, just strengths/development areas)
- C) Yes, with instructor control — Instructor can toggle "Share LID feedback with student" per assignment

**Recommendation for v0.1.0:** Option A. Option C for v0.2.0.

**Rationale:** Formative feedback is valuable, but we need to validate output quality with instructors first. Student-facing release requires higher confidence in feedback accuracy and tone.

---

### Q3: How do we handle multi-file submissions (e.g., essay + appendix + dataset)?
**Options:**
- A) Not supported in v0.1.0 — only single file or text box
- B) Concatenate all text files into one submission context
- C) Analyze primary file only, ignore others

**Recommendation for v0.1.0:** Option A. Option B for v0.2.0.

**Rationale:** Multi-file submissions require careful UX (which file is primary?) and increase prompt complexity. Defer until single-file workflow is validated.

---

### Q4: Should LID analysis count toward assignment completion tracking?
**Options:**
- A) No — analysis is invisible to completion tracking
- B) Yes — "Assignment analyzed by instructor" counts as a grading step in Moodle's activity completion

**Recommendation:** Option A for v0.1.0.

**Rationale:** LID is a behind-the-scenes tool; completion tracking should reflect student actions, not instructor actions.

---

### Q5: Do we need a separate "LID report" download (PDF/CSV export)?
**Options:**
- A) No — dashboards are sufficient
- B) Yes — instructors can export assignment-level results as CSV for import into SIS
- C) Yes — students can export their own LID portfolio as PDF

**Recommendation for v0.1.0:** Option A. Option B for v0.2.0 (CSV export). Option C for v0.3.0 (student portfolio PDF).

**Rationale:** Export features add complexity; validate core workflow first.

---

## Dependencies & Prerequisites

### Moodle Requirements
- Moodle 4.5+ (tested on 5.1 and 4.5 LTS)
- PHP 8.1+
- MariaDB 10.6+ or PostgreSQL 13+

### Moodle Features Required
- Assignment activity (`mod_assign`) — core module
- Advanced grading (rubric or marking guide) — optional but recommended
- Course competencies — optional but recommended
- Scheduled tasks (cron) — required for queue processing

### External Dependencies
- Google AI Studio API key (or compatible OpenAI-style endpoint)
- `pdftotext` (for PDF extraction) — installed via system package manager or PHP library (e.g., `smalot/pdfparser`)

### Optional Integrations
- `local_lid` plugin (shared API key configuration)
- Ralph LRS (future xAPI integration)
- Keycloak SSO (if deployed at lms.cucorn.com)

---

## Licensing & Open Source Strategy

### License
**GPL v3** (same as Moodle core)

**Rationale:** Moodle plugins must be GPL-compatible per Moodle licensing requirements. GPL ensures ecosystem compatibility and encourages contributions.

### Repository
- **GitHub:** `seanrugg/assignment_lid` (public after v0.1.0 release)
- **Moodle Plugins Directory:** Submit after v0.1.0 tested in beta

### Contribution Guidelines
- Pull requests welcome
- Issues tracked in GitHub
- Code style: Follow Moodle coding standards (phpcs, phpdoc)
- All contributions must include tests

---

## Success Metrics (How We Know It Works)

### Technical Metrics
- [ ] Plugin installs cleanly on Moodle 5.1 and 4.5 without errors
- [ ] Analysis success rate >95% (failures only for genuinely invalid inputs)
- [ ] Queue processing throughput: ≥3 analyses per minute per cron run
- [ ] Average processing time per analysis: <30 seconds
- [ ] API cost per analysis: <$0.005 USD
- [ ] Zero data loss (all analyses stored with full audit trail)

### Usability Metrics
- [ ] Instructors can trigger analysis in <3 clicks from grading interface
- [ ] Dashboard loads in <2 seconds for 100-student assignment
- [ ] Pilot instructors rate output quality as "useful" or "very useful" (>80% agreement)
- [ ] Zero GDPR complaints or data privacy incidents

### Adoption Metrics (Post-Release)
- [ ] 10+ Moodle sites install the plugin within 6 months
- [ ] 100+ analyses run across all installations within 6 months
- [ ] 5+ GitHub stars and 2+ external contributors within 1 year

---

## Next Steps (Immediate Actions)

1. **Confirm scope and architecture** with Sean (this document)
2. **Create GitHub repository** (`seanrugg/assignment_lid`)
3. **Set up development environment** (local Moodle 5.1 instance or staging at lms.cucorn.com)
4. **Draft `assignment-analyzer-prompt.md` v1.0** (extract rubric scoring logic and competency mapping patterns from `local_lid` forum analysis as starting point)
5. **Scaffold plugin directory structure** (Phase 0, Week 1)
6. **Begin Phase 1: Core Analysis Engine** (prompt builder + LLM integration)

---

## Appendix A: Relationship to `local_lid` Plugin

### Shared Components (Reuse Opportunities)
| Component | `local_lid` | `assignment_lid` | Notes |
|---|---|---|---|
| LLM Client | `gemini_client.php` | Same | Abstract into shared library? |
| Queue Processor | `process_queue.php` | Same pattern | Copy + adapt |
| Competency Mapper | `competency_mapper.php` | Same | Reuse directly |
| Cost Calculation | Inline in analyzer | Same | Extract to helper class |
| Dashboard Styling | `styles.css` | Adapt | Consistent LID design language |
| Admin Settings | API key config | Shared or separate? | Decision needed |

### Divergent Components (Unique to `assignment_lid`)
- PDF text extraction (`pdftotext` integration)
- Rubric/marking guide parser (`rubric_parser.php`)
- Assignment-specific prompt template (`assignment-analyzer-prompt.md`)
- Integration with `mod_assign` grading interface (vs. forum UI)

### Refactoring Opportunity
Consider creating a shared library `local_lid_core` that both plugins depend on:
- `lib/gemini_client.php`
- `lib/cost_calculator.php`
- `lib/competency_mapper.php`
- `lib/queue_processor_base.php`

**Decision:** Defer to v0.2.0. For v0.1.0, copy-paste and adapt from `local_lid` to move faster.

---

## Appendix B: Sample JSON Output Schema (Draft)

```json
{
  "schema_version": "1.0",
  "analysis_metadata": {
    "analyzed_at": "2026-04-27T14:32:00Z",
    "model_version": "gemini-2.5-flash",
    "submission_id": 12345,
    "student_userid": 67890,
    "assignment_id": 101,
    "submission_version": 1,
    "confidence_overall": "high"
  },
  "submission_analysis": {
    "overall_quality_score": 82,
    "cognitive_depth_score": 78,
    "coherence_score": 85,
    "evidence_quality_score": 80,
    "word_count": 1523
  },
  "rubric_evaluation": [
    {
      "criterion_name": "Thesis Clarity",
      "criterion_id": "rubric_criterion_1",
      "suggested_score": 4,
      "max_score": 5,
      "evidence_excerpt": "The thesis statement in paragraph 1 is clearly articulated: 'This paper argues that...'",
      "strengths": [
        "Thesis is specific and arguable",
        "Positioned prominently in introduction"
      ],
      "areas_for_growth": [
        "Could strengthen by previewing main supporting points"
      ],
      "confidence": "high"
    },
    {
      "criterion_name": "Use of Evidence",
      "criterion_id": "rubric_criterion_2",
      "suggested_score": 3,
      "max_score": 5,
      "evidence_excerpt": "The paper cites 4 peer-reviewed sources, but analysis of each is shallow.",
      "strengths": [
        "Sources are credible and recent"
      ],
      "areas_for_growth": [
        "Deepen analysis of how each source supports the argument",
        "Integrate more direct quotations with commentary"
      ],
      "confidence": "medium"
    }
  ],
  "competency_demonstration": [
    {
      "competency_name": "Critical Thinking",
      "competency_id": "comp_001",
      "bloom_level": 5,
      "bloom_label": "Evaluate",
      "evidence_excerpt": "The student evaluates the strengths and limitations of competing theories...",
      "depth_rating": "proficient",
      "confidence": "high"
    },
    {
      "competency_name": "Research Design",
      "competency_id": "comp_002",
      "bloom_level": 3,
      "bloom_label": "Apply",
      "evidence_excerpt": "The proposed methodology applies mixed-methods design principles...",
      "depth_rating": "developing",
      "confidence": "medium"
    }
  ],
  "formative_feedback": {
    "key_strengths": [
      "Clear thesis and logical argument structure",
      "Effective use of topic sentences to guide reader",
      "Strong synthesis of multiple perspectives"
    ],
    "development_priorities": [
      "Deepen engagement with counterarguments",
      "Provide more detailed analysis of evidence (not just citation)",
      "Strengthen conclusion by connecting back to broader implications"
    ],
    "next_steps": [
      "Read 2-3 additional sources that challenge your main argument",
      "For each piece of evidence, ask: 'So what? Why does this matter for my thesis?'",
      "Revise conclusion to address the 'larger significance' question"
    ]
  },
  "bloom_distribution": {
    "remember": 2,
    "understand": 5,
    "apply": 8,
    "analyze": 12,
    "evaluate": 6,
    "create": 3
  },
  "api_usage": {
    "input_tokens": 3456,
    "output_tokens": 2387,
    "thought_tokens": 4821,
    "total_tokens": 10664,
    "cost_usd": 0.00289
  }
}
```

---

*End of Instructional Scope Document*
