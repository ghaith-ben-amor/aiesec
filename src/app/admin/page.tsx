"use client";

import { useState, useEffect } from "react";
import { Shield, Upload, FileSpreadsheet, CheckCircle2, AlertCircle, RefreshCw, Database, Users, Layers } from "lucide-react";

export default function AdminPage() {
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<{ type: "success" | "error"; text: string } | null>(null);
  const [stats, setStats] = useState({ users: 0, opportunities: 0, eps: 0 });

  const fetchStats = async () => {
    try {
      const res = await fetch("/api/ep");
      const data = await res.json();
      setStats(prev => ({ ...prev, eps: data.eps?.length || 0 }));
    } catch {}
  };

  useEffect(() => {
    fetchStats();
  }, []);

  const handleCsvUpload = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file) return;

    setLoading(true);
    setMessage(null);

    try {
      const formData = new FormData();
      formData.append("file", file);

      const res = await fetch("/api/admin/csv-upload", {
        method: "POST",
        body: formData,
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "CSV Upload failed");

      setMessage({ type: "success", text: `Successfully imported ${data.count} opportunities!` });
      setFile(null);
      fetchStats();
    } catch (err: any) {
      setMessage({ type: "error", text: err.message });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-5xl mx-auto px-4 py-10">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
            <Shield className="w-8 h-8 text-[#037ef3]" />
            Admin Backoffice
          </h1>
          <p className="text-sm text-[#8b95a6] mt-1">
            Manage opportunity catalog CSVs, system configuration, and database settings.
          </p>
        </div>
      </div>

      {/* Summary Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-[#037ef3]/15 flex items-center justify-center text-[#037ef3]">
            <Database className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs text-[#8b95a6] font-semibold uppercase">Catalog Opportunities</p>
            <p className="text-2xl font-extrabold text-white mt-0.5">Active CSV</p>
          </div>
        </div>

        <div className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-[#00d4aa]/15 flex items-center justify-center text-[#00d4aa]">
            <Layers className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs text-[#8b95a6] font-semibold uppercase">Registered EPs</p>
            <p className="text-2xl font-extrabold text-white mt-0.5">{stats.eps}</p>
          </div>
        </div>

        <div className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-purple-500/15 flex items-center justify-center text-purple-400">
            <Users className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs text-[#8b95a6] font-semibold uppercase">System Status</p>
            <p className="text-2xl font-extrabold text-emerald-400 mt-0.5">Online</p>
          </div>
        </div>
      </div>

      {/* CSV Upload Card */}
      <div className="bg-[#161a24] border border-[#242a3a] rounded-2xl p-8 shadow-xl mb-8">
        <div className="flex items-center gap-3 mb-4">
          <FileSpreadsheet className="w-6 h-6 text-[#00d4aa]" />
          <h2 className="text-xl font-bold text-white">Upload Opportunities CSV</h2>
        </div>
        <p className="text-sm text-[#8b95a6] mb-6">
          Upload a CSV file containing AIESEC opportunities (`title, description, skills, location, sourceUrl`). This file takes highest matching priority across the platform.
        </p>

        {message && (
          <div
            className={`mb-6 p-4 rounded-xl border flex items-center gap-3 text-sm ${
              message.type === "success"
                ? "bg-emerald-500/10 border-emerald-500/30 text-emerald-400"
                : "bg-red-500/10 border-red-500/30 text-red-400"
            }`}
          >
            {message.type === "success" ? (
              <CheckCircle2 className="w-5 h-5 shrink-0" />
            ) : (
              <AlertCircle className="w-5 h-5 shrink-0" />
            )}
            <span>{message.text}</span>
          </div>
        )}

        <form onSubmit={handleCsvUpload} className="space-y-4">
          <div className="border-2 border-dashed border-[#242a3a] hover:border-[#00d4aa] rounded-xl p-6 text-center bg-[#0b0d12] transition-colors">
            <input
              type="file"
              accept=".csv"
              required
              id="csv-input"
              className="hidden"
              onChange={e => e.target.files?.[0] && setFile(e.target.files[0])}
            />
            <label htmlFor="csv-input" className="cursor-pointer flex flex-col items-center">
              <Upload className="w-8 h-8 text-gray-500 mb-2" />
              <span className="text-sm font-semibold text-white">
                {file ? file.name : "Click to select CSV File"}
              </span>
              <span className="text-xs text-[#8b95a6] mt-1">Accepts standard .csv format</span>
            </label>
          </div>

          <button
            type="submit"
            disabled={loading || !file}
            className="w-full py-3.5 rounded-xl font-bold bg-[#00d4aa] text-black hover:bg-[#00c099] transition-all disabled:opacity-50 flex items-center justify-center gap-2"
          >
            {loading ? "Importing CSV Data..." : "Upload & Sync Catalog"}
          </button>
        </form>
      </div>
    </div>
  );
}
