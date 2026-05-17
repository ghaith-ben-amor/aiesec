import json
import os
import sys
from datetime import date
from urllib.parse import urljoin
import re


try:
    sys.stdout.reconfigure(encoding="utf-8")
except Exception:
    pass

try:
    import requests
    from bs4 import BeautifulSoup
except Exception:
    requests = None
    BeautifulSoup = None

TARGET_URL = "https://aiesec.org/search?programmes=8"
GRAPHQL_URL = "https://gis-api.aiesec.org/graphql"
PROGRAMME_ID = 8

OPPORTUNITY_QUERY = """
query GetAllOpportunitiesQuery($page: Int, $per_page: Int, $q: String, $sort: String, $smart_search: Boolean, $filters: OpportunityFilter, $loggedInUser: Boolean!, $cdn_links: Boolean) {
  allOpportunity: allOpportunity(page: $page, per_page: $per_page, q: $q, sort: $sort, smart_search: $smart_search, filters: $filters) {
    data {
      applicants_count
      applications_close_date
      branch {
        company {
          id
          name
          __typename
        }
        __typename
      }
      description
      host_lc {
        address_detail {
          country
          __typename
        }
        __typename
      }
      id
      duration
      opportunity_duration_type {
        duration_type
        __typename
      }
      earliest_start_date
      location
      programme {
        id
        short_name
        short_name_display
        __typename
      }
      project_name
      project_description
      remote_opportunity
      experience_type
      role_info {
        learning_points_list
        __typename
      }
      title
      __typename
    }
    paging {
      total_items
      total_pages
      current_page
      __typename
    }
    __typename
  }
}
"""

# Try to use Selenium for JavaScript rendering
try:
    from selenium import webdriver
    from selenium.webdriver.common.by import By
    from selenium.webdriver.support.ui import WebDriverWait
    from selenium.webdriver.support import expected_conditions as EC
    SELENIUM_AVAILABLE = True
except Exception:
    SELENIUM_AVAILABLE = False


def scrape_with_selenium():
    """Scrape real opportunities using Selenium for JavaScript rendering."""
    if not SELENIUM_AVAILABLE:
        return None
    
    try:
        print("[INFO] Starting Selenium-based scraping from AIESEC.org", file=sys.stderr)
        
        # Try Chrome first, fall back to Firefox
        options = None
        driver = None
        
        try:
            from selenium.webdriver.chrome.options import Options as ChromeOptions
            options = ChromeOptions()
            options.add_argument("--headless")
            options.add_argument("--no-sandbox")
            options.add_argument("--disable-dev-shm-usage")
            options.add_argument("--disable-gpu")
            options.add_argument("--window-size=1920,1080")
            options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")
            
            driver = webdriver.Chrome(options=options)
            print("[INFO] Using Chrome WebDriver", file=sys.stderr)
        except Exception as e:
            print(f"[DEBUG] Chrome not available: {e}", file=sys.stderr)
            try:
                from selenium.webdriver.firefox.options import Options as FirefoxOptions
                options = FirefoxOptions()
                options.add_argument("--headless")
                options.add_argument("--width=1920")
                options.add_argument("--height=1080")
                
                driver = webdriver.Firefox(options=options)
                print("[INFO] Using Firefox WebDriver", file=sys.stderr)
            except Exception as e:
                print(f"[DEBUG] Firefox not available: {e}", file=sys.stderr)
                return None
        
        if not driver:
            return None
        
        try:
            print(f"[INFO] Fetching {TARGET_URL}", file=sys.stderr)
            driver.get(TARGET_URL)
            
            # Wait for opportunity listings to load
            wait = WebDriverWait(driver, 15)
            
            # Look for opportunity items - try multiple selectors
            try:
                wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, "[data-test*='opportunity'], [class*='OpportunityCard'], article[class*='Card'], div[data-id]")))
            except:
                print("[DEBUG] Timeout waiting for opportunities, trying anyway", file=sys.stderr)
            
            # Give extra time for dynamic content
            import time
            time.sleep(3)
            
            soup = BeautifulSoup(driver.page_source, "html.parser")
            opportunities = parse_opportunities_html(soup)
            
            if opportunities and len(opportunities) > 0:
                print(f"[INFO] Successfully scraped {len(opportunities)} opportunities", file=sys.stderr)
                return opportunities
            
            print("[DEBUG] No opportunities found, using fallback", file=sys.stderr)
            return None
            
        finally:
            driver.quit()
    
    except Exception as e:
        print(f"[ERROR] Selenium scraping failed: {str(e)}", file=sys.stderr)
        return None


