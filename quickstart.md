# Assignment LID — Developer Quick Start

Get up and running with Assignment LID development in under 30 minutes.

---

## Prerequisites Checklist

Before you begin, ensure you have:

- [ ] Moodle 4.5+ or 5.1 installed (local or remote)
- [ ] PHP 8.1+ with required extensions
- [ ] MariaDB 10.6+ or PostgreSQL 13+
- [ ] Composer installed
- [ ] Git installed
- [ ] Google AI Studio API key ([get free key](https://aistudio.google.com/app/apikey))
- [ ] `pdftotext` installed (optional but recommended)

---

## Quick Setup (5 Minutes)

### 1. Clone the Repository

```bash
cd /path/to/moodle/mod/assign/submission/
git clone https://github.com/seanrugg/assignment_lid.git lid
cd lid
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Install the Plugin in Moodle

**Via Web Interface:**
1. Log in to Moodle as admin
2. Navigate to **Site administration → Notifications**
3. Click **Upgrade Moodle database now**
4. Follow the prompts

**Via CLI (faster):**
```bash
cd /path/to/moodle
php admin/cli/upgrade.php
```

### 4. Configure API Settings

1. Go to **Site administration → Plugins → Assignment submissions → Learning Intelligence Dashboard**
2. Enter your **Google AI Studio API key**
3. Leave other settings at defaults for now
4. Click **Save changes**

### 5. Verify Installation

```bash
# Check plugin is registered
php admin/cli/plugin_info.php assignsubmission_lid

# Check database tables were created
mysql -u root -p moodle_db -e "SHOW TABLES LIKE 'mdl_assignsubmission_lid%';"

# Expected output:
# mdl_assignsubmission_lid_queue
# mdl_assignsubmission_lid_analysis
```

✅ **Installation complete!**

---

## Your First Analysis (10 Minutes)

### Create a Test Assignment

1. In Moodle, go to a test course
2. **Add an activity or resource → Assignment**
3. Configure:
   - **Assignment name:** "Test Essay for LID"
   - **Submission types:** Check "Online text"
   - **Advanced grading method:** Select "Rubric" (optional but recommended)
   - Scroll to **Learning Intelligence Dashboard** section
   - Check "Enable LID analysis for this assignment"
4. **Save and display**

### Add a Rubric (Optional but Recommended)

1. In the assignment, click **Advanced grading**
2. Select **Rubric** from dropdown
3. **Define new grading form from scratch**
4. Create 2-3 simple criteria:
   - **Criterion 1:** "Thesis Clarity" (0-5 points)
   - **Criterion 2:** "Use of Evidence" (0-5 points)
   - **Criterion 3:** "Organization" (0-5 points)
5. **Save rubric**

### Submit a Sample Essay

1. Switch to a student account (or create one)
2. Open the assignment
3. Click **Add submission**
4. Paste this sample text:

```
Climate change represents one of the most pressing challenges of our time. 
This essay argues that immediate action is necessary to mitigate its worst 
effects through a combination of policy reform, technological innovation, 
and individual behavioral change.

The scientific consensus is clear: human activities, particularly the 
burning of fossil fuels, have led to unprecedented levels of atmospheric 
CO2. According to the IPCC's latest report, we have less than a decade 
to limit warming to 1.5°C above pre-industrial levels.

Policy interventions must include carbon pricing mechanisms, renewable 
energy subsidies, and strict emissions standards. Countries like Denmark 
and Costa Rica have demonstrated that economic growth and environmental 
protection are not mutually exclusive.

Technological solutions, from carbon capture to grid-scale battery storage, 
offer promise but require massive investment. Meanwhile, individual actions 
- from dietary changes to transportation choices - aggregate to meaningful 
impact.

In conclusion, addressing climate change demands coordinated action at all 
levels. The window for meaningful intervention is closing, but solutions 
exist if we have the collective will to implement them.
```

5. Click **Save changes**
6. **Submit assignment**

### Analyze the Submission

1. Switch back to teacher account
2. Go to the assignment → **View all submissions**
3. Find the student's row
4. Click **Grade** (or click directly on submission)
5. In the grading panel, click **"Analyze with LID"** button
6. Wait 20-30 seconds
7. See analysis results appear in the panel

**Expected Output:**
- Overall quality score (~75-85)
- Rubric criterion suggestions (if rubric enabled)
- Competency demonstrations (if competencies enabled)
- Formative feedback (strengths, areas for growth, next steps)

✅ **First analysis complete!**

---

## Development Workflow

### Project Structure

```
assignment_lid/
├── classes/              # PHP classes (PSR-4 autoloaded)
│   ├── analyzer.php
│   ├── prompt_builder.php
│   ├── gemini_client.php
│   ├── rubric_parser.php
│   ├── competency_mapper.php
│   ├── task/
│   │   └── process_queue.php
│   ├── output/
│   │   ├── assignment_dashboard.php
│   │   ├── course_dashboard.php
│   │   └── student_dashboard.php
│   └── privacy/
│       └── provider.php
├── db/
│   ├── install.xml       # Database schema
│   └── upgrade.php       # Schema migrations
├── lang/en/
│   └── assignsubmission_lid.php  # Language strings
├── templates/            # Mustache templates
├── amd/src/              # JavaScript (AMD modules)
├── tests/                # PHPUnit tests
├── prompts/
│   └── assignment-analyzer-prompt.md
├── version.php           # Plugin metadata
├── lib.php               # Plugin callbacks
└── settings.php          # Admin settings
```

### Making Changes

#### 1. Code Changes

```bash
# Create feature branch
git checkout -b feature/my-new-feature

# Make changes to files
# Example: Edit classes/analyzer.php

# Run code style check
vendor/bin/phpcs --standard=moodle classes/analyzer.php

# Fix style issues automatically
vendor/bin/phpcbf --standard=moodle classes/analyzer.php

# Commit changes
git add classes/analyzer.php
git commit -m "Add retry logic to analyzer"
```

#### 2. Database Schema Changes

If you need to add/modify database tables:

```bash
# 1. Edit db/install.xml (for fresh installs)
# 2. Increment version in version.php
# 3. Add upgrade step to db/upgrade.php
```

Example upgrade step:

```php
// In db/upgrade.php
function xmldb_assignsubmission_lid_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026042702) {
        // Define new field
        $table = new xmldb_table('assignsubmission_lid_analysis');
        $field = new xmldb_field('confidence_score', XMLDB_TYPE_INTEGER, '3', null, null, null, null, 'model_version');

        // Add field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026042702, 'assignsubmission', 'lid');
    }

    return true;
}
```

Then run:

```bash
php admin/cli/upgrade.php
```

#### 3. Language String Changes

Add strings to `lang/en/assignsubmission_lid.php`:

```php
$string['analyze_button'] = 'Analyze with LID';
$string['analysis_pending'] = 'Analysis queued. Refresh in 2-3 minutes.';
$string['analysis_failed'] = 'Analysis failed: {$a}';
```

Use in code:

```php
echo get_string('analyze_button', 'assignsubmission_lid');
```

#### 4. Testing Your Changes

```bash
# Run unit tests
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit tests/analyzer_test.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

