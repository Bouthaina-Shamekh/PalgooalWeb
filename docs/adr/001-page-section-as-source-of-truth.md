# ADR 001: Page + Section as Source of Truth

## Status
Accepted

## Context

The system previously had multiple content sources:

- Page + Section (admin CMS)
- PageBuilderStructure (builder storage)
- Published HTML
- Raw translation fallback

This created ambiguity in rendering and development.

## Decision

We define:

- Page + Section as the canonical authored content model
- SectionQueryResolver as the primary runtime resolver
- PageBuilderStructure as a storage and publish layer only
- BuilderSectionDataResolver as transitional and non-primary

## Consequences

- New features must rely on Page + Section
- Builder output must not become the primary source
- Rendering logic should move toward a single consistent path over time