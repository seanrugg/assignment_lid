# Assignment LID — Technical Architecture

**Version:** 0.1.0-dev  
**Last Updated:** 2026-04-27

---

## System Overview

Assignment LID is a Moodle assignment submission plugin that integrates AI-powered analysis into the grading workflow. It uses a queue-based architecture to process submissions asynchronously, calling the Gemini 2.5 Flash LLM to evaluate student work against rubrics, competency frameworks, and Bloom's taxonomy.

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         MOODLE CORE                             │
│  ┌────────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Assignment   │  │  Advanced   │  │   Competency        │  │
│  │   Module       │  │  Grading    │  │   Framework         │  │
│  │   (mod_assign) │  │  (Rubric)   │  │                     │  │
│  └────────┬───────┘  └──────┬──────┘  └──────┬──────────────┘  │
│           │                 │                │                  │
└───────────┼─────────────────┼────────────────┼──────────────────┘
            │                 │                │
            ▼                 ▼                ▼
┌─────────────────────────────────────────────────────────────────┐
│              ASSIGNMENT LID PLUGIN (assignsubmission_lid)       │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    PRESENTATION LAYER                     │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌──────────────────┐  │  │
│  │  │  Grading    │  │ Assignment  │  │     Course       │  │  │
│  │  │  Interface  │  │  Dashboard  │  │    Dashboard     │  │  │
│  │  │  Panel      │  │             │  │                  │  │  │
│  │  └──────┬──────┘  └──────┬──────┘  └────────┬─────────┘  │  │
│  └─────────┼────────────────┼─────────────────┼────────────┘  │
│            │                │                 │                │
│  ┌─────────┼────────────────┼─────────────────┼────────────┐  │
│  │         │     APPLICATION LAYER            │            │  │
│  │  ┌──────▼──────┐  ┌──────▼──────┐  ┌──────▼─────────┐  │  │
│  │  │  Analyzer   │  │   Prompt    │  │    Rubric      │  │  │
│  │  │ Orchestrator│◄─┤   Builder   │◄─┤    Parser      │  │  │
│  │  └──────┬──────┘  └─────────────┘  └────────────────┘  │  │
│  │         │                                               │  │
│  │         │          ┌─────────────────┐                  │  │
│  │         └─────────►│  Competency     │                  │  │
│  │                    │    Mapper       │                  │  │
│  │                    └─────────────────┘                  │  │
│  └────────┬───────────────────────────────────────────────┘  │
│           │                                                   │
│  ┌────────▼───────────────────────────────────────────────┐  │
│  │              INTEGRATION LAYER                         │  │
│  │  ┌──────────────┐         ┌──────────────────────┐     │  │
│  │  │   Gemini     │         │    Queue Processor   │     │  │
│  │  │   Client     │◄────────┤   (Scheduled Task)   │     │  │
│  │  └──────┬───────┘         └──────────────────────┘     │  │
│  └─────────┼────────────────────────────────────────────┘  │
│            │                                                │
│  ┌─────────▼────────────────────────────────────────────┐  │
│  │               DATA PERSISTENCE LAYER                  │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌────────────┐  │  │
│  │  │  Queue       │  │  Analysis    │  │  Rubric    │  │  │
│  │  │  Table       │  │  Results     │  │  Scores    │  │  │
│  │  │              │  │  Table       │  │  Table     │  │  │
│  │  └──────────────┘  └──────────────┘  └────────────┘  │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
                ┌───────────────────────┐
                │   EXTERNAL SERVICES   │
                │                       │
                │  Google AI Studio     │
                │  (Gemini 2.5 Flash)   │
                └───────────────────────┘
