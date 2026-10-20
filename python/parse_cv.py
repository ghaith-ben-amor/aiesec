import json
import os
import re
import sys
from pathlib import Path

try:
    from dotenv import load_dotenv
    env_path = Path(__file__).parent.parent / '.env'
    if env_path.exists():
        load_dotenv(env_path)
except Exception:
    pass

try:
    import fitz  # PyMuPDF
except Exception:
    fitz = None

try:
    import pdfplumber
except Exception:
    pdfplumber = None

try:
    import requests
except Exception:
    requests = None

SKILL_KEYWORDS = [
    "marketing", "sales", "leadership", "communication", "teamwork", "project management",
    "python", "php", "javascript", "html", "css", "sql", "data analysis", "research",
    "content creation", "design", "public speaking", "problem solving", "excel", "ai", "nlp",
    "java", "c", "c++", "c#", "flutter", "flutterflow", "mvc", "qt", "arduino", "stm32", "photoshop", "illustrator",
    "machine learning", "deep learning", "web development", "mobile development", "software development",
    "ui/ux", "ui ux", "user interface", "user experience", "figma", "canva", "bootstrap", "tailwind",
    "react", "vue", "angular", "node", "node.js", "django", "flask", "laravel", "symfony",
    "git", "github", "linux", "word", "powerpoint", "microsoft office", "power bi", "tableau",
    "autocad", "matlab", "kotlin", "swift", "typescript", "seo", "social media", "premiere pro",
    "presentation", "copywriting", "translation", "customer service", "problem-solving", "critical thinking",
    "embedded systems", "iot", "electronics", "robotics", "networking", "mysql", "postgresql", "mongodb"
]

LANGUAGE_KEYWORDS = ["english", "french", "spanish", "german", "arabic", "portuguese", "italian", "hindi", "chinese", "turkish"]
EDUCATION_KEYWORDS = [
    "bachelor", "master", "mba", "phd", "bsc", "msc", "license", "licence",
    "university", "college", "school", "institute", "degree", "diploma",
    "baccalaureate", "baccalauréat", "preparatory", "engineering", "esprit", "insat", "enit"
]

SECTION_PATTERNS = {
    "summary": r"(?:professional\s+summary|summary|profile|about\s+me|objective)",
    "education": r"(?:education|academic\s+background|studies|formation)",
    "experience": r"(?:professional\s+experience|work\s+experience|experience|employment(?:\s+history)?)",
    "projects": r"(?:academic\s*(?:&|and)\s*technical\s+projects|academic\s+projects|technical\s+projects|projects|key\s+projects|personal\s+projects)",
    "community": r"(?:community\s+involvement|volunteer(?:ing)?|extracurricular\s+activities|activities)",
    "skills": r"(?:technical\s+skills|core\s+skills|key\s+skills|skills|technologies|competencies)",
    "languages": r"(?:languages|spoken\s+languages)",
    "certifications": r"(?:certifications?|licenses|certificates)",
}

ROLE_KEYWORDS = [
    "seller", "sales", "developer", "engineer", "designer", "intern", "manager",
    "lead", "leader", "freelance", "specialist", "consultant", "analyst", "assistant",
    "teacher", "instructor", "officer", "coordinator", "volunteer", "technician", "architect",
    "representative", "associate"
]

def debug_log(msg):
    if os.getenv("APP_DEBUG") or os.getenv("PY_DEBUG"):
        print(f"[DEBUG] {msg}", file=sys.stderr)


def extract_text(pdf_path: str) -> str:
    debug_log(f"extract_text: {pdf_path}")
    
    if fitz is not None:
        try:
            document = fitz.open(pdf_path)
            text = []
            for page in document:
                text.append(page.get_text())
            combined = "\n".join(text).strip()
            if combined:
                return combined
        except Exception as e:
            debug_log(f"PyMuPDF error: {e}")

    if pdfplumber is not None:
        try:
            lines = []
            with pdfplumber.open(pdf_path) as pdf:
                for page in pdf.pages:
                    extracted = page.extract_text() or ""
                    if extracted:
                        lines.append(extracted)
            result = "\n".join(lines).strip()
            if result:
                return result
        except Exception as e:
            debug_log(f"pdfplumber error: {e}")

    return ""


