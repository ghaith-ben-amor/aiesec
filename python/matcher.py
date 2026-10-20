import json
from pathlib import Path
import re
import sys

try:
    sys.stdout.reconfigure(encoding="utf-8")
except Exception:
    pass

# ─── Alias map: normalize skill variations ────────────────────────────────────
ALIASES = {
    "js": "javascript",
    "nodejs": "javascript",
    "node.js": "javascript",
    "node": "javascript",
    "ts": "typescript",
    "mysql": "sql",
    "postgresql": "sql",
    "postgres": "sql",
    "sqlite": "sql",
    "figma": "ui ux",
    "ui/ux": "ui ux",
    "ui": "ui ux",
    "ux": "ui ux",
    "graphic design": "design",
    "web design": "design",
    "content writing": "content creation",
    "writing": "content creation",
    "communicat": "communication",
    "collaboration": "teamwork",
    "team work": "teamwork",
    "team player": "teamwork",
    "problem-solving": "problem solving",
    "ml": "machine learning",
    "ai": "artificial intelligence",
    "deep learning": "machine learning",
    "react.js": "react",
    "reactjs": "react",
    "vue.js": "vue",
    "vuejs": "vue",
    "angular.js": "angular",
    "customer service": "customer service",
    "social media": "social media marketing",
    "social media management": "social media marketing",
    "information technology": "it",
    "marketing": "marketing",
    "sales": "sales",
    "seller": "sales",
    "management": "management",
    "leadership": "leadership",
    "public speaking": "presentation",
    "microsoft office": "microsoft office",
    "ms office": "microsoft office",
    "excel": "microsoft office",
    "word": "microsoft office",
    "powerpoint": "microsoft office",
    "business development": "business",
    "business administration": "business",
    "entrepreneurship": "business",
    "data analysis": "data analysis",
    "data science": "data science",
    "software development": "software development",
    "software engineering": "software development",
    "web development": "web development",
    "mobile development": "mobile development",
    "android": "mobile development",
    "ios": "mobile development",
    "flutter": "mobile development",
    "flutterflow": "mobile development",
    "project management": "project management",
    "agile": "project management",
    "scrum": "project management",
    "iot": "iot",
    "embedded systems": "embedded systems",
    "arduino": "embedded systems",
    "stm32": "embedded systems",
    "teaching": "teaching",
    "teacher": "teaching",
    "education": "education",
    "educator": "teaching",
    "tutoring": "teaching",
    "tutor": "teaching",
    "instructor": "teaching",
    "pedagogy": "teaching",
    "lesson planning": "teaching",
    "classroom management": "teaching",
    "finance": "finance",
    "accounting": "finance",
    "financial analysis": "finance",
    "hr": "human resources",
    "human resources": "human resources",
    "recruitment": "human resources",
    "photoshop": "graphic design",
    "adobe photoshop": "graphic design",
    "illustrator": "graphic design",
    "adobe illustrator": "graphic design",
    "premiere pro": "video editing",
    "after effects": "video editing",
    "motion graphics": "video editing",
    "canva": "graphic design",
}

# ─── Stop words: must NOT be treated as skills ────────────────────────────────
STOP_WORDS = {
    # Generic filler
    "the", "and", "for", "with", "that", "this", "from", "are", "will",
    "have", "has", "been", "can", "able", "your", "our", "their", "its",
    "you", "we", "they", "who", "what", "how", "when", "where", "which",
    "all", "any", "each", "more", "most", "some", "such", "than", "then",
    "also", "both", "but", "not", "only", "other", "same", "very", "just",
    # CV structural headers
    "cv", "resume", "summary", "profile", "contact", "address", "email", "phone",
    "responsible", "responsibilities", "activities", "activity",
    # AIESEC specific filler
    "aiesec", "exchange", "participant", "lc", "mc", "gv", "gt", "gte", "gta",
    "outgoing", "incoming", "raising", "awareness", "opportunity", "product",
    "global", "volunteer", "talent", "openings",
    # Locations
    "tunisia", "tunisie", "tunis", "ariana", "sfax", "sousse", "monastir",
    "remote", "country", "countries", "city", "region", "location",
    "africa", "europe", "asia", "america", "greece", "india", "egypt", "brazil",
    "turkey", "ukraine", "romania", "vietnam", "colombia", "mexico", "kenya",
    "nigeria", "ghana", "rwanda", "bolivia", "nepal", "sri", "lanka", "ecuador",
    "cairo", "alexandria", "mansoura", "menofia", "helwan", "tanta", "zagazig",
    "giza", "october", "miu", "must", "guc", "esprit",
    # Filler descriptors
    "good", "strong", "excellent", "great", "high", "new", "various",
    "different", "specific", "important", "based", "including", "providing",
    "support", "supporting", "ensure", "environment", "impact", "strategy",
    "local", "results", "working", "developing", "building", "creating",
    "understanding", "learning", "people", "tasks", "tools", "time",
    "weeks", "months", "level", "area", "areas", "needs", "goals", "goal",
    "open", "offer", "part", "department", "company", "center", "centre",
    # Numbers and non-skills
    "4", "6", "8", "10", "12", "up", "scale", "skill", "discover",
}

