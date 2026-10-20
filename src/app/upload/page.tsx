"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import {
  Upload,
  FileText,
  Sparkles,
  AlertCircle,
  ArrowRight,
  BookOpen,
  Wrench,
  Key,
  ShieldCheck,
  CheckCircle2,
  Cpu,
  Globe,
  Clock,
  Calendar,
  MapPin
} from "lucide-react";

export default function UploadPage() {
  const router = useRouter();
  const [file, setFile] = useState<File | null>(null);
  const [skills, setSkills] = useState("");
  const [fieldOfStudy, setFieldOfStudy] = useState("");
  const [preferredCountry, setPreferredCountry] = useState("all");
  const [customCountry, setCustomCountry] = useState("");
  const [preferredDuration, setPreferredDuration] = useState<"all" | "short" | "medium" | "long">("all");
  const [earliestStartDate, setEarliestStartDate] = useState("");
  const [token, setToken] = useState("e316ebe109dd84ed16734e5161a2d236d0a7e6daf499941f7c110078e3c75493");
  const [showTokenSettings, setShowTokenSettings] = useState(false);
  const [isDragOver, setIsDragOver] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loadingStep, setLoadingStep] = useState(0);
  const [error, setError] = useState("");

  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (loading) {
      setLoadingStep(1);
      interval = setInterval(() => {
        setLoadingStep(prev => (prev < 3 ? prev + 1 : prev));
      }, 1400);
    } else {
      setLoadingStep(0);
    }
    return () => clearInterval(interval);
  }, [loading]);

  const handleFileDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      const droppedFile = e.dataTransfer.files[0];
      if (droppedFile.type === "application/pdf") {
        setFile(droppedFile);
      } else {
        setError("Veuillez déposer un document PDF valide.");
      }
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file && !skills.trim()) {
      setError("Veuillez sélectionner un CV au format PDF ou saisir vos compétences manuellement.");
      return;
    }

    setError("");
    setLoading(true);

    try {
      const formData = new FormData();
      if (file) formData.append("file", file);
      if (skills) formData.append("skills", skills);
      if (fieldOfStudy) formData.append("fieldOfStudy", fieldOfStudy);
      if (token) formData.append("token", token);

      const targetCountry = preferredCountry === "custom" ? customCountry.trim() : preferredCountry;
      if (targetCountry && targetCountry !== "all") {
        formData.append("country", targetCountry);
      }
      if (preferredDuration && preferredDuration !== "all") {
        formData.append("duration", preferredDuration);
      }
      if (earliestStartDate) {
        formData.append("earliestStartDate", earliestStartDate);
      }

      const res = await fetch("/api/upload-cv", {
        method: "POST",
        body: formData,
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Échec du traitement du CV");

      // Save results to session storage for results page
      sessionStorage.setItem("aiesec_match_results", JSON.stringify(data));
      router.push("/results");
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto px-4 py-12">
      <div className="text-center mb-10">
        <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#00d4aa]/15 text-[#00d4aa] text-xs font-semibold mb-3 border border-[#00d4aa]/30">
          <Sparkles className="w-3.5 h-3.5" />
          <span>Synchronisation AIESEC GIS & Analyse IA</span>
        </div>
        <h1 className="text-3xl md:text-4xl font-extrabold text-white">
          Analyse de CV & Opportunités Réelles
        </h1>
        <p className="text-[#8b95a6] mt-2 max-w-xl mx-auto text-sm">
          Téléchargez votre CV pour extraire vos compétences, votre formation et trouver instantanément les opportunités AIESEC réelles qui vous correspondent dans le monde.
        </p>
      </div>

      {error && (
        <div className="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 flex items-center gap-3 text-red-400 text-sm max-w-2xl mx-auto">
          <AlertCircle className="w-5 h-5 shrink-0" />
          <span>{error}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl mx-auto">
        {/* PDF Drag & Drop Area */}
        <div
          onDragOver={e => {
            e.preventDefault();
            setIsDragOver(true);
          }}
          onDragLeave={() => setIsDragOver(false)}
          onDrop={handleFileDrop}
          className={`border-2 border-dashed rounded-2xl p-8 text-center transition-all bg-[#161a24] ${
            isDragOver
              ? "border-[#00d4aa] bg-[#00d4aa]/5 scale-[1.01]"
              : file
              ? "border-[#00d4aa] bg-[#00d4aa]/5"
              : "border-[#242a3a] hover:border-[#8b95a6]"
          }`}
        >
          {file ? (
            <div className="flex flex-col items-center">
              <div className="w-14 h-14 rounded-2xl bg-[#00d4aa]/20 flex items-center justify-center text-[#00d4aa] mb-3">
                <FileText className="w-7 h-7" />
              </div>
              <p className="font-semibold text-white text-base">{file.name}</p>
              <p className="text-xs text-[#8b95a6] mt-1">
                {(file.size / (1024 * 1024)).toFixed(2)} MB • Document PDF prêt
              </p>
              <button
                type="button"
                onClick={() => setFile(null)}
                className="mt-3 text-xs text-red-400 hover:underline"
              >
                Changer de fichier PDF
              </button>
            </div>
          ) : (
            <div className="flex flex-col items-center">
              <div className="w-14 h-14 rounded-2xl bg-[#242a3a] flex items-center justify-center text-[#00d4aa] mb-4">
                <Upload className="w-7 h-7" />
              </div>
              <p className="font-semibold text-white text-lg mb-1">
                Glissez & déposez votre CV PDF ici
              </p>
              <p className="text-xs text-[#8b95a6] mb-4">
                Prend en charge les formats PDF standards jusqu&apos;à 10 Mo
              </p>
              <label className="px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#242a3a] text-white hover:bg-[#2d3548] cursor-pointer transition-colors shadow-sm">
                Parcourir les fichiers
                <input
                  type="file"
                  accept="application/pdf"
                  className="hidden"
                  onChange={e => e.target.files?.[0] && setFile(e.target.files[0])}
                />
              </label>
            </div>
          )}
        </div>

        {/* Manual Skill & Field of Study Entry */}
        <div className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl space-y-4">
          <div className="flex items-center gap-2 border-b border-[#242a3a] pb-3 text-white font-semibold text-sm">
            <Wrench className="w-4 h-4 text-[#00d4aa]" />
            <span>Optionnel : Préciser le domaine ou des compétences manuelles</span>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#8b95a6] uppercase tracking-wider mb-2">
              Domaine d&apos;études / Spécialité
            </label>
            <div className="relative">
              <BookOpen className="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={fieldOfStudy}
                onChange={e => setFieldOfStudy(e.target.value)}
                placeholder="Génie Logiciel, Informatique, Marketing, Finance..."
                className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white placeholder-gray-500 text-sm focus:outline-none focus:border-[#00d4aa]"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#8b95a6] uppercase tracking-wider mb-2">
              Compétences complémentaires (séparées par des virgules)
            </label>
            <input
              type="text"
              value={skills}
              onChange={e => setSkills(e.target.value)}
              placeholder="Java, React, Anglais, UI/UX, Sales, Gestion de projet..."
              className="w-full px-4 py-2.5 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white placeholder-gray-500 text-sm focus:outline-none focus:border-[#00d4aa]"
            />
          </div>
        </div>

        {/* Mobility Preferences (Filters like AIESEC.org) */}
        <div className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl space-y-6">
          <div className="flex items-center justify-between border-b border-[#242a3a] pb-3">
            <div className="flex items-center gap-2 text-white font-semibold text-sm">
              <Globe className="w-4 h-4 text-[#00d4aa]" />
              <span>Filtres de Mobilité (Standard AIESEC.org)</span>
            </div>
            <span className="text-[11px] text-[#00d4aa] font-medium bg-[#00d4aa]/10 px-2.5 py-0.5 rounded-full border border-[#00d4aa]/25">
              Optionnel
            </span>
          </div>

          {/* Duration Filter (like AIESEC.org) */}
          <div>
            <div className="flex items-center justify-between mb-2">
              <label className="text-xs font-semibold text-[#8b95a6] uppercase tracking-wider flex items-center gap-1.5">
                <Clock className="w-3.5 h-3.5 text-[#00d4aa]" />
                Durée de l&apos;expérience (comme AIESEC.org)
              </label>
              <span className="text-[11px] text-gray-400">
                {preferredDuration === "all"
                  ? "Toutes durées acceptées"
                  : preferredDuration === "short"
                  ? "Court : 6 à 12 semaines"
                  : preferredDuration === "medium"
                  ? "Moyen : 13 à 26 semaines"
                  : "Long : 27 à 78 semaines"}
              </span>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
              {[
                { id: "all", label: "Toutes", sub: "Flexible", icon: "🌐" },
                { id: "short", label: "6 - 12 sem.", sub: "Court terme", icon: "⚡" },
                { id: "medium", label: "13 - 26 sem.", sub: "Moyen terme", icon: "🚀" },
                { id: "long", label: "27 - 78 sem.", sub: "Long terme", icon: "🌍" },
              ].map(d => {
                const isSelected = preferredDuration === d.id;
                return (
                  <button
                    key={d.id}
                    type="button"
                    onClick={() => setPreferredDuration(d.id as any)}
                    className={`p-3 rounded-xl border text-left transition-all ${
                      isSelected
                        ? "bg-[#00d4aa]/15 border-[#00d4aa] text-white shadow-md shadow-[#00d4aa]/10"
                        : "bg-[#0b0d12] border-[#242a3a] text-gray-300 hover:border-[#8b95a6]/60"
                    }`}
                  >
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-sm">{d.icon}</span>
                      {isSelected && <span className="w-2 h-2 rounded-full bg-[#00d4aa]" />}
                    </div>
                    <div className="text-xs font-bold leading-tight">{d.label}</div>
                    <div className="text-[10px] text-[#8b95a6] mt-0.5">{d.sub}</div>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Country / Destination Filter */}
          <div>
            <label className="text-xs font-semibold text-[#8b95a6] uppercase tracking-wider block mb-2 flex items-center gap-1.5">
              <MapPin className="w-3.5 h-3.5 text-[#00d4aa]" />
              Destination / Pays souhaité
            </label>

            <div className="flex flex-wrap gap-2 mb-2.5">
              {[
                { id: "all", label: "🌍 Tous les pays" },
                { id: "Egypt", label: "🇪🇬 Égypte" },
                { id: "Romania", label: "🇷🇴 Roumanie" },
                { id: "Germany", label: "🇩🇪 Allemagne" },
                { id: "Tunisia", label: "🇹🇳 Tunisie" },
                { id: "Turkey", label: "🇹🇷 Turquie" },
                { id: "Vietnam", label: "🇻🇳 Vietnam" },
                { id: "India", label: "🇮🇳 Inde" },
                { id: "custom", label: "✏️ Autre pays..." },
              ].map(dest => {
                const isSelected = preferredCountry === dest.id;
                return (
                  <button
                    key={dest.id}
                    type="button"
                    onClick={() => setPreferredCountry(dest.id)}
                    className={`px-3 py-1.5 rounded-xl text-xs font-medium border transition-all ${
                      isSelected
                        ? "bg-[#00d4aa] text-black font-bold border-[#00d4aa] shadow-sm"
                        : "bg-[#0b0d12] border-[#242a3a] text-gray-300 hover:text-white hover:border-[#8b95a6]/60"
                    }`}
                  >
                    {dest.label}
                  </button>
                );
              })}
            </div>

            {preferredCountry === "custom" && (
              <div className="relative mt-2">
                <MapPin className="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  value={customCountry}
                  onChange={e => setCustomCountry(e.target.value)}
                  placeholder="Saisissez un pays (ex: Italie, Espagne, Japon, Colombie...)"
                  className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-[#0b0d12] border border-[#00d4aa] text-white placeholder-gray-500 text-xs focus:outline-none"
                  autoFocus
                />
              </div>
            )}
          </div>

          {/* Earliest Start Date Filter */}
          <div>
            <label className="text-xs font-semibold text-[#8b95a6] uppercase tracking-wider block mb-2 flex items-center gap-1.5">
              <Calendar className="w-3.5 h-3.5 text-[#00d4aa]" />
              Date de début souhaitée (Quand souhaitez-vous commencer ?)
            </label>

            <div className="flex items-center gap-3">
              <div className="relative flex-1">
                <input
                  type="date"
                  value={earliestStartDate}
                  onChange={e => setEarliestStartDate(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-xs focus:outline-none focus:border-[#00d4aa] [color-scheme:dark]"
                />
              </div>

              {earliestStartDate ? (
                <button
                  type="button"
                  onClick={() => setEarliestStartDate("")}
                  className="text-xs text-red-400 hover:text-red-300 px-3 py-2 rounded-xl border border-[#242a3a] hover:bg-[#242a3a]"
                >
                  Effacer la date
                </button>
              ) : (
                <span className="text-[11px] text-[#8b95a6] italic">
                  Dès que possible (flexible)
                </span>
              )}
            </div>
          </div>
        </div>

        {/* GIS Token Settings Toggle */}
        <div className="bg-[#161a24] border border-[#242a3a] p-4 rounded-2xl">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 text-xs text-gray-300">
              <ShieldCheck className="w-4 h-4 text-[#00d4aa]" />
              <span className="font-semibold">Token AIESEC GIS Actif</span>
              <span className="px-2 py-0.5 rounded-full bg-[#00d4aa]/15 text-[#00d4aa] text-[10px] font-bold">
                Connecté
              </span>
            </div>
            <button
              type="button"
              onClick={() => setShowTokenSettings(!showTokenSettings)}
              className="text-xs text-[#8b95a6] hover:text-white underline"
            >
              {showTokenSettings ? "Masquer la clé" : "Modifier le token"}
            </button>
          </div>

          {showTokenSettings && (
            <div className="mt-3 pt-3 border-t border-[#242a3a]">
              <label className="block text-[11px] font-semibold text-[#8b95a6] mb-1.5 uppercase">
                AIESEC Personal Access Token
              </label>
              <div className="relative">
                <Key className="w-4 h-4 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  value={token}
                  onChange={e => setToken(e.target.value)}
                  placeholder="e316ebe109dd84ed16734e5161a2d236d0a7e6daf499941f7c110078e3c75493"
                  className="w-full pl-9 pr-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-xs font-mono focus:outline-none focus:border-[#00d4aa]"
                />
              </div>
              <p className="text-[10px] text-[#8b95a6] mt-1.5">
                Utilisé pour interroger en direct le GraphQL de l&apos;API AIESEC (https://gis-api.aiesec.org/graphql).
              </p>
            </div>
          )}
        </div>

        {/* Submit & Loading Process Indicator */}
        {loading ? (
          <div className="bg-[#161a24] border border-[#00d4aa]/40 p-6 rounded-2xl space-y-3 shadow-lg shadow-[#00d4aa]/10">
            <div className="flex items-center justify-between text-xs text-[#00d4aa] font-bold">
              <span className="flex items-center gap-2">
                <Cpu className="w-4 h-4 animate-spin" />
                Traitement en cours...
              </span>
              <span>{loadingStep}/3</span>
            </div>

            <div className="w-full bg-[#0b0d12] h-2 rounded-full overflow-hidden border border-[#242a3a]">
              <div
                className="bg-[#00d4aa] h-full transition-all duration-700"
                style={{ width: `${(loadingStep / 3) * 100}%` }}
              />
            </div>

            <p className="text-xs text-gray-300">
              {loadingStep === 1 && "1. Analyse du CV : extraction des compétences, langues et formation..."}
              {loadingStep === 2 && "2. Interrogation de l'API AIESEC GIS avec votre token pour les opportunités réelles..."}
              {loadingStep >= 3 && "3. Calcul des scores de matching et classement personnalisé..."}
            </p>
          </div>
        ) : (
          <button
            type="submit"
            disabled={loading}
            className="w-full py-4 rounded-xl font-bold bg-[#00d4aa] text-black hover:bg-[#00c099] transition-all flex items-center justify-center gap-2 shadow-lg shadow-[#00d4aa]/25 text-base hover:scale-[1.01]"
          >
            Lancer l&apos;analyse & Trouver les opportunités réelles
            <ArrowRight className="w-5 h-5" />
          </button>
        )}
      </form>
    </div>
  );
}
