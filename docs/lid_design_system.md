# Learning Intelligence Dashboard (LID) — Official Design System

**Version:** 1.0  
**Based on:** `local_lid` v0.6.0 and session LID browser tools  
**Purpose:** Unified visual identity across all LID tools (forum, assignment, session analytics)

---

## 🎨 Brand Overview

The LID design system creates a **futuristic, data-driven aesthetic** that signals:
- Advanced learning analytics
- AI-powered intelligence
- Modern educational technology
- Professional competency tracking

### Design Philosophy
- **Cyberpunk-inspired color palette** — Dark backgrounds with neon accents
- **Grid-based backgrounds** — Subtle tech aesthetic
- **Gradient typography** — Dynamic, eye-catching headers
- **Bebas Neue + DM Sans** — Distinctive font pairing
- **Monospace for data** — DM Mono for technical information
- **Card-based layouts** — Elevated, floating panels
- **Consistent spacing** — 8px grid system

---

## 🎭 Color Palette

### Core Colors (CSS Variables)

```css
:root {
    --lid-bg:       #080c10;    /* Deep space blue-black */
    --lid-surface:  #0e1520;    /* Elevated surface */
    --lid-surface2: #141d2e;    /* Secondary surface */
    --lid-border:   #1e2d44;    /* Subtle borders */
    
    --lid-accent:   #00e5ff;    /* Electric cyan (primary) */
    --lid-accent2:  #7b61ff;    /* Purple (secondary) */
    --lid-accent3:  #00ff9d;    /* Neon green (success) */
    --lid-warn:     #ff6b35;    /* Orange (warning) */
    
    --lid-text:     #e8edf5;    /* Off-white text */
    --lid-muted:    #5a7090;    /* Muted blue-gray */
    --lid-dim:      #2a3d58;    /* Dimmed elements */
    
    --lid-radius:   6px;        /* Border radius */
}
```

### Color Usage

| Color | Variable | Usage | Hex |
|-------|----------|-------|-----|
| **Primary Accent** | `--lid-accent` | Headers, primary buttons, links | `#00e5ff` |
| **Secondary Accent** | `--lid-accent2` | Secondary elements, gradients | `#7b61ff` |
| **Success** | `--lid-accent3` | Positive metrics, completion | `#00ff9d` |
| **Warning** | `--lid-warn` | Alerts, needs attention | `#ff6b35` |
| **Background** | `--lid-bg` | Page background | `#080c10` |
| **Surface** | `--lid-surface` | Cards, panels | `#0e1520` |
| **Text** | `--lid-text` | Primary text | `#e8edf5` |
| **Muted** | `--lid-muted` | Secondary text, labels | `#5a7090` |

---

## 📐 Typography

### Font Stack

```css
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap');

.lid-page {
    font-family: 'DM Sans', -apple-system, sans-serif;
}

.lid-heading {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 28px;
    letter-spacing: 2px;
    background: linear-gradient(135deg, var(--lid-accent), var(--lid-accent2));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lid-mono {
    font-family: 'DM Mono', monospace;
}
```

### Typography Scale

| Element | Font | Size | Weight | Letter Spacing |
|---------|------|------|--------|----------------|
| **Page Heading** | Bebas Neue | 28px | Normal | 2px |
| **Section Title** | Bebas Neue | 32px | Normal | 2px |
| **Card Heading** | DM Sans | 14px | 600 | Normal |
| **Body Text** | DM Sans | 12px | 400 | Normal |
| **Meta/Labels** | DM Mono | 9-11px | 400 | 1-2px |
| **Score Display** | Bebas Neue | 40-52px | Normal | 2px |

### Gradient Text Pattern

All major headings use the signature LID gradient:

```css
.lid-heading {
    background: linear-gradient(135deg, #00e5ff, #7b61ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
```

---

## 🏗️ Layout Components

### 1. Page Container