# ─── Fake opp skill tokens to filter out ─────────────────────────────────────
# These appear in auto-generated skill lists from titles but add no signal
OPP_SKILL_NOISE = {
    "talent", "opportunity", "weeks", "months", "global", "volunteer",
    "outgoing", "openings", "product", "aiesec", "lc", "mc",
    "4", "6", "8", "up", "scale", "skill", "discover", "map",
}

# ─── Generic single-word tokens too broad for reliable skill matching ─────────
# These tokens (bare "developer", "design", etc.) appear in many unrelated
# opportunity titles and cause false-positive matches.
# They are stripped from CV skills BEFORE matching, but compound phrases
# containing them (e.g. "web developer", "graphic design") remain valid.
GENERIC_TOKENS = {
    "developer", "designer", "design", "engineer", "engineering",
    "business", "development", "management", "specialist", "analyst",
    "manager", "coordinator", "assistant", "representative", "intern",
    "trainer", "director", "officer", "consultant", "executive",
    "graphic", "visual", "creative", "programmer", "programmer",
    "etap", "freelance",   # CV-specific noise from role tokenization
}

# ─── Domain synergies: CV practical skills → job role keywords ────────────────
TECH_DOMAINS = [
    {
        "triggers": {"php", "javascript", "html", "css", "react", "vue", "node",
                     "angular", "symfony", "laravel", "mvc", "django", "flask", "web"},
        "skills": ["web developer", "web development", "full stack",
                   "fullstack", "frontend", "backend"]
        # NOTE: do NOT add bare "developer" — it would match "C# Developer", "Business Developer" etc.
    },
    {
        "triggers": {"python", "java", "c++", "c#", "software development",
                     "software engineering", "algorithms", "data structures"},
        "skills": ["software developer", "software development", "software engineer"]
        # NOTE: no bare "developer" or "engineer"
    },
    {
        "triggers": {"photoshop", "illustrator", "figma", "ui ux",
                     "graphic design", "premiere pro", "canva", "after effects",
                     "motion graphics", "adobe photoshop", "video editing"},
        "skills": ["graphic design", "graphic designer", "ui ux",
                   "motion graphics", "video editing", "content creator", "visual"]
        # NOTE: no bare "design" or "designer" — would match "electrical design", "sound designer" etc.
    },
    {
        "triggers": {"sales", "customer service", "seller", "negotiation", "crm"},
        "skills": ["sales representative", "customer service", "sales"]
        # NOTE: no bare "business" — would match "business developer", "business development"
    },
    {
        "triggers": {"marketing", "social media marketing", "seo", "content creation",
                     "digital marketing", "advertising", "branding", "email marketing"},
        "skills": ["digital marketing", "marketing specialist",
                   "content creator", "social media marketing", "media buying"]
    },
    {
        "triggers": {"teaching", "teacher", "tutoring", "lesson planning",
                     "classroom management", "pedagogy", "education", "curriculum"},
        "skills": ["teaching", "teacher", "educator", "tutor", "instructor",
                   "education", "curriculum", "trainer"]
    },
    {
        "triggers": {"arduino", "stm32", "esp32", "embedded systems", "iot",
                     "electronics", "robotics", "pic", "mikroc"},
        "skills": ["embedded systems", "iot", "hardware", "electronics", "robotics"]
    },
    {
        "triggers": {"finance", "accounting", "financial analysis", "budgeting"},
        "skills": ["finance specialist", "financial analyst", "accounting"]
    },
    {
        "triggers": {"human resources", "hr", "recruitment", "talent acquisition",
                     "onboarding", "performance management"},
        "skills": ["hr specialist", "human resources", "recruitment"]
    },
    {
        "triggers": {"data analysis", "data science", "machine learning",
                     "artificial intelligence", "statistics", "tableau", "power bi"},
        "skills": ["data analysis", "data science", "data analyst", "machine learning"]
    },
]