```

---

## Component Descriptions

### Presentation Layer

#### 1. Grading Interface Panel
- **Location:** Single student grading view in mod_assign
- **Purpose:** Display analysis results alongside submission
- **Key Features:**
  - Collapsible "Learning Intelligence Analysis" panel
  - Overall quality scores (0-100)
  - Top 3 competencies demonstrated
  - Key strengths and development priorities
  - "View Full Analysis" link to dashboard
  - "Re-analyze" button for updated submissions

#### 2. Assignment Dashboard
- **URL:** `/mod/assign/submission/lid/view.php?id={assignmentid}&view=assignment`
- **Purpose:** Aggregate view of all students in one assignment
- **Key Features:**
  - Summary statistics (avg scores, competency heatmap)
  - Student list table with analysis status
  - Bulk actions ("Analyze All", "Export CSV")
  - API cost summary

#### 3. Course Dashboard
- **URL:** `/mod/assign/submission/lid/view.php?id={courseid}&view=course`
- **Purpose:** Aggregate view across all assignments in course
- **Key Features:**
  - Competency progression timeline
  - Assignment summary table
  - Cost tracking by assignment
  - Filters (by competency, date range, assignment type)

#### 4. Student Dashboard
- **URL:** `/mod/assign/submission/lid/view.php?userid={userid}&courseid={courseid}`
- **Purpose:** All analyses for one student across course
- **Key Features:**
  - Competency radar chart
  - Assignment timeline (chronological cards)
  - Competency evidence portfolio
  - Export to PDF functionality

---

### Application Layer

#### 1. Analyzer (Orchestrator)
**Class:** `assignsubmission_lid\analyzer`  
**Responsibilities:**
- Coordinate analysis workflow
- Fetch submission content (text or PDF)
- Call prompt builder to construct LLM input
- Call Gemini client to get analysis
- Validate and parse JSON response
- Store results in database
- Update queue status

**Key Methods:**
```php
public function analyze_submission(int $submissionid): stdClass
public function queue_analysis(int $submissionid, int $priority = 0): int
public function get_analysis(int $submissionid): ?stdClass
public function reanalyze_submission(int $submissionid): stdClass
```

#### 2. Prompt Builder
**Class:** `assignsubmission_lid\prompt_builder`  
**Responsibilities:**
- Load assignment-analyzer-prompt.md template
- Extract submission text (online text or PDF)
- Parse rubric/marking guide criteria
- Fetch course competencies
- Substitute placeholders in template
- Return complete prompt string

**Key Methods:**
```php
public function build_prompt(stdClass $submission, stdClass $context): string
private function extract_submission_text(stdClass $submission): string
private function get_rubric_data(int $assignmentid): array
private function get_competency_data(int $courseid): array
```

#### 3. Rubric Parser
**Class:** `assignsubmission_lid\rubric_parser`  
**Responsibilities:**
- Detect grading method (rubric vs marking guide)
- Extract criterion definitions
- Extract level descriptions
- Format as JSON or markdown table for prompt

**Key Methods:**
```php
public function parse_rubric(int $assignmentid): array
public function format_for_prompt(array $rubric_data, string $format = 'json'): string
```

#### 4. Competency Mapper
**Class:** `assignsubmission_lid\competency_mapper`  
**Responsibilities:**
- Fetch course competencies from framework
- Extract competency descriptions
- Format for inclusion in prompt
- Map analysis results back to competency IDs

**Key Methods:**
```php
public function get_course_competencies(int $courseid): array
public function format_for_prompt(array $competencies): string
public function map_results_to_competencies(stdClass $analysis): array
```

---

### Integration Layer

#### 1. Gemini Client
**Class:** `assignsubmission_lid\gemini_client`  
**Responsibilities:**
- Construct API request payload
- Make HTTP POST to Gemini endpoint
- Handle authentication (API key)
- Parse response headers for token counts
- Extract JSON from response body
- Handle errors (timeouts, rate limits, invalid responses)

**Key Methods:**
```php
public function analyze(string $prompt, array $options = []): stdClass
private function make_request(array $payload): string
private function parse_response(string $response): stdClass
private function calculate_cost(int $input, int $output, int $thought): float
```

**Configuration:**
- API endpoint from plugin settings
- API key (encrypted storage)
- Model selection (default: gemini-2.5-flash)
- Max tokens, temperature, etc.

#### 2. Queue Processor
**Class:** `assignsubmission_lid\task\process_queue`  
**Type:** Scheduled task (extends `\core\task\scheduled_task`)  
**Schedule:** Every 5 minutes (configurable)  
**Responsibilities:**
- Phase 0: Cleanup stale claims (jobs claimed >10 minutes ago)
- Phase 1: Claim next pending job (atomic UPDATE query)
- Phase 2: Call analyzer for claimed job
- Phase 3: Update queue status (completed or failed)
- Phase 4: Retry logic (up to 3 attempts with exponential backoff)

**Key Methods:**
```php
public function execute(): void
private function cleanup_stale_claims(): int
private function claim_next_job(): ?stdClass
private function process_job(stdClass $job): bool
private function handle_failure(stdClass $job, string $error): void
```

---

### Data Persistence Layer

#### Database Tables

##### `mdl_assignsubmission_lid_queue`
Queue for pending analyses.

| Column | Type | Index | Description |
|--------|------|-------|-------------|
| `id` | BIGINT | PK | Auto-increment |
| `assignmentid` | BIGINT | FK | Assignment ID |
| `submissionid` | BIGINT | FK | Submission ID |
| `userid` | BIGINT | FK | Student user ID |
| `status` | VARCHAR(20) | IDX | pending, processing, completed, failed |
| `priority` | INT | IDX | Processing priority (0-10) |
| `attempt` | INT | | Retry counter |
| `claimed_at` | BIGINT | | Claim timestamp |
| `claimed_by` | VARCHAR(255) | | Processor instance ID |
| `created_at` | BIGINT | IDX | Queue entry creation |
| `processed_at` | BIGINT | | Completion timestamp |
| `error_message` | TEXT | | Error details if failed |

**Indexes:**
- `(assignmentid, userid)` — Unique per submission
- `(status, priority, created_at)` — Queue processing
- `submissionid` — FK integrity

##### `mdl_assignsubmission_lid_analysis`
Stores analysis results (one row per analyzed submission version).

| Column | Type | Index | Description |
|--------|------|-------|-------------|
| `id` | BIGINT | PK | Auto-increment |
| `assignmentid` | BIGINT | FK | Assignment ID |
| `submissionid` | BIGINT | FK | Submission ID |
| `userid` | BIGINT | FK | Student user ID |
| `submission_version` | INT | IDX | Attempt number |
| `analysis_json` | LONGTEXT | | Full JSON output |
| `analyzed_at` | BIGINT | IDX | Analysis timestamp |
| `analyzed_by_userid` | BIGINT | FK | Instructor who triggered |
| `api_cost_usd` | DECIMAL(10,6) | | API cost |
| `input_tokens` | INT | | Input token count |
| `output_tokens` | INT | | Output token count |
| `thought_tokens` | INT | | Thinking token count |
| `processing_time_ms` | INT | | Duration |
| `model_version` | VARCHAR(50) | | LLM identifier |

**Indexes:**
- `(assignmentid, userid, submission_version)` — Unique per version
- `submissionid` — FK integrity
- `analyzed_at` — Temporal queries

##### `mdl_assignsubmission_lid_rubric_scores` (Optional)
Denormalized rubric scores for query performance.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `analysisid` | BIGINT | FK to analysis table |
| `criterion_id` | BIGINT | Rubric criterion ID |
| `suggested_score` | DECIMAL(10,2) | LLM-suggested score |
| `evidence_excerpt` | TEXT | Supporting text |
| `confidence` | VARCHAR(20) | high, medium, low |

**Note:** For MVP, this may be omitted; scores stored in `analysis_json` only.

##### `mdl_assignsubmission_lid_competency_map` (Optional)
Denormalized competency evidence.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `analysisid` | BIGINT | FK to analysis table |
| `competencyid` | BIGINT | FK to mdl_competency |
| `bloom_level` | INT | 1-6 |
| `bloom_label` | VARCHAR(50) | Remember, Understand, etc. |
| `evidence_excerpt` | TEXT | Supporting text |
| `confidence` | VARCHAR(20) | high, medium, low |

**Note:** For MVP, this may be omitted; evidence stored in `analysis_json` only.

---

## Data Flow

### Analysis Request Flow

```
1. Instructor clicks "Analyze" button
   │
   ├─► Check: Analysis already exists for this submission_version?
   │   ├─► Yes → Display existing result, offer re-analysis
   │   └─► No → Continue
   │
   ├─► INSERT into lid_queue (status='pending', priority=0)
   │   └─► Display "Analysis queued" message to instructor
   │
