---
name: app-store-screenshots
description: Create App Store and Google Play screenshots and preview videos. Use when building iOS/Android apps, preparing store listings, generating device mockups, writing screenshot captions, or planning screenshot strategy. Triggers on 'store screenshots', 'App Store listing', 'Play Store screenshots', 'device mockup', 'screenshot strategy', 'preview video'.
---

# App Store Screenshots

Create app store screenshots and preview videos via inference.sh CLI.

> Requires inference.sh CLI (`infsh`): `npx skills add inference-sh/skills@agent-tools` then `infsh login`

## Platform Specifications

### Apple App Store (iOS)

| Device | Dimensions (px) | Required |
|---|---|---|
| iPhone 6.7" (15 Pro Max) | 1290 x 2796 | ✅ Required |
| iPhone 6.5" (11 Pro Max) | 1284 x 2778 | ✅ Required |
| iPhone 5.5" (8 Plus) | 1242 x 2208 | Optional |
| iPad Pro 12.9" (6th gen) | 2048 x 2732 | If iPad app |
| iPad Pro 11" | 1668 x 2388 | If iPad app |

- Up to **10 screenshots** per localization
- First **3 screenshots** visible without scrolling (critical — plan these first)
- Formats: PNG or JPEG (no alpha/transparency for JPEG)

### Google Play Store (Android)

| Spec | Value |
|---|---|
| Min dimensions | 320 px any side |
| Max dimensions | 3840 px any side |
| Aspect ratio | 16:9 or 9:16 |
| Max screenshots | 8 per device type |
| Feature graphic | 1024 x 500 px (required for featuring) |

## The First 3 Rule

**80% of App Store impressions show only the first 3 screenshots** before the user scrolls. Plan these first.

| Position | Content | Purpose |
|---|---|---|
| **1** | Hero — core value, best feature | Stop the scroll, communicate what the app does |
| **2** | Key differentiator | What makes you unique vs competitors |
| **3** | Most popular feature | The thing users love most |
| 4 | Social proof or outcome | Ratings, results, testimonials |
| 5–8 | Additional features | Supporting features, settings, integrations |
| 9–10 | Edge cases | Specialized features for niche users |

## Caption Writing

- **Max 2 lines** of text
- **Benefit-focused**, not feature-focused
- **30pt+ equivalent** font size (must be readable in store)

```
❌ Feature-focused (bad):
"Push Notification System"
"Calendar View with Filters"

✅ Benefit-focused (good):
"Never Miss a Deadline Again"
"See Your Week at a Glance"
```

## Generating Screenshots

### Hero Screenshot (Position 1)
```bash
infsh app run falai/flux-dev-lora --input '{
  "prompt": "modern iPhone showing [describe your app UI], device floating at slight angle against soft gradient background, professional product shot, clean minimal composition, subtle reflection",
  "width": 1024,
  "height": 1536
}'
```

### Feature Highlight with Callouts
```bash
infsh app run bytedance/seedream-4-5 --input '{
  "prompt": "app store screenshot style, iPhone showing [feature] highlighted, clean white background, subtle UI callout arrows, professional marketing asset",
  "size": "2K"
}'
```

### Lifestyle Context
```bash
infsh app run falai/flux-dev-lora --input '{
  "prompt": "person holding iPhone showing [your app], [relevant setting], warm natural lighting, lifestyle photography, authentic feeling",
  "width": 1024,
  "height": 1536
}'
```

### Before/After Split
```bash
infsh app run infsh/stitch-images --input '{
  "images": ["before-screenshot.png", "after-screenshot.png"],
  "direction": "horizontal"
}'
```

## Preview Videos

### Apple App Store Specs
| Spec | Value |
|---|---|
| Duration | 15–30 seconds |
| Format | H.264, .mov or .mp4 |
| Audio | Optional (loops silently) |

### Preview Video Structure
| Segment | Duration | Content |
|---|---|---|
| Hook | 0–3s | Core outcome / wow moment |
| Feature 1 | 3–10s | Top feature in action |
| Feature 2 | 10–18s | Second key feature |
| Feature 3 | 18–25s | Third feature or social proof |
| CTA | 25–30s | App icon end screen |

```bash
infsh app run google/veo-3-1-fast --input '{
  "prompt": "smooth screen recording style, finger tapping on a modern mobile app interface, swiping between screens showing [describe content], clean UI transitions, professional app demo"
}'
```

## Screenshot Styles

1. **Device Frame with Caption** — Standard: device mockup + caption above/below
2. **Full-Bleed UI** — App fills entire screenshot (immersive apps)
3. **Lifestyle Context** — Device in real-world setting
4. **Feature Callouts** — UI with arrows/circles pointing to features

## Common Mistakes to Avoid

| Mistake | Fix |
|---|---|
| Settings screen as screenshot | Show core value, not infrastructure |
| Onboarding flow screenshots | Show app in-use state |
| Too much text | Max 2 lines, 30pt+ font |
| Wrong dimensions | Use exact specs above |
| All screenshots look the same | Vary composition and content |
| Feature-focused captions | "Never Miss a Deadline" > "Push Notifications" |
| Outdated UI | Update with every major release |

## Pre-Submission Checklist

- [ ] Correct dimensions for target platform
- [ ] First 3 screenshots communicate core value
- [ ] Captions are benefit-focused, max 2 lines
- [ ] No onboarding or settings screens
- [ ] Preview video 15–30s with hook in first 3s
- [ ] Feature graphic (1024×500) for Google Play
- [ ] Screenshots updated for current app version
- [ ] Localized for primary markets (EN, JA, KO, ZH, DE, FR, ES, PT)

## Localization Priority

| Market | Approach |
|---|---|
| Primary (EN, JA, KO, ZH) | New screenshots + translated captions |
| Secondary (DE, FR, ES, PT) | Translated captions, same screenshots |
| Other | English defaults |

## Related Skills

```bash
npx skills add inference-sh/skills@ai-image-generation
npx skills add inference-sh/skills@ai-video-generation
npx skills add inference-sh/skills@image-upscaling
```

## Works Best With

- `/app-store-submission-auditor` — audit for rejection risks before submitting
- `/swiftui-pro` — ensure app UI follows iOS HIG before screenshotting