def keyword_hits(text: str, keywords):
    lowered = text.lower()
    hits = []
    for keyword in sorted(keywords, key=len, reverse=True):
        pattern = re.escape(keyword.lower())
        if re.search(rf"(?<![a-z0-9]){pattern}(?![a-z0-9])", lowered):
            hits.append(keyword)
    return hits


def normalize_skill_text(value: str) -> str:
    clean = " ".join(str(value).replace("•", " ").replace("·", " ").split())
    clean = clean.strip(" -–—:;,.|/\\")
    lowered = clean.lower()

    alias = {
        "js": "javascript",
        "nodejs": "javascript",
        "node.js": "javascript",
        "mysql": "mysql",
        "postgresql": "postgresql",
        "postgres": "postgresql",
        "database": "sql",
        "ui": "ui ux",
        "ux": "ui ux",
        "ui/ux": "ui ux",
        "ui/ux design": "ui ux",
        "ux design": "ui ux",
        "ui design": "ui ux",
        "user interface": "ui ux",
        "user experience": "ui ux",
        "problem-solving": "problem solving",
        "microsoft office": "microsoft office",
        "premiere": "premiere pro",
        "adobe premiere": "premiere pro",
    }
    return alias.get(lowered, lowered)


def dedupe_keep_order(items):
    seen = set()
    out = []
    for item in items:
        clean = " ".join(str(item).strip().split())
        if not clean:
            continue
        key = clean.lower()
        if key in seen:
            continue
        seen.add(key)
        out.append(clean)
    return out


def parse_cv_sections(raw_text: str) -> dict:
    all_headers_pattern = r"(?im)^[ \t]*(?:[•·▪●-]\s*)?(" + "|".join(SECTION_PATTERNS.values()) + r")[ \t]*:?[ \t]*$"
    splits = list(re.finditer(all_headers_pattern, raw_text))

    sections = {}
    for i, m in enumerate(splits):
        hdr = m.group(0).strip().lower()
        start = m.end()
        end = splits[i+1].start() if i+1 < len(splits) else len(raw_text)
        for key, pat in SECTION_PATTERNS.items():
            if re.search(pat, hdr, re.IGNORECASE):
                sections[key] = raw_text[start:end].strip()
                break
    return sections


def extract_skills_robust(raw_text: str, sections: dict) -> list:
    hits = keyword_hits(raw_text, SKILL_KEYWORDS)
    skills_sec = sections.get("skills", "")
    
    candidates = []
    if skills_sec:
        for line in skills_sec.splitlines():
            line_clean = re.sub(r"^[A-Za-z0-9\s/&–—\-]+:\s*", "", line).strip()
            for token in re.split(r"[,;|•·]+", line_clean):
                tok = normalize_skill_text(token)
                if tok and len(tok) > 1 and len(tok) < 40:
                    candidates.append(tok)

    merged = hits + candidates
    filtered = []
    for s in merged:
        s_norm = normalize_skill_text(s)
        if s_norm and not any(stop in s_norm for stop in ["software development", "embedded systems", "adobe & creative tools"]):
            filtered.append(s_norm)
    return dedupe_keep_order(filtered)[:40]


def extract_languages_robust(raw_text: str, sections: dict) -> list:
    langs = keyword_hits(raw_text, LANGUAGE_KEYWORDS)
    lang_sec = sections.get("languages", "")
    if lang_sec:
        for tok in re.split(r"[,;|•·\s]+", lang_sec.lower()):
            t = tok.strip()
            if t in LANGUAGE_KEYWORDS:
                langs.append(t)
    return dedupe_keep_order(langs)[:8]