def scrape_with_graphql():
    """Fetch live opportunities from the same GraphQL API used by AIESEC.org."""
    if requests is None:
        return None

    token = os.getenv("AIESEC_ACCESS_TOKEN", "").strip()
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Origin": "https://aiesec.org",
        "Referer": TARGET_URL,
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    }
    if token:
        headers["Authorization"] = token

    payload = {
        "operationName": "GetAllOpportunitiesQuery",
        "query": OPPORTUNITY_QUERY,
        "variables": {
            "page": 1,
            "per_page": int(os.getenv("AIESEC_PER_PAGE", "30")),
            "q": "",
            "sort": "relevance",
            "smart_search": True,
            "loggedInUser": False,
            "cdn_links": False,
            "filters": {
                "earliest_start_date": {"from": date.today().isoformat()},
                "programmes": [PROGRAMME_ID],
            },
        },
    }

    try:
        print("[INFO] Fetching live AIESEC opportunities from GraphQL", file=sys.stderr)
        response = requests.post(GRAPHQL_URL, headers=headers, json=payload, timeout=20)
        response.raise_for_status()
        body = response.json()

        if body.get("errors"):
            print(f"[DEBUG] GraphQL errors: {body.get('errors')}", file=sys.stderr)
            return None

        raw_items = (((body.get("data") or {}).get("allOpportunity") or {}).get("data") or [])
        opportunities = [normalize_graphql_opportunity(item) for item in raw_items]
        opportunities = [item for item in opportunities if item and item.get("title")]

        if opportunities:
            print(f"[INFO] GraphQL returned {len(opportunities)} live opportunities", file=sys.stderr)
            return opportunities
    except Exception as e:
        print(f"[DEBUG] GraphQL scraping failed: {str(e)}", file=sys.stderr)

    return None


def normalize_graphql_opportunity(item):
    """Map AIESEC GraphQL records to the local opportunity shape."""
    title = clean_text(item.get("title") or item.get("project_name") or "AIESEC Opportunity")
    description = clean_text(
        item.get("description")
        or item.get("project_description")
        or "Open AIESEC opportunity. View the source for full details."
    )
    role_info = item.get("role_info") or {}
    learning_points = role_info.get("learning_points_list") or []
    learning_text = " ".join(str(point) for point in learning_points)
    combined_text = " ".join([title, description, item.get("project_description") or "", learning_text])

    host_lc = item.get("host_lc") or {}
    address = host_lc.get("address_detail") or {}
    location = clean_text(item.get("location") or address.get("country") or "Global")
    if item.get("remote_opportunity"):
        location = "Remote"

    duration_type = item.get("opportunity_duration_type") or {}
    duration = clean_text(item.get("duration") or duration_type.get("duration_type") or "See AIESEC details")
    programme = item.get("programme") or {}
    company = ((item.get("branch") or {}).get("company") or {}).get("name")

    opportunity_id = item.get("id")
    programme_name = clean_text(programme.get("short_name_display") or programme.get("short_name") or "Global Talent")
    source_url = build_opportunity_url(opportunity_id, programme_name)

    skills = extract_skills_from_text(combined_text)
    languages = extract_languages_from_text(combined_text)

    if not skills:
        skills = ["communication", "teamwork", "leadership"]

    return {
        "external_id": str(opportunity_id) if opportunity_id else None,
        "title": title[:150],
        "description": description[:500],
        "skills": skills,
        "languages": languages,
        "location": location,
        "source_url": source_url,
        "category": programme_name,
        "duration": duration,
        "company": clean_text(company or ""),
        "source_type": "live",
    }


def clean_text(value):
    return " ".join(str(value or "").strip().split())


