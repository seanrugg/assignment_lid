# Assignment LID Plugin — Installation & Development

This directory contains the Assignment LID plugin for Moodle. For complete project documentation, see the [main repository](https://github.com/seanrugg/assignment_lid).

## Quick Installation

### 1. Install Plugin Files

```bash
cd /path/to/moodle/mod/assign/submission/
git clone https://github.com/seanrugg/assignment_lid.git lid
```

Or download and extract manually to `/mod/assign/submission/lid/`

### 2. Install Database Tables

Log in as admin and navigate to:
**Site administration → Notifications**

Click **"Upgrade Moodle database now"**

### 3. Configure API Settings

Navigate to:
**Site administration → Plugins → Assignment submissions → Learning Intelligence Dashboard (LID)**

Enter your Google AI Studio API key and configure other settings.

### 4. Enable Scheduled Task

Navigate to:
**Site administration → Server → Scheduled tasks**

Find **"Process LID Analysis Queue"** and verify it's enabled.

## Directory Structure

```
lid/
├── classes/              # Core PHP classes (PSR-4 autoloaded)
│   ├── analyzer.php      # Analysis orchestrator (to be created)
│   ├── privacy/          # GDPR compliance (to be created)
│   └── task/             # Scheduled tasks (to be created)
├── db/                   # Database definitions
│   ├── access.php        # Capabilities ✓
│   ├── install.xml       # Schema ✓
│   └── upgrade.php       # Migrations ✓
├── lang/en/              # Language strings
│   └── assignsubmission_lid.php ✓
├── prompts/              # LLM prompt templates
│   └── assignment-analyzer-prompt.md ✓
├── templates/            # Mustache templates
│   ├── no_analysis.mustache ✓
│   └── analysis_summary.mustache ✓
├── lib.php               # Plugin callbacks ✓
├── settings.php          # Admin settings ✓
├── styles.css            # Styling ✓
├── version.php           # Plugin metadata ✓
└── README.md             # This file
```

✓ = Created  
(to be created) = Phase 1-2 development

## Development Status

**Phase 0: Setup & Scaffolding** — ✅ COMPLETE
- [x] Plugin structure created
- [x] Database schema defined
- [x] Language strings complete
- [x] Settings page configured
- [x] Basic templates created
- [x] Prompt template drafted

**Phase 1: Core Analysis Engine** — 🔄 IN PROGRESS
- [ ] Implement analyzer.php
- [ ] Implement gemini_client.php
- [ ] Implement prompt_builder.php
- [ ] Create scheduled task
- [ ] Test single submission analysis

**Next Steps:**
See [ROADMAP.md](../../../ROADMAP.md) for full development timeline.

## Testing the Plugin

### Manual Test (After Phase 1)

1. Create a test assignment with "Online text" submission type
2. Enable "Learning Intelligence Dashboard" in assignment settings
3. Submit a sample essay as a student
4. As instructor, click "Analyze with LID" in grading interface
5. Wait for scheduled task to process (or run manually)
6. Verify analysis appears in grading view

### Run Scheduled Task Manually

```bash
php admin/cli/scheduled_task.php \
  --execute='\assignsubmission_lid\task\process_queue'
```

## Configuration

### Required Settings

- **API Key**: Google AI Studio API key ([get free key](https://aistudio.google.com/app/apikey))
- **API Endpoint**: Default is Google's OpenAI-compatible endpoint
- **Model**: `gemini-2.5-flash` recommended

### Optional Settings

- Queue processing interval (default: 300 seconds)
- Cost tracking (enabled by default)
- Token pricing (pre-configured for Gemini 2.5 Flash)

## Troubleshooting

### Plugin doesn't appear after installation

1. Clear Moodle caches: `php admin/cli/purge_caches.php`
2. Verify files are in correct directory: `/mod/assign/submission/lid/`
3. Check file permissions

### Database tables not created

1. Check for errors in **Site administration → Reports → Logs**
2. Verify `install.xml` is valid XML
3. Try manual upgrade: `php admin/cli/upgrade.php`

### Analysis not processing

1. Verify scheduled task is enabled
2. Check cron is running: `php admin/cli/cron.php`
3. Review queue status in database:
   ```sql
   SELECT * FROM mdl_assignsubmission_lid_queue 
   WHERE status='pending' OR status='failed';
   ```

## Support

- **Issues**: [GitHub Issues](https://github.com/seanrugg/assignment_lid/issues)

## License

GPL v3 or later — see [LICENSE](../../../LICENSE)

## Credits

Developed by Sean Rugg as part of the Learning Intelligence Dashboard (LID) Framework.