# ─── GV programme theme keywords → CV signal words ───────────────────────────
# Maps GV opportunity title keywords to what kind of CV they suit
GV_THEME_MAP = {
    "skill up":       {"skills", "software", "developer", "design", "business", "marketing", "tech",
                       "engineering", "communication", "leadership", "professional"},
    "scale up":       {"business", "entrepreneur", "management", "leadership", "economics",
                       "finance", "sales", "startup", "innovation"},
    "discover":       {"culture", "language", "communication", "social", "education",
                       "teaching", "international", "exchange"},
    "aquatica":       {"environment", "nature", "biology", "ecology", "science",
                       "sustainability", "conservation", "marine"},
    "on the map":     {"geography", "environment", "social", "community", "culture",
                       "sustainability", "education"},
    "code":           {"programming", "software", "developer", "coding", "tech", "computer"},
    "teach":          {"teaching", "education", "language", "english", "communication",
                       "curriculum", "children", "school"},
    "sports":         {"sports", "fitness", "coaching", "health", "physical"},
    "health":         {"health", "medicine", "biology", "nursing", "pharmacy", "public health"},
    "environment":    {"environment", "sustainability", "ecology", "science", "biology"},
    "art":            {"art", "design", "creative", "painting", "photography", "music"},
    "business":       {"business", "management", "finance", "marketing", "entrepreneurship"},
    "community":      {"social", "community", "volunteer", "ngo", "development", "people"},
    "innovation":     {"engineering", "tech", "startup", "entrepreneur", "innovation", "design"},
}


def normalize(value: str) -> str:
    v = re.sub(r"\s+", " ", str(value).lower().strip())
    return ALIASES.get(v, v)


def tokenize(text: str) -> set:
    """Extract meaningful word tokens from text, excluding stop words."""
    text = text.lower()
    tokens = set()
    for tok in re.findall(r"[a-zA-Z][a-zA-Z+#.]{1,}", text):
        n = normalize(tok)
        if n and n not in STOP_WORDS and len(n) > 2:
            tokens.add(n)
    return tokens


def skill_overlap(cv_skills: set, opp_skills: list) -> list:
    """Return the list of opportunity skills that match CV skills (exact or smart substring).
    cv_skills must already be cleaned of GENERIC_TOKENS before calling this."""
    matched = []
    for raw_skill in opp_skills:
        if not raw_skill:
            continue
        ns = normalize(str(raw_skill))
        if ns in STOP_WORDS or ns in OPP_SKILL_NOISE or ns in GENERIC_TOKENS or len(ns) < 3:
            continue

        # Exact match
        if ns in cv_skills:
            matched.append(raw_skill)
            continue

        # Compound-phrase substring match only:
        # e.g. opp="web developer" ↔ cv="web developer" (exact compound)
        # e.g. opp="web developer" ↔ cv="full stack web developer" (compound ⊂ compound)
        # NOT: opp="c# developer" ↔ cv skips because "developer" alone is GENERIC
        for cs in cv_skills:
            if len(ns) < 5 or len(cs) < 5:
                continue
            if " " in ns and " " in cs and (ns in cs or cs in ns):
                matched.append(raw_skill)
                break
    return sorted(set(matched))


def expand_skills_with_domains(skills_set: set) -> set:
    """Expand CV skills using domain synergies. Only adds compound phrases, not bare generics."""
    expanded = set(skills_set)
    for dom in TECH_DOMAINS:
        if skills_set & dom["triggers"]:
            for s in dom["skills"]:
                expanded.add(normalize(s))
    return expanded


# Generic single-word tokens that are too broad to use for matching
# (they appear in many unrelated opportunity titles and cause false positives)
GENERIC_TOKENS = {
    "developer", "designer", "design", "engineer", "business", "development",
    "management", "specialist", "analyst", "manager", "coordinator", "assistant",
    "representative", "intern", "trainer", "director", "officer", "consultant",
}