def build_opportunity_url(opportunity_id, programme_name="Global Talent"):
    if not opportunity_id:
        return TARGET_URL

    programme_slug = {
        "global talent": "global-talent",
        "global teacher": "global-teacher",
        "global volunteer": "global-volunteer",
    }.get(clean_text(programme_name).lower())

    if not programme_slug:
        programme_slug = re.sub(r"[^a-z0-9]+", "-", clean_text(programme_name).lower()).strip("-") or "global-talent"

    return f"https://aiesec.org/opportunity/{programme_slug}/{opportunity_id}"


def parse_opportunities_html(soup):
    """Parse opportunities from BeautifulSoup object."""
    opportunities = []
    
    # Look for opportunity containers with various selectors
    selectors = [
        "article[class*='Card']",
        "div[class*='OpportunityCard']",
        "div[data-id]",
        "div[class*='listing']",
        "div[class*='opportunity']"
    ]
    
    for selector in selectors:
        elements = soup.select(selector)
        
        if elements and len(elements) > 0:
            print(f"[DEBUG] Found {len(elements)} elements with selector: {selector}", file=sys.stderr)
            
            for idx, elem in enumerate(elements[:20]):  # Limit to 20
                try:
                    opp = extract_opportunity_from_element(elem, idx + 1)
                    if opp and opp['title']:
                        opportunities.append(opp)
                except Exception as e:
                    print(f"[DEBUG] Error parsing opportunity {idx}: {e}", file=sys.stderr)
            
            if opportunities:
                break
    
    return opportunities


def extract_opportunity_from_element(elem, index):
    """Extract opportunity data from a DOM element."""
    try:
        # Try to find title
        title_elem = elem.find(["h2", "h3", "h4", "a"])
        title = title_elem.get_text(strip=True) if title_elem else f"Opportunity #{index}"
        
        # Try to find description
        desc_elem = elem.find(["p", "div[class*='description']"])
        description = desc_elem.get_text(strip=True) if desc_elem else ""
        
        # Try to find link
        link_elem = elem.find("a", href=True)
        link = link_elem.get("href") if link_elem else None
        if link and not link.startswith("http"):
            link = urljoin("https://aiesec.org", link)

        external_id = elem.get("data-id") or elem.get("data-opportunity-id")
        if not link and external_id:
            link = build_opportunity_url(external_id)
        
        # Extract location
        full_text = elem.get_text(" ", strip=True)
        location = extract_location(full_text)
        
        # Extract skills from description or title
        skills = extract_skills_from_text(full_text)
        if not skills:
            skills = ["teamwork", "communication"]
        
        return {
            "id": index,
            "external_id": str(external_id) if external_id else None,
            "title": title.strip()[:150] if title else None,
            "description": description[:300] if description else full_text[:300],
            "skills": skills,
            "location": location,
            "source_url": link if link else TARGET_URL,
            "category": "Global Opportunity",
            "duration": extract_duration(full_text) or "3-6 months",
            "source_type": "live",
        }
    except Exception as e:
        print(f"[DEBUG] Error extracting opportunity: {e}", file=sys.stderr)
        return None


def extract_skills_from_text(text):
    """Extract potential skills from opportunity text."""
    text_lower = text.lower()
    
    common_skills = {
        # Tech skills
        "python": ["python", "python3"],
        "php": ["php"],
        "javascript": ["javascript", "js", "nodejs", "node.js"],
        "java": ["java"],
        "c++": ["c++", "cpp"],
        "c#": ["c#", "csharp"],
        "sql": ["sql", "mysql", "postgresql", "database"],
        "html": ["html", "html5"],
        "css": ["css", "css3"],
        "react": ["react", "reactjs"],
        "angular": ["angular"],
        "vue": ["vue", "vuejs"],
        "flutter": ["flutter", "dart"],
        "aws": ["aws", "amazon web services"],
        "docker": ["docker"],
        "git": ["git", "github"],
        # Soft skills
        "communication": ["communication", "communicat"],
        "teamwork": ["teamwork", "team work", "collaboration"],
        "leadership": ["leadership", "leader"],
        "problem solving": ["problem solving", "troubleshoot"],
        "project management": ["project management", "agile", "scrum"],
        # Design
        "design": ["design", "designer"],
        "photoshop": ["photoshop"],
        "figma": ["figma"],
        # Marketing
        "marketing": ["marketing", "seo", "sem"],
        "content creation": ["content", "writing"],
        # Data
        "data analysis": ["data analysis", "analytics", "bi"],
        "excel": ["excel", "spreadsheet"],
    }
    
    found_skills = set()
    for skill_name, keywords in common_skills.items():
        for keyword in keywords:
            if keyword in text_lower:
                found_skills.add(skill_name)
                break
    
    return sorted(found_skills)