```css
.lid-page {
    background: var(--lid-bg);
    color: var(--lid-text);
    padding: 24px;
    border-radius: var(--lid-radius);
    min-height: 400px;
    position: relative;
}

/* Grid background texture */
.lid-page::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: 
        linear-gradient(var(--lid-border) 1px, transparent 1px),
        linear-gradient(90deg, var(--lid-border) 1px, transparent 1px);
    background-size: 40px 40px;
    opacity: 0.2;
    z-index: 0;
    pointer-events: none;
    border-radius: var(--lid-radius);
}

.lid-page > * {
    position: relative;
    z-index: 1;
}
```

### 2. Cards/Panels

```css
.lid-panel {
    background: var(--lid-surface);
    border: 1px solid var(--lid-border);
    border-radius: 6px;
    padding: 18px;
    animation: lid-fadeUp 0.4s ease both;
}

@keyframes lid-fadeUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

### 3. Score Cards

```css
.lid-score-card {
    background: var(--lid-surface);
    border: 1px solid var(--lid-border);
    border-radius: 6px;
    padding: 16px;
    position: relative;
    overflow: hidden;
}

/* Colored top border */
.lid-score-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
}

.lid-score-cyan::before   { background: var(--lid-accent); }
.lid-score-purple::before { background: var(--lid-accent2); }
.lid-score-green::before  { background: var(--lid-accent3); }
.lid-score-orange::before { background: var(--lid-warn); }
```

### 4. Panel Titles

```css
.lid-panel-title {
    font-size: 9px;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--lid-muted);
    font-family: 'DM Mono', monospace;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.lid-panel-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--lid-border);
}
```

---

## 🎯 UI Components

### Buttons

```css
.lid-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 4px;
    cursor: pointer;
    font-family: 'DM Mono', monospace;
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    border: 1px solid;
    transition: all 0.2s;
    background: transparent;
}

.lid-btn-primary {
    border-color: var(--lid-accent);
    color: var(--lid-accent);
}

.lid-btn-primary:hover {
    background: rgba(0,229,255,0.1);
}

.lid-btn-secondary {
    border-color: var(--lid-border);
    color: var(--lid-muted);
}

