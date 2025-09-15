// api/server.js
import "dotenv/config";
import express from "express";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import { z } from "zod";
import path from "path";
import { fileURLToPath } from "url";

import db from "./db.js";

const app = express();
app.use(express.json());

// --- Serve static test page (api/public/index.html) ---
const __dirname = path.dirname(fileURLToPath(import.meta.url));
app.use(express.static(path.join(__dirname, "public")));

const JWT_SECRET = process.env.JWT_SECRET || "dev-secret-change-me";

// --- Helpers ---
function isAllowedSchoolEmail(email) {
  const at = email.indexOf("@");
  if (at < 0) return false;
  const domain = email.slice(at + 1).toLowerCase();
  const row = db.prepare("SELECT 1 FROM allowed_domains WHERE lower(domain)=?").get(domain);
  return !!row;
}

// Password policy: >= 8 chars, 1 upper, 1 lower, 1 number, 1 special
const pwdRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

const registerSchema = z.object({
  email: z.string().email(),
  display_name: z.string().min(1),
  password: z.string().regex(pwdRegex, {
    message:
      "Password must be at least 8 characters and include upper, lower, number, and special character."
  }),
  confirm_password: z.string()
}).refine((d) => d.password === d.confirm_password, {
  message: "Passwords do not match",
  path: ["confirm_password"]
});

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1)
});

// --- Routes ---

// Health check
app.get("/healthz", (_req, res) => res.json({ ok: true }));

// Signup (CSUB-only)
app.post("/api/register", async (req, res) => {
  const parsed = registerSchema.safeParse(req.body);
  if (!parsed.success) {
    return res.status(422).json({ code: "invalid_input", errors: parsed.error.flatten() });
  }

  const { email, display_name, password } = parsed.data;

  // Enforce @csub.edu (also enforced by DB trigger)
  if (!isAllowedSchoolEmail(email)) {
    return res.status(400).json({
      code: "domain_not_allowed",
      disclaimer: "Only CSUB emails can sign up. Please use your @csub.edu address."
    });
  }

  try {
    const hash = await bcrypt.hash(password, 10);
    const info = db
      .prepare("INSERT INTO users(email, display_name, password_hash) VALUES (?,?,?)")
      .run(email, display_name, hash);

    return res.status(201).json({ id: info.lastInsertRowid });
  } catch (e) {
    const msg = String(e);
    if (msg.includes("UNIQUE")) {
      return res.status(409).json({ code: "email_exists", message: "Email already registered." });
    }
    if (msg.includes("Email domain not allowed")) {
      return res.status(400).json({
        code: "domain_not_allowed",
        disclaimer: "Only CSUB emails can sign up. Please use your @csub.edu address."
      });
    }
    console.error(e);
    return res.status(500).json({ code: "server_error" });
  }
});

// Login (returns JWT)
app.post("/api/login", async (req, res) => {
  const parsed = loginSchema.safeParse(req.body);
  if (!parsed.success) {
    return res.status(422).json({ code: "invalid_input", errors: parsed.error.flatten() });
  }

  const { email, password } = parsed.data;

  const user = db
    .prepare("SELECT id, email, display_name, password_hash FROM users WHERE email=?")
    .get(email);

  if (!user) return res.status(401).json({ code: "invalid_credentials" });

  const ok = await bcrypt.compare(password, user.password_hash);
  if (!ok) return res.status(401).json({ code: "invalid_credentials" });

  const token = jwt.sign({ id: user.id, email: user.email }, JWT_SECRET, { expiresIn: "7d" });

  return res.json({
    token,
    user: { id: user.id, email: user.email, display_name: user.display_name }
  });
});

// (Optional) simple middleware for protected routes
export function requireAuth(req, res, next) {
  const auth = req.headers.authorization || "";
  const token = auth.startsWith("Bearer ") ? auth.slice(7) : null;
  if (!token) return res.status(401).json({ code: "unauthorized" });
  try {
    const payload = jwt.verify(token, JWT_SECRET);
    req.user = payload;
    return next();
  } catch {
    return res.status(401).json({ code: "unauthorized" });
  }
}

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`API running on http://localhost:${PORT}`);
});
