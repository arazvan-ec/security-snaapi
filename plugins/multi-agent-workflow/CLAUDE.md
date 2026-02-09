# Multi-Agent Workflow Plugin

A compound engineering framework for coordinating multiple AI agents in parallel on software development.

## Routing — Entry Point for Every Request

Every interaction passes through the workflow router before any work begins.

1. **Analyze** the user's request
2. **Classify** the work type (feature, bug, refactor, investigation, etc.)
3. **Assess** complexity and scope
4. **Ask clarifying questions** if confidence < 60%
5. **Route** to the appropriate workflow via `/workflows:route`

When the request is ambiguous, ask before acting. See `core/docs/ROUTING_REFERENCE.md` for question templates and the decision matrix.

Exception: continuing an already-routed task with valid `50_state.md` context.

## Core Workflows

| Phase | Command | Purpose |
|-------|---------|---------|
| Shape | `/workflows:shape` | Separate problem from solution, explore alternatives, spike unknowns (pre-planning) |
| Plan | `/workflows:plan` | Architecture-first planning (80% of effort) |
| Work | `/workflows:work` | Execute with parallelization by roles, layers, or stacks |
| Review | `/workflows:review` | Multi-agent quality review before merge |
| Compound | `/workflows:compound` | Capture learnings for future work |

## Commands

| Command | Purpose |
|---------|---------|
| `/workflows:route` | Route requests to appropriate workflow (entry point) |
| `/workflows:shape` | Shape features: problem/solution separation, spikes, breadboarding, slicing |
| `/workflows:plan` | Architecture-first planning |
| `/workflows:work` | Execute plan with task management |
| `/workflows:review` | Multi-agent review |
| `/workflows:compound` | Capture learnings |
| `/workflows:role` | Work as a specific role |
| `/workflows:sync` | Synchronize state between agents |
| `/workflows:status` | View all roles' status |
| `/workflows:reload` | Hot-reload skills/agents |
| `/workflows:snapshot` | Save session state |
| `/workflows:restore` | Restore from snapshot |
| `/workflows:metrics` | Performance analytics |
| `/workflows:discover` | Auto-analyze project architecture |
| `/workflows:specs` | Manage living specifications |
| `/workflows:skill-dev` | Develop and test skills with hot-reload |

## Agents

| Category | Count | Agents |
|----------|-------|--------|
| Roles | 4 | planner, backend, frontend, qa |
| Review | 7 | security, performance, ddd-compliance, code-ts, agent-native, simplicity, pattern-recognition |
| Research | 5 | codebase-analyzer, git-historian, dependency-auditor, learnings-researcher, best-practices-researcher |
| Workflow | 5 | bug-reproducer, spec-analyzer, spec-extractor, style-enforcer, comprehension-guardian |
| Design | 2 | api-designer, ui-verifier |

## Skills

| Category | Skills |
|----------|--------|
| Core | consultant, checkpoint, git-sync |
| Quality | test-runner, coverage-checker, lint-fixer |
| Workflow | worktree-manager, commit-formatter |
| Compound | changelog-generator, layer-validator, spec-merger |
| Integration | mcp-connector |
| SOLID | solid-analyzer, criteria-generator |
| Shaping | shaper, breadboarder |

## Context Activation Model

| Content | Activation | When Loaded |
|---------|-----------|-------------|
| This file (CLAUDE.md) | Always | Every session |
| Operational rules (`core/rules/framework_rules.md`) | Always | Every session |
| Scoped rules (`core/rules/*-rules.md`) | Software-determined | When matching file types are edited |
| Role definitions (`core/roles/`) | LLM-determined | When role is active |
| Skills (`skills/`) | Human-triggered | On `/skill:X` invocation |
| Review agents (`agents/review/`) | Human-triggered | During `/workflows:review` |
| Project hooks (`.ai/hooks/`) | Software-determined | Automatic on tool events |

Heavy skills and all 7 review agents run with `context: fork` — isolated context windows returning summaries only. See `core/docs/CONTEXT_ENGINEERING.md`.

## Capability Providers

The plugin is **model-agnostic** and **execution-agnostic**. It abstracts capabilities (parallelization, context management, fork strategy, execution mode) behind providers that auto-detect the running model and select the best implementation. The execution_mode provider determines whether the agent generates code autonomously (`agent-executes`), guides a human (`human-guided`), or generates with human review gates (`hybrid`). Configuration in `core/providers.yaml`, detection logic in `core/docs/CAPABILITY_PROVIDERS.md`.

When `providers.yaml` is set to `auto` (default), resolve providers using the Detection Protocol before executing provider-dependent commands (`/workflows:parallel`, `/workflows:snapshot`, fork decisions).

## Key Patterns

- **Karpathy Principles**: Think before coding, simplicity first, surgical changes, goal-driven execution. Details in `core/docs/KARPATHY_PRINCIPLES.md`.
- **Ralph Wiggum Loop**: Auto-correct up to 10 iterations, then mark BLOCKED. Details in `core/rules/framework_rules.md`.
- **Compound Capture**: After each feature, extract patterns and update rules via `/workflows:compound`.
- **SOLID Constraint**: Phase 3 solutions target score ≥22/25. See `core/solid-pattern-matrix.md`.
- **Context as Resource**: Thresholds adapt to provider (compaction-aware or manual-snapshots). Details in `core/docs/SESSION_CONTINUITY.md`.

## State Management

All roles communicate via `50_state.md`. Status values: `PENDING`, `IN_PROGRESS`, `BLOCKED`, `WAITING_API`, `COMPLETED`, `APPROVED`, `REJECTED`.

## Best Practices

1. Route first — every request through `/workflows:route`
2. Ask when unclear — confidence < 60% means ask before acting
3. State assumptions before coding
4. Define testable success criteria
5. Implement only what's requested
6. Touch only essential code, preserve existing style
7. Plan 80%, execute 20%
8. One role per session
9. Sync before work — pull latest changes
10. Write tests before implementation (TDD)
11. Run `/workflows:compound` after each feature
12. Snapshot before breaks or risky operations
13. Compact proactively at ~70% context capacity

## Reference Documentation

| Topic | Location |
|-------|----------|
| Capability providers & detection | `core/docs/CAPABILITY_PROVIDERS.md` |
| Provider configuration | `core/providers.yaml` |
| Routing details & question templates | `core/docs/ROUTING_REFERENCE.md` |
| Karpathy principles | `core/docs/KARPATHY_PRINCIPLES.md` |
| Context engineering & fork model | `core/docs/CONTEXT_ENGINEERING.md` |
| Session continuity & snapshots | `core/docs/SESSION_CONTINUITY.md` |
| Lifecycle hooks | `core/docs/LIFECYCLE_HOOKS.md` |
| MCP integration | `core/docs/MCP_INTEGRATION.md` |
| SOLID pattern matrix | `core/solid-pattern-matrix.md` |
| Operational rules | `core/rules/framework_rules.md` |
| Testing conventions | `core/rules/testing-rules.md` |
| Security & trust model | `core/rules/security-rules.md` |
| Git workflow | `core/rules/git-rules.md` |
| Full project structure | `README.md` |

---

**Version**: 2.8.0 | **Aligned with**: Compound Engineering + Karpathy + Context Engineering (Fowler) + Capability Providers + Shape Up (Singer)
