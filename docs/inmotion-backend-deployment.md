# InMotion Backend Deployment

## Purpose

This note documents the working backend deployment path for the Barbados Escapes WordPress install on InMotion. It covers only the backend connection and deployment flow, not any frontend hosting.

Verified on April 12-13, 2026.

## AI Chat Notes

- This file is the first place to check for Barbados Escapes backend deployment context.
- `AGENTS.md` points here for InMotion, cPanel Git, GitHub Actions deploy, and `/wp-json/` troubleshooting.
- The most important distinction is key direction:
  - `barbados-escapes-git` is for server -> GitHub pulls.
  - `github_deploy_key` is for GitHub Actions -> server SSH login.
- If `/wp-json/` fails but `/?rest_route=/` works, suspect rewrite routing before suspecting WordPress or deployment.
- The live `.htaccess` file is currently a server-side prerequisite, not a repo-managed artifact.

## Live Paths

- GitHub repo: `git@github.com:christos22/barbados-escapes.git`
- cPanel-managed server repo: `/home/grapsa5/repositories/barbados-escapes`
- Live WordPress docroot: `/home/grapsa5/barbadosescapes.verseandvision.ca`
- Deployed theme path: `/home/grapsa5/barbadosescapes.verseandvision.ca/wp-content/themes/gutenberg-lab-vvm`
- Deployed plugin path: `/home/grapsa5/barbadosescapes.verseandvision.ca/wp-content/plugins/gutenberg-lab-blocks`

## Deploy Contract

- Only custom backend code is deployed from this repo.
- `.cpanel.yml` copies:
  - `wp-content/themes/gutenberg-lab-vvm`
  - `wp-content/plugins/gutenberg-lab-blocks`
- WordPress core, uploads, and third-party plugins remain server-managed.
- Auto-deploy is triggered by pushes to `main` that touch:
  - `.cpanel.yml`
  - `wp-content/themes/gutenberg-lab-vvm/**`
  - `wp-content/plugins/gutenberg-lab-blocks/**`

## Connection Model

There are two separate SSH trust relationships in this setup:

1. Server -> GitHub
   The InMotion server pulls the private GitHub repo with a repo-specific deploy key.

2. GitHub Actions -> Server
   GitHub Actions logs into the InMotion server over SSH and runs the deployment script.

Keep those keys separate conceptually. They solve different problems.

## Server -> GitHub Setup

The cPanel Git clone UI rejected both:

- HTTPS URLs with embedded credentials
- SSH alias URLs like `ssh://git@github.com-barbados-escapes/...`

The working setup was:

1. Generate a dedicated server key in cPanel Terminal with no passphrase:

   ```bash
   ssh-keygen -t rsa -f ~/.ssh/barbados-escapes-git -b 4096 -C "grapsa5@github.com-barbados-escapes" -N ""
   ```

2. Add the public key from `~/.ssh/barbados-escapes-git.pub` as a deploy key on `christos22/barbados-escapes`.

3. Authorize the key in cPanel SSH Access.

4. Add an SSH alias in `~/.ssh/config` so this repo uses the correct key:

   ```sshconfig
   Host github.com-barbados-escapes
       HostName github.com
       User git
       IdentityFile /home/grapsa5/.ssh/barbados-escapes-git
       IdentitiesOnly yes
   ```

5. Verify the alias:

   ```bash
   ssh -T git@github.com-barbados-escapes
   ```

6. Manually clone in Terminal because the cPanel clone form rejects the alias host:

   ```bash
   mkdir -p /home/grapsa5/repositories
   git clone ssh://git@github.com-barbados-escapes/christos22/barbados-escapes.git /home/grapsa5/repositories/barbados-escapes
   ```

7. Add the existing clone to cPanel Git Version Control by creating a repository with `Clone a Repository` turned off and pointing it at `/home/grapsa5/repositories/barbados-escapes`.

## GitHub Actions -> Server Setup

The repo workflow is [`.github/workflows/deploy-backend.yml`](../.github/workflows/deploy-backend.yml).

Required GitHub Actions secrets:

- `SSH_HOST=ecngx362.inmotionhosting.com`
- `SSH_USERNAME=grapsa5`
- `SSH_PRIVATE_KEY=<private key for server login>`

For this project, the same private key used by the VVM deployment worked:

- local file: `~/.ssh/github_deploy_key`
- public fingerprint: `SHA256:Tns8Ctn9Zqw4SiaoXVN54EGK61MJ5AD722iZedflJ2s`
- public label: `github-deploy`

