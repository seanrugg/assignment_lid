# Grading Method Edge Cases — Assignment LID

## 📊 Moodle Grading Methods Coverage

Assignment LID now supports **all 4 Moodle grading methods** through the `grading_method_handler` class.

---

## ✅ Supported Grading Methods

### 1. Simple Direct Grading (Point-Based)

**Description:** Traditional numeric grading (0-100, 0-10, etc.)

**Example:**
- Assignment max grade: 100 points
- Student receives: 85/100

**What LID Extracts:**
```json
{
  "method": "point",
  "max_points": 100,
  "criteria": [{
    "criterion_name": "Overall Grade",
    "type": "point",
    "max_score": 100,
    "description": "Simple numeric grade out of 100 points"
  }]
}
```

**LLM Prompt Includes:**
- Max grade points
- Overall grade criterion
- No specific rubric criteria

**Edge Cases Handled:**
- ✅ Custom max grades (10 points, 20 points, 1000 points, etc.)
- ✅ Zero-based assignments (max = 0, used for completion only)
- ✅ Decimal grades (if Moodle allows)

---

### 2. Rubric (Advanced Grading)

**Description:** Multi-criteria assessment with defined performance levels

**Example:**
```
Research Quality [0-25 points]
  → Exceptional (25): Extensive primary sources, critical analysis
  → Proficient (20): Good sources, solid analysis
  → Developing (15): Limited sources, basic analysis
  → Emerging (10): Minimal sources, superficial analysis

Argumentation [0-25 points]
  → Exceptional (25): Sophisticated, well-supported claims
  → Proficient (20): Clear arguments with evidence
  ...
```

**What LID Extracts:**
```json
{
  "method": "rubric",
  "total_points": 100,
  "criteria": [
    {
      "criterion_name": "Research Quality",
      "type": "rubric",
      "max_score": 25,
      "levels": [
        {"score": 25, "definition": "Extensive primary sources, critical analysis"},
        {"score": 20, "definition": "Good sources, solid analysis"},
        {"score": 15, "definition": "Limited sources, basic analysis"},
        {"score": 10, "definition": "Minimal sources, superficial analysis"}
      ]
    },
    {
      "criterion_name": "Argumentation",
      "type": "rubric",
      "max_score": 25,
      "levels": [...]
    }
  ]
}
```

**LLM Prompt Includes:**
- Each criterion name
- Performance levels with scores
- Level descriptors
- Total possible points

**LLM Output Includes:**
```json
{
  "rubric_evaluation": [
    {
      "criterion_name": "Research Quality",
      "suggested_score": 20,
      "evidence_excerpt": "Student cites 8 peer-reviewed sources...",
      "confidence": "high",
      "level_matched": "Proficient"
    }
  ]
}
```

**Edge Cases Handled:**
- ✅ Rubrics with 2-10 criteria
- ✅ Rubrics with 2-8 performance levels per criterion
- ✅ Uneven point distributions (25+25+25+15+10 = 100)
- ✅ Criteria without descriptions (auto-labels as "Criterion 1", etc.)
- ✅ HTML formatting in criterion descriptions (stripped)
- ✅ Empty rubric definitions (fallback to point grading)

---

### 3. Marking Guide (Advanced Grading)

**Description:** Criteria-based assessment with max scores but no predefined levels

**Example:**
```
Content Knowledge [0-30 points]
  Demonstrates understanding of key concepts and theories

Critical Analysis [0-25 points]
  Evaluates sources and synthesizes information

Writing Quality [0-20 points]
  Clear organization, grammar, and style
```

**What LID Extracts:**
```json
{
  "method": "marking_guide",
  "total_points": 75,
  "criteria": [
    {
      "criterion_name": "Content Knowledge",
      "description": "Demonstrates understanding of key concepts and theories",
      "type": "marking_guide",
      "max_score": 30
    },
    {
      "criterion_name": "Critical Analysis",
      "description": "Evaluates sources and synthesizes information",
      "type": "marking_guide",
      "max_score": 25
    },
    {
      "criterion_name": "Writing Quality",
      "description": "Clear organization, grammar, and style",
      "type": "marking_guide",
      "max_score": 20
    }
  ]
}
```

**LLM Prompt Includes:**
- Criterion names
- Criterion descriptions
- Max score per criterion
- Total possible points

**LLM Output Includes:**
```json
{
  "rubric_evaluation": [
    {
      "criterion_name": "Content Knowledge",
      "suggested_score": 25,
      "evidence_excerpt": "Student demonstrates solid grasp of Piaget's stages...",
      "confidence": "high"
    }
  ]
}
```

**Edge Cases Handled:**
- ✅ Guides with 2-15 criteria
- ✅ Varied max scores per criterion (30+25+20+15+10 = 100)
- ✅ Missing descriptions (uses criterion name only)
- ✅ HTML formatting in descriptions (stripped)
- ✅ Frequently confused comments (LID ignores these)
- ✅ Empty marking guide (fallback to point grading)

---

### 4. Custom Scales

**Description:** Qualitative assessment using predefined levels

**Example:**
```
Scale: "Performance Levels"
  1. Not Yet Meeting Expectations
  2. Approaching Expectations
  3. Meeting Expectations
  4. Exceeding Expectations
  5. Outstanding
```

