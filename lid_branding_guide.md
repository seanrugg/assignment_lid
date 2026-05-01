# Assignment LID — Visual Branding System

## 🎨 Two-Mode Design Approach

Assignment LID supports **two visual modes** to accommodate different institutional preferences:

### Mode 1: Theme Inheritance (Default)
- **File:** `styles.css`
- **Appearance:** Clean, minimal, inherits from Moodle theme
- **Best for:** Conservative institutions, consistent Moodle experience
- **Colors:** Uses theme's primary/secondary/success colors
- **Typography:** Uses theme's font stack

### Mode 2: LID Branding (Optional)
- **File:** `styles-lid-brand.css` (loaded in addition to `styles.css`)
- **Appearance:** Dark cyberpunk aesthetic with neon accents
- **Best for:** Modern institutions, distinctive LID experience
- **Colors:** Custom LID palette (cyan, purple, neon green)
- **Typography:** Bebas Neue + DM Sans + DM Mono

---

## 🔧 How to Enable LID Branding

### For Site Admins:
1. Navigate to **Site administration → Plugins → Assignment submissions → Learning Intelligence Dashboard**
2. Find **"Use LID Branding"** checkbox
3. Check the box to enable
4. Save changes
5. Clear cache if needed

### For Developers:
The setting controls which CSS files are loaded:

```php
// In lib.php - add_to_page() method
public function add_to_page(moodle_page $page) {
    // Always load base styles (theme-compatible)
    $page->requires->css('/mod/assign/submission/lid/styles.css');
    
    // Conditionally load LID branding
    if (get_config('assignsubmission_lid', 'futuristicui')) {
        $page->requires->css('/mod/assign/submission/lid/styles-lid-brand.css');
    }
}
```

---

## 🎭 Visual Comparison

### Theme Inheritance Mode:
```
┌─────────────────────────────────────┐
│ Analysis Results                    │  ← Uses theme's heading style
│                                     │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐│
│ │  85     │ │  78     │ │  92     ││  ← Uses theme's card style
│ │ Quality │ │ Depth   │ │ Evidence││  ← Uses theme colors
│ └─────────┘ └─────────┘ └─────────┘│
└─────────────────────────────────────┘
```

### LID Branding Mode:
```
╔═════════════════════════════════════╗
║ Analysis Results        ✨          ║  ← Gradient cyan→purple text
║ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  ║  ← Grid background texture
║ ╔═══════╗ ╔═══════╗ ╔═══════╗     ║
║ ║  85   ║ ║  78   ║ ║  92   ║     ║  ← Dark surface, colored borders
║ ║Quality║ ║ Depth ║ ║Evidence║     ║  ← Neon accent colors
║ ╚═══════╝ ╚═══════╝ ╚═══════╝     ║
╚═════════════════════════════════════╝
```

---

## 🎨 LID Brand Specifications

### Color Palette
```css
--lid-bg:       #060a0f;  /* Deep space background */
--lid-surface:  #0c1420;  /* Card backgrounds */
--lid-surface2: #111b2c;  /* Nested elements */

--lid-accent:   #00e5ff;  /* Electric cyan (primary) */
--lid-accent2:  #7b61ff;  /* Purple (secondary) */
--lid-accent3:  #00ff9d;  /* Neon green (success) */
--lid-warn:     #ff6b35;  /* Orange (warning) */

--lid-text:     #e2eaf5;  /* Primary text */
--lid-muted:    #4e6a8a;  /* Secondary text */
```

### Typography
- **Headings:** Bebas Neue (28-80px, gradient effect)
- **Body:** DM Sans (11-14px, weights 300-600)
- **Data/Meta:** DM Mono (8-11px, monospace)

### Key Visual Elements
- **Grid texture** — Subtle 48px grid overlay
- **Gradient text** — Cyan→purple on all major headings
- **Colored borders** — 2px top borders on score cards
- **Uppercase labels** — All DM Mono meta information
- **Fade-up animation** — Smooth entrance for panels
- **Neon accents** — Electric cyan highlights throughout

---

## 🔄 Brand Consistency Across LID Ecosystem

The LID branding mode ensures **visual consistency** with:

1. **Forum LID** (`local_lid` plugin) — Same dark theme, same colors
2. **Session LID** (browser-based portfolio) — Identical design DNA
3. **Course ROI Dashboard** — Shared aesthetic language