def gv_theme_score(opp_title: str, all_cv_terms: set) -> float:
    """
    Score a Global Volunteer opportunity based on its theme vs the CV.
    Returns 0–25 points.
    """
    title_lower = opp_title.lower()
    best = 0.0
    for theme, related_words in GV_THEME_MAP.items():
        if theme in title_lower:
            hits = all_cv_terms & related_words
            score = min(25.0, len(hits) * 6.0)
            if score > best:
                best = score
    # Fallback: "Skill Up!" suits everyone with any professional skill
    if best == 0 and ("skill" in title_lower or "scale" in title_lower):
        if len(all_cv_terms) > 5:
            best = 15.0
    return best


def match(cv_data: dict, opportunities: list) -> list:
    # ── 1. Parse Skills ────────────────────────────────────────────────────────
    cv_skills_raw = [s for s in cv_data.get("skills", []) if s]
    cv_skills = {normalize(s) for s in cv_skills_raw} - STOP_WORDS

    # Expand skills with domain synergies
    cv_skills = expand_skills_with_domains(cv_skills)

    # ── 2. Parse Languages ────────────────────────────────────────────────────
    cv_lang_raw = cv_data.get("languages", [])
    cv_languages = {normalize(l) for l in cv_lang_raw} - STOP_WORDS
    if not cv_languages:
        cv_languages = {"english"}

    # ── 3. Parse Experience ───────────────────────────────────────────────────
    exp_data = cv_data.get("experience", {})
    if isinstance(exp_data, dict):
        cv_exp_roles = exp_data.get("roles", [])
        cv_exp_descs = exp_data.get("descriptions", [])
        cv_exp_text = exp_data.get("text", "") or " ".join(cv_exp_roles + cv_exp_descs)
    elif isinstance(exp_data, list):
        cv_exp_roles = exp_data
        cv_exp_descs = []
        cv_exp_text = " ".join(cv_exp_roles)
    else:
        cv_exp_roles = []
        cv_exp_descs = []
        cv_exp_text = str(exp_data)

    cv_exp_tokens = tokenize(cv_exp_text)

    # Feed experience roles into the skills pool
    for r in cv_exp_roles:
        r_clean = re.sub(r"^(?:intern\s*[-–]\s*|freelance\s*|junior\s*|senior\s*)", "", r, flags=re.IGNORECASE)
        for tok in tokenize(r_clean):
            cv_skills.add(tok)
    # Re-expand after adding experience tokens
    cv_skills = expand_skills_with_domains(cv_skills)

    # ── 4. Parse Job Titles ────────────────────────────────────────────────────
    cv_job_titles = cv_data.get("job_titles", [])
    if not cv_job_titles and cv_exp_roles:
        cv_job_titles = [re.split(r"[-–—@|(]", r)[0].strip() for r in cv_exp_roles if r]

    for jt in cv_job_titles:
        for tok in tokenize(jt):
            cv_skills.add(tok)

    # ── 5. Parse Projects ─────────────────────────────────────────────────────
    cv_projects = cv_data.get("projects", [])
    cv_proj_text = " ".join(cv_projects) if isinstance(cv_projects, list) else str(cv_projects)
    cv_proj_tokens = tokenize(cv_proj_text)

    # ── 6. Parse Education ────────────────────────────────────────────────────
    cv_education = cv_data.get("education", [])
    cv_edu_text = " ".join(cv_education) if isinstance(cv_education, list) else str(cv_education)
    cv_edu_tokens = tokenize(cv_edu_text)

    # ── Strip GENERIC_TOKENS from cv_skills — prevents false-positive matches ──
    # e.g. bare "developer" from role tokenization must NOT match "C# Developer"
    # Compound phrases like "web developer", "graphic designer" are safe and stay.
    cv_skills -= GENERIC_TOKENS

    # ── All CV terms combined (for GV theme scoring & general overlap) ─────────
    all_cv_terms = (
        cv_skills | cv_languages | cv_exp_tokens
        | cv_proj_tokens | cv_edu_tokens
        | tokenize(cv_data.get("summary", ""))
    )

    ranked = []

    for opp in opportunities:
        opp_title = (opp.get("title", "") or "").strip()
        opp_desc  = (opp.get("description", "") or "").strip()
        opp_cat   = (opp.get("category", "") or "").strip()

        opp_skills_raw = opp.get("skills", [])
        if isinstance(opp_skills_raw, str):
            try:
                opp_skills_raw = json.loads(opp_skills_raw)
            except Exception:
                opp_skills_raw = [s.strip() for s in opp_skills_raw.split(",") if s.strip()]

        opp_lang_raw = opp.get("languages", [])
        if isinstance(opp_lang_raw, str):
            try:
                opp_lang_raw = json.loads(opp_lang_raw)
            except Exception:
                opp_lang_raw = [l.strip() for l in opp_lang_raw.split(",") if l.strip()]

        opp_bg_raw = opp.get("backgrounds", [])

        opp_full_text = f"{opp_title} {opp_desc}"
        opp_tokens = tokenize(opp_full_text)
        opp_title_tokens = tokenize(opp_title)

        # Filter noise from opp skills
        clean_opp_skills = [
            s for s in opp_skills_raw
            if normalize(str(s)) not in OPP_SKILL_NOISE
            and normalize(str(s)) not in STOP_WORDS
            and len(normalize(str(s))) > 2
        ]

        # ── Dimension A: Skills Match (0–35 pts) ──────────────────────────────
        matched_skills = skill_overlap(cv_skills, clean_opp_skills)

        if clean_opp_skills:
            # How many of the opp's required skills does the CV cover?
            coverage = len(matched_skills) / len(clean_opp_skills)
            skill_score = coverage * 35
        else:
            # No structured skills: fall back to token overlap with title+desc
            # Exclude generic tokens from this fallback to prevent false positives
            skill_hits = (cv_skills - GENERIC_TOKENS) & (opp_tokens - GENERIC_TOKENS)
            skill_score = min(30, len(skill_hits) * 7)

        # ── Dimension B: Work Experience Match (0–25 pts) ─────────────────────
        matched_experience_items = []
        exp_score = 0.0
        # Use a cleaned version of exp_tokens (no generic words like "developer", "designer")
        cv_exp_tokens_clean = cv_exp_tokens - GENERIC_TOKENS
        opp_tokens_clean = opp_tokens - GENERIC_TOKENS
        if cv_exp_roles:
            for role in cv_exp_roles:
                role_toks = tokenize(role) - GENERIC_TOKENS
                # Match if role shares NON-GENERIC tokens with opp title or description
                if role_toks and role_toks & (opp_title_tokens - GENERIC_TOKENS | opp_tokens_clean):
                    matched_experience_items.append(role)

            exp_keyword_hits = cv_exp_tokens_clean & opp_tokens_clean
            if matched_experience_items:
                exp_score = min(25.0, 12.0 + len(exp_keyword_hits) * 3.0)
            else:
                exp_score = min(18.0, len(exp_keyword_hits) * 3.0)

        # ── Dimension C: Job Title Fit (0–20 pts) ─────────────────────────────
        matched_job_roles = []
        job_score = 0.0
        for title in cv_job_titles:
            title_toks = tokenize(title) - GENERIC_TOKENS
            if not title_toks:
                continue
            if title_toks & (opp_title_tokens - GENERIC_TOKENS):
                matched_job_roles.append(title)
            elif title_toks & opp_tokens_clean:
                matched_job_roles.append(title)

        if matched_job_roles:
            # Strong match = job title NON-GENERIC tokens appear in opp title
            strong = any((tokenize(t) - GENERIC_TOKENS) & (opp_title_tokens - GENERIC_TOKENS)
                         for t in matched_job_roles)
            job_score = 20.0 if strong else 10.0

        # ── Dimension D: Project Portfolio (0–12 pts) ──────────────────────────
        matched_projects = []
        proj_score = 0.0
        if cv_projects:
            for p in (cv_projects if isinstance(cv_projects, list) else [cv_proj_text]):
                p_toks = tokenize(str(p)) - GENERIC_TOKENS
                if p_toks and p_toks & opp_tokens_clean:
                    matched_projects.append(p)
            proj_hits = (cv_proj_tokens - GENERIC_TOKENS) & opp_tokens_clean
            if matched_projects:
                proj_score = min(12.0, 6.0 + len(proj_hits) * 2.0)
            else:
                proj_score = min(8.0, len(proj_hits) * 2.0)

        # ── Dimension E: Languages (0–8 pts) ──────────────────────────────────
        matched_languages = []
        lang_score = 0.0
        if opp_lang_raw:
            matched_languages = skill_overlap(cv_languages, opp_lang_raw)
            lang_score = (len(matched_languages) / max(1, len(opp_lang_raw))) * 8.0
        else:
            # No language requirement listed — give partial credit if has english
            matched_languages = list(cv_languages)[:2]
            lang_score = 6.0 if "english" in cv_languages else 3.0

        # ── Dimension F: Education / Background Bonus (0–5 pts) ───────────────
        edu_score = 0.0
        if opp_bg_raw:
            opp_bg_tokens = tokenize(" ".join(str(b) for b in opp_bg_raw))
            if opp_bg_tokens & (cv_edu_tokens | cv_exp_tokens | cv_skills):
                edu_score = 5.0
        else:
            edu_keywords = ["engineering", "business", "computer", "developer",
                            "education", "teacher", "science", "design", "finance",
                            "marketing", "management", "health", "law", "it"]
            if any(kw in opp_full_text.lower() for kw in edu_keywords):
                edu_score = 3.0

        # ── Dimension G: GV Theme Match (0–25 pts, only for GV) ───────────────
        # GV opportunities have garbage skills — score them via theme keyword match
        gv_score = 0.0
        if opp_cat == "GV":
            gv_score = gv_theme_score(opp_title, all_cv_terms)
            # For GV: reduce weight of skills dimension (they never have real skills)
            # and boost the GV theme score
            skill_score = min(skill_score, 10.0)  # cap, since GV skills are fake
        else:
            gv_score = 0.0

        # ── Total ──────────────────────────────────────────────────────────────
        total = skill_score + exp_score + job_score + proj_score + lang_score + edu_score + gv_score
        # Max theoretical non-GV: 35+25+20+12+8+5 = 105
        # Max theoretical GV:     10+25+20+12+8+5+25 = 105 (same)
        # Normalize to 0–99 — divide by 105 and cap
        final_score = round(min(99.0, max(15.0, total * 99.0 / 105.0)), 1)

        # Build human-readable reasons
        reasons = []
        if matched_skills:
            reasons.append(f"Compétences clés : {', '.join(matched_skills[:4])}")
        if matched_experience_items:
            reasons.append(f"Expérience validée : {', '.join(matched_experience_items[:2])}")
        if matched_job_roles:
            reasons.append(f"Poste en adéquation : {', '.join(matched_job_roles[:2])}")
        if matched_projects:
            reasons.append(f"Projets applicables : {', '.join(matched_projects[:2])}")
        if matched_languages:
            reasons.append(f"Langue confirmée : {', '.join(matched_languages[:2]).title()}")
        if gv_score > 0:
            reasons.append(f"Programme adapté à votre profil ({opp_title})")

        general_keywords = sorted(all_cv_terms & opp_tokens)

        ranked.append({
            **opp,
            "score": final_score,
            "matched_skills": matched_skills,
            "matched_experience": matched_experience_items,
            "matched_job_roles": matched_job_roles,
            "matched_projects": matched_projects,
            "matched_languages": matched_languages,
            "matched_keywords": general_keywords[:8],
            "reasons": reasons,
        })

    # ── Sort by score descending ───────────────────────────────────────────────
    ranked.sort(key=lambda x: (-x["score"], x.get("title", "")))

    # ── Diversification: prevent identical titles from monopolizing Page 1 ─────
    # Max 2 of the same title in the primary stream
    title_counts: dict[str, int] = {}
    primary = []
    deferred = []

    for opp in ranked:
        title_norm = re.sub(r"\s*[\[(].*?[\])]", "", (opp.get("title", "") or "")).lower().strip()
        cnt = title_counts.get(title_norm, 0)
        if cnt < 2:
            primary.append(opp)
            title_counts[title_norm] = cnt + 1
        else:
            deferred.append(opp)

    return primary + deferred


def load_input(argument: str):
    path = Path(argument)
    if path.exists():
        return json.loads(path.read_text(encoding="utf-8"))
    return json.loads(argument)


def main():
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Usage: matcher.py <cv_json> <opportunities_json>"}))
        return
    cv_data = load_input(sys.argv[1])
    opportunities = load_input(sys.argv[2])
    print(json.dumps(match(cv_data, opportunities), ensure_ascii=False))


if __name__ == "__main__":
    main()