---

## Common Development Tasks

### Task 1: Add a New Field to Analysis Output

**Goal:** Add a "readability_score" field to the JSON output.

1. **Update the prompt** (`prompts/assignment-analyzer-prompt.md`):
   ```markdown
   "submission_analysis": {
     "overall_quality_score": 0-100,
     "cognitive_depth_score": 0-100,
     "coherence_score": 0-100,
     "evidence_quality_score": 0-100,
     "readability_score": 0-100  // NEW
   }
   ```

2. **Update the dashboard template** (`templates/assignment_dashboard.mustache`):
   ```html
   <div class="metric">
       <span class="label">Readability</span>
       <span class="value">{{readability_score}}</span>
   </div>
   ```

3. **Test:**
   - Re-analyze a submission
   - Check JSON output in database
   - Verify it appears in dashboard

### Task 2: Add a New Dashboard Filter

**Goal:** Add date range filter to course dashboard.

1. **Update output class** (`classes/output/course_dashboard.php`):
   ```php
   public function get_analyses($courseid, $start_date = null, $end_date = null) {
       $params = ['courseid' => $courseid];
       $sql_where = "WHERE courseid = :courseid";
       
       if ($start_date) {
           $sql_where .= " AND analyzed_at >= :start_date";
           $params['start_date'] = $start_date;
       }
       if ($end_date) {
           $sql_where .= " AND analyzed_at <= :end_date";
           $params['end_date'] = $end_date;
       }
       
       $sql = "SELECT * FROM {assignsubmission_lid_analysis} $sql_where";
       return $DB->get_records_sql($sql, $params);
   }
   ```