def extract_experience_robust(raw_text: str, sections: dict) -> dict:
    exp_blob = sections.get("experience", "")
    years = sorted(set(re.findall(r"(\d+)\+?\s+years?", raw_text.lower())))
    
    exp_roles = []
    exp_descriptions = []
    current_role = None
    current_desc = []

    lines = exp_blob.splitlines() if exp_blob else raw_text.splitlines()
    for line in lines:
        clean = line.strip()
        if not clean:
            continue
        lowered = clean.lower()

        is_role_header = (
            (any(re.search(rf"\b{re.escape(k)}\b", lowered) for k in ROLE_KEYWORDS) or " – " in clean or " - " in clean or " at " in clean)
            and not clean.startswith("•") and not clean.startswith("-") and not clean.startswith("·")
            and not re.search(r"^(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|\d{4})\b", lowered)
            and len(clean) < 80
        )

        if is_role_header:
            if current_role:
                exp_roles.append(current_role)
                if current_desc:
                    exp_descriptions.append(" ".join(current_desc))
                    current_desc = []
            current_role = clean
        elif clean.startswith("•") or clean.startswith("-") or clean.startswith("·") or any(v in lowered for v in ["developed", "managed", "assisted", "created", "designed", "implemented"]):
            desc_line = re.sub(r"^[•·▪●-]\s*", "", clean)
            current_desc.append(desc_line)

    if current_role:
        exp_roles.append(current_role)
    if current_desc:
        exp_descriptions.append(" ".join(current_desc))

    for match in re.finditer(r"\b(20\d{2})\s*(?:–|-|to)\s*(present|20\d{2})\b", exp_blob, re.IGNORECASE):
        years.append(match.group(0))

    return {
        "years": dedupe_keep_order(years),
        "roles": dedupe_keep_order(exp_roles)[:8],
        "descriptions": dedupe_keep_order(exp_descriptions)[:8],
        "text": exp_blob[:1200],
    }


def extract_projects_robust(raw_text: str, sections: dict) -> list:
    proj_blob = sections.get("projects", "")
    projects = []
    
    if proj_blob:
        for line in proj_blob.splitlines():
            clean = line.strip()
            if not clean:
                continue
            if (not clean.startswith("•") and not clean.startswith("-") and not clean.startswith("·") 
                and len(clean) < 70 and not clean.lower().startswith("technologies") and not clean.lower().startswith("tools")):
                projects.append(clean)
    else:
        for line in raw_text.splitlines():
            clean = line.strip()
            if re.match(r"^[A-Z][A-Za-z0-9\s\-]{3,40}(?:App|Platform|System|Tool|Website|Bot|Game)$", clean):
                projects.append(clean)

    return dedupe_keep_order(projects)[:12]


def extract_education_robust(raw_text: str, sections: dict) -> list:
    edu_blob = sections.get("education", "")
    education = []
    
    lines = edu_blob.splitlines() if edu_blob else raw_text.splitlines()
    for line in lines:
        clean = line.strip()
        lowered = clean.lower()
        if not clean or len(clean) < 4:
            continue
        if re.match(r"^\d{4}\s*(?:–|-)\s*(?:present|\d{4})$", lowered) or re.match(r"^\d{4}$", clean):
            continue
        if any(keyword in lowered for keyword in EDUCATION_KEYWORDS) or "esprit" in lowered or "cycle" in lowered:
            education.append(clean)

    return dedupe_keep_order(education)[:8]


def extract_job_titles(exp_roles: list, summary: str, raw_text: str) -> list:
    titles = []
    for r in exp_roles:
        cleaned = re.sub(r"^(?:intern\s*[-–]\s*|freelance\s*)", "", r, flags=re.IGNORECASE)
        cleaned = re.split(r"[-–—@|(]", cleaned)[0].strip()
        if cleaned and len(cleaned) < 40:
            titles.append(cleaned)

    domain_map = [
        ("web developer", "Web Developer"),
        ("software developer", "Software Developer"),
        ("software engineer", "Software Engineer"),
        ("graphic designer", "Graphic Designer"),
        ("ui/ux designer", "UI/UX Designer"),
        ("embedded systems", "Embedded Systems Engineer"),
        ("iot", "IoT Developer"),
        ("sales", "Sales Associate"),
        ("customer service", "Customer Service Specialist"),
        ("marketing", "Marketing Specialist"),
        ("teacher", "Teacher"),
    ]
    summary_lower = (summary + " " + raw_text[:800]).lower()
    for kw, title in domain_map:
        if kw in summary_lower:
            titles.append(title)

    return dedupe_keep_order(titles)[:8]


