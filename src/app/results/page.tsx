"use client";

import { useEffect, useState, useMemo } from "react";
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
  AlertCircle,
  Clock,
  Calendar,
  X,
  SlidersHorizontal
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
    duration?: number;
    durationBracket?: "short" | "medium" | "long";
    earliestStartDate?: string | null;
    latestEndDate?: string | null;
  };
  score: number;
  reasons: string[];
  matchingSkills?: string[];
  missingSkills?: string[];
}

function formatDate(dateStr?: string | null): string {
  if (!dateStr) return "";
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString("fr-FR", {
      day: "numeric",
      month: "short",
      year: "numeric",
    });
  } catch {
    return dateStr;
  }
}

function getDurationBadgeInfo(weeks?: number | null) {
  const w = weeks || 12;
  if (w <= 12) {
    return {
      label: `${w} sem.`,
      bracket: "6 - 12 sem.",
      type: "Court terme",
      style: "bg-emerald-500/15 text-emerald-400 border-emerald-500/30",
    };
  }
  if (w <= 26) {
    return {
      label: `${w} sem.`,
      bracket: "13 - 26 sem.",
      type: "Moyen terme",
      style: "bg-cyan-500/15 text-cyan-400 border-cyan-500/30",
    };
  }
  return {
    label: `${w} sem.`,
    bracket: "27 - 78 sem.",
    type: "Long terme",
    style: "bg-purple-500/15 text-purple-400 border-purple-500/30",
  };
}