2. **Update template** to add date pickers

3. **Add AMD JavaScript** for date filter interaction

### Task 3: Modify the Queue Priority Logic

**Goal:** Rush assignments get higher priority.

1. **Edit** `classes/analyzer.php`:
   ```php
   public function queue_analysis(int $submissionid, int $priority = 0): int {
       // Check if assignment is marked as "rush"
       $assignment = $this->get_assignment($submissionid);
       if ($assignment->rush_grading) {
           $priority = 10; // Higher priority
       }
       
       // Insert into queue with calculated priority
       $queue_entry = new stdClass();
       $queue_entry->submissionid = $submissionid;
       $queue_entry->priority = $priority;
       // ... rest of fields
   }
   ```

2. **Test:** Create rush assignment, verify it processes first

---

## Debugging Tips

### Enable Debug Mode

In Moodle config.php:

```php
$CFG->debug = (E_ALL | E_STRICT);
$CFG->debugdisplay = 1;
```

### View Error Logs

```bash
# Moodle logs
tail -f /path/to/moodledata/error_log

# Apache/Nginx logs
tail -f /var/log/apache2/error.log
```

### Debug Queue Processing

```bash
# Run queue processor manually with verbose output
php mod/assign/submission/lid/process_queue_cli.php --verbose

# Check queue status
mysql -u root -p moodle_db -e "SELECT * FROM mdl_assignsubmission_lid_queue WHERE status='pending';"
```

### Debug API Calls

Add logging to `classes/gemini_client.php`:

```php
private function make_request(array $payload): string {
    debugging('Gemini API Request: ' . json_encode($payload), DEBUG_DEVELOPER);
    
    $response = $this->curl_client->post($this->endpoint, $payload);
    
    debugging('Gemini API Response: ' . $response, DEBUG_DEVELOPER);
    
    return $response;
}
```

### Inspect Database

```bash
# View recent analyses
mysql -u root -p moodle_db -e "
SELECT id, userid, analyzed_at, api_cost_usd, model_version 
FROM mdl_assignsubmission_lid_analysis 
ORDER BY analyzed_at DESC 
LIMIT 10;
"

# View failed jobs
mysql -u root -p moodle_db -e "
SELECT id, submissionid, status, error_message, attempt 
FROM mdl_assignsubmission_lid_queue 
WHERE status='failed';
"
```

---

## Useful Commands

### Moodle CLI

```bash
# Purge all caches
php admin/cli/purge_caches.php

# Upgrade database
php admin/cli/upgrade.php

# Run scheduled task immediately
php admin/cli/scheduled_task.php --execute='\assignsubmission_lid\task\process_queue'

# List all plugins
php admin/cli/plugin_info.php
```

### Git

```bash
# Check status
git status

# View diff
git diff classes/analyzer.php

# Commit with message
git commit -m "Fix: Handle empty submissions gracefully"

# Push to branch
git push origin feature/my-new-feature

# Pull latest changes
git pull origin main
```

### Composer

```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Dump autoload (if you add new classes)
composer dump-autoload
```

---

## Getting Help

### Documentation
- **README.md** — Overview and installation
- **ARCHITECTURE.md** — Technical deep-dive
- **CONTRIBUTING.md** — Contribution guidelines
- **Moodle Dev Docs** — https://moodledev.io

### Community
- **GitHub Issues** — Report bugs, request features
- **GitHub Discussions** — Ask questions, share ideas
- **Moodle Forums** — General Moodle development questions


---

## Next Steps

Now that you're set up:

1. **Read the scope document** — Understand project goals and boundaries
2. **Review ARCHITECTURE.md** — Understand system design
3. **Check ROADMAP.md** — See current development phase
4. **Pick a task** — Look for "good first issue" labels on GitHub
5. **Join the community** — Introduce yourself in Discussions

Happy coding! 🚀
