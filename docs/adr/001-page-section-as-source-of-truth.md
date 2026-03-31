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

That ambiguity also applied to tenant-owned site chrome:

- homepage and inner pages
- global site header
- global site footer

Without an explicit rule, those areas could drift into separate storage models.

## Decision

We define:

- Page + Section as the canonical authored content model
- SectionQueryResolver as the primary runtime resolver
- PageBuilderStructure as a storage and publish layer only
- BuilderSectionDataResolver as transitional and non-primary
- tenant global site chrome must also use Page + Section
- tenant header and footer are stored as regular `Page` records with dedicated contexts:
  - `tenant_header`
  - `tenant_footer`
- tenant shell blocks remain regular `Section` records and currently use:
  - `site_header`
  - `site_footer`

## Consequences

- New features must rely on Page + Section
- Builder output must not become the primary source
- Rendering logic should move toward a single consistent path over time
- tenant runtime may wrap normal tenant pages with tenant-owned shell pages, but those shells are still part of the same canonical content model
- new global tenant UI areas should be added through Page + Section plus a small orchestration service, not through parallel header/footer tables
