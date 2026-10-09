"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import {
  Sparkles,
  MapPin,
  ExternalLink,
  Star,
  CheckCircle,
  Search,
  Filter,
  ArrowLeft,
  RefreshCw,
  Building,
  GraduationCap,
  Mail,
  Phone,
  Globe,
  Briefcase,
  AlertCircle
} from "lucide-react";

interface ParsedCandidate {
  name?: string;
  email?: string;
  phone?: string;
  fieldOfStudy?: string;
  education?: string[];
  skills?: string[];
  languages?: string[];
  summary?: string;
}

interface MatchItem {
  opportunity: {
    id: number;
    gisId?: string;
    title: string;
    description: string;
    skills: string[];
    location: string;
    country?: string;
    city?: string;
    organisation?: string;
    programmeName?: string;
    programmeSlug?: string;
    programme?: {
      id?: string;
      short_name?: string;
    };
    sourceUrl: string;
    coverPhoto?: string | null;
  };
  score: number;
  reasons: string[];
  matchingSkills?: string[];
  missingSkills?: string[];
}

export default function ResultsPage() {
  const [matches, setMatches] = useState<MatchItem[]>([]);
  const [parsedCv, setParsedCv] = useState<ParsedCandidate | null>(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedProgramme, setSelectedProgramme] = useState<string>("ALL");
  const [favorites, setFavorites] = useState<number[]>([]);
  const [copiedId, setCopiedId] = useState<number | null>(null);

  useEffect(() => {
    const cached = sessionStorage.getItem("aiesec_match_results");
    if (cached) {
      try {
        const data = JSON.parse(cached);
        setMatches(data.matches || []);
        setParsedCv(data.parsedCv || null);
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
    const titleMatch = m.opportunity.title?.toLowerCase().includes(query);
    const locMatch = m.opportunity.location?.toLowerCase().includes(query);
    const orgMatch = m.opportunity.organisation?.toLowerCase().includes(query);
    const descMatch = m.opportunity.description?.toLowerCase().includes(query);
    const skillMatch = (m.opportunity.skills || []).some(s => s.toLowerCase().includes(query));

    const matchesSearch = titleMatch || locMatch || orgMatch || descMatch || skillMatch;

    if (!matchesSearch) return false;

    if (selectedProgramme === "ALL") return true;
    const progCode = (m.opportunity.programme?.short_name || "").toUpperCase();
    if (selectedProgramme === "GT") return progCode === "GT" || progCode === "GTA";
    if (selectedProgramme === "GV") return progCode === "GV";
    if (selectedProgramme === "GTE") return progCode === "GTE" || progCode === "TE";

    return true;
  });

  const getProgrammeBadge = (shortName?: string) => {
    const code = (shortName || "").toUpperCase();
    if (code === "GV") {
      return {
        label: "Global Volunteer",
        short: "GV",
        style: "bg-emerald-500/15 text-emerald-400 border-emerald-500/30",
      };
    }
    if (code === "GTE" || code === "TE") {
      return {
        label: "Global Teacher",
        short: "GTe",
        style: "bg-amber-500/15 text-amber-400 border-amber-500/30",
      };
    }
    return {
      label: "Global Talent",
      short: "GT",
      style: "bg-blue-500/15 text-blue-400 border-blue-500/30",
    };
  };

  return (
    <div className="max-w-6xl mx-auto px-4 py-10">
      {/* Top Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <Link href="/upload" className="inline-flex items-center gap-1.5 text-xs text-[#8b95a6] hover:text-white mb-2 transition-colors">
            <ArrowLeft className="w-3.5 h-3.5" /> Retour à l&apos;analyse du CV
          </Link>
          <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
            Opportunités Réelles AIESEC
            <span className="text-sm font-semibold bg-[#00d4aa]/15 text-[#00d4aa] px-3 py-1 rounded-full border border-[#00d4aa]/30">
              {filteredMatches.length} Disponibles
            </span>
          </h1>
          <p className="text-sm text-[#8b95a6] mt-1">
            Opportunités réelles synchronisées en direct avec l&apos;API AIESEC GIS
          </p>
        </div>

        <Link
          href="/upload"
          className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-[#161a24] border border-[#242a3a] text-white hover:bg-[#242a3a] transition-all"
        >
          <RefreshCw className="w-4 h-4 text-[#00d4aa]" />
          Analyser un autre CV
        </Link>
      </div>

      {/* Candidate Profile Summary Box */}
      {parsedCv && (
        <div className="bg-[#161a24] border border-[#242a3a] rounded-2xl p-6 mb-8 shadow-xl">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#242a3a] pb-4 mb-4">
            <div>
              <div className="flex items-center gap-2">
                <span className="w-2.5 h-2.5 rounded-full bg-[#00d4aa] animate-pulse"></span>
                <span className="text-xs uppercase tracking-wider font-bold text-[#00d4aa]">
                  Profil Candidat Analysé
                </span>
              </div>
              <h2 className="text-xl font-bold text-white mt-1">
                {parsedCv.name || "Candidat AIESEC"}
              </h2>
              {parsedCv.fieldOfStudy && (
                <p className="text-xs text-gray-300 flex items-center gap-1.5 mt-1">
                  <GraduationCap className="w-3.5 h-3.5 text-[#00d4aa]" />
                  {parsedCv.fieldOfStudy}
                </p>
              )}
            </div>

            <div className="flex flex-wrap items-center gap-3 text-xs text-[#8b95a6]">
              {parsedCv.email && (
                <div className="flex items-center gap-1.5 bg-[#0b0d12] px-3 py-1.5 rounded-lg border border-[#242a3a]">
                  <Mail className="w-3.5 h-3.5 text-[#00d4aa]" />
                  <span>{parsedCv.email}</span>
                </div>
              )}
              {parsedCv.phone && (
                <div className="flex items-center gap-1.5 bg-[#0b0d12] px-3 py-1.5 rounded-lg border border-[#242a3a]">
                  <Phone className="w-3.5 h-3.5 text-[#00d4aa]" />
                  <span>{parsedCv.phone}</span>
                </div>
              )}
              {parsedCv.languages && parsedCv.languages.length > 0 && (
                <div className="flex items-center gap-1.5 bg-[#0b0d12] px-3 py-1.5 rounded-lg border border-[#242a3a]">
                  <Globe className="w-3.5 h-3.5 text-[#00d4aa]" />
                  <span>{parsedCv.languages.join(", ")}</span>
                </div>
              )}
            </div>
          </div>

          {/* Detected Skills Cloud */}
          {parsedCv.skills && parsedCv.skills.length > 0 && (
            <div>
              <span className="text-xs font-semibold text-[#8b95a6] uppercase tracking-wider block mb-2">
                Compétences identifiées ({parsedCv.skills.length}) :
              </span>
              <div className="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto pr-1">
                {parsedCv.skills.map((skill, idx) => (
                  <span
                    key={idx}
                    className="text-xs font-medium bg-[#0b0d12] text-gray-200 border border-[#242a3a] px-2.5 py-1 rounded-lg hover:border-[#00d4aa]/40 transition-colors"
                  >
                    {skill}
                  </span>
                ))}
              </div>
            </div>
          )}
        </div>
      )}

      {/* Filter and Search Bar */}
      <div className="flex flex-col md:flex-row gap-4 mb-8">
        <div className="relative flex-1">
          <Search className="w-5 h-5 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            value={searchTerm}
            onChange={e => setSearchTerm(e.target.value)}
            placeholder="Rechercher par poste, entreprise, pays, compétence..."
            className="w-full pl-12 pr-4 py-3 rounded-xl bg-[#161a24] border border-[#242a3a] text-white placeholder-gray-500 focus:outline-none focus:border-[#00d4aa] text-sm"
          />
        </div>

        {/* Programme Filter Tabs */}
        <div className="flex items-center gap-1.5 bg-[#161a24] border border-[#242a3a] p-1 rounded-xl">
          {[
            { id: "ALL", label: "Tous" },
            { id: "GT", label: "Global Talent" },
            { id: "GV", label: "Global Volunteer" },
            { id: "GTE", label: "Global Teacher" },
          ].map(tab => (
            <button
              key={tab.id}
              onClick={() => setSelectedProgramme(tab.id)}
              className={`px-3 py-2 rounded-lg text-xs font-semibold transition-all ${
                selectedProgramme === tab.id
                  ? "bg-[#00d4aa] text-black shadow-md"
                  : "text-gray-400 hover:text-white hover:bg-[#242a3a]"
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      {/* Results Grid */}
      {filteredMatches.length === 0 ? (
        <div className="text-center py-16 bg-[#161a24] border border-[#242a3a] rounded-2xl">
          <Filter className="w-10 h-10 text-gray-500 mx-auto mb-3" />
          <p className="text-white font-semibold text-lg">Aucune opportunité trouvée avec ces filtres.</p>
          <p className="text-sm text-[#8b95a6] mt-1">Essayez d&apos;ajuster vos critères de recherche ou réinitialisez le filtre.</p>
          <button
            onClick={() => {
              setSearchTerm("");
              setSelectedProgramme("ALL");
            }}
            className="mt-4 px-4 py-2 text-xs font-bold bg-[#242a3a] text-white rounded-xl hover:bg-[#2d3548]"
          >
            Réinitialiser les filtres
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {filteredMatches.map((m, idx) => {
            const isFav = favorites.includes(m.opportunity.id);
            const prog = getProgrammeBadge(m.opportunity.programme?.short_name);
            const scoreColor =
              m.score >= 85
                ? "text-[#00d4aa] border-[#00d4aa]/40 bg-[#00d4aa]/10"
                : m.score >= 70
                ? "text-blue-400 border-blue-400/40 bg-blue-400/10"
                : "text-amber-400 border-amber-400/40 bg-amber-400/10";

            return (
              <div
                key={m.opportunity.id || idx}
                className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl flex flex-col justify-between hover:border-[#00d4aa]/50 transition-all shadow-lg hover:shadow-2xl"
              >
                <div>
                  {/* Top Badges (Programme & Fit Score) */}
                  <div className="flex items-start justify-between gap-3 mb-3">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className={`text-[11px] font-bold px-2.5 py-0.5 rounded-lg border ${prog.style}`}>
                        {prog.label}
                      </span>
                      {m.opportunity.organisation && (
                        <span className="text-[11px] font-medium text-gray-400 flex items-center gap-1 bg-[#0b0d12] border border-[#242a3a] px-2 py-0.5 rounded-lg">
                          <Building className="w-3 h-3 text-[#00d4aa]" />
                          <span className="truncate max-w-[150px]">{m.opportunity.organisation}</span>
                        </span>
                      )}
                    </div>

                    <div className={`px-2.5 py-1 rounded-xl text-xs font-extrabold border ${scoreColor} shrink-0`}>
                      {m.score}% Correspondance
                    </div>
                  </div>

                  {/* Title & Location */}
                  <h3 className="text-lg font-bold text-white leading-snug mb-2 hover:text-[#00d4aa] transition-colors">
                    {m.opportunity.title}
                  </h3>

                  <div className="flex items-center gap-2 text-xs text-[#8b95a6] mb-3">
                    <MapPin className="w-3.5 h-3.5 text-[#00d4aa] shrink-0" />
                    <span>{m.opportunity.location || m.opportunity.country || "International"}</span>
                  </div>

                  {/* Description */}
                  <p className="text-xs text-gray-300 line-clamp-3 leading-relaxed mb-4">
                    {m.opportunity.description}
                  </p>

                  {/* Match Reasons */}
                  {m.reasons && m.reasons.length > 0 && (
                    <div className="space-y-1.5 mb-4 bg-[#0b0d12]/70 p-3 rounded-xl border border-[#242a3a]">
                      {m.reasons.map((r, rIdx) => (
                        <div key={rIdx} className="flex items-start gap-2 text-xs text-[#00d4aa]">
                          <CheckCircle className="w-3.5 h-3.5 shrink-0 mt-0.5" />
                          <span className="leading-snug">{r}</span>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Skills badges */}
                  {m.opportunity.skills && m.opportunity.skills.length > 0 && (
                    <div className="mb-4">
                      <span className="text-[10px] uppercase font-bold text-[#8b95a6] block mb-1.5 tracking-wider">
                        Compétences requises :
                      </span>
                      <div className="flex flex-wrap gap-1.5">
                        {m.opportunity.skills.slice(0, 7).map((s, sIdx) => {
                          const isMatch = m.matchingSkills?.some(
                            ms => ms.toLowerCase().includes(s.toLowerCase()) || s.toLowerCase().includes(ms.toLowerCase())
                          );
                          return (
                            <span
                              key={sIdx}
                              className={`text-[11px] font-medium px-2 py-0.5 rounded-md border ${
                                isMatch
                                  ? "bg-[#00d4aa]/15 text-[#00d4aa] border-[#00d4aa]/30 font-semibold"
                                  : "bg-[#242a3a] text-gray-300 border-transparent"
                              }`}
                            >
                              {s}
                            </span>
                          );
                        })}
                      </div>
                    </div>
                  )}
                </div>

                {/* Card Footer Actions */}
                <div className="flex items-center justify-between border-t border-[#242a3a] pt-4 mt-2">
                  <button
                    onClick={() => toggleFavorite(m.opportunity.id)}
                    className={`flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors ${
                      isFav
                        ? "bg-amber-500/20 text-amber-400 border-amber-500/40"
                        : "bg-[#0b0d12] text-gray-400 border-[#242a3a] hover:text-white"
                    }`}
                  >
                    <Star className={`w-3.5 h-3.5 ${isFav ? "fill-amber-400 text-amber-400" : ""}`} />
                    {isFav ? "Enregistré" : "Sauvegarder"}
                  </button>

                  <a
                    href={m.opportunity.sourceUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="flex items-center gap-2 text-xs font-bold text-black bg-[#00d4aa] hover:bg-[#00c099] px-4 py-2 rounded-xl transition-all shadow-md hover:scale-[1.02]"
                  >
                    Postuler sur AIESEC.org
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
