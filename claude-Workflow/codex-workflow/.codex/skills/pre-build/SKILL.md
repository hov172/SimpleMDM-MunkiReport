---
name: pre-build
description: Pre-build checklist — run before writing any code. Covers data mapping, edge cases, security boundaries, production thinking, definition of done.
---

Before writing any code for: {user-provided arguments}

Run through this pre-build checklist. Answer each question. Flag anything unclear before proceeding.

Current project state:
(read directory listing)

---

## 1. REQUIREMENTS (What & Why)

- [ ] What business problem does this solve? Who is the user?
- [ ] What are the must-have features vs nice-to-have? (write them as separate lists)
- [ ] What does the primary user flow look like step by step?
- [ ] What are the acceptance criteria — how will we know it works?

## 2. DATA & SENSITIVE INFORMATION

- [ ] What data is being collected? Where does it come from? Where does it go?
- [ ] Circle anything that looks like personal or sensitive information (email, payment, health, etc.)
- [ ] Can we use synthetic/fake data during development instead of real data?
- [ ] Are all secrets in .env (never hardcoded)? See Step 7 of the 16-step workflow.

## 3. SECURITY BOUNDARIES

- [ ] Who can access this feature? Authentication required?
- [ ] What is the least privilege needed for each service/database connection?
- [ ] If this app uses an AI/LLM: what is the worst the model could do if a user tells it to misbehave?
- [ ] If this app uses an AI/LLM: have we planned for prompt injection? (see security rules)

## 4. EDGE CASES (the corners that break prototypes)

Think through each category:
- **Input extremes**: empty, very large, malformed, or unexpected data
- **Timing issues**: slow responses, concurrent requests, race conditions  
- **System limits**: API rate limits, memory, storage, file size
- **Partial failures**: some services succeed while others fail
- **Unexpected user behavior**: actions outside the normal workflow

For each major feature, name at least one edge case and how we'll handle it.

## 5. PRODUCTION THINKING (10x / 100x users)

- [ ] How does each component behave with 10 users? 100? 1000?
- [ ] What is the failure mode for each critical dependency? How should the system respond?
- [ ] What is the rollback plan if a deployment breaks something?
- [ ] Are basic logs and metrics in place before this reaches real users?
- [ ] Is data consistency protected under concurrent access or partial failures?

## 6. TECHNICAL DEBT DECISIONS

- [ ] Which shortcuts are we taking intentionally? (Document them — intentional debt is manageable)
- [ ] Which parts are exploratory vs foundational? (Exploratory can be rough; foundational must be clean)
- [ ] Label any temporary decisions with TODO(name): desc (#issue) so they are revisitable

## 7. DEFINITION OF DONE

- [ ] What does "complete" look like? (specific, verifiable criteria)
- [ ] How will we test it? (unit test? manual? screenshot loop?)
- [ ] What verification method will Claude use to prove it works before marking done?

---

After reviewing this checklist:
- If any item is unclear — **resolve it first** before any implementation
- If all items are clear — write a brief summary of the answers, then proceed to the brainstorming skill or $prd
- **Do NOT start coding until this checklist is complete**

The habits formed in a prototype become the architecture of the product.