That key is for GitHub Actions logging into the InMotion server. It is not the same key as the server-side `barbados-escapes-git` deploy key used to pull from GitHub.

## Rewrite Requirement

Deployment worked before routing worked.

WordPress was installed and reachable at `/wp-login.php`, and the REST API worked at `/?rest_route=/`, but `/wp-json/` returned `404` until `.htaccess` existed in the live docroot.

The working file on the server is:

```apacheconf
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

Current note:

- this `.htaccess` file was created directly on the server
- it is not currently deployed from this repo
- if the docroot is rebuilt or replaced, `/wp-json/` should be rechecked immediately

## Local Refresh

The standard local refresh workflow is a DDEV host command:

```bash
ddev sync-remote
```

This command is intended for local development only. It refreshes the local DDEV database and uploads from the live InMotion WordPress install.

### Local SSH Prerequisite

Use a local SSH alias named `inmotion-grapsasdev` so both manual SSH and the sync command share one source of truth for host, port, user, and key path.

Recommended `~/.ssh/config` block:

```sshconfig
Host inmotion-grapsasdev
    HostName 209.182.196.26
    Port 2222
    User grapsa5
    IdentityFile /home/Quester/Nextcloud/Documents/Development/Server/SSH-Keys/inmotion
    IdentitiesOnly yes
```

Keep the key passphrase-protected. Before running the sync command, unlock it with:

```bash
ssh-add /home/Quester/Nextcloud/Documents/Development/Server/SSH-Keys/inmotion
```

If `ssh -o BatchMode=yes inmotion-grapsasdev "echo ok"` fails, the command should be treated as not ready to run.

### Local Sync Config

Copy the example file:

```bash
cp .ddev/.env.sync-remote.example .ddev/.env.sync-remote
```

Then fill in the live database credentials. The real `.ddev/.env.sync-remote` file is intentionally ignored by Git.

When a live database is imported, the command auto-detects its WordPress table prefix and writes a local-only override file:

- `wp-config-ddev-local.php`

That file is intentionally ignored by Git. It exists so local DDEV can boot imported databases that do not use the default `wp_` prefix. The command also patches the local ignored `wp-config-ddev.php` once so DDEV loads that override file on future requests.

### Command Modes

- `ddev sync-remote`
  Refresh DB and uploads
- `ddev sync-remote --db-only`
  Refresh only the database
- `ddev sync-remote --uploads-only`
  Refresh only uploads
- `ddev sync-remote --mirror-uploads`
  Mirror uploads exactly using `rsync --delete`
- `ddev sync-remote --skip-snapshot`
  Skip the pre-import DDEV DB snapshot
- `ddev sync-remote --dry-run`
  Validate SSH and remote paths without importing data

### Rollback

By default, database refresh creates a DDEV snapshot first. Roll back with:

```bash
ddev snapshot restore --latest
```

## Verification Checklist

Use these checks after setup changes or deployment troubleshooting:

1. Confirm the server repo remote:

   ```bash
   git -C /home/grapsa5/repositories/barbados-escapes remote -v
   ```

2. Confirm deployed files exist:

   ```bash
   test -f /home/grapsa5/barbadosescapes.verseandvision.ca/wp-content/themes/gutenberg-lab-vvm/style.css && echo "theme ok"
   test -f /home/grapsa5/barbadosescapes.verseandvision.ca/wp-content/plugins/gutenberg-lab-blocks/gutenberg-lab-blocks.php && echo "plugin ok"
   ```

3. Confirm WordPress REST is healthy:

   ```bash
   curl -I -A "Mozilla/5.0" https://barbadosescapes.verseandvision.ca/wp-json/
   ```

   Expected result: `HTTP/2 200`

4. If `/wp-json/` fails, test the fallback route:

   ```bash
   curl -I -A "Mozilla/5.0" "https://barbadosescapes.verseandvision.ca/?rest_route=/"
   ```

   If this returns `200`, WordPress is likely healthy and the issue is rewrite routing.

## Normal Workflow

1. Commit changes to `main`.
2. Push to GitHub.
3. GitHub Actions runs `Deploy Backend to InMotion`.
4. The workflow SSHes into InMotion, pulls `/home/grapsa5/repositories/barbados-escapes`, copies the custom theme and plugin into the live WordPress install, and verifies `/wp-json/`.
5. If needed, cPanel `Git Version Control -> Pull or Deploy -> Deploy HEAD Commit` remains available as a manual fallback.
