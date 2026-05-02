# Phase 1 Testing Guide — Core Analysis Engine

This document describes how to test the Phase 1 core analysis engine functionality.

## Prerequisites

- Moodle 4.5+ or 5.1 installed
- Assignment LID plugin installed (all Phase 0 + Phase 1 files)
- Google AI Studio API key configured
- At least one test course with:
  - One assignment (with online text or file submission enabled)
  - One student enrolled
  - One submitted essay (500+ words recommended)

## Installation Verification

### 1. Check Plugin Installation

```bash
# Verify files are in place
ls -la /path/to/moodle/mod/assign/submission/lid/

# Expected: version.php, lib.php, settings.php, classes/, db/, etc.
```

### 2. Run Database Upgrade

**Via Web:**
1. Log in as admin
2. Navigate to **Site administration → Notifications**
3. Click **Upgrade Moodle database now**

**Via CLI:**
```bash
php admin/cli/upgrade.php
```

### 3. Verify Database Tables

```sql
-- Check tables were created
SHOW TABLES LIKE 'mdl_assignsubmission_lid%';

-- Expected output:
-- mdl_assignsubmission_lid_queue
-- mdl_assignsubmission_lid_analysis

-- Check table structure
DESCRIBE mdl_assignsubmission_lid_queue;
DESCRIBE mdl_assignsubmission_lid_analysis;
```

### 4. Verify Scheduled Task

1. Navigate to **Site administration → Server → Scheduled tasks**
2. Search for "Process LID Analysis Queue"
3. Verify task is enabled and scheduled to run every 5 minutes

## Configuration Verification

### 1. Configure API Settings

1. Go to **Site administration → Plugins → Assignment submissions → Learning Intelligence Dashboard**
2. Enter your Google AI Studio API key
3. Verify other settings:
   - **API Endpoint**: Should be Google's OpenAI-compatible endpoint
   - **Model**: gemini-2.5-flash recommended
   - **Max Output Tokens**: 16384
   - **Queue Processing Interval**: 300 seconds
   - **Max Retries**: 3
   - **Enable Cost Tracking**: Yes
4. Save changes

### 2. Test API Connection

Create a simple PHP test script:

```php
<?php
require_once(__DIR__ . '/config.php');

use assignsubmission_lid\gemini_client;

$client = new gemini_client();

echo "Testing API connection...\n";

if ($client->test_connection()) {
    echo "✓ API connection successful!\n";
} else {
    echo "✗ API connection failed. Check API key and endpoint.\n";
}
```

Save as `test_api.php` in Moodle root and run:
```bash
php test_api.php
```

## Functional Testing

### Test 1: Enable LID for Assignment

1. Go to your test course
2. Create or edit an assignment
3. Scroll to **Learning Intelligence Dashboard (LID)** section
4. Check **Enable LID analysis for this assignment**
5. Check **Include competency analysis** (if competencies configured)
6. Check **Generate rubric score suggestions** (if rubric configured)
7. Save assignment

**Expected:** Settings save without errors.

### Test 2: Submit Sample Essay

1. Switch to student account (or create test student)
2. Open the assignment
3. Click **Add submission**
4. Paste this sample essay (or write your own 500+ word essay):