**Result:** When a user sees Assignment LID with branding enabled, they immediately recognize it as part of the same LID family.

---

## 📊 Design Decision Matrix

| Scenario | Recommended Mode | Reason |
|----------|------------------|--------|
| **K-12 institution** | Theme Inheritance | Familiar, age-appropriate |
| **Corporate training** | LID Branding | Modern, data-driven aesthetic |
| **Traditional university** | Theme Inheritance | Institutional consistency |
| **EdTech startup** | LID Branding | Innovative, distinctive |
| **Government/compliance** | Theme Inheritance | Conservative, accessible |
| **AI/Tech training programs** | LID Branding | Aligns with subject matter |

---

## ♿ Accessibility

Both modes maintain **WCAG 2.1 AA compliance**:

### Theme Inheritance Mode:
- ✅ Inherits theme's color contrast ratios
- ✅ Uses theme's accessible font sizes
- ✅ Respects theme's focus indicators

### LID Branding Mode:
- ✅ 4.5:1 contrast ratios maintained
- ✅ Respects `prefers-reduced-motion`
- ✅ Keyboard navigation fully functional
- ✅ Screen reader compatible
- ✅ Touch targets 44x44px minimum

**Reduced Motion Support:**
```css
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
    }
}
```

---

## 🌓 Dark Mode Compatibility

### Theme Inheritance Mode:
- Adapts automatically to theme's dark mode
- Uses theme's dark color palette
- Seamless integration

### LID Branding Mode:
- **Already dark by default**
- Designed for dark environments
- No additional dark mode needed
- Works alongside light-themed Moodle interfaces

---

## 🔧 Customization for Site Admins

If you want to customize the LID branding colors to match your institution:

### Option 1: Override via Theme's Additional CSS
```css
/* In Theme Settings > Additional CSS */
:root {
    --lid-accent: #your-primary-color;    /* Change cyan */
    --lid-accent2: #your-secondary-color; /* Change purple */
    --lid-accent3: #your-success-color;   /* Change green */
}
```

### Option 2: Modify Plugin CSS Directly
1. Navigate to `/mod/assign/submission/lid/styles-lid-brand.css`
2. Edit the `:root` CSS custom properties
3. Clear Moodle cache

### Option 3: Create Institution-Specific Fork
- Fork `styles-lid-brand.css` to `styles-custom.css`
- Modify colors, fonts, spacing as needed
- Load conditionally via third setting option

---

## 📁 File Structure

```
assignment_lid/
├── styles.css              ← Theme inheritance (always loaded)
├── styles-lid-brand.css    ← LID branding (conditionally loaded)
├── settings.php            ← Contains "Use LID Branding" toggle
└── lib.php                 ← Controls CSS loading logic
```

---

## 🚀 Migration Path

If you start with **Theme Inheritance** and later want to switch to **LID Branding**:

1. No code changes needed
2. Just enable the setting
3. Changes take effect immediately
4. Can toggle back anytime

**Zero migration cost** — the two modes are designed to be completely interchangeable.

---

## 🎯 Recommended Default

For **new installations**, we recommend:

- **Default:** Theme Inheritance (OFF)
- **Reason:** Cautious approach, ensures compatibility
- **Recommendation to users:** Try LID Branding if you want distinctive analytics aesthetic

For **existing LID ecosystem users** (have `local_lid` or session LID):

- **Default:** LID Branding (ON)
- **Reason:** Visual consistency across LID family
- **User expectation:** They already know and appreciate the LID aesthetic

---

## 📖 For Theme Developers

If you're developing a Moodle theme and want to make it LID-compatible:

### Ensure Your Theme Provides:
- `.card` component (Bootstrap 4 compatible)
- `.badge` component variants
- `.btn-primary`, `.btn-secondary` classes
- `.text-muted`, `.text-primary` utilities
- Standard color CSS custom properties

### Test With LID:
1. Enable Assignment LID plugin
2. Toggle **both modes** (branding ON and OFF)
3. Verify visual coherence in both states
4. Check dark mode if your theme supports it

---

**Need help?** Contact [sean@learning-intelligence.dev](mailto:sean@learning-intelligence.dev)
