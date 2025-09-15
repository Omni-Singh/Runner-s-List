PRAGMA foreign_keys = ON;
PRAGMA user_version = 1;

CREATE TABLE allowed_domains (
  domain TEXT PRIMARY KEY
);

-- only CSUB allowed
INSERT OR IGNORE INTO allowed_domains(domain) VALUES ('csub.edu');

CREATE TABLE users (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  email         TEXT NOT NULL UNIQUE,
  display_name  TEXT NOT NULL,
  password_hash TEXT NOT NULL,
  is_verified   INTEGER NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- enforce CSUB domain
CREATE TRIGGER users_email_domain_check
BEFORE INSERT ON users
BEGIN
  SELECT CASE
    WHEN instr(NEW.email,'@')=0 THEN RAISE(ABORT,'Invalid email address.')
    WHEN NOT EXISTS (
      SELECT 1 FROM allowed_domains d
       WHERE lower(substr(NEW.email, instr(NEW.email,'@')+1)) = lower(d.domain)
    ) THEN RAISE(ABORT,'Email domain not allowed.')
  END;
END;