```
Climate Change Mitigation: A Multi-Faceted Approach

Climate change represents one of the most pressing challenges facing humanity 
in the 21st century. This essay examines the scientific evidence for 
anthropogenic climate change and evaluates the effectiveness of various 
mitigation strategies, arguing that a comprehensive approach combining policy 
reform, technological innovation, and behavioral change is essential for 
meaningful progress.

The Scientific Consensus

The scientific evidence for human-caused climate change is overwhelming. 
According to the Intergovernmental Panel on Climate Change (IPCC), human 
activities have caused approximately 1.0°C of global warming above 
pre-industrial levels, with current rates of warming likely to reach 1.5°C 
between 2030 and 2052 if current trends continue. This warming is primarily 
driven by greenhouse gas emissions from fossil fuel combustion, deforestation, 
and industrial processes.

The consequences of inaction are severe. Rising global temperatures contribute 
to sea-level rise, extreme weather events, ocean acidification, and disruptions 
to ecosystems and agricultural systems. These impacts disproportionately affect 
vulnerable populations in developing nations, raising significant ethical 
concerns about climate justice.

Policy Interventions

Effective climate policy must balance environmental protection with economic 
sustainability. Carbon pricing mechanisms, such as carbon taxes and cap-and-trade 
systems, create economic incentives for emissions reductions. Countries like 
Sweden and Canada have demonstrated that carbon taxes can reduce emissions while 
maintaining economic growth. However, implementation challenges include political 
resistance, concerns about competitiveness, and the need for revenue recycling 
to protect low-income households.

Regulatory approaches, such as renewable energy standards and vehicle emissions 
standards, complement market-based policies. The European Union's Emissions 
Trading System and California's clean energy mandates show how regulatory 
frameworks can drive technological adoption and industrial transformation.

Technological Solutions

Technological innovation offers promising pathways for decarbonization. 
Renewable energy technologies, particularly solar and wind power, have 
experienced dramatic cost reductions, making them competitive with fossil 
fuels in many markets. Grid-scale energy storage solutions, such as advanced 
batteries and pumped hydro storage, address the intermittency challenges of 
renewable energy.

Carbon capture and storage (CCS) technologies may play a role in decarbonizing 
heavy industry and enabling negative emissions. However, CCS remains expensive 
and energy-intensive, raising questions about scalability and cost-effectiveness 
compared to emissions prevention strategies.

Individual and Collective Action

While systemic change requires policy and technological solutions, individual 
actions aggregate to meaningful impact. Dietary shifts toward plant-based foods, 
transportation choices favoring public transit and active transportation, and 
consumption patterns emphasizing durability and repair over disposability all 
contribute to emissions reductions.

However, placing primary responsibility on individual consumers risks obscuring 
the structural factors that constrain choice. Effective climate action requires 
both individual engagement and systemic transformation of energy systems, urban 
design, and economic incentives.

Conclusion

Addressing climate change demands coordinated action at multiple scales. 
Policy interventions must create enabling conditions for technological innovation 
while ensuring equitable distribution of costs and benefits. Technological 
solutions must be deployed rapidly and at scale, supported by appropriate 
infrastructure and regulatory frameworks. Individual actions, while insufficient 
alone, reinforce cultural shifts toward sustainability and create political 
pressure for systemic change.

The window for limiting warming to 1.5°C is closing, but pathways to meaningful 
climate action remain available. Success requires unprecedented cooperation 
between governments, businesses, civil society, and individuals, guided by 
scientific evidence and principles of climate justice.
```

5. Click **Save changes** then **Submit assignment**

**Expected:** Submission saves successfully.

### Test 3: Queue Analysis (Manual Method)

Since we haven't built the UI integration yet, we'll queue the analysis via PHP:

Create `test_queue.php` in Moodle root:

```php
<?php
require_once(__DIR__ . '/config.php');
require_login();

use assignsubmission_lid\analyzer;

// Get the submission ID from URL parameter
$submissionid = required_param('submissionid', PARAM_INT);

$analyzer = new analyzer();

echo "Queuing analysis for submission {$submissionid}...\n";

try {
    $queueid = $analyzer->queue_analysis($submissionid);
    
    if ($queueid > 0) {
        echo "✓ Successfully queued! Queue ID: {$queueid}\n";
        echo "Check status: SELECT * FROM mdl_assignsubmission_lid_queue WHERE id = {$queueid};\n";
    } else {
        echo "Already analyzed or queued.\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
```

Get the submission ID from the database:
```sql
SELECT id, userid, assignment, attemptnumber 
FROM mdl_assign_submission 
WHERE status = 'submitted' 
ORDER BY id DESC 
LIMIT 1;
```

Run the test script:
```bash
php test_queue.php?submissionid=123  # Replace 123 with actual ID
```

**Expected:** "Successfully queued!" message.

### Test 4: Process Queue Manually

Run the CLI queue processor:

```bash
php mod/assign/submission/lid/process_queue_cli.php --verbose
```

**Expected Output:**
```
LID Analysis Queue Processor
================================================================================

Starting queue processing...

Processing LID analysis job 1 for submission 123...
  ✓ Completed successfully (analysis ID: 1)

Queue processing complete.
```

**Monitor for errors:**
- "Error: API key not configured" → Check settings
- "Failed to extract submission text" → Check submission has content
- "Invalid response from AI service" → Check API endpoint and key
- "HTTP 429: Rate limit exceeded" → Wait a few minutes and retry

### Test 5: Verify Analysis Stored

Check the database:

```sql
-- Check queue status
SELECT id, submissionid, status, error_message, processed_at
FROM mdl_assignsubmission_lid_queue
WHERE submissionid = 123;  -- Replace with your submission ID

-- Check analysis result
SELECT id, submissionid, analyzed_at, api_cost_usd, 
       input_tokens, output_tokens, model_version,
       LEFT(analysis_json, 200) as json_preview
FROM mdl_assignsubmission_lid_analysis
WHERE submissionid = 123;
```

**Expected:**
- Queue status = 'completed'
- Analysis record exists
- analysis_json contains valid JSON
- Token counts > 0
- API cost > 0 (if cost tracking enabled)

### Test 6: Verify Analysis JSON Structure

Extract and validate the JSON:

```sql
SELECT analysis_json
FROM mdl_assignsubmission_lid_analysis
WHERE submissionid = 123;
```

