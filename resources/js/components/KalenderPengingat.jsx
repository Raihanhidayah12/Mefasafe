import { useState, useEffect, useCallback } from "react";
import { createPortal } from "react-dom";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import {
  ArrowLeft, Calendar, Plus, Bell, Check, Trash2,
  Clock, ChevronLeft, ChevronRight, Pill, Stethoscope,
  Syringe, AlarmClock, X, Loader2, AlertCircle, CreditCard,
} from "lucide-react";

const API = "/api/v1";

const CATEGORY_META = {
  kontrol: { label: "Kontrol",  icon: Stethoscope, color: "from-blue-500 to-cyan-500",    bg: "bg-blue-50",   text: "text-blue-700",   border: "border-blue-200"   },
  obat:    { label: "Obat",     icon: Pill,         color: "from-purple-500 to-pink-500",  bg: "bg-purple-50", text: "text-purple-700", border: "border-purple-200" },
  vaksin:  { label: "Vaksin",   icon: Syringe,      color: "from-green-500 to-emerald-500",bg: "bg-green-50",  text: "text-green-700",  border: "border-green-200"  },
  premi:   { label: "Premi",    icon: CreditCard,   color: "from-orange-500 to-red-500",   bg: "bg-orange-50", text: "text-orange-700", border: "border-orange-200" },
  lainnya: { label: "Lainnya",  icon: AlarmClock,   color: "from-orange-500 to-amber-500", bg: "bg-orange-50", text: "text-orange-700", border: "border-orange-200" },
};

const DAYS_ID  = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
const MONTHS_ID = [
  "Januari","Februari","Maret","April","Mei","Juni",
  "Juli","Agustus","September","Oktober","November","Desember",
];

function pad(n) { return String(n).padStart(2, "0"); }
function toDateStr(d) { return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }
function reminderDateStr(value) {
  if (!value) return "";

  if (typeof value === "string") {
    const datePart = value.slice(0, 10);
    if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) return datePart;
  }

  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? "" : toDateStr(date);
}

function parseReminderDate(value) {
  const dateStr = reminderDateStr(value);
  if (!dateStr) return null;

  const date = new Date(`${dateStr}T00:00:00`);
  return Number.isNaN(date.getTime()) ? null : date;
}

