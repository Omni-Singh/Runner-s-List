// api/scripts/migrate.js
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import db from "../db.js";

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);

// Always read migrations from: api/migrations
const MIGRATIONS_DIR = path.resolve(__dirname, "../migrations");
const files = fs.readdirSync(MIGRATIONS_DIR).filter(f => f.endsWith(".sql")).sort();

for (const f of files) {
  const sql = fs.readFileSync(path.join(MIGRATIONS_DIR, f), "utf-8");
  console.log("Applying", f);
  db.exec(sql);
}
console.log(" All migrations applied");
