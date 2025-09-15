// api/db.js
import Database from "better-sqlite3";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);

// Always put the DB in: api/data/app.sqlite
const DATA_DIR = path.join(__dirname, "data");
if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });

const DB_FILE = path.join(DATA_DIR, "app.sqlite");
console.log("[DB] Using SQLite file:", DB_FILE);

const db = new Database(DB_FILE);
db.pragma("foreign_keys = ON");

export default db;