2. Scheduled task runs (every 5 minutes)
   │
   ├─► Phase 0: UPDATE stale claims SET status='pending'
   │            WHERE claimed_at < (NOW - 10 minutes)
   │
   ├─► Phase 1: Claim next job
   │   └─► UPDATE lid_queue SET status='processing',
   │       claimed_at=NOW(), claimed_by=INSTANCE_ID
   │       WHERE status='pending' ORDER BY priority DESC, created_at ASC
   │       LIMIT 1
   │
   ├─► Phase 2: Process claimed job
   │   ├─► Fetch submission from mdl_assign_submission
   │   ├─► Extract text (online text or PDF via pdftotext)
   │   ├─► Load rubric data (if exists)
   │   ├─► Load competencies (if enabled)
   │   ├─► Build prompt using template
   │   ├─► Call Gemini API
   │   ├─► Parse JSON response
   │   ├─► Validate schema
   │   ├─► Calculate cost
   │   └─► INSERT into lid_analysis
   │
   └─► Phase 3: Update queue status
       ├─► Success → UPDATE queue SET status='completed', processed_at=NOW()
       └─► Failure → UPDATE queue SET status='failed', error_message=ERROR
                     (or retry if attempt < 3)
```

### Dashboard Rendering Flow

```
1. Instructor navigates to dashboard URL
   │
   ├─► Determine view type (assignment/course/student)
   │
   ├─► Fetch relevant analyses from lid_analysis table
   │   └─► JOIN with mdl_user (student names)
   │   └─► JOIN with mdl_assign (assignment names)
   │
   ├─► Parse analysis_json for each result
   │   ├─► Extract overall_quality_score
   │   ├─► Extract rubric_evaluation array
   │   ├─► Extract competency_demonstration array
   │   └─► Extract formative_feedback
   │
   ├─► Aggregate data (if course or assignment view)
   │   ├─► Calculate average scores
   │   ├─► Build competency heatmap
   │   └─► Sum API costs
   │
   ├─► Render Mustache template
   │   └─► Pass data context to template
   │
   └─► Return HTML to browser
       └─► AMD module adds interactivity (filtering, expand/collapse)
