# Deploying the SLSEA Solar Generation API (beginners)

This guide deploys the API to **Railway** (recommended) with a **persistent MySQL**
database and **free automatic HTTPS**. No server administration is required.

> The API is already production-ready in this repo: a `Dockerfile`, an idempotent
> startup script, a `/api/health` probe and a `.env.production.example` template
> are included. You only create accounts, push to GitHub, and paste secrets.

---

## 0. What you will end up with

| Thing | URL |
| --- | --- |
| Swagger UI | `https://YOUR-APP.up.railway.app/api/documentation` |
| Raw OpenAPI | `https://YOUR-APP.up.railway.app/api/docs` |
| Health check | `https://YOUR-APP.up.railway.app/api/health` |
| Liveness probe | `https://YOUR-APP.up.railway.app/up` |
| Operator login | `POST https://YOUR-APP.up.railway.app/api/auth/operator/login` |

---

## 1. Hosting recommendation (honest comparison)

**Recommended: Railway** — GitHub deploys, one-click MySQL, automatic HTTPS,
shows logs in the browser, no server to patch. Best "works in an hour" choice.

| Provider | MySQL persistence | HTTPS | Rough cost | Honest limits |
| --- | --- | --- | --- | --- |
| **Railway** (recommended) | Yes (managed MySQL service + volume) | Automatic | Hobby plan ~**$5/month** including ~$5 usage credit; a tiny API + MySQL usually stays near that, but heavy use can add a few dollars | Usage-based billing can surprise you; free trial credit is limited |
| **Render** | **No managed MySQL** (only PostgreSQL/Redis) | Automatic | Web service ~$7/month always-on; free web service sleeps | You must bring an external MySQL (e.g. Aiven) → more moving parts |
| **Hostinger / Namecheap shared (cPanel)** | Yes | Free AutoSSL | ~**$2–4/month** | Weaker/limited SSH, Git deploy is clunky, no container; slower to get right |
| **DigitalOcean / Hetzner VPS** | Yes (self-managed) | Manual (Let's Encrypt) | ~$5–6/month + your time | You patch the OS, firewall, nginx/MySQL yourself — not beginner-friendly |

If Railway's Docker build ever fails for you, tell me and I will switch this repo
to Railway's **Nixpacks** build (no Dockerfile) in minutes.

---

## 2. Pre-flight checks (run locally, on Windows)

```powershell
php artisan test          # expect: 134 passed
php artisan config:cache ; php artisan route:cache ; php artisan optimize:clear
```

Make sure `.env` is **not** committed. This repo's `.gitignore` already ignores it.

---

## 3. Push the project to GitHub

```powershell
git init
git add .
git commit -m "Prepare production deployment"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/solar-generation-api.git
git push -u origin main
```

If the remote already exists, use `git remote set-url origin <url>` instead.
Never `git add .env`; if you ever did, rotate the secrets immediately.

---

## 4. Create the Railway project

1. Go to <https://railway.app> and sign in with GitHub.
2. **New Project → Deploy from GitHub repo →** pick `solar-generation-api`.
3. Railway detects the `Dockerfile` and starts building. **Do not** add the
   database yet — finish the next step first so variables exist.

---

## 5. Generate the two secrets (locally, once)

```powershell
php artisan key:generate --show
php -r "echo base64_encode(random_bytes(48)), PHP_EOL;"
```

- The first output is your `APP_KEY` (starts with `base64:`).
- The second is your `JWT_SECRET` (HS256 needs ≥ 32 bytes; this is 48 bytes).

Keep these only in the Railway dashboard (and a password manager). Never commit them.

---

## 6. Add a MySQL database

1. In the Railway project: **New → Database → Add MySQL**.
2. Railway creates a `MYSQL*` service. It injects `MYSQLHOST`, `MYSQLPORT`,
   `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD` (and `MYSQL_URL`) into your app
   automatically — the startup script maps them to Laravel's `DB_*` variables.
   You do **not** need to copy them by hand.

---

## 7. Set the app's environment variables

Open your **app service → Variables → Raw Editor** and add the following.
Replace `YOUR-APP` with the domain Railway shows in **Settings → Networking**
(add `https://` in `APP_URL` and **no** trailing slash).

```
APP_NAME="SLSEA Solar Generation API"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:PASTE_THE_FIRST_GENERATED_VALUE=
APP_URL=https://YOUR-APP.up.railway.app

JWT_SECRET=PASTE_THE_SECOND_GENERATED_VALUE
JWT_ALGO=HS256
JWT_ISSUER=solar-generation-api
JWT_AUDIENCE=slsea-api
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_LEEWAY=30

DB_CONNECTION=mysql
SESSION_DRIVER=cookie
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=warning

FORCE_HTTPS=true
TRUSTED_PROXIES=*
API_RATE_LIMIT=120
API_AUTH_RATE_LIMIT=10

L5_SWAGGER_GENERATE_ALWAYS=false
```

Save. Railway redeploys automatically.

---

## 8. First boot does everything for you

At every start the container runs (see `docker/start.sh`):

1. `php artisan config:cache` / `route:cache` / `view:cache`
2. `php artisan migrate --force` — applies only **new** migrations
3. `php artisan db:seed --force` — idempotent (`firstOrCreate`); a second run
   inserts **nothing** and never deletes data
4. `php artisan l5-swagger:generate` — refreshes `/api/docs`
5. starts Apache on `$PORT`

The first deploy seeds 9 provinces, 25 districts, 24 substations, 216
installations and **one week (7 days) of quarter-hourly readings** (≈145,152 rows).
This can take a minute; the health check waits up to 300 seconds.

---

## 9. Turn on public HTTPS

1. App service → **Settings → Networking → Generate Domain**.
2. Copy the domain into `APP_URL` (step 7) and save — Railway then serves HTTPS
   automatically with a valid certificate.
3. Optional: **Custom Domain → Add** and create a CNAME as instructed. HTTPS is
   issued automatically.

---

## 10. Verify the deployment (replace YOUR-APP)

Swagger + health (PowerShell):

```powershell
Invoke-RestMethod https://YOUR-APP.up.railway.app/api/health
Invoke-WebRequest https://YOUR-APP.up.railway.app/api/documentation -UseBasicParsing | Select-Object StatusCode
Invoke-WebRequest https://YOUR-APP.up.railway.app/api/docs        -UseBasicParsing | Select-Object StatusCode, ContentType
```

JWT login (create a demo account first):

```powershell
# 1) one-off, in the Railway app shell (or locally against the same DB):
#    php artisan demo:credentials --password="Demo-Password-123!"
$body = @{ email = "admin@slsea.test"; password = "Demo-Password-123!" } | ConvertTo-Json
$login = Invoke-RestMethod -Method Post `
  -Uri https://YOUR-APP.up.railway.app/api/auth/operator/login `
  -ContentType "application/json" -Body $body
$token = $login.data.access_token

# 2) call a protected endpoint with the token
Invoke-RestMethod -Uri https://YOUR-APP.up.railway.app/api/provinces `
  -Headers @{ Authorization = "Bearer $token" }
```

Database sanity check (Railway → MySQL service → **Data** tab, or Query):

```sql
SELECT COUNT(*) FROM provinces;        -- 9
SELECT COUNT(*) FROM districts;        -- 25
SELECT COUNT(*) FROM grid_substations; -- 24
SELECT COUNT(*) FROM solar_installations; -- 216
SELECT COUNT(*) FROM generation_readings; -- 145152
```

> No public URL has been created by me: deployment only succeeds once the
> commands above return 200s on **your** Railway domain.

---

## 11. Optional: test the exact container locally

If you install Docker Desktop:

```powershell
docker compose up --build
# then open:  http://localhost:8080/api/documentation
```

`docker-compose.yml` uses throwaway local credentials and a named volume, so it
never touches real data.

---

## 12. Troubleshooting

| Symptom | Fix |
| --- | --- |
| Health check fails / container restarts | Open **Deploy logs**. Usually `APP_KEY` is missing (the script exits with a clear message) or the MySQL service is not attached |
| `SQLSTATE... Connection refused` | Ensure the **MySQL** service is in the same project; the script maps `MYSQL*` → `DB_*` automatically |
| Swagger UI blank | Set `APP_URL` to the exact `https://...` domain and redeploy; `L5_SWAGGER_GENERATE_ALWAYS=false` is fine because the spec is regenerated at boot |
| 301 redirects on the health URL | Not possible: `/api/health` and `/up` are exempt from HTTPS redirects by design |
| Login returns 401 | Run `php artisan demo:credentials` (or create a user) before logging in |
| Rate limited (429) | `API_RATE_LIMIT` / `API_AUTH_RATE_LIMIT` are per-minute; raise them or wait |

---

## 13. Data-safety guarantees

- The startup sequence never runs `migrate:fresh`, `db:wipe`, `truncate` or drops.
- Seeders use `firstOrCreate` / skip-if-present, so re-deploys are no-ops.
- `.env` is git-ignored and Docker-ignored; only `.env.production.example`
  (placeholders, no secrets) is committed.
- Rotating `APP_KEY` invalidates encrypted data and existing sessions; rotating
  `JWT_SECRET` invalidates all issued tokens (users simply log in again).
