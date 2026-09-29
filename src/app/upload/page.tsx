"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Upload, FileText, Sparkles, CheckCircle2, AlertCircle, ArrowRight, BookOpen, Wrench } from "lucide-react";

export default function UploadPage() {
  const router = useRouter();
  const [file, setFile] = useState<File | null>(null);
  const [skills, setSkills] = useState("");
  const [fieldOfStudy, setFieldOfStudy] = useState("");
  const [isDragOver, setIsDragOver] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleFileDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      const droppedFile = e.dataTransfer.files[0];
      if (droppedFile.type === "application/pdf") {
        setFile(droppedFile);
      } else {
        setError("Please upload a valid PDF document.");
      }
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file && !skills.trim()) {
      setError("Please select a PDF file or enter skills manually.");
      return;
    }

    setError("");
    setLoading(true);

    try {
      const formData = new FormData();
      if (file) formData.append("file", file);
      if (skills) formData.append("skills", skills);
      if (fieldOfStudy) formData.append("fieldOfStudy", fieldOfStudy);

      const res = await fetch("/api/upload-cv", {
        method: "POST",
        body: formData,
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "CV processing failed");

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
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#00d4aa]/15 text-[#00d4aa] text-xs font-semibold mb-3">
          <Sparkles className="w-3.5 h-3.5" />
          <span>Instant AI Matching Engine</span>
        </div>
        <h1 className="text-3xl font-extrabold text-white">Upload Your CV PDF</h1>
        <p className="text-[#8b95a6] mt-2">
          Extract skills automatically or enter details manually to get matched with top AIESEC opportunities worldwide.
        </p>
      </div>

      {error && (
        <div className="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 flex items-center gap-3 text-red-400 text-sm max-w-2xl mx-auto">
          <AlertCircle className="w-5 h-5 shrink-0" />
          <span>{error}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-8 max-w-2xl mx-auto">
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
              <p className="text-xs text-[#8b95a6] mt-1">{(file.size / (1024 * 1024)).toFixed(2)} MB PDF Document</p>
              <button
                type="button"
                onClick={() => setFile(null)}
                className="mt-3 text-xs text-red-400 hover:underline"
              >
                Change PDF File
              </button>
            </div>
          ) : (
            <div className="flex flex-col items-center">
              <div className="w-14 h-14 rounded-2xl bg-[#242a3a] flex items-center justify-center text-[#00d4aa] mb-4">
                <Upload className="w-7 h-7" />
              </div>
              <p className="font-semibold text-white text-lg mb-1">Drag & Drop your CV PDF here</p>
              <p className="text-xs text-[#8b95a6] mb-4">Supports standard PDF resumes up to 10MB</p>
              <label className="px-5 py-2.5 rounded-xl text-sm font-semibold bg-[#242a3a] text-white hover:bg-[#2d3548] cursor-pointer transition-colors">
                Browse Files
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
            <span>Optional: Specify Skills & Background</span>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#8b95a6] uppercase tracking-wider mb-2">
              Field of Study / Major
            </label>
            <div className="relative">
              <BookOpen className="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={fieldOfStudy}
                onChange={e => setFieldOfStudy(e.target.value)}
                placeholder="Computer Science, Business Administration, Marketing..."
                className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white placeholder-gray-500 text-sm focus:outline-none focus:border-[#00d4aa]"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#8b95a6] uppercase tracking-wider mb-2">
              Key Skills (Comma Separated)
            </label>
            <input
              type="text"
              value={skills}
              onChange={e => setSkills(e.target.value)}
              placeholder="React, English, Project Management, Sales, Teaching..."
              className="w-full px-4 py-2.5 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white placeholder-gray-500 text-sm focus:outline-none focus:border-[#00d4aa]"
            />
          </div>
        </div>

        <button
          type="submit"
          disabled={loading}
          className="w-full py-4 rounded-xl font-bold bg-[#00d4aa] text-black hover:bg-[#00c099] transition-all flex items-center justify-center gap-2 shadow-lg shadow-[#00d4aa]/25 disabled:opacity-50 text-base"
        >
          {loading ? (
            <span className="flex items-center gap-2">
              <span className="w-4 h-4 border-2 border-black border-t-transparent rounded-full animate-spin" />
              Analyzing & Matching Opportunities...
            </span>
          ) : (
            <>
              Run AI Opportunity Matcher
              <ArrowRight className="w-5 h-5" />
            </>
          )}
        </button>
      </form>
    </div>
  );
}