def build_summary(skills, languages, education, experience, projects):
    parts = []
    if skills:
        parts.append(f"{len(skills)} skills detected")
    if languages:
        parts.append(f"{len(languages)} languages ({', '.join(languages)})")
    if education:
        parts.append(f"{len(education)} education entries")
    if experience.get("roles"):
        parts.append(f"{len(experience.get('roles', []))} experience roles")
    if projects:
        parts.append(f"{len(projects)} technical projects")

    if not parts:
        return "No structured profile data could be extracted from the PDF text."
    return "Comprehensive CV Profile: " + ", ".join(parts) + "."


def groq_extract_profile(raw_text: str):
    api_key = os.getenv("GROQ_API_KEY", "").strip()
    if not api_key or requests is None:
        return None

    try:
        response = requests.post(
            "https://api.groq.com/openai/v1/chat/completions",
            headers={
                "Authorization": f"Bearer {api_key}",
                "Content-Type": "application/json",
            },
            json={
                "model": os.getenv("GROQ_MODEL", "llama-3.3-70b-versatile"),
                "messages": [
                    {
                        "role": "system",
                        "content": (
                            "You are an expert CV parser. Extract all detailed candidate information from the CV text and return ONLY valid JSON.\n"
                            "Return format:\n"
                            "{\n"
                            '  "skills": ["..."],\n'
                            '  "languages": ["..."],\n'
                            '  "education": ["..."],\n'
                            '  "experience": {\n'
                            '    "years": ["..."],\n'
                            '    "roles": ["..."],\n'
                            '    "descriptions": ["..."],\n'
                            '    "text": "..."\n'
                            "  },\n"
                            '  "projects": ["..."],\n'
                            '  "job_titles": ["..."],\n'
                            '  "summary": "...",\n'
                            '  "suggestions": ["..."]\n'
                            "}"
                        ),
                    },
                    {
                        "role": "user",
                        "content": f"Extract complete CV profile from this text:\n\n{raw_text[:16000]}",
                    },
                ],
                "temperature": 0.2,
                "max_tokens": 2000,
            },
            timeout=30,
        )
        response.raise_for_status()
        data = response.json()
        content = data["choices"][0]["message"]["content"]
        match = re.search(r"\{.*\}", content, re.DOTALL)
        if match:
            parsed = json.loads(match.group(0))
            if parsed and isinstance(parsed, dict) and len(parsed.get("skills", [])) > 0:
                return parsed
    except Exception as e:
        debug_log(f"Groq API error: {e}")
    return None


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "PDF path required"}))
        return

    pdf_path = Path(sys.argv[1])
    raw_text = extract_text(str(pdf_path))

    if len(raw_text.strip()) >= 100:
        groq_profile = groq_extract_profile(raw_text)
        if groq_profile:
            groq_profile["raw_text"] = raw_text
            print(json.dumps(groq_profile, ensure_ascii=False))
            return

    sections = parse_cv_sections(raw_text)
    skills = extract_skills_robust(raw_text, sections)
    languages = extract_languages_robust(raw_text, sections)
    education = extract_education_robust(raw_text, sections)
    experience = extract_experience_robust(raw_text, sections)
    projects = extract_projects_robust(raw_text, sections)
    summary_text = sections.get("summary", "") or raw_text[:400]
    job_titles = extract_job_titles(experience.get("roles", []), summary_text, raw_text)

    profile = {
        "raw_text": raw_text,
        "skills": skills,
        "languages": languages,
        "education": education,
        "experience": experience,
        "projects": projects,
        "job_titles": job_titles,
        "summary": build_summary(skills, languages, education, experience, projects),
        "suggestions": [
            "Add measurable impact and metric-driven achievements to your roles.",
            "Include direct live links or GitHub repos for your technical projects.",
        ],
    }

    print(json.dumps(profile, ensure_ascii=False))


if __name__ == "__main__":
    main()
