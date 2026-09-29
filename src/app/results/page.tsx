"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Sparkles, MapPin, ExternalLink, Star, CheckCircle, Search, Filter, ArrowLeft, RefreshCw } from "lucide-react";

interface MatchItem {
  opportunity: {
    id: number;
    title: string;
    description: string;
    skills: string[];
    location: string;
    sourceUrl: string;
  };
  score: number;
  reasons: string[];
  matchingSkills?: string[];
}

export default function ResultsPage() {
  const [matches, setMatches] = useState<MatchItem[]>([]);
  const [parsedSkills, setParsedSkills] = useState<string[]>([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [favorites, setFavorites] = useState<number[]>([]);

  useEffect(() => {
    const cached = sessionStorage.getItem("aiesec_match_results");
    if (cached) {
      try {
        const data = JSON.parse(cached);
        setMatches(data.matches || []);
        setParsedSkills(data.parsedCv?.skills || []);
      } catch (err) {
        console.error("Failed to parse cached match results", err);
      }
    }
  }, []);

  const toggleFavorite = (id: number) => {
    setFavorites(prev => (prev.includes(id) ? prev.filter(f => f !== id) : [...prev, id]));
  };

  const filteredMatches = matches.filter(m => {
    const query = searchTerm.toLowerCase();
    return (
      m.opportunity.title.toLowerCase().includes(query) ||
      m.opportunity.location.toLowerCase().includes(query) ||
      m.opportunity.description.toLowerCase().includes(query)
    );
  });

  return (
    <div className="max-w-6xl mx-auto px-4 py-10">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <Link href="/upload" className="inline-flex items-center gap-1.5 text-xs text-[#8b95a6] hover:text-white mb-2">
            <ArrowLeft className="w-3.5 h-3.5" /> Back to Upload
          </Link>
          <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
            Top Matched Opportunities
            <span className="text-sm font-semibold bg-[#00d4aa]/15 text-[#00d4aa] px-3 py-1 rounded-full border border-[#00d4aa]/30">
              {matches.length} Results Found
            </span>
          </h1>
        </div>

        <Link
          href="/upload"
          className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[#161a24] border border-[#242a3a] text-white hover:bg-[#242a3a]"
        >
          <RefreshCw className="w-4 h-4 text-[#00d4aa]" />
          Match New CV
        </Link>
      </div>

      {/* Extracted Skills Bar */}
      {parsedSkills.length > 0 && (
        <div className="bg-[#161a24] border border-[#242a3a] p-4 rounded-2xl mb-8 flex items-center gap-3 flex-wrap">
          <span className="text-xs font-semibold text-[#8b95a6] uppercase tracking-wider">Detected Skills:</span>
          {parsedSkills.map((skill, idx) => (
            <span key={idx} className="text-xs font-medium bg-[#0b0d12] text-[#00d4aa] border border-[#00d4aa]/30 px-2.5 py-1 rounded-lg">
              {skill}
            </span>
          ))}
        </div>
      )}

      {/* Search Filter */}
      <div className="relative mb-8">
        <Search className="w-5 h-5 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          value={searchTerm}
          onChange={e => setSearchTerm(e.target.value)}
          placeholder="Filter by job title, country, or skill..."
          className="w-full pl-12 pr-4 py-3 rounded-xl bg-[#161a24] border border-[#242a3a] text-white placeholder-gray-500 focus:outline-none focus:border-[#00d4aa] text-sm"
        />
      </div>

      {/* Results Grid */}
      {filteredMatches.length === 0 ? (
        <div className="text-center py-16 bg-[#161a24] border border-[#242a3a] rounded-2xl">
          <Filter className="w-10 h-10 text-gray-500 mx-auto mb-3" />
          <p className="text-white font-semibold text-lg">No opportunities found matching search.</p>
          <p className="text-sm text-[#8b95a6] mt-1">Try resetting search filters or upload another CV.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {filteredMatches.map((m, idx) => {
            const isFav = favorites.includes(m.opportunity.id);
            const scoreColor =
              m.score >= 80 ? "text-[#00d4aa] border-[#00d4aa]/40 bg-[#00d4aa]/10" : "text-[#037ef3] border-[#037ef3]/40 bg-[#037ef3]/10";

            return (
              <div
                key={idx}
                className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl flex flex-col justify-between hover:border-[#00d4aa]/50 transition-all shadow-lg"
              >
                <div>
                  <div className="flex items-start justify-between gap-4 mb-3">
                    <h3 className="text-lg font-bold text-white leading-snug">{m.opportunity.title}</h3>
                    <div className={`px-3 py-1 rounded-xl text-sm font-extrabold border ${scoreColor} shrink-0`}>
                      {m.score}% Fit
                    </div>
                  </div>

                  <div className="flex items-center gap-2 text-xs text-[#8b95a6] mb-4">
                    <MapPin className="w-3.5 h-3.5 text-[#00d4aa]" />
                    <span>{m.opportunity.location}</span>
                  </div>

                  <p className="text-sm text-gray-300 line-clamp-3 leading-relaxed mb-4">
                    {m.opportunity.description}
                  </p>

                  {/* Match Reasons */}
                  {m.reasons && m.reasons.length > 0 && (
                    <div className="space-y-1.5 mb-4 bg-[#0b0d12]/60 p-3 rounded-xl border border-[#242a3a]">
                      {m.reasons.map((r, rIdx) => (
                        <div key={rIdx} className="flex items-start gap-2 text-xs text-[#00d4aa]">
                          <CheckCircle className="w-3.5 h-3.5 shrink-0 mt-0.5" />
                          <span>{r}</span>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Skills badges */}
                  <div className="flex flex-wrap gap-1.5 mb-6">
                    {m.opportunity.skills.map((s, sIdx) => (
                      <span key={sIdx} className="text-[11px] font-medium bg-[#242a3a] text-gray-300 px-2 py-0.5 rounded-md">
                        {s}
                      </span>
                    ))}
                  </div>
                </div>

                <div className="flex items-center justify-between border-t border-[#242a3a] pt-4 mt-2">
                  <button
                    onClick={() => toggleFavorite(m.opportunity.id)}
                    className={`flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors ${
                      isFav
                        ? "bg-amber-500/20 text-amber-400 border-amber-500/40"
                        : "bg-[#0b0d12] text-gray-400 border-[#242a3a] hover:text-white"
                    }`}
                  >
                    <Star className={`w-3.5 h-3.5 ${isFav ? "fill-amber-400" : ""}`} />
                    {isFav ? "Saved" : "Save Opportunity"}
                  </button>

                  <a
                    href={m.opportunity.sourceUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="flex items-center gap-1.5 text-xs font-bold text-black bg-[#00d4aa] hover:bg-[#00c099] px-4 py-1.5 rounded-lg transition-colors"
                  >
                    Apply Now
                    <ExternalLink className="w-3.5 h-3.5" />
                  </a>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