Copy the JSON and validate it has the expected structure:

```json
{
  "schema_version": "1.0",
  "submission_analysis": {
    "overall_quality_score": 75,
    "cognitive_depth_score": 78,
    "coherence_score": 82,
    "evidence_quality_score": 76
  },
  "rubric_evaluation": [...],
  "competency_demonstration": [...],
  "formative_feedback": {
    "key_strengths": [...],
    "development_priorities": [...],
    "next_steps": [...]
  },
  "bloom_distribution": {...},
  "meta": {...}
}
```

**Expected:**
- All top-level keys present
- Scores are numbers 0-100
- Arrays are not empty (if rubric/competencies configured)
- formative_feedback has 3-5 items in each array

## Performance Testing

### Test 7: Batch Queue Multiple Submissions

If you have multiple submissions, test batch queuing:

```php
<?php
require_once(__DIR__ . '/config.php');

use assignsubmission_lid\analyzer;

$analyzer = new analyzer();

// Get all submitted assignments
$submissions = $DB->get_records_sql("
    SELECT id 
    FROM {assign_submission} 
    WHERE status = 'submitted' 
    AND assignment = ?
    LIMIT 10
", [123]);  // Replace 123 with your assignment ID

$submissionids = array_keys($submissions);

echo "Batch queuing " . count($submissionids) . " submissions...\n";

$queueids = $analyzer->batch_queue_analyses($submissionids);

echo "Queued " . count($queueids) . " analyses.\n";
```

**Expected:** All submissions queued without errors.

### Test 8: Process Multiple Jobs

Run queue processor and time it:

```bash
time php mod/assign/submission/lid/process_queue_cli.php
```

**Expected:**
- 10 submissions processed in < 5 minutes
- Average ~18-30 seconds per submission
- No timeout errors

## Error Handling Testing

### Test 9: Empty Submission

1. Create new submission with empty text
2. Queue analysis
3. Process queue

**Expected:** Status = 'failed', error_message = "Cannot analyze empty submission"

### Test 10: Invalid API Key

1. Change API key to invalid value in settings
2. Queue analysis
3. Process queue

**Expected:** Status = 'failed', error_message contains "API key" or "401"

### Test 11: Retry Logic

1. Temporarily break API (set invalid endpoint)
2. Queue analysis
3. Process queue 3 times
4. Check queue status

**Expected:** 
- Attempt counter increments
- After 3 attempts, status = 'failed'

## Cost Tracking Verification

### Test 12: Verify Cost Calculation

```sql
SELECT 
    COUNT(*) as analyses,
    SUM(input_tokens) as total_input,
    SUM(output_tokens) as total_output,
    SUM(thought_tokens) as total_thought,
    SUM(api_cost_usd) as total_cost,
    AVG(api_cost_usd) as avg_cost
FROM mdl_assignsubmission_lid_analysis;
```

**Expected:**
- Total cost matches manual calculation based on token counts
- Average cost ~$0.002-0.005 per analysis

## Success Criteria

Phase 1 is complete when:

- [x] All classes compile without errors
- [x] Database tables created successfully
- [x] Scheduled task registered and runnable
- [x] API connection test passes
- [x] Single submission can be queued
- [x] Queue processor runs without errors
- [x] Analysis JSON is valid and well-formed
- [x] Scores are in expected ranges (0-100)
- [x] Formative feedback is specific and actionable
- [x] Token counts and costs are tracked correctly
- [x] Retry logic works for failed jobs
- [x] Batch queuing works for multiple submissions

## Next Steps

After Phase 1 testing passes:
- **Phase 2:** Build dashboard views (assignment-level, course-level, student-level)
- **Phase 3:** Integrate into Moodle UI (grading interface, bulk actions)
- **Phase 4:** Add rubric and competency deep integration
- **Phase 5:** Error handling refinements
- **Phase 6:** GDPR compliance implementation

## Troubleshooting

### Common Issues

**"Class not found" errors**
- Run: `php admin/cli/purge_caches.php`
- Check file permissions

**"API key not configured"**
- Verify in settings page
- Check database: `SELECT * FROM mdl_config_plugins WHERE plugin = 'assignsubmission_lid' AND name = 'apikey';`

**Queue not processing**
- Check cron is running: `php admin/cli/cron.php`
- Run manually: `php mod/assign/submission/lid/process_queue_cli.php --verbose`

**Invalid JSON responses**
- Check prompt template exists
- Verify API endpoint is correct
- Test with simpler prompt

## Support

If you encounter issues not covered here:
1. Check error logs: **Site administration → Reports → Logs**
2. Enable debugging: `$CFG->debug = DEBUG_DEVELOPER;`
3. Report issue: https://github.com/seanrugg/assignment_lid/issues
