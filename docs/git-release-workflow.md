# Git And Release Workflow

## Branch Roles

- `main` is the source of truth for production code.
- `codex/feature-*` branches contain work that is still being reviewed.
- Staging is a deployment environment, not a branch that receives merges.

The legacy `staging` branch is retained temporarily as a rollback reference,
but routine releases must not merge, rebase, or push to it.

## Start Work

Always start a feature from the current remote production baseline:

```bash
git switch main
git pull --ff-only origin main
git switch -c codex/feature-short-name
```

Commit and push the feature normally:

```bash
git add <reviewed-files>
git commit -m "Describe the change"
git push -u origin HEAD
```

## Deploy The Feature To Staging

1. Open **GitHub Actions → Deploy Selected Branch to Staging**.
2. Choose **Run workflow**.
3. In **Use workflow from**, select the feature branch.
4. Run the workflow.

The workflow deploys that branch's exact commit to the isolated staging
WordPress install. It does not merge or rebase a `staging` branch, and it does
not change production.

## Release To Production

After staging approval, merge the feature branch into `main`. The existing
production workflow deploys reviewed custom theme and plugin changes from
`main` automatically.

Delete the merged feature branch when it is no longer needed. Never merge the
legacy `staging` branch back into `main`.

## WordPress Content

Git deploys custom theme and plugin code only. Villa records, pages, uploads,
and database changes continue to use the separate guarded WordPress publishing
commands documented in `docs/inmotion-backend-deployment.md`.