def extract_languages_from_text(text):
    text_lower = text.lower()
    languages = []
    for language in ["english", "french", "spanish", "german", "arabic", "portuguese", "italian", "hindi"]:
        if language in text_lower:
            languages.append(language)
    return languages


def extract_duration(text):
    """Extract opportunity duration from text."""
    text_lower = text.lower()
    durations = {
        "3-6 months": ["3 months", "4 months", "5 months", "6 months"],
        "6-12 months": ["6 months", "7 months", "8 months", "9 months", "10 months", "11 months", "1 year"],
        "1-3 months": ["1 month", "2 months", "3 months"],
        "Variable": ["flexible", "variable"],
    }
    
    for duration, keywords in durations.items():
        for keyword in keywords:
            if keyword in text_lower:
                return duration
    
    return None


def fallback_data():
    """
    AIESEC.org uses JavaScript rendering which requires browser automation.
    AIESEC.org uses JavaScript rendering which requires browser automation.
    If Selenium/WebDriver is not available, these are sample opportunities based on common AIESEC programmes.
    Install Selenium and ChromeDriver/GeckoDriver for real AIESEC.org scraping.
    """
    return [
        {
            "id": 1,
            "title": "Global Talent - Web Developer (Sample)",
            "description": "Full-stack web development position with PHP, JavaScript, MySQL. Build scalable web applications.",
            "skills": ["php", "javascript", "html", "css", "mvc", "sql", "teamwork", "communication"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Talent",
            "duration": "3-6 months",
            "source_type": "sample"
        },
        {
            "id": 2,
            "title": "Global Talent - Backend Engineer (Sample)",
            "description": "Backend development with Python and SQL. Design and maintain APIs and databases.",
            "skills": ["arduino", "c", "c++", "embedded systems", "iot", "problem solving", "teamwork"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Volunteer",
            "duration": "6-12 months",
            "source_type": "sample"
        },
        {
            "id": 3,
            "title": "Global Talent - UX/UI Designer (Sample)",
            "description": "Design user interfaces with Figma and CSS. Create beautiful, responsive web designs.",
            "skills": ["marketing", "design", "photoshop", "illustrator", "content creation", "communication"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Talent",
            "duration": "3-6 months",
            "source_type": "sample"
        },
        {
            "id": 4,
            "title": "Global Talent - Data Analyst (Sample)",
            "description": "Analyze data with Python, SQL, and Excel. Generate insights from business data.",
            "skills": ["python", "sql", "data analysis", "excel", "research", "communication"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Talent",
            "duration": "6-12 months",
            "source_type": "sample"
        },
        {
            "id": 5,
            "title": "Global Volunteer - Community Developer (Sample)",
            "description": "Develop social impact solutions using technology. Work with React and Python for good.",
            "skills": ["design", "photoshop", "illustrator", "css", "communication", "teamwork"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Talent",
            "duration": "3-6 months",
            "source_type": "sample"
        },
        {
            "id": 6,
            "title": "Global Volunteer - Project Lead (Sample)",
            "description": "Lead community development projects with focus on project management and teamwork.",
            "skills": ["leadership", "communication", "teamwork", "project management", "problem solving"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Volunteer",
            "duration": "6-12 months",
            "source_type": "sample"
        },
        {
            "id": 7,
            "title": "Global Talent - Mobile Developer (Sample)",
            "description": "Build cross-platform apps with Flutter and Dart. Create production-ready mobile solutions.",
            "skills": ["flutter", "dart", "java", "javascript", "teamwork", "problem solving"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Talent",
            "duration": "3-6 months",
            "source_type": "sample"
        },
        {
            "id": 8,
            "title": "Global Volunteer - Tech for Good (Sample)",
            "description": "Contribute to social impact with technology. Use Java, Python, or JavaScript to solve real problems.",
            "skills": ["java", "python", "javascript", "problem solving", "teamwork", "leadership"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Volunteer",
            "duration": "6-12 months",
            "source_type": "sample"
        },
        {
            "id": 9,
            "title": "Global Talent - QA Engineer (Sample)",
            "description": "Ensure software quality through manual and automated testing. Use problem-solving skills.",
            "skills": ["problem solving", "teamwork", "communication", "research", "excel"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Talent",
            "duration": "3-6 months",
            "source_type": "sample"
        },
        {
            "id": 10,
            "title": "Global Talent - Full-Stack Developer (Sample)",
            "description": "Build full-stack web applications with PHP backend and JavaScript frontend. Work with databases.",
            "skills": ["python", "php", "sql", "mvc", "problem solving", "teamwork"],
            "location": "Global",
            "source_url": "https://aiesec.org/search?programmes=8",
            "category": "Global Talent",
            "duration": "6-12 months",
            "source_type": "sample"
        }
    ]


def scrape():
    """
    Scrape real opportunities from AIESEC.org search page.
    First tries the public GraphQL endpoint used by AIESEC.org.
    Then tries Selenium-based scraping (JavaScript rendering).
    Falls back to mock data if unavailable.
    """
    graphql_result = scrape_with_graphql()
    if graphql_result:
        return graphql_result

    # Try Selenium first
    selenium_result = scrape_with_selenium()
    if selenium_result:
        return selenium_result
    
    # Fallback to static mock data
    if requests is None or BeautifulSoup is None:
        return fallback_data()
    
    try:
        # Try fetching with a proper User-Agent
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        }
        
        response = requests.get(TARGET_URL, headers=headers, timeout=15)
        response.raise_for_status()
        
        soup = BeautifulSoup(response.text, "html.parser")
        
        # Try different selectors to find opportunity listings
        opportunities = []
        
        # Look for job cards with various possible selectors
        selectors = [
            "div[class*='opportunity']",
            "div[class*='job']",
            "article[class*='listing']",
            "div[class*='card'][data-test*='opportunity']",
            "a[href*='opportunity']"
        ]
        
        for selector in selectors:
            elements = soup.select(selector)
            if elements:
                for idx, elem in enumerate(elements[:15]):  # Limit to 15
                    text = elem.get_text(" ", strip=True)
                    if len(text) > 20:
                        # Extract title (first meaningful text)
                        title = text[:120].split('\n')[0] if '\n' in text else text[:120]
                        
                        # Try to get link
                        link = None
                        link_elem = elem.find("a", href=True)
                        if link_elem:
                            link = link_elem.get("href")
                            if link and not link.startswith("http"):
                                link = urljoin("https://aiesec.org", link)
                        
                        location = extract_location(text)
                        
                        opportunity = {
                            "id": idx + 1,
                            "title": title.strip(),
                            "description": text[:280] if len(text) > 280 else text,
                            "skills": extract_skills_from_text(text),
                            "location": location,
                            "source_url": link if link else TARGET_URL,
                            "category": "Global Opportunity",
                            "duration": "3-6 months",
                            "source_type": "live",
                        }
                        
                        if opportunity not in opportunities:
                            opportunities.append(opportunity)
                
                if opportunities:
                    return opportunities
        
        # If we got some content but no structured opportunities, return fallback
        return fallback_data()
        
    except Exception as e:
        print(f"[DEBUG] Scraping failed: {str(e)}", file=sys.stderr)
        return fallback_data()


def extract_location(text):
    """Extract potential location from text."""
    locations = ["Germany", "France", "UK", "Poland", "India", "Brazil", 
                 "Kenya", "Singapore", "Netherlands", "Remote", "Global"]
    text_lower = text.lower()
    for loc in locations:
        if loc.lower() in text_lower:
            return loc
    return "Global"


def main():
    print(json.dumps(scrape(), ensure_ascii=False))


if __name__ == "__main__":
    main()
