#!/usr/bin/env python
import json
from python.scraper import fallback_data
from python.matcher import match

# CV data extracted from earlier test
cv_data = {
    'skills': ['project management', 'php', 'javascript', 'html', 'css', 'design', 'ai', 
               'java', 'c', 'c++', 'flutter', 'flutterflow', 'mvc', 'qt', 'arduino', 
               'stm32', 'photoshop', 'illustrator'],
    'languages': ['english', 'arabic', 'french']
}

opportunities = fallback_data()
results = match(cv_data, opportunities)

print('\n=== TOP MATCHING OPPORTUNITIES ===\n')
for i, opp in enumerate(results[:5], 1):
    print(f"{i}. {opp['title']}")
    print(f"   Score: {opp['score']}%")
    print(f"   Location: {opp['location']}")
    print(f"   Matched Skills: {', '.join(opp['matched_skills'][:8])}")
    print()

print(f"\nTotal opportunities: {len(results)}")
print("Average score:", round(sum(o['score'] for o in results) / len(results), 2))