**What LID Extracts:**
```json
{
  "method": "scale",
  "scale_name": "Performance Levels",
  "levels": [
    {"level": 1, "label": "Not Yet Meeting Expectations"},
    {"level": 2, "label": "Approaching Expectations"},
    {"level": 3, "label": "Meeting Expectations"},
    {"level": 4, "label": "Exceeding Expectations"},
    {"level": 5, "label": "Outstanding"}
  ],
  "criteria": [{
    "criterion_name": "Overall Performance",
    "type": "scale",
    "scale_name": "Performance Levels",
    "levels": [...]
  }]
}
```

**LLM Prompt Includes:**
- Scale name
- All scale levels (in order)
- Level labels

**LLM Output Includes:**
```json
{
  "submission_analysis": {
    "overall_quality_score": 80,
    "suggested_scale_level": 4,
    "scale_level_label": "Exceeding Expectations",
    "rationale": "Submission demonstrates strong analytical skills..."
  }
}
```

**Edge Cases Handled:**
- ✅ Scales with 2-10 levels
- ✅ Custom scale names
- ✅ Standard Moodle scales ("Separate and Connected ways of knowing", etc.)
- ✅ Course-specific custom scales
- ✅ Site-wide custom scales
- ✅ Numeric-looking scale items ("1 - Poor", "2 - Fair", etc.)

---

## 🔍 Grading Method Detection Logic

The `grading_method_handler` detects grading methods in this order:

```php
1. Check for advanced grading controller
   ├─ If 'rubric' → Extract rubric criteria
   └─ If 'guide' → Extract marking guide criteria

2. Check assignment grade value
   ├─ If negative (< 0) → Scale ID → Extract scale criteria
   └─ If positive (> 0) → Max points → Point grading

3. Fallback: Simple point grading (100 points)
```

**Detection Examples:**

| Assignment Config | Detection Result |
|-------------------|------------------|
| `grade = 100` | Point grading (100 points) |
| `grade = 10` | Point grading (10 points) |
| `grade = -3` | Scale grading (scale ID 3) |
| Advanced grading active + method = 'rubric' | Rubric |
| Advanced grading active + method = 'guide' | Marking guide |

---

## 🧪 Testing Scenarios

### Test 1: Point Grading (0-100)
```
Setup:
  - Assignment max grade: 100
  - No advanced grading

Expected:
  - LID detects: 'point' method
  - Prompt includes: max_points = 100
  - LLM suggests: numeric score 0-100
```

### Test 2: Rubric with 4 Criteria
```
Setup:
  - Advanced grading: Rubric
  - 4 criteria, 4 levels each
  - Total: 100 points (25+25+25+25)

Expected:
  - LID detects: 'rubric' method
  - Prompt includes: 4 criteria with level definitions
  - LLM suggests: score per criterion + evidence
```

### Test 3: Marking Guide with 5 Criteria
```
Setup:
  - Advanced grading: Marking guide
  - 5 criteria with descriptions
  - Total: 50 points (10+10+10+10+10)

Expected:
  - LID detects: 'guide' method
  - Prompt includes: 5 criteria with descriptions
  - LLM suggests: score per criterion (0-10)
```

### Test 4: Custom Scale (5 levels)
```
Setup:
  - Grade = -2 (scale ID 2)
  - Scale: "Competency Levels" (1-5)

Expected:
  - LID detects: 'scale' method
  - Prompt includes: scale name + 5 levels
  - LLM suggests: scale level (1-5) + rationale
```

### Test 5: Zero-Point Assignment
```
Setup:
  - Grade = 0 (completion only)
  - No advanced grading

Expected:
  - LID detects: 'point' method
  - Prompt includes: max_points = 0
  - LLM focuses on feedback (no numeric score)
```

---

## ⚠️ Known Limitations

### Current Version (v0.1.0):

1. **No Grade Assignment:**
   - LID suggests scores but does NOT auto-fill grades
   - Instructor must manually enter grades
   - Coming in v0.2.0

2. **Single-File Rubrics Only:**
   - Rubrics with file attachments not processed
   - Only criterion descriptions used

3. **No Rubric History:**
   - LID doesn't track rubric changes over time
   - Uses current rubric definition only

4. **Scale Limitations:**
   - No scale item descriptions (if scale has them)
   - Only scale labels used

---

## 🔧 Implementation Files

| Component | File | Purpose |
|-----------|------|---------|
| **Grading Handler** | `classes/grading_method_handler.php` | Detect & extract grading criteria |
| **Analyzer Integration** | `classes/analyzer.php` | Call grading handler during analysis |
| **Language Strings** | `lang/en/assignsubmission_lid.php` | Method names & labels |
| **Edge Cases Doc** | `docs/GRADING_EDGE_CASES.md` | This file (testing guide) |

---

## 📝 For Developers

### Adding Grading Criteria to Prompts:

```php
// In prompt builder class:
$gradinghandler = new \assignsubmission_lid\grading_method_handler($assignment, $assignmentid);
$criteria = $gradinghandler->get_grading_criteria();

$prompt .= "GRADING CRITERIA:\n";
$prompt .= json_encode($criteria, JSON_PRETTY_PRINT);
```

### Checking Grading Method:

```php
$gradinghandler = new \assignsubmission_lid\grading_method_handler($assignment, $assignmentid);
$method = $gradinghandler->detect_grading_method(); // 'rubric', 'guide', 'scale', 'point'

if ($gradinghandler->is_advanced_grading()) {
    // Rubric or marking guide in use
}
```

---

**Last Updated:** 2026-05-02  
**Status:** All grading methods supported ✅