/* ─── Modal Tambah / Edit ─────────────────────────────────────────── */
function ReminderModal({ initial, onClose, onSave, userId }) {
  const [form, setForm] = useState({
    title:         initial?.title         ?? "",
    description:   initial?.description   ?? "",
    reminder_date: reminderDateStr(initial?.reminder_date) || toDateStr(new Date()),
    reminder_time: initial?.reminder_time ? initial.reminder_time.slice(0,5)  : "",
    category:      initial?.category      ?? "kontrol",
    repeat:        initial?.repeat        ?? "none",
  });
  const [saving, setSaving] = useState(false);
  const [error,  setError]  = useState("");

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.title.trim()) { setError("Judul wajib diisi."); return; }
    setSaving(true);
    setError("");
    try {
      const token = localStorage.getItem("mefasafe_token");
      const headers = { Authorization: `Bearer ${token}` };
      const payload = { ...form, user_id: userId };
      if (initial?.id) {
        await axios.put(`${API}/reminders/${initial.id}`, payload, { headers });
      } else {
        await axios.post(`${API}/reminders`, payload, { headers });
      }
      onSave();
    } catch (err) {
      setError(err.response?.data?.message ?? "Gagal menyimpan pengingat.");
    } finally {
      setSaving(false);
    }
  };

  return createPortal(
    <div className="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div className="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden animate-slide-up">
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h2 className="text-lg font-bold text-gray-900">
            {initial?.id ? "Edit Pengingat" : "Tambah Pengingat"}
          </h2>
          <button onClick={onClose} className="p-2 rounded-xl hover:bg-gray-100 transition-colors">
            <X className="w-5 h-5 text-gray-500" />
          </button>
        </div>
        <form onSubmit={handleSubmit} className="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
          {error && (
            <div className="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
              <AlertCircle className="w-4 h-4 shrink-0" />{error}
            </div>
          )}
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">Judul *</label>
            <input
              type="text" value={form.title} onChange={e => set("title", e.target.value)}
              placeholder="Contoh: Kontrol gula darah"
              className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-400 text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label>
            <textarea
              value={form.description} onChange={e => set("description", e.target.value)}
              rows={2} placeholder="Catatan tambahan (opsional)"
              className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-400 text-sm resize-none"
            />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">Tanggal *</label>
              <input
                type="date" value={form.reminder_date} onChange={e => set("reminder_date", e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-400 text-sm"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">Waktu</label>
              <input
                type="time" value={form.reminder_time} onChange={e => set("reminder_time", e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-400 text-sm"
              />
            </div>
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">Kategori</label>
            <div className="grid grid-cols-2 gap-2">
              {Object.entries(CATEGORY_META).map(([key, meta]) => {
                const Icon = meta.icon;
                return (
                  <button key={key} type="button" onClick={() => set("category", key)}
                    className={`flex items-center gap-2 px-3 py-2.5 rounded-xl border text-sm font-medium transition-all ${
                      form.category === key
                        ? `bg-gradient-to-r ${meta.color} text-white border-transparent shadow-md`
                        : `${meta.bg} ${meta.text} ${meta.border} hover:shadow-sm`
                    }`}>
                    <Icon className="w-4 h-4" />{meta.label}
                  </button>
                );
              })}
            </div>
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">Pengulangan</label>
            <select value={form.repeat} onChange={e => set("repeat", e.target.value)}
              className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-400 text-sm bg-white">
              <option value="none">Tidak berulang</option>
              <option value="daily">Setiap hari</option>
              <option value="weekly">Setiap minggu</option>
              <option value="monthly">Setiap bulan</option>
            </select>
          </div>
          <button type="submit" disabled={saving}
            className="w-full py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-300 flex items-center justify-center gap-2 disabled:opacity-60">
            {saving ? <Loader2 className="w-5 h-5 animate-spin" /> : <Bell className="w-5 h-5" />}
            {saving ? "Menyimpan..." : (initial?.id ? "Simpan Perubahan" : "Buat Pengingat")}
          </button>
        </form>
      </div>
    </div>,
    document.body
  );
}

/* ─── Komponen Utama ──────────────────────────────────────────────── */
export default function KalenderPengingat({ user }) {
  const navigate = useNavigate();
  const today = new Date();

  const [viewDate,   setViewDate]   = useState(new Date(today.getFullYear(), today.getMonth(), 1));
  const [selected,   setSelected]   = useState(toDateStr(today));
  const [reminders,  setReminders]  = useState([]);
  const [loading,    setLoading]    = useState(true);
  const [modal,      setModal]      = useState(null); // null | {} | reminder obj

  /* ── fetch semua reminder ── */
  const fetchReminders = useCallback(async () => {
    try {
      const token = localStorage.getItem("mefasafe_token");
      const userId = user?.id;
      const res = await axios.get(`${API}/reminders`, {
        headers: { Authorization: `Bearer ${token}` },
        params: { user_id: userId },
      });
      if (res.data.success) setReminders(res.data.data);
    } catch (e) {
      console.error("Fetch reminders error:", e);
    } finally {
      setLoading(false);
    }
  }, [user]);

  useEffect(() => {
    fetchReminders();
  }, [fetchReminders]);

  /* ── helpers kalender ── */
  const year  = viewDate.getFullYear();
  const month = viewDate.getMonth();

  const firstDay  = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();

  const prevMonth = () => setViewDate(new Date(year, month - 1, 1));
  const nextMonth = () => setViewDate(new Date(year, month + 1, 1));

  /* reminder per tanggal (map dateStr → array) */
  const reminderMap = {};
  reminders.forEach(r => {
    const d = reminderDateStr(r.reminder_date);
    if (!d) return;
    if (!reminderMap[d]) reminderMap[d] = [];
    reminderMap[d].push(r);
  });

  /* reminder untuk tanggal yang dipilih */
  const selectedReminders = reminderMap[selected] ?? [];

  /* ── mark done ── */
  const markDone = async (id, done) => {
    try {
      const token = localStorage.getItem("mefasafe_token");
      await axios.put(`${API}/reminders/${id}`, { is_done: done, user_id: user?.id }, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setReminders(prev => prev.map(r => r.id === id ? { ...r, is_done: done } : r));
    } catch (e) { console.error(e); }
  };

  /* ── delete ── */
  const deleteReminder = async (id) => {
    if (!window.confirm("Hapus pengingat ini?")) return;
    try {
      const token = localStorage.getItem("mefasafe_token");
      await axios.delete(`${API}/reminders/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
        params: { user_id: user?.id },
      });
      setReminders(prev => prev.filter(r => r.id !== id));
    } catch (e) { console.error(e); }
  };

  const onModalSave = () => {
    setModal(null);
    fetchReminders();
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50/30 to-amber-50/50 relative">
      {/* Background orbs */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-[-100px] right-[-80px] h-72 w-72 rounded-full bg-orange-200/30 blur-3xl" />
        <div className="absolute bottom-[-100px] left-[-60px] h-80 w-80 rounded-full bg-amber-200/30 blur-3xl" />
      </div>

      <div className="relative max-w-5xl mx-auto px-4 py-6 md:px-8 md:py-8 space-y-6">

        {/* Header */}
        <div className="flex items-center gap-4">
          <button onClick={() => navigate("/home")}
            className="p-2.5 rounded-xl bg-white/80 border border-gray-100 shadow-sm hover:shadow-md hover:bg-white transition-all">
            <ArrowLeft className="w-5 h-5 text-gray-600" />
          </button>
          <div>
            <p className="text-xs font-bold uppercase tracking-widest text-orange-500">Kalender</p>
            <h1 className="text-2xl md:text-3xl font-black text-gray-900">Pengingat Kesehatan</h1>
          </div>
          <button onClick={() => setModal({})}
            className="ml-auto flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all text-sm">
            <Plus className="w-4 h-4" />
            <span className="hidden sm:inline">Tambah</span>
          </button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-6">
          {/* ── Kalender ── */}
          <div className="rounded-3xl bg-white/90 backdrop-blur-xl border border-white/70 shadow-xl p-5 md:p-6">
            {/* Nav bulan */}
            <div className="flex items-center justify-between mb-5">
              <button onClick={prevMonth}
                className="p-2 rounded-xl hover:bg-gray-100 transition-colors">
                <ChevronLeft className="w-5 h-5 text-gray-600" />
              </button>
              <h2 className="text-lg font-black text-gray-900">
                {MONTHS_ID[month]} {year}
              </h2>
              <button onClick={nextMonth}
                className="p-2 rounded-xl hover:bg-gray-100 transition-colors">
                <ChevronRight className="w-5 h-5 text-gray-600" />
              </button>
            </div>

            {/* Nama hari */}
            <div className="grid grid-cols-7 mb-2">
              {DAYS_ID.map(d => (
                <div key={d} className="text-center text-xs font-bold text-gray-400 py-1">{d}</div>
              ))}
            </div>

            {/* Grid tanggal */}
            <div className="grid grid-cols-7 gap-1">
              {/* Padding awal */}
              {Array.from({ length: firstDay }).map((_, i) => (
                <div key={`pad-${i}`} />
              ))}
              {Array.from({ length: daysInMonth }).map((_, i) => {
                const day     = i + 1;
                const dateStr = `${year}-${pad(month+1)}-${pad(day)}`;
                const isToday = dateStr === toDateStr(today);
                const isSel   = dateStr === selected;
                const dots    = reminderMap[dateStr] ?? [];
                const hasDone = dots.some(r => r.is_done);
                const hasPend = dots.some(r => !r.is_done);
                const isPast  = dateStr < toDateStr(today) && !isToday;

                return (
                  <button key={day}
                    onClick={() => !isPast && setSelected(dateStr)}
                    disabled={isPast}
                    className={`relative flex flex-col items-center justify-center rounded-xl py-2 transition-all duration-200
                      ${isPast  ? "text-gray-300 cursor-not-allowed opacity-40" :
                        isSel   ? "bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-lg scale-105" :
                        isToday ? "bg-orange-50 border-2 border-orange-400 text-orange-700 font-bold" :
                                  "hover:bg-gray-50 text-gray-700 cursor-pointer"}`}>
                    <span className="text-sm font-semibold">{day}</span>
                    {!isPast && dots.length > 0 && (
                      <div className="flex gap-0.5 mt-0.5">
                        {hasPend && <span className={`w-1.5 h-1.5 rounded-full ${isSel ? "bg-white" : "bg-orange-400"}`} />}
                        {hasDone && <span className={`w-1.5 h-1.5 rounded-full ${isSel ? "bg-white/60" : "bg-green-400"}`} />}
                      </div>
                    )}
                  </button>
                );
              })}
            </div>

            {/* Legenda */}
            <div className="flex items-center gap-4 mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
              <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-orange-400 inline-block" />Belum selesai</span>
              <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-green-400 inline-block" />Selesai</span>
              <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-full border-2 border-orange-400 inline-block" />Hari ini</span>
            </div>
          </div>

          {/* ── Panel Detail Tanggal ── */}
          <div className="rounded-3xl bg-white/90 backdrop-blur-xl border border-white/70 shadow-xl p-5 flex flex-col">
            <div className="flex items-center justify-between mb-4">
              <div>
                <p className="text-xs font-bold uppercase tracking-widest text-gray-400">Jadwal</p>
                <h3 className="text-base font-black text-gray-900">
                  {new Date(selected + "T00:00:00").toLocaleDateString("id-ID", {
                    weekday: "long", day: "numeric", month: "long",
                  })}
                </h3>
              </div>
              {selected >= toDateStr(today) && (
                <button onClick={() => setModal({ reminder_date: selected })}
                  className="p-2 rounded-xl bg-orange-50 text-orange-600 hover:bg-orange-100 transition-colors">
                  <Plus className="w-5 h-5" />
                </button>
              )}
            </div>

            {loading ? (
              <div className="flex-1 flex items-center justify-center text-gray-400">
                <Loader2 className="w-6 h-6 animate-spin" />
              </div>
            ) : selectedReminders.length === 0 ? (
              <div className="flex-1 flex flex-col items-center justify-center text-center py-8">
                <div className="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                  <Calendar className="w-7 h-7 text-gray-300" />
                </div>
                <p className="text-sm font-semibold text-gray-500">Tidak ada pengingat</p>
                <p className="text-xs text-gray-400 mt-1">Klik + untuk menambahkan</p>
              </div>
            ) : (
              <div className="space-y-3 overflow-y-auto flex-1 pr-1">
                {selectedReminders.map(r => {
                  const meta = CATEGORY_META[r.category] ?? CATEGORY_META.lainnya;
                  const Icon = meta.icon;
                  return (
                    <div key={r.id}
                      className={`rounded-2xl border p-3.5 transition-all ${
                        r.is_done
                          ? "bg-gray-50 border-gray-100 opacity-60"
                          : `${meta.bg} ${meta.border}`
                      }`}>
                      <div className="flex items-start gap-3">
                        <div className={`p-2 rounded-xl bg-gradient-to-br ${meta.color} text-white shrink-0`}>
                          <Icon className="w-4 h-4" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className={`text-sm font-bold truncate ${r.is_done ? "line-through text-gray-400" : "text-gray-900"}`}>
                            {r.title}
                          </p>
                          {r.description && (
                            <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{r.description}</p>
                          )}
                          <div className="flex items-center gap-2 mt-1.5 flex-wrap">
                            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${meta.bg} ${meta.text}`}>
                              {meta.label}
                            </span>
                            {r.reminder_time && (
                              <span className="flex items-center gap-1 text-[10px] text-gray-500">
                                <Clock className="w-3 h-3" />{r.reminder_time.slice(0,5)}
                              </span>
                            )}
                            {r.repeat !== "none" && (
                              <span className="text-[10px] text-gray-400">
                                🔁 {r.repeat === "daily" ? "Harian" : r.repeat === "weekly" ? "Mingguan" : "Bulanan"}
                              </span>
                            )}
                          </div>
                        </div>
                      </div>
                      <div className="flex items-center gap-2 mt-3 pt-2 border-t border-white/60">
                        <button onClick={() => markDone(r.id, !r.is_done)}
                          className={`flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                            r.is_done
                              ? "bg-gray-200 text-gray-600 hover:bg-gray-300"
                              : "bg-green-500 text-white hover:bg-green-600"
                          }`}>
                          <Check className="w-3.5 h-3.5" />
                          {r.is_done ? "Batal Selesai" : "Tandai Selesai"}
                        </button>
                        <button onClick={() => setModal(r)}
                          className="p-1.5 rounded-lg bg-white/80 text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors border border-gray-100">
                          <Calendar className="w-3.5 h-3.5" />
                        </button>
                        <button onClick={() => deleteReminder(r.id)}
                          className="p-1.5 rounded-lg bg-white/80 text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors border border-gray-100">
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* ── Semua Pengingat Mendatang ── */}
        <div className="rounded-3xl bg-white/90 backdrop-blur-xl border border-white/70 shadow-xl p-5 md:p-6">
          <h3 className="text-base font-black text-gray-900 mb-4 flex items-center gap-2">
            <AlarmClock className="w-5 h-5 text-orange-500" />
            Semua Pengingat Aktif
          </h3>
          {loading ? (
            <div className="flex justify-center py-8 text-gray-400"><Loader2 className="w-6 h-6 animate-spin" /></div>
          ) : reminders.filter(r => !r.is_done).length === 0 ? (
            <p className="text-sm text-gray-400 text-center py-6">Belum ada pengingat aktif.</p>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
              {reminders
                .filter(r => !r.is_done)
                .sort((a, b) => reminderDateStr(a.reminder_date).localeCompare(reminderDateStr(b.reminder_date)))
                .map(r => {
                  const meta = CATEGORY_META[r.category] ?? CATEGORY_META.lainnya;
                  const Icon = meta.icon;
                  const rDateStr = reminderDateStr(r.reminder_date);
                  const rDate = parseReminderDate(r.reminder_date);
                  const isRToday = rDate ? toDateStr(rDate) === toDateStr(today) : false;
                  const isPast  = rDate ? rDate < today && !isRToday : false;
                  return (
                    <button key={r.id}
                      onClick={() => {
                        if (!rDate || !rDateStr) return;
                        setSelected(rDateStr);
                        setViewDate(new Date(rDate.getFullYear(), rDate.getMonth(), 1));
                      }}
                      className={`text-left rounded-2xl border p-3.5 transition-all hover:-translate-y-0.5 hover:shadow-md ${
                        isPast ? "bg-gray-50 border-gray-100 opacity-60" :
                        isRToday ? `${meta.bg} ${meta.border} ring-2 ring-orange-400` :
                        `${meta.bg} ${meta.border}`
                      }`}>
                      <div className="flex items-center gap-2 mb-2">
                        <div className={`p-1.5 rounded-lg bg-gradient-to-br ${meta.color} text-white`}>
                          <Icon className="w-3.5 h-3.5" />
                        </div>
                        {isRToday && (
                          <span className="text-[10px] font-bold px-2 py-0.5 bg-orange-500 text-white rounded-full">HARI INI</span>
                        )}
                        {isPast && (
                          <span className="text-[10px] font-bold px-2 py-0.5 bg-gray-400 text-white rounded-full">LEWAT</span>
                        )}
                      </div>
                      <p className="text-sm font-bold text-gray-900 truncate">{r.title}</p>
                      <p className="text-xs text-gray-500 mt-0.5">
                        {rDate ? rDate.toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" }) : "Tanggal belum valid"}
                        {r.reminder_time ? ` • ${r.reminder_time.slice(0,5)}` : ""}
                      </p>
                    </button>
                  );
                })}
            </div>
          )}
        </div>
      </div>

      {/* Modal */}
      {modal !== null && (
        <ReminderModal
          initial={modal}
          onClose={() => setModal(null)}
          onSave={onModalSave}
          userId={user?.id}
        />
      )}

      <style>{`
        @keyframes slide-up {
          from { opacity: 0; transform: translateY(40px); }
          to   { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up { animation: slide-up 0.3s ease-out forwards; }
      `}</style>
    </div>
  );
}
