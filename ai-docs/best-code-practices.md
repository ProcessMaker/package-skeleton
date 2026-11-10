# Best code practices

Cross-project coding standards for package-plg. Prefer these when writing or refactoring code so SonarQube and review stay green.

**SOLID:** On every frontend and backend implementation, apply SOLID. Agent skill: [`.cursor/skills/apply-solid/SKILL.md`](../.cursor/skills/apply-solid/SKILL.md).

## Sonar review (required)

Every change must **pass SonarQube analysis** on the touched code before merge or handoff. Treat Sonar as a quality gate, not optional feedback.

- Resolve **new** issues introduced by the change, at minimum Blocker, Critical, and Major severities.
- Address common frontend rules in the same pass (for example: declare Vue `emits` for every `$emit`, prefer optional chaining where Sonar flags manual null checks).
- Do not merge with a failing Sonar quality gate on the PR unless the team has an explicit, documented exception.
- Do not silence findings with broad Sonar exclusions, unexplained `eslint-disable`, or equivalent suppressions without team agreement and a short rationale in the PR.

When Sonar reports an issue, fix the root cause in code rather than debating the rule at review time.

## Cognitive complexity (SonarQube)

Keep each function/method at cognitive complexity ≤ 15. When a method approaches or exceeds that limit, extract nested branching or loops into named private helpers, prefer early returns over deep nesting, and fix Sonar findings before handoff.

## Use at most 3 returns in a function

Functions should not contain too many return statements

## Class size

Keep each class at a maximum of 20 methods. If a class grows beyond that, split responsibilities into collaborators (ports, use cases, or focused helpers) rather than accumulating more private methods on the same type.

## Optional chaining

Prefer optional chain expressions (`?.`) over nested null/undefined checks. They are more concise and easier to read—for example, use `user?.profile?.name` instead of `user && user.profile && user.profile.name`, and `file?.name` instead of `file && file.name`.

## Related quality expectations

- Prefer small, single-purpose private methods over one large multi-branch method.
- Run local lint/format checks for the stack you touched (PHP, JavaScript/Vue) when available before pushing.

## Related to code

- Do not use unnecessary use of boolean literals in conditional expression. e.g. `condition ? true : false` when `condition` is already a boolean.
- [S7744] Do not use unnecessary fallback objects should not be used when spreading in object literals.

## Related to Vue components
- Declare emits array for all emitted events.
- Declare the props as object that include type of the properties.