export default function ResultsPage() {
  const [matches, setMatches] = useState<MatchItem[]>([]);
  const [parsedCv, setParsedCv] = useState<ParsedCandidate | null>(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedProgramme, setSelectedProgramme] = useState<string>("ALL");
  const [selectedDuration, setSelectedDuration] = useState<string>("ALL"); // "ALL" | "short" | "medium" | "long"
  const [selectedCountry, setSelectedCountry] = useState<string>("ALL");
  const [filterStartDate, setFilterStartDate] = useState<string>("");
  const [favorites, setFavorites] = useState<number[]>([]);
  const [copiedId, setCopiedId] = useState<number | null>(null);

  useEffect(() => {
    const cached = sessionStorage.getItem("aiesec_match_results");
    if (cached) {
      try {
        const data = JSON.parse(cached);
        setMatches(data.matches || []);
        setParsedCv(data.parsedCv || null);

        // Preload user preferences if defined from upload form
        if (data.preferences) {
          if (data.preferences.country && data.preferences.country !== "all") {
            setSelectedCountry(data.preferences.country);
          }
          if (data.preferences.duration && data.preferences.duration !== "all") {
            setSelectedDuration(data.preferences.duration);
          }
          if (data.preferences.earliestStartDate) {
            setFilterStartDate(data.preferences.earliestStartDate);
          }
        }
      } catch (err) {
        console.error("Failed to parse cached match results", err);
      }
    }
  }, []);

  const toggleFavorite = (id: number) => {
    setFavorites(prev => (prev.includes(id) ? prev.filter(f => f !== id) : [...prev, id]));
  };

  // Compute available countries with counts for filter dropdown
  const availableCountries = useMemo(() => {
    const map = new Map<string, number>();
    matches.forEach(m => {
      const c = (m.opportunity.country || "").trim();
      if (c && c !== "Global" && c !== "International") {
        map.set(c, (map.get(c) || 0) + 1);
      }
    });
    return Array.from(map.entries())
      .map(([name, count]) => ({ name, count }))
      .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name));
  }, [matches]);

  const filteredMatches = matches.filter(m => {
    const opp = m.opportunity;
    const query = searchTerm.toLowerCase();

    // 1. Text search match
    const titleMatch = opp.title?.toLowerCase().includes(query);
    const locMatch = opp.location?.toLowerCase().includes(query);
    const orgMatch = opp.organisation?.toLowerCase().includes(query);
    const descMatch = opp.description?.toLowerCase().includes(query);
    const skillMatch = (opp.skills || []).some(s => s.toLowerCase().includes(query));
    if (query && !titleMatch && !locMatch && !orgMatch && !descMatch && !skillMatch) {
      return false;
    }

    // 2. Programme filter
    if (selectedProgramme !== "ALL") {
      const progCode = (opp.programme?.short_name || "").toUpperCase();
      if (selectedProgramme === "GT" && progCode !== "GT" && progCode !== "GTA") return false;
      if (selectedProgramme === "GV" && progCode !== "GV") return false;
      if (selectedProgramme === "GTE" && progCode !== "GTE" && progCode !== "TE") return false;
    }

    // 3. Duration filter (mirroring AIESEC.org: Short 6-12w, Medium 13-26w, Long 27-78w)
    if (selectedDuration !== "ALL") {
      const oppWeeks = opp.duration || (opp.durationBracket === "short" ? 8 : opp.durationBracket === "medium" ? 20 : 36);
      if (selectedDuration === "short" && (oppWeeks < 6 || oppWeeks > 12)) return false;
      if (selectedDuration === "medium" && (oppWeeks < 13 || oppWeeks > 26)) return false;
      if (selectedDuration === "long" && oppWeeks < 27) return false;
    }

    // 4. Country filter
    if (selectedCountry !== "ALL") {
      const oppCountry = (opp.country || "").toLowerCase();
      const oppLoc = (opp.location || "").toLowerCase();
      const targetCountry = selectedCountry.toLowerCase();
      if (!oppCountry.includes(targetCountry) && !oppLoc.includes(targetCountry)) {
        return false;
      }
    }

    // 5. Earliest Start Date filter
    if (filterStartDate) {
      const filterTime = new Date(filterStartDate).getTime();
      if (opp.earliestStartDate) {
        const oppStart = new Date(opp.earliestStartDate).getTime();
        if (!isNaN(oppStart) && !isNaN(filterTime) && oppStart < filterTime) {
          return false;
        }
      }
    }

    return true;
  });

  const resetAllFilters = () => {
    setSearchTerm("");
    setSelectedProgramme("ALL");
    setSelectedDuration("ALL");
    setSelectedCountry("ALL");
    setFilterStartDate("");
  };

  const hasActiveFilters =
    searchTerm !== "" ||
    selectedProgramme !== "ALL" ||
    selectedDuration !== "ALL" ||
    selectedCountry !== "ALL" ||
    filterStartDate !== "";

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

      {/* Filter and Search Bar with AIESEC.org Duration, Country and Start Date filters */}
      <div className="bg-[#161a24] border border-[#242a3a] rounded-2xl p-5 mb-8 space-y-4 shadow-xl">
        {/* Row 1: Search & Programme Selector */}
        <div className="flex flex-col lg:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-5 h-5 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={searchTerm}
              onChange={e => setSearchTerm(e.target.value)}
              placeholder="Rechercher par poste, entreprise, pays, compétence..."
              className="w-full pl-12 pr-4 py-2.5 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white placeholder-gray-500 focus:outline-none focus:border-[#00d4aa] text-sm"
            />
            {searchTerm && (
              <button
                onClick={() => setSearchTerm("")}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white"
              >
                <X className="w-4 h-4" />
              </button>
            )}
          </div>

          {/* Programme Filter Tabs */}
          <div className="flex items-center gap-1 bg-[#0b0d12] border border-[#242a3a] p-1 rounded-xl shrink-0 overflow-x-auto">
            {[
              { id: "ALL", label: "Tous programmes" },
              { id: "GT", label: "Global Talent" },
              { id: "GV", label: "Global Volunteer" },
              { id: "GTE", label: "Global Teacher" },
            ].map(tab => (
              <button
                key={tab.id}
                onClick={() => setSelectedProgramme(tab.id)}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${
                  selectedProgramme === tab.id
                    ? "bg-[#00d4aa] text-black shadow-md font-bold"
                    : "text-gray-400 hover:text-white hover:bg-[#242a3a]"
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>
        </div>

        {/* Row 2: AIESEC.org Duration & Country & Start Date Filters */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-3 pt-3 border-t border-[#242a3a]">
          {/* Duration Filter (like AIESEC.org) */}
          <div className="md:col-span-5 flex flex-col gap-1.5">
            <span className="text-[11px] font-bold text-[#8b95a6] uppercase tracking-wider flex items-center gap-1.5">
              <Clock className="w-3.5 h-3.5 text-[#00d4aa]" />
              Durée (Filtre AIESEC.org)
            </span>
            <div className="grid grid-cols-4 gap-1 bg-[#0b0d12] border border-[#242a3a] p-1 rounded-xl">
              {[
                { id: "ALL", label: "Toutes", title: "Toutes les durées" },
                { id: "short", label: "6-12 sem.", title: "Court terme (6-12 sem.)" },
                { id: "medium", label: "13-26 sem.", title: "Moyen terme (13-26 sem.)" },
                { id: "long", label: "27-78 sem.", title: "Long terme (27-78 sem.)" },
              ].map(d => {
                const isSelected = selectedDuration === d.id;
                return (
                  <button
                    key={d.id}
                    title={d.title}
                    onClick={() => setSelectedDuration(d.id)}
                    className={`py-1.5 px-2 rounded-lg text-xs font-semibold text-center transition-all truncate ${
                      isSelected
                        ? "bg-[#00d4aa] text-black font-bold shadow-sm"
                        : "text-gray-400 hover:text-white hover:bg-[#242a3a]"
                    }`}
                  >
                    {d.label}
                  </button>
                );
              })}
            </div>
          </div>

          {/* Country Filter */}
          <div className="md:col-span-4 flex flex-col gap-1.5">
            <span className="text-[11px] font-bold text-[#8b95a6] uppercase tracking-wider flex items-center gap-1.5">
              <MapPin className="w-3.5 h-3.5 text-[#00d4aa]" />
              Pays / Destination
            </span>
            <div className="relative">
              <select
                value={selectedCountry}
                onChange={e => setSelectedCountry(e.target.value)}
                className="w-full px-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-xs font-medium focus:outline-none focus:border-[#00d4aa] appearance-none cursor-pointer"
              >
                <option value="ALL">🌍 Tous les pays ({matches.length})</option>
                {availableCountries.map(c => (
                  <option key={c.name} value={c.name}>
                    📍 {c.name} ({c.count})
                  </option>
                ))}
              </select>
              <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                <Filter className="w-3.5 h-3.5" />
              </div>
            </div>
          </div>

          {/* Earliest Start Date Filter */}
          <div className="md:col-span-3 flex flex-col gap-1.5">
            <span className="text-[11px] font-bold text-[#8b95a6] uppercase tracking-wider flex items-center gap-1.5">
              <Calendar className="w-3.5 h-3.5 text-[#00d4aa]" />
              Début à partir du...
            </span>
            <div className="relative">
              <input
                type="date"
                value={filterStartDate}
                onChange={e => setFilterStartDate(e.target.value)}
                className="w-full px-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-xs focus:outline-none focus:border-[#00d4aa] [color-scheme:dark]"
              />
              {filterStartDate && (
                <button
                  onClick={() => setFilterStartDate("")}
                  className="absolute right-8 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white"
                  title="Effacer la date"
                >
                  <X className="w-3.5 h-3.5" />
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Active Filters Summary & Reset */}
        {hasActiveFilters && (
          <div className="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-[#242a3a]/60 text-xs text-[#8b95a6]">
            <div className="flex flex-wrap items-center gap-1.5">
              <span className="font-semibold text-gray-400">Filtres actifs :</span>
              {selectedProgramme !== "ALL" && (
                <span className="bg-[#242a3a] text-white px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                  Programme: {selectedProgramme}
                  <button onClick={() => setSelectedProgramme("ALL")}><X className="w-3 h-3 hover:text-red-400" /></button>
                </span>
              )}
              {selectedDuration !== "ALL" && (
                <span className="bg-[#00d4aa]/15 text-[#00d4aa] border border-[#00d4aa]/30 px-2.5 py-0.5 rounded-lg flex items-center gap-1 font-semibold">
                  Durée: {selectedDuration === "short" ? "6-12 sem." : selectedDuration === "medium" ? "13-26 sem." : "27-78 sem."}
                  <button onClick={() => setSelectedDuration("ALL")}><X className="w-3 h-3 hover:text-red-400" /></button>
                </span>
              )}
              {selectedCountry !== "ALL" && (
                <span className="bg-[#242a3a] text-white px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                  Pays: {selectedCountry}
                  <button onClick={() => setSelectedCountry("ALL")}><X className="w-3 h-3 hover:text-red-400" /></button>
                </span>
              )}
              {filterStartDate && (
                <span className="bg-[#242a3a] text-white px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                  Dès: {formatDate(filterStartDate)}
                  <button onClick={() => setFilterStartDate("")}><X className="w-3 h-3 hover:text-red-400" /></button>
                </span>
              )}
              {searchTerm && (
                <span className="bg-[#242a3a] text-white px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                  « {searchTerm} »
                  <button onClick={() => setSearchTerm("")}><X className="w-3 h-3 hover:text-red-400" /></button>
                </span>
              )}
            </div>

            <button
              onClick={resetAllFilters}
              className="text-xs font-semibold text-[#00d4aa] hover:underline flex items-center gap-1"
            >
              Réinitialiser tous les filtres
            </button>
          </div>
        )}
      </div>

      {/* Results Grid */}
      {filteredMatches.length === 0 ? (
        <div className="text-center py-16 bg-[#161a24] border border-[#242a3a] rounded-2xl">
          <Filter className="w-10 h-10 text-gray-500 mx-auto mb-3" />
          <p className="text-white font-semibold text-lg">Aucune opportunité ne correspond à ces critères.</p>
          <p className="text-sm text-[#8b95a6] mt-1">Essayez d&apos;élargir vos filtres de durée, de pays ou de date de début.</p>
          <button
            onClick={resetAllFilters}
            className="mt-4 px-4 py-2 text-xs font-bold bg-[#242a3a] text-white rounded-xl hover:bg-[#2d3548] transition-colors"
          >
            Réinitialiser tous les filtres
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {filteredMatches.map((m, idx) => {
            const isFav = favorites.includes(m.opportunity.id);
            const prog = getProgrammeBadge(m.opportunity.programme?.short_name);
            const durationInfo = getDurationBadgeInfo(m.opportunity.duration);
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
                  {/* Top Badges (Programme & Organisation & Fit Score) */}
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

                  {/* Title */}
                  <h3 className="text-lg font-bold text-white leading-snug mb-2 hover:text-[#00d4aa] transition-colors">
                    {m.opportunity.title}
                  </h3>

                  {/* Key Metadata Badges: Location, Duration (like AIESEC.org), Start Date */}
                  <div className="flex flex-wrap items-center gap-2 mb-3.5 text-xs">
                    {/* Location */}
                    <div className="flex items-center gap-1.5 text-gray-300 bg-[#0b0d12] px-2.5 py-1 rounded-lg border border-[#242a3a]">
                      <MapPin className="w-3.5 h-3.5 text-[#00d4aa] shrink-0" />
                      <span className="font-medium">{m.opportunity.location || m.opportunity.country || "International"}</span>
                    </div>

                    {/* Duration (AIESEC style) */}
                    <div className={`flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs font-semibold ${durationInfo.style}`}>
                      <Clock className="w-3.5 h-3.5 shrink-0" />
                      <span>{durationInfo.label}</span>
                      <span className="text-[10px] opacity-80">({durationInfo.type})</span>
                    </div>

                    {/* Start Date */}
                    {m.opportunity.earliestStartDate && (
                      <div className="flex items-center gap-1.5 text-gray-300 bg-[#0b0d12] px-2.5 py-1 rounded-lg border border-[#242a3a]">
                        <Calendar className="w-3.5 h-3.5 text-[#00d4aa] shrink-0" />
                        <span>Début : {formatDate(m.opportunity.earliestStartDate)}</span>
                      </div>
                    )}
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