```

---

## Security Considerations

### API Key Protection
- Store API key encrypted in `mdl_config_plugins` table
- Never expose in client-side code
- Validate on every API call
- Rotate periodically (admin responsibility)

### Access Control
- Capability checks on all dashboard views
- Only editing teachers can trigger analysis
- Only assigned graders see student results
- Respect Moodle's role-based access control

### Data Privacy
- Student names not sent to LLM (only userid)
- Submission text processed per Google AI Studio ToS
- GDPR compliance via privacy provider
- Audit trail for all analyses (who, when, what)

### Input Validation
- Sanitize all user inputs (assignment settings, filters)
- Validate submission IDs before processing
- Prevent SQL injection (use placeholders)
- Prevent XSS in dashboard output (Mustache auto-escapes)

---

## Performance Optimization

### Query Optimization
- Index on `(assignmentid, userid)` for lookups
- Index on `(status, priority, created_at)` for queue processing
- Use JOINs efficiently (avoid N+1 queries in dashboards)
- Cache rubric and competency data per request

### Queue Throughput
- Atomic claim operation prevents race conditions
- Process ≥3 jobs per minute (target: 18s per analysis)
- Stale claim cleanup prevents blocked queue
- Exponential backoff on retries

### Dashboard Rendering
- Paginate student lists (50 per page)
- Lazy-load charts and graphs
- Cache aggregated statistics (5-minute TTL)
- Use AMD modules for client-side filtering (no server round-trip)

### LLM Response Caching
- Store full response in `analysis_json`
- Re-use cached analysis if submission unchanged
- Only re-analyze if submission_version increments

---

## Error Handling Strategy

### Recoverable Errors (Retry)
- LLM API timeout (>60s) → Retry with backoff
- Rate limit exceeded → Retry after delay
- Network errors → Retry
- Invalid JSON (occasional) → Retry

### Permanent Errors (Fail)
- Empty submission text → Mark failed, clear message
- PDF extraction failure → Mark failed, suggest re-upload
- API key invalid → Mark failed, alert admin
- JSON schema mismatch (persistent) → Mark failed, log for debug
- Max retries exceeded → Mark failed, notify instructor

### User-Facing Messages
- **Success:** "Analysis complete. View results below."
- **Pending:** "Analysis queued. Refresh in 2-3 minutes."
- **Failed (empty):** "Cannot analyze empty submission."
- **Failed (PDF):** "PDF text extraction failed. Please upload a text-based PDF."
- **Failed (API):** "Analysis failed due to API error. Contact administrator if this persists."

---

## Scalability Considerations

### Current Capacity (v0.1.0)
- **Queue throughput:** ~180 analyses/hour (3/min × 60 min)
- **Concurrent users:** No limit (queue handles backpressure)
- **Cost at scale:** $0.45/hour at full throughput (180 × $0.0025)

### Scaling Strategies (Future)
- **Horizontal scaling:** Multiple queue processor instances (claimed_by prevents collisions)
- **Batch processing:** Group small submissions into single API call (reduce overhead)
- **Priority queues:** Rush assignments get priority=10, normal=0
- **Caching:** Store prompt templates in memory, not re-read from disk

---

## Deployment Architecture

### Production Environment (lms.cucorn.com)
```
┌────────────────────────────────────────┐
│         Moodle Web Server              │
│  (Apache/Nginx + PHP 8.1)              │
│                                        │
│  ┌──────────────────────────────────┐ │
│  │  Assignment LID Plugin           │ │
│  │  - Presentation layer            │ │
│  │  - Application logic             │ │
│  └──────────┬───────────────────────┘ │
└─────────────┼──────────────────────────┘
              │
              ▼
