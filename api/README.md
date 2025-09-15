# Lost & Found – Auth Backend

Backend service for the campus Lost & Found project.

Implements:
- **CSUB-only signup** (`@csub.edu` enforced by app + DB trigger)
- **Password policy** (≥8 chars, includes upper/lower/number/special)
- **Secure password storage** (bcrypt hashing)
- **JWT login** (returns `{ token, user }`)
- **Health check** endpoint
- Optional **simple test page** for manual signup/login

---

## Project structure

```
api/
  data/                 # SQLite DB file (gitignored, created on migrate)
  migrations/           # SQL migrations (001_init.sql)
  scripts/              # migrate.js (+ optional inspect.js)
  public/               # static test page (index.html)
  db.js                 # sqlite connection
  server.js             # express app (register/login)
  package.json
  .env.example          # template env vars
  .gitignore
```

---

## Setup

### Requirements
- Node.js 18+ (LTS recommended)
- npm

### Install dependencies
```bash
cd api
npm install
```

### Environment variables
Copy `.env.example` → `.env` and set a strong JWT secret:

```
JWT_SECRET=<long-random-string>
PORT=3000
NODE_ENV=development
```

 Never commit `.env`.

---

##  Database

Run migrations to create the schema:

```bash
cd api
npm run migrate
```

Expected output:
```
Applying 001_init.sql
All migrations applied
```

SQLite file will be created at: `api/data/app.sqlite`.

---

##  Run the server

```bash
npm run dev
```

Server available at: <http://localhost:3000>

## Test page

If `public/index.html` exists, open:  
<http://localhost:3000/index.html>  

Use the forms to test signup/login.

---

## 🛠️ Common issues

- **`SqliteError: no such table`**  
  → Run `npm run migrate`. If still failing, delete `api/data/app.sqlite` and re-run.

- **`'nodemon' is not recognized`**  
  → Run `npm i -D nodemon` or change `"dev": "node server.js"` in package.json.

---

## Next steps

- Use `requireAuth` middleware in `server.js` to protect future routes.  
- Add lost item posting + retrieval endpoints.  
- Add image upload support 
- Implement email verification (uses `is_verified` column).  
