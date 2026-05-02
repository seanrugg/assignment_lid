# Theme Compatibility Guide — Assignment LID

This document explains how Assignment LID achieves compatibility with all Moodle themes (Boost, Classic, and 3rd party themes) and provides guidelines for maintaining this compatibility as the plugin evolves.

---

## Design Philosophy

Assignment LID follows a **"theme-first"** approach:

1. **Inherit, don't override** — Use the active theme's styles wherever possible
2. **Bootstrap-native** — Rely on Bootstrap components and utilities
3. **Minimal custom CSS** — Only add styling that doesn't exist in Bootstrap
4. **No hardcoded colors** — Use theme variables and contextual classes
5. **Responsive by default** — Use Bootstrap's grid system
6. **Accessible** — Follow WCAG 2.1 AA standards via Bootstrap components

---

## Technical Implementation

### 1. Template Structure (Mustache)

All templates use **Bootstrap 4 classes** exclusively (Moodle 4.5+ uses Bootstrap 4):

#### ✅ Good Example (Theme-Compatible)
```mustache
<div class="card">
    <div class="card-body">
        <h4 class="card-title text-primary">Analysis Results</h4>
        <p class="text-muted">{{timestamp}}</p>
        <button class="btn btn-primary">Analyze</button>
    </div>
</div>
```

#### ❌ Bad Example (Theme-Breaking)
```mustache
<div style="background: #f9f9f9; border: 1px solid #ccc;">
    <h4 style="color: #0066cc;">Analysis Results</h4>
    <p style="color: #666;">{{timestamp}}</p>
    <button style="background: #0066cc; color: white;">Analyze</button>
</div>
```

### 2. Bootstrap Classes Used

| Component | Bootstrap Classes | Purpose |
|-----------|-------------------|---------|
| **Layout** | `container`, `row`, `col-*` | Grid system |
| **Cards** | `card`, `card-body`, `card-title` | Content containers |
| **Buttons** | `btn`, `btn-primary`, `btn-secondary`, `btn-outline-*` | Actions |
| **Alerts** | `alert`, `alert-info`, `alert-danger` | Messages |
| **Badges** | `badge`, `badge-secondary`, `badge-success` | Status indicators |
| **Utilities** | `text-muted`, `text-primary`, `mb-*`, `mt-*` | Spacing, colors |
| **Tables** | `table`, `table-striped`, `table-hover` | Data display |
| **Borders** | `border`, `border-primary`, `border-top` | Visual separation |

### 3. CSS Custom Properties (CSS Variables)

Our custom CSS uses **no hardcoded colors**. Instead, we rely on:

- **Bootstrap's color system:** `primary`, `secondary`, `success`, `danger`, `warning`, `info`
- **Relative units:** `em`, `rem` (not `px`)
- **Theme inheritance:** `currentColor`, `inherit`
- **Opacity for variations:** `opacity: 0.75` instead of custom gray colors

#### Example from styles.css:
```css
/* ✅ Theme-compatible */
.lid-metric-label {
    opacity: 0.75;  /* Inherits theme's text color, just lighter */
    font-size: 0.875rem;  /* Relative to theme's base font size */
}

.lid-metric-value {
    font-weight: 700;
    /* No color specified - inherits from .text-primary in template */
}

/* ❌ Theme-breaking - NEVER do this */
.lid-metric-label {
    color: #666666;  /* Hardcoded gray - won't adapt to dark themes */
    font-size: 14px;  /* Fixed pixels - ignores accessibility font scaling */
}
```

### 4. Component Mapping

How LID components map to theme elements:

| LID Component | Theme Component | Implementation |
|---------------|-----------------|----------------|
| Analysis summary panel | Bootstrap card | `<div class="card">` |
| Metric display | Card + utilities | `<div class="card bg-light">` |
| Status badges | Bootstrap badge | `<span class="badge badge-success">` |
| Action buttons | Bootstrap buttons | `<button class="btn btn-primary">` |
| Dashboard header | Page header | Uses theme's heading styles |
| Data tables | Bootstrap table | `<table class="table table-striped">` |
| Loading spinner | CSS animation | Uses `currentColor` for theme color |

---

## Theme-Specific Considerations

### Boost Theme (Moodle Default)

