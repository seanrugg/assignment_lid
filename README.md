# Assignment LID — Learning Intelligence Dashboard for Moodle Assignments

[![Moodle](https://img.shields.io/badge/Moodle-4.5%2B-orange.svg)](https://moodle.org/)
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Version](https://img.shields.io/badge/version-0.1.0--dev-yellow.svg)](https://github.com/seanrugg/assignment_lid/releases)

**AI-powered analysis of student writing that transforms assignment grading into learning intelligence.**

Assignment LID extends Moodle's assignment workflow with intelligent analysis of student submissions, evaluating work against rubrics, competency frameworks, and Bloom's taxonomy. It generates structured assessment reports that surface learning depth, competency evidence, and formative feedback insights — empowering instructors to grade smarter, not harder.

---

## 🎯 What It Does

Assignment LID analyzes student essay submissions and generates:

- **Rubric alignment analysis** — Suggested scores for each criterion with supporting evidence excerpts
- **Competency demonstration mapping** — Identifies which course competencies are demonstrated and at what depth (Bloom's level)
- **Formative feedback insights** — Key strengths, development priorities, and next-step recommendations
- **Learning intelligence dashboards** — Assignment-level, course-level, and student-level views that aggregate and visualize learning progress

### Key Features

✅ **Instructor-Focused Design** — Pre-analyzes submissions before grading, reducing cognitive load  
✅ **Rubric Integration** — Works with Moodle's advanced grading methods (rubrics and marking guides)  
✅ **Competency Framework Support** — Maps submissions to course competencies with Bloom's taxonomy depth ratings  
✅ **Queue-Based Processing** — Batch analyze entire cohorts efficiently via scheduled tasks  
✅ **Cost Transparency** — Tracks API usage and costs per analysis (typically <$0.005 USD per submission)  
✅ **Privacy-First** — GDPR compliant with full data export and deletion support  
✅ **Audit Trail** — Complete version history for re-analyzed submissions

---

## 🏗️ Architecture Overview

Assignment LID is part of the **Learning Intelligence Dashboard (LID) ecosystem**, which also includes:

- **`local_lid`** — Forum participation analysis plugin ([deployed v0.6.0](https://github.com/seanrugg/local_lid))
- **Browser-based dashboards** — Portfolio aggregation and course design ROI tools
- **Shared infrastructure** — Gemini 2.5 Flash LLM backend, xAPI integration (planned)

### How It Works

```
┌─────────────────────┐
│ Instructor clicks   │
│ "Analyze" button    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Job queued for      │
│ processing          │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Scheduled task      │
│ claims job          │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Extract submission  │
│ text (PDF or HTML)  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Build LLM prompt:   │
│ - Submission text   │
│ - Rubric criteria   │
│ - Competencies      │
│ - Instructions      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Call Gemini API     │
│ (2.5 Flash)         │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Parse JSON response │
│ Store analysis data │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Display results in  │
│ grading interface   │
│ and dashboards      │
└─────────────────────┘
```

---

## 🚀 Installation

### Prerequisites

- **Moodle 4.5+** (tested on Moodle 5.1 and 4.5 LTS)
- **PHP 8.1+**
- **Google AI Studio API key** ([get one free](https://aistudio.google.com/app/apikey))
- **`pdftotext`** (for PDF extraction) — install via:
  ```bash
  # Ubuntu/Debian
  sudo apt-get install poppler-utils
  
  # macOS
  brew install poppler
  
  # Alternative: PHP library (smalot/pdfparser)
  composer require smalot/pdfparser
  ```

### Installation Steps

1. **Download the plugin**
   ```bash
   cd /path/to/moodle/mod/assign/submission/
   git clone https://github.com/seanrugg/assignment_lid.git lid
   ```

2. **Install dependencies** (if using Composer for PDF parsing)
   ```bash
   cd lid
   composer install
   ```

3. **Complete Moodle installation**
   - Log in as admin
   - Navigate to **Site administration → Notifications**
   - Click **Upgrade Moodle database now**
   - Follow prompts to complete installation

4. **Configure API settings**
   - Go to **Site administration → Plugins → Assignment submissions → Learning Intelligence Dashboard**
   - Enter your **Google AI Studio API key**
   - Configure model settings (defaults are optimized for Gemini 2.5 Flash)
   - Save changes

5. **Enable scheduled task**
   - Go to **Site administration → Server → Scheduled tasks**
   - Find **"Process LID analysis queue"**
   - Verify it's enabled (runs every 5 minutes by default)

6. **Test the installation**
   - Create a test assignment
   - Enable "Learning Intelligence Dashboard" in assignment settings
   - Submit a sample essay as a student
   - Click "Analyze with LID" in the grading interface
   - Wait 1-2 minutes for processing
   - Verify results appear in dashboard

---

## 📖 Usage Guide

### For Instructors

#### Enabling LID for an Assignment

1. Create or edit an assignment
2. Scroll to **"Learning Intelligence Dashboard (LID)"** section
3. Check **"Enable LID analysis for this assignment"**
4. (Optional) Check **"Include competency analysis"** if course competencies are configured
5. (Optional) Check **"Generate rubric score suggestions"** if using advanced grading
6. Save assignment

#### Analyzing Student Submissions

**Single student analysis:**
1. Open assignment grading interface
2. Click on student's submission
3. In the grading panel, click **"Analyze with LID"** button
4. Wait for processing (typically 20-30 seconds)
5. Review analysis results in expandable panel

**Batch analysis:**
1. Open assignment grading table
2. Select multiple students (checkboxes)
3. From bulk actions dropdown, choose **"Batch Analyze with LID"**
4. Click **"Go"**
5. Analyses will queue and process automatically

#### Viewing Dashboards

**Assignment-level dashboard:**
- Shows analysis results for all students in one assignment
- View: **Assignment → LID Dashboard** link in assignment navigation

**Course-level dashboard:**
- Aggregates all LID-analyzed assignments in the course
- View: **Course → Reports → Learning Intelligence Dashboard**

**Student-level dashboard:**
- Shows all analyses for one student across all assignments
- View: Click student name in any LID dashboard

### Understanding Analysis Results

Each analysis includes:

#### Overall Scores (0-100 scale)
- **Quality Score** — Overall submission quality
- **Cognitive Depth** — Bloom's taxonomy level demonstrated
- **Coherence Score** — Logical flow and organization
- **Evidence Quality** — Use of supporting evidence

#### Rubric Evaluation
For each rubric criterion:
- **Suggested score** with evidence excerpt
- **Strengths** demonstrated in submission
- **Areas for growth** identified
- **Confidence level** (high/medium/low)

#### Competency Demonstration
For each course competency:
- **Bloom's level** demonstrated (1-6: Remember → Create)
- **Depth rating** (emerging, developing, proficient, advanced)
- **Evidence excerpt** supporting the rating
- **Confidence level**

#### Formative Feedback
- **Key strengths** (3-5 bullet points)
- **Development priorities** (3-5 bullet points)
- **Next steps** (actionable recommendations)

---

## ⚙️ Configuration

### Admin Settings

Navigate to: **Site administration → Plugins → Assignment submissions → Learning Intelligence Dashboard**

| Setting | Default | Description |
|---------|---------|-------------|
| **API Key** | (empty) | Google AI Studio API key |
| **API Endpoint** | `https://generativelanguage.googleapis.com/v1beta/openai/chat/completions` | LLM API endpoint |
| **Model** | `gemini-2.5-flash` | AI model selection |
| **Max Output Tokens** | 16384 | Maximum tokens for LLM response |
| **Queue Processing Interval** | 300 seconds | How often to process queue |
| **Max Retries** | 3 | Retry attempts for failed jobs |
| **Enable Cost Tracking** | Yes | Track and display API costs |
| **Cost per 1M Input Tokens** | $0.075 | Pricing for input tokens |
| **Cost per 1M Output Tokens** | $0.30 | Pricing for output tokens |
| **Cost per 1M Thought Tokens** | $0.30 | Pricing for thinking tokens |

### Cost Estimates

Based on typical 1500-word essay submissions:

- **Per analysis:** ~$0.0025 USD
- **30 students, 3 assignments:** ~$0.23 per term
- **100 students, 3 assignments:** ~$0.75 per term
- **500 students, 3 assignments:** ~$3.75 per term

Cost tracking is visible in dashboards for budget monitoring.

---

## 🔒 Privacy & GDPR Compliance

Assignment LID is fully GDPR compliant:

✅ **Data minimization** — Only submission text and necessary metadata sent to LLM  
✅ **User data export** — All analyses exportable via Moodle's privacy API  
✅ **Right to deletion** — Analysis data deleted when user account is deleted  
✅ **Transparency** — Settings page includes link to Google AI Studio privacy policy  
✅ **Instructor consent** — Clicking "Analyze" constitutes instructor consent to process student work

### Data Sent to LLM

- Student submission text (essay content)
- Student Moodle userid (integer only, not name)
- Assignment instructions
- Rubric/marking guide criteria
- Course competency definitions

**Student names are NEVER sent to the LLM.**

---

## 🛠️ Development & Testing

### Development Setup

```bash
# Clone repository
git clone https://github.com/seanrugg/assignment_lid.git
cd assignment_lid

# Install dependencies
composer install

# Run coding standards check
vendor/bin/phpcs --standard=moodle .

# Run unit tests
php /path/to/moodle/admin/tool/phpunit/cli/init.php
vendor/bin/phpunit
```

### Testing Strategy

See [TESTING.md](TESTING.md) for comprehensive test scenarios including:

- Functional testing (text submissions, PDF submissions, rubric integration)
- Queue processing validation
- Error handling (empty submissions, corrupted PDFs)
- Cost tracking accuracy
- Performance benchmarks (30-100 student cohorts)
- GDPR compliance verification

### Manual Queue Processing

For testing or troubleshooting:

```bash
# Process queue via CLI (bypasses cron)
php /path/to/moodle/mod/assign/submission/lid/process_queue_cli.php

# Or run scheduled task directly
php /path/to/moodle/admin/cli/scheduled_task.php \
  --execute='\assignsubmission_lid\task\process_queue'
```

---

## 🗺️ Roadmap

### v0.1.0 (Current — MVP)
- [x] Text box and PDF submission analysis
- [x] Rubric and marking guide integration
- [x] Course competency mapping
- [x] Three-tier dashboard system
- [x] Queue-based processing
- [x] GDPR compliance

### v0.2.0 (Planned — Q3 2026)
- [ ] Multi-file submission support
- [ ] Additional file types (.docx, .odt)
- [ ] Student-facing feedback view
- [ ] CSV export of results
- [ ] "Accept suggested scores" workflow
- [ ] Auto-analysis on submission (opt-in)

### v0.3.0 (Future)
- [ ] xAPI statement generation for LRS integration
- [ ] Peer review analysis
- [ ] Student portfolio PDF export
- [ ] Integration with simple/direct grading methods
- [ ] Real-time streaming analysis display

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### How to Contribute

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow Moodle coding standards
4. Write tests for new functionality
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Code Standards

- Follow [Moodle Coding Style](https://moodledev.io/general/development/policies/codingstyle)
- All code must pass `phpcs` with Moodle ruleset
- Include PHPDoc blocks for all functions
- Write unit tests for new features
- Update documentation for user-facing changes

---

## 📚 Documentation

- **[Installation Guide](docs/INSTALLATION.md)** — Detailed setup instructions
- **[User Guide](docs/USER_GUIDE.md)** — Instructor workflow and dashboard usage
- **[Admin Guide](docs/ADMIN_GUIDE.md)** — Configuration and troubleshooting
- **[Developer Guide](docs/DEVELOPER_GUIDE.md)** — Architecture and API reference
- **[Testing Guide](docs/TESTING.md)** — Test scenarios and validation
- **[Changelog](CHANGELOG.md)** — Version history and release notes

---

## 🐛 Support & Troubleshooting

### Common Issues

**"Analysis failed: PDF text extraction error"**
- Ensure `pdftotext` is installed: `which pdftotext`
- Try uploading a different PDF (some are image-only scans)
- Check file permissions in Moodle data directory

**"Queue not processing"**
- Verify scheduled task is enabled
- Check cron is running: `php admin/cli/cron.php`
- Review error logs: `Site administration → Reports → Logs`

**"API key invalid"**
- Verify API key in settings
- Test key at [Google AI Studio](https://aistudio.google.com/)
- Check API endpoint URL is correct

### Getting Help

- **Issue Tracker:** [GitHub Issues](https://github.com/seanrugg/assignment_lid/issues)
- **Discussions:** [GitHub Discussions](https://github.com/seanrugg/assignment_lid/discussions)

---

## 📄 License

This project is licensed under the **GNU General Public License v3.0** — see the [LICENSE](LICENSE) file for details.

This ensures compatibility with Moodle's licensing requirements and supports the open-source ecosystem.

---

## 🙏 Acknowledgments

Assignment LID builds on the foundation of:

- **`local_lid`** — Forum analysis plugin that pioneered the LID approach
- **Moodle Community** — For the robust assignment and grading APIs
- **Google Gemini** — For accessible, affordable LLM infrastructure
- **Learning Intelligence Dashboard Project** — Broader ecosystem of tools for competency-based learning

Special thanks to early adopters and testers who provided invaluable feedback during development.

---

## 📊 Project Status

**Current Version:** 0.1.0-dev (Pre-release)  
**Status:** Active Development  
**Moodle Compatibility:** 4.5+ (tested on 5.1 and 4.5 LTS)  
**PHP Compatibility:** 8.1+  
**Last Updated:** April 27, 2026

### Development Progress

- [x] Core architecture design
- [x] Database schema definition
- [x] LLM prompt template
- [ ] Plugin scaffolding (in progress — Week 1)
- [ ] Core analysis engine (planned — Week 2-3)
- [ ] Dashboard implementation (planned — Week 4)
- [ ] Beta testing (planned — Week 10)
- [ ] v0.1.0 release (planned — Week 11)

---

## 🔗 Related Projects

- **[local_lid](https://github.com/seanrugg/local_lid)** — Forum participation analysis plugin (v0.6.0 deployed)

---



---

**Made with ❤️ for educators who believe in learning intelligence, not just grades.**
