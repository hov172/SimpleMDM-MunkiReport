---
description: Create a Product Requirements Document (PRD) — the blueprint before any building starts
argument-hint: "[app description or paste research findings]"
---

Based on this context: $ARGUMENTS

Create a detailed PRD (Product Requirements Document) as a markdown file saved to `docs/plans/YYYY-MM-DD-<topic>-prd.md`.

The PRD must include:

## App Overview
One paragraph: what it does, who it's for, why it exists.

## Core Features
Numbered list. Each feature: name, description, acceptance criteria.

## Tech Stack
- Frontend: _recommended_
- Backend: _recommended_
- Database: _recommended_
- AI/APIs: _recommended_
- Deployment: Railway (connect GitHub → auto-deploy)

## Data Model
Key entities and their fields. Relationships between them.

## User Flows
Step-by-step: how a user completes each core feature.

## API Keys Needed
List every external service and what the key is for. All go in `.env`.

## Out of Scope (v1)
What we're explicitly NOT building in this version.

After writing the PRD, ask: "Should I start building from this plan, or do you want to review and edit first?"

**Rule:** Never start building until the PRD is reviewed and approved.
