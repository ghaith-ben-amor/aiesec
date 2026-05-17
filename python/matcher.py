import json
from pathlib import Path
import re
import sys


try:
    sys.stdout.reconfigure(encoding="utf-8")
except Exception:
    pass


ALIASES = {
    "js": "javascript",
    "nodejs": "javascript",
    "node.js": "javascript",
    "mysql": "sql",
    "postgresql": "sql",
    "postgres": "sql",
    "database": "sql",
    "ui": "design",
    "ux": "design",
    "figma": "design",
    "content": "content creation",
    "writing": "content creation",
    "communicat": "communication",
    "collaboration": "teamwork",
    "team work": "teamwork",
}


def normalize(value):
    value = re.sub(r"\s+", " ", str(value).lower().strip())
    return ALIASES.get(value, value)


def text_tokens(*values):
    text = " ".join(str(value or "") for value in values).lower()
    return {normalize(token) for token in re.findall(r"[a-zA-Z][a-zA-Z+#.]{1,}", text)}


def overlaps(required, available):
    matched = []
    for skill in required:
        if not skill:
            continue
        if skill in available:
            matched.append(skill)
            continue
        for item in available:
            if len(skill) > 2 and len(item) > 2 and (skill in item or item in skill):
                matched.append(skill)
                break
    return sorted(set(matched))


def match(cv_data, opportunities):
    cv_skills = {normalize(skill) for skill in cv_data.get("skills", [])}
    cv_languages = {normalize(lang) for lang in cv_data.get("languages", [])}
    cv_text = text_tokens(
        cv_data.get("summary", ""),
        " ".join(cv_data.get("education", [])),
        " ".join(cv_data.get("experience", {}).get("roles", [])) if isinstance(cv_data.get("experience"), dict) else "",
    )
    candidate_terms = cv_skills | cv_languages | cv_text
    ranked = []
    
    for opportunity in opportunities:
        required_skills = [normalize(skill) for skill in opportunity.get("skills", [])]
        required_languages = [normalize(lang) for lang in opportunity.get("languages", [])]
        opportunity_terms = text_tokens(
            opportunity.get("title", ""),
            opportunity.get("description", ""),
            opportunity.get("category", ""),
            opportunity.get("location", ""),
        )

        matched = overlaps(required_skills, cv_skills)
        matched_languages = overlaps(required_languages, cv_languages)
        keyword_matches = sorted((candidate_terms & opportunity_terms) - set(matched))[:8]

        skill_score = (len(matched) / len(required_skills) * 70) if required_skills else 0
        language_score = (len(matched_languages) / len(required_languages) * 15) if required_languages else 0
        text_score = min(15, len(keyword_matches) * 3)

        if not required_skills and keyword_matches:
            skill_score = min(45, len(keyword_matches) * 7)

        score = round(min(100, skill_score + language_score + text_score), 2)
        
        ranked.append({
            **opportunity,
            "matched_skills": matched,
            "matched_languages": matched_languages,
            "matched_keywords": keyword_matches,
            "score": score,
        })
    
    # Sort by score descending, then by title for consistency
    return sorted(ranked, key=lambda item: (-item["score"], item.get("title", "")))


def main():
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Usage: matcher.py <cv_json> <opportunities_json>"}))
        return
    cv_data = load_input(sys.argv[1])
    opportunities = load_input(sys.argv[2])
    print(json.dumps(match(cv_data, opportunities), ensure_ascii=False))


def load_input(argument):
    path = Path(argument)
    if path.exists():
        return json.loads(path.read_text(encoding="utf-8"))
    return json.loads(argument)


if __name__ == "__main__":
    main()