**Characteristics:**
- Bootstrap 4 based
- Clean, modern design
- Lots of whitespace
- Primary color: Blue (#0f6cbf by default)

**Compatibility:**
- ✅ Full compatibility out of the box
- ✅ All Bootstrap classes work natively
- ✅ Inherits primary color for accents

**Testing:**
```bash
# Set theme to Boost
php admin/cli/cfg.php --name=theme --set=boost
```

### Classic Theme

**Characteristics:**
- Bootstrap 4 based (Moodle 4.0+)
- More compact layout
- Traditional Moodle look
- Slightly different spacing

**Compatibility:**
- ✅ Full compatibility
- ✅ May appear slightly more compact (this is expected)
- ✅ All components render correctly

**Testing:**
```bash
# Set theme to Classic
php admin/cli/cfg.php --name=theme --set=classic
```

### 3rd Party Themes

**Examples:** Moove, Edumy, Fordson, Lambda, etc.

**Common Patterns:**
- Most extend Boost
- Custom color schemes
- Custom fonts
- May override Bootstrap components

**Compatibility Strategy:**
- ✅ Use Bootstrap classes (they work in all themes)
- ✅ Avoid plugin-specific color definitions
- ✅ Test with at least one popular 3rd party theme
- ⚠️ Some themes may override badge or button styles (this is OK)

---

## Dark Mode Support

Modern themes may support dark mode (especially 3rd party themes).

### How LID Supports Dark Mode:

1. **No hardcoded background colors** — Cards use Bootstrap's `.bg-light` which adapts
2. **No hardcoded text colors** — Uses `.text-muted`, `.text-primary`, etc.
3. **Opacity-based shading** — Instead of grays, use `opacity: 0.75`
4. **System preference detection:**

```css
@media (prefers-color-scheme: dark) {
    .lid-metric-label {
        opacity: 0.65;  /* Slightly more transparent in dark mode */
    }
}
```

### Testing Dark Mode:

```css
/* In browser DevTools, enable dark mode simulation */
/* Or use a theme that supports dark mode like Moove */
```

---

## Accessibility Compliance

LID components meet **WCAG 2.1 AA** standards through Bootstrap:

| Requirement | Implementation | Bootstrap Class |
|-------------|----------------|-----------------|
| Color contrast 4.5:1 | Theme's default text colors | `.text-primary`, `.text-muted` |
| Focus indicators | Visible outline on all interactive elements | `:focus` styles |
| Semantic HTML | Proper heading hierarchy | `<h1>` - `<h6>` |
| Screen reader text | Hidden labels for buttons | `.sr-only` |
| Keyboard navigation | All buttons and links are keyboard-accessible | Native `<button>`, `<a>` |
| Touch targets 44x44px | Bootstrap button padding | `.btn` |

### Custom Accessibility Enhancements:

```css
/* Ensure focus is always visible, even in custom themes */
.lid-analyze-button:focus,
.lid-reanalyze-button:focus {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}
```

---

## Testing Checklist

Before releasing any UI changes, test against:

- [ ] **Boost theme** (default)
- [ ] **Classic theme** (traditional)
- [ ] **At least one 3rd party theme** (e.g., Moove, Fordson)
- [ ] **Dark mode** (if theme supports it)
- [ ] **Mobile viewport** (responsive behavior)
- [ ] **High contrast mode** (accessibility)
- [ ] **RTL language** (right-to-left, e.g., Arabic)

### Testing Commands:

```bash
# Switch themes for testing
php admin/cli/cfg.php --name=theme --set=boost
php admin/cli/cfg.php --name=theme --set=classic

# Clear cache after theme change
php admin/cli/purge_caches.php
```

### Visual Regression Testing:

```bash
# Take screenshots in each theme
# Compare before/after changes
# Tools: BackstopJS, Percy, or manual comparison
```

---

## Common Pitfalls to Avoid

### ❌ Don't: Use Inline Styles
```mustache
<div style="background: #f9f9f9;">...</div>
```

### ✅ Do: Use Bootstrap Classes
```mustache
<div class="bg-light">...</div>
```

---

### ❌ Don't: Hardcode Colors in CSS
```css
.lid-header {
    color: #0066cc;
}
```

### ✅ Do: Use Theme Variables or Classes
```css
.lid-header {
    /* Color comes from .text-primary in template */
}
```

---

### ❌ Don't: Use Fixed Pixel Sizes
```css
.lid-metric {
    font-size: 18px;
    padding: 20px;
}
```

### ✅ Do: Use Relative Units
```css
.lid-metric {
    font-size: 1.125rem;  /* 18px at default 16px base */
    padding: 1.25rem;     /* 20px at default 16px base */
}
```

---

### ❌ Don't: Override Theme Components
```css
.btn-primary {
    background: #ff0000 !important;  /* Breaks theme consistency */
}
```

### ✅ Do: Use Contextual Classes
```mustache
{{! Use theme's existing button variants }}
<button class="btn btn-primary">Primary Action</button>
<button class="btn btn-secondary">Secondary Action</button>
```

---

## Customization for Site Admins

### Via Theme Settings (Recommended)

Site admins can customize LID's appearance through their theme's settings:

1. **Primary color** — Change in theme settings, LID inherits it
2. **Font family** — Change in theme settings, LID inherits it
3. **Button styles** — Defined by theme, LID uses them
4. **Card styles** — Defined by theme, LID uses them

### Via Custom CSS (Advanced)

If a site admin wants to customize LID specifically:

```css
/* In Theme Settings > Additional CSS */

/* Change LID metric cards background */
.lid-metric.card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.lid-metric .lid-metric-value {
    color: white;
}

/* Change competency section accent */
.lid-competencies.border-success {
    border-color: #9333ea !important;  /* Purple instead of green */
}

.lid-competencies h5.text-success {
    color: #9333ea !important;
}
```

**Important:** This should be done in the theme's "Additional CSS" area, **not** by modifying the plugin's CSS file.

---

## Future-Proofing

### Moodle 5.x and Bootstrap 5

Moodle will eventually migrate to Bootstrap 5. Our strategy:

1. **Use semantic classes** — `btn-primary` works in both BS4 and BS5
2. **Avoid deprecated classes** — No `float-left` (use `float-start`)
3. **Test early** — When Moodle 5.x beta releases, test LID immediately
4. **Minimal migration impact** — Most changes will be automatic

### Classes That May Change:

| Bootstrap 4 | Bootstrap 5 | Impact |
|-------------|-------------|--------|
| `.text-left` | `.text-start` | Low (rare use) |
| `.float-left` | `.float-start` | None (not used) |
| `.ml-*`, `.mr-*` | `.ms-*`, `.me-*` | None (using standard margin utilities) |

---

## Developer Guidelines

When adding new UI components to LID:

### Step 1: Check Bootstrap Documentation
Before writing custom CSS, check if Bootstrap has a component:
- https://getbootstrap.com/docs/4.6/components/

### Step 2: Use Semantic Class Names
```css
/* ✅ Good - Describes purpose */
.lid-metric-value { ... }

/* ❌ Bad - Describes appearance */
.blue-large-text { ... }
```

### Step 3: Test in Multiple Themes
```bash
# Test workflow
1. Develop in Boost (default)
2. Test in Classic (traditional)
3. Test in at least one 3rd party theme
4. Test dark mode if available
5. Test mobile viewport
```

### Step 4: Document Theme Assumptions
```css
/**
 * Metric display component
 * 
 * THEME COMPATIBILITY:
 * - Uses .card component (all themes support this)
 * - Uses .text-primary for value color (inherits theme color)
 * - Uses .bg-light for background (adapts to theme's light color)
 */
.lid-metric { ... }
```

---

## Support for Theme Developers

If you're a theme developer and LID doesn't look right in your theme:

### 1. Check Bootstrap Compatibility

LID assumes Bootstrap 4. If your theme uses a different version:
- **Bootstrap 3:** Some classes may not work (upgrade theme recommended)
- **Bootstrap 5:** Mostly compatible, minor class name changes
- **No Bootstrap:** LID may need custom CSS for your theme

### 2. Override LID Styles in Your Theme

Add to your theme's CSS:

```css
/* In your theme's styles.css or additional CSS */

/* Example: Change LID card style to match theme */
.assignsubmission_lid_analysis_summary.card {
    border: 2px solid var(--your-theme-primary-color);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
```

### 3. Report Compatibility Issues

If LID breaks in your theme:
1. Create an issue: https://github.com/seanrugg/assignment_lid/issues
2. Include: Theme name, Moodle version, screenshot
3. We'll work with you to fix it

---

## Summary

**Assignment LID achieves universal theme compatibility by:**

✅ Using Bootstrap 4 components and utilities exclusively  
✅ Never hardcoding colors or sizes  
✅ Relying on relative units (em, rem)  
✅ Using opacity for color variations  
✅ Following Moodle's CSS conventions  
✅ Testing in multiple themes  
✅ Supporting dark mode via theme inheritance  
✅ Maintaining WCAG 2.1 AA accessibility  

**Result:** LID looks native in Boost, Classic, and all 3rd party themes without any theme-specific code.