┌────────────────────────────────────────┐
│        Database Server                 │
│  (MariaDB 10.6+ / PostgreSQL 13+)      │
│                                        │
│  - mdl_assignsubmission_lid_queue      │
│  - mdl_assignsubmission_lid_analysis   │
└────────────────────────────────────────┘
              │
              │ (via cron/scheduled task)
              ▼
┌────────────────────────────────────────┐
│     Queue Processor (Cron Job)         │
│  Runs every 5 minutes                  │
│  - Claims jobs atomically              │
│  - Calls Gemini API                    │
│  - Stores results                      │
└────────────┬───────────────────────────┘
             │
             │ HTTPS
             ▼
┌────────────────────────────────────────┐
│      Google AI Studio API              │
│  (Gemini 2.5 Flash)                    │
│  - Receives prompt                     │
│  - Returns JSON analysis               │
└────────────────────────────────────────┘
```

### Development Environment
- Local Moodle instance (Docker or XAMPP)
- MariaDB or PostgreSQL
- Manual queue processing via CLI (`process_queue_cli.php`)
- Test API key (separate from production)

---

## Monitoring & Observability

### Key Metrics to Track
- **Queue depth:** Number of pending jobs (alert if >50)
- **Processing time:** Average seconds per analysis (target <30s)
- **Success rate:** Completed / (Completed + Failed) (target >95%)
- **API cost:** Daily spend (alert if >$10/day)
- **Error types:** Distribution of failure reasons

### Logging Strategy
- **Info:** Queue job claimed, analysis started, analysis completed
- **Warning:** Retry triggered, stale claim cleaned up
- **Error:** Analysis failed (with error_message), API key invalid
- **Debug:** Full prompt and response (only if debug mode enabled)

### Admin Dashboard (Future)
- Real-time queue status
- Cost projection graphs
- Error log viewer
- Performance metrics

---

## Testing Strategy

### Unit Tests
- Test prompt_builder substitution logic
- Test rubric_parser for all grading methods
- Test competency_mapper data extraction
- Test cost calculation accuracy
- Test JSON schema validation

### Integration Tests
- Test database schema creation/upgrade
- Test queue claim atomicity (race conditions)
- Test API call with mock responses
- Test privacy provider data export/deletion

### Functional Tests
- Test text submission analysis end-to-end
- Test PDF submission analysis end-to-end
- Test rubric integration
- Test competency integration
- Test error scenarios (empty, corrupted, timeout)

### Performance Tests
- Load test: 100 submissions queued, measure completion time
- Stress test: 500 submissions, verify no data loss
- Query performance: Dashboard with 100 students, measure load time

---

## Dependencies

### Moodle Core APIs
- `mod_assign` — Assignment module
- `core_competency` — Competency framework
- `grading` — Advanced grading methods (rubric, marking guide)
- `core\task` — Scheduled tasks
- `core_privacy` — GDPR compliance

### External Libraries
- **pdftotext** (system) or **smalot/pdfparser** (PHP) — PDF extraction
- **Mustache** (core) — Template rendering
- **AMD** (core) — JavaScript modules

### External Services
- **Google AI Studio** — LLM API (Gemini 2.5 Flash)
- **Optional:** Ralph LRS (future xAPI integration)

---

## Configuration Management

### Admin Settings Location
`Site administration → Plugins → Assignment submissions → Learning Intelligence Dashboard`

### Key Configuration Items
- API endpoint and key
- Model selection
- Token limits
- Queue processing interval
- Cost tracking toggles
- Pricing per token type

### Environment-Specific Config
- **Development:** Short queue interval (1 min), verbose logging
- **Staging:** Medium interval (3 min), normal logging
- **Production:** Standard interval (5 min), error-only logging

---

## Future Architecture Enhancements

### v0.2.0+
- Event-driven processing (Moodle events trigger queue instead of polling)
- Websocket updates (real-time dashboard refresh)
- Distributed queue (Redis or RabbitMQ for horizontal scaling)

### v0.3.0+
- LRS integration (xAPI statements for competency tracking)
- Multi-LLM support (fallback to Claude or OpenAI)
- Plugin registry (allow custom analyzers)

---

**Document Status:** Living document, updated as architecture evolves  
**Maintainer:** Sean Rugg  
**Review Schedule:** After each major phase completion