.lid-btn-secondary:hover {
    border-color: var(--lid-accent2);
    color: var(--lid-accent2);
}
```

### Badges

```css
.lid-status-badge {
    font-family: 'DM Mono', monospace;
    font-size: 10px;
    padding: 3px 9px;
    border-radius: 3px;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.lid-status-complete {
    background: rgba(0,255,157,0.1);
    color: var(--lid-accent3);
    border: 1px solid rgba(0,255,157,0.2);
}

.lid-status-pending {
    background: rgba(255,107,53,0.1);
    color: var(--lid-warn);
    border: 1px solid rgba(255,107,53,0.2);
}
```

### Count Badges

```css
.lid-count-badge {
    font-family: 'DM Mono', monospace;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 3px;
    letter-spacing: 0.5px;
}

.lid-count-complete {
    background: rgba(0,255,157,0.1);
    color: var(--lid-accent3);
}

.lid-count-pending {
    background: rgba(255,107,53,0.1);
    color: var(--lid-warn);
}
```

### Tags

```css
.lid-tag {
    font-size: 8px;
    padding: 3px 7px;
    border-radius: 2px;
    font-family: 'DM Mono', monospace;
    letter-spacing: 1px;
    text-transform: uppercase;
    border: 1px solid;
}

.lid-tag-cyan   { background: rgba(0,229,255,0.08);  color: var(--lid-accent);  border-color: rgba(0,229,255,0.2); }
.lid-tag-purple { background: rgba(123,97,255,0.08); color: var(--lid-accent2); border-color: rgba(123,97,255,0.2); }
.lid-tag-green  { background: rgba(0,255,157,0.08);  color: var(--lid-accent3); border-color: rgba(0,255,157,0.2); }
```

---

## 📊 Data Visualization

### Score Display

```css
.lid-score-value {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 40px;
    letter-spacing: 2px;
    line-height: 1;
}

.lid-score-cyan   .lid-score-value { color: var(--lid-accent); }
.lid-score-purple .lid-score-value { color: var(--lid-accent2); }
.lid-score-green  .lid-score-value { color: var(--lid-accent3); }
.lid-score-orange .lid-score-value { color: var(--lid-warn); }
```

### Competency Bars

```css
.lid-comp-bar-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 1.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.lid-comp-cyan   { background: linear-gradient(90deg, var(--lid-accent2), var(--lid-accent)); }
.lid-comp-green  { background: linear-gradient(90deg, var(--lid-accent), var(--lid-accent3)); }
.lid-comp-orange { background: linear-gradient(90deg, var(--lid-warn), #ffb347); }
.lid-comp-purple { background: linear-gradient(90deg, var(--lid-accent2), #b388ff); }
```

### Bloom's Taxonomy Grid

```css
.lid-blooms-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
}

.lid-bloom-card {
    background: var(--lid-surface2);
    border: 1px solid var(--lid-border);
    border-radius: 4px;
    padding: 12px;
    position: relative;
    overflow: hidden;
}

.lid-bloom-inactive {
    opacity: 0.4;
}

.lid-bloom-num {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 28px;
    color: var(--lid-dim);
    position: absolute;
    top: -4px;
    right: 6px;
    line-height: 1;
}
```

---

## 🎬 Animations

### Fade Up (Entry Animation)

```css
@keyframes lid-fadeUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.lid-panel {
    animation: lid-fadeUp 0.4s ease both;
}
```

### Spinner

```css
@keyframes lid-spin {
    to { transform: rotate(360deg); }
}

.lid-status-spinner {
    width: 10px;
    height: 10px;
    border: 1.5px solid var(--lid-accent);
    border-top-color: transparent;
    border-radius: 50%;
    animation: lid-spin 0.8s linear infinite;
    display: inline-block;
}
```

---

## 📱 Responsive Breakpoints

```css
/* Desktop first, mobile adaptations */
@media (max-width: 1100px) {
    .lid-main-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 700px) {
    .lid-score-row {
        grid-template-columns: 1fr 1fr;
    }
    .lid-main-grid {
        grid-template-columns: 1fr;
    }
    .lid-blooms-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
```

---

## 🎨 Key Design Patterns

### 1. Grid Background Texture
Every LID page has the subtle grid background for that "tech dashboard" feel.

### 2. Gradient Headers
All major headings use the cyan-to-purple gradient.

### 3. Colored Top Borders
Cards use 2px colored top borders to indicate type/category.

### 4. Uppercase Labels
All labels and meta-information use uppercase DM Mono.

### 5. Fade-Up Animation
All panels fade up on load for smooth entrance.

### 6. Monospace Data
All numerical/technical data uses DM Mono font.

---

## 🔄 Integration with Moodle Themes

### Two-Mode Approach

**Mode 1: Pure LID Branding (Default)**
- Dark background (#080c10)
- Neon accents (#00e5ff, #7b61ff, #00ff9d)
- Full LID design system active
- Bebas Neue headings
- Grid background texture

**Mode 2: Theme Inheritance (Optional)**
- Light/theme background
- Theme's color scheme
- Bootstrap components
- Theme's typography
- Minimal custom styling

### Switching Between Modes

```php
// In plugin settings
$futuristicmode = get_config('assignsubmission_lid', 'futuristicui');

if ($futuristicmode) {
    // Load styles-lid-brand.css (full LID design system)
} else {
    // Load styles.css (theme inheritance)
}
```

---

## 📋 Usage Guidelines

### When to Use LID Branding
- Dedicated LID dashboards
- Full-page LID views
- Standalone LID reports
- Data visualization contexts

### When to Use Theme Inheritance
- Inline components in standard Moodle pages
- Small widgets in grading interface
- Institutional preference for consistency
- Accessibility requirements

---

## 🎯 Implementation Priority

For Assignment LID:

1. **Create `styles-lid-brand.css`** — Full LID design system
2. **Keep `styles.css`** — Theme inheritance fallback
3. **Add toggle in settings** — "Use LID Branding" checkbox
4. **Apply consistently** — All dashboards use chosen mode

This ensures:
- ✅ Brand consistency across LID family
- ✅ Flexibility for different institutions
- ✅ Future-proof for both approaches

---

**Next Step:** Should I create the actual `styles-lid-brand.css` file implementing this complete design system?
