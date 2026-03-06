import React, { useState, useEffect, useRef } from 'react';
import { Trash2, Plus, Printer, Save, CheckCircle, Edit3, X, Download, FileText } from 'lucide-react';

const App = () => {
  const [agendas, setAgendas] = useState([]);
  const [showSavedStatus, setShowSavedStatus] = useState(false);
  const [isEditing, setIsEditing] = useState(null);
  const isInitialMount = useRef(true);

  const [formData, setFormData] = useState({
    hariTanggal: '',
    waktu: '',
    kegiatanLokasi: '',
    namaDitinjau: '',
    keterangan: 'RUTILAHU'
  });

  // 1. Load data dari localStorage saat startup
  useEffect(() => {
    const savedData = localStorage.getItem('agenda_rambatan_wetan');
    if (savedData) {
      setAgendas(JSON.parse(savedData));
    }
  }, []);

  // 2. Auto-save ke localStorage (Notif hanya muncul saat ada perubahan data nyata)
  useEffect(() => {
    localStorage.setItem('agenda_rambatan_wetan', JSON.stringify(agendas));
    
    if (isInitialMount.current) {
      isInitialMount.current = false;
    } else {
      setShowSavedStatus(true);
      const timer = setTimeout(() => setShowSavedStatus(false), 2000);
      return () => clearTimeout(timer);
    }
  }, [agendas]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!formData.hariTanggal || !formData.namaDitinjau) {
      alert("Mohon isi minimal Tanggal dan Nama warga.");
      return;
    }

    if (isEditing) {
      setAgendas(agendas.map(item => item.id === isEditing ? { ...formData, id: isEditing } : item));
      setIsEditing(null);
    } else {
      const newAgenda = { id: Date.now(), ...formData };
      setAgendas([...agendas, newAgenda]);
    }

    // Reset Form
    setFormData({
      hariTanggal: '',
      waktu: '',
      kegiatanLokasi: '',
      namaDitinjau: '',
      keterangan: 'RUTILAHU'
    });
  };

  const startEdit = (item) => {
    setIsEditing(item.id);
    setFormData({ ...item });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const cancelEdit = () => {
    setIsEditing(null);
    setFormData({
      hariTanggal: '',
      waktu: '',
      kegiatanLokasi: '',
      namaDitinjau: '',
      keterangan: 'RUTILAHU'
    });
  };

  const deleteAgenda = (id) => {
    if (window.confirm("Hapus data agenda ini?")) {
      setAgendas(agendas.filter(item => item.id !== id));
    }
  };

  // 3. Fungsi Ekspor Excel (CSV) dengan perbaikan format Excel
  const exportToCSV = () => {
    if (agendas.length === 0) return alert("Tidak ada data untuk diekspor.");
    
    const headers = ["No", "Hari/Tanggal", "Waktu", "Kegiatan/Lokasi", "Nama Ditinjau", "Keterangan"];
    const csvContent = "\uFEFF" + [
      headers.join(","),
      ...agendas.map((item, idx) => [
        idx + 1,
        item.hariTanggal,
        item.waktu || "-",
        `"${item.kegiatanLokasi.replace(/"/g, '""')}"`,
        `"${item.namaDitinjau.replace(/"/g, '""')}"`,
        item.keterangan
      ].join(","))
    ].join("\n");
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Rekap_Agenda_Desa_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // 4. Helper Format Tanggal Indonesia
  const formatTanggalIndo = (dateStr) => {
    if (!dateStr) return '-';
    try {
      return new Date(dateStr).toLocaleDateString('id-ID', { 
        weekday: 'long', 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric' 
      });
    } catch { return dateStr; }
  };

  return (
    <div className="min-h-screen bg-slate-50 p-4 md:p-8 font-sans text-slate-900">
      
      {/* HEADER RESMI (KOP SURAT) */}
      <div className="max-w-6xl mx-auto mb-8 text-center border-b-4 border-double border-black pb-4 print:mb-2">
        <h2 className="text-lg md:text-xl font-bold uppercase leading-tight">Pemerintah Kabupaten Indramayu</h2>
        <h2 className="text-lg md:text-xl font-bold uppercase leading-tight">Kecamatan Sindang</h2>
        <h1 className="text-3xl md:text-5xl font-black uppercase tracking-tighter my-2">
          Desa Rambatan Wetan
        </h1>
        <p className="text-xs italic hidden print:block">Alamat: Jl. Raya Rambatan Wetan No. 01, Kec. Sindang, Indramayu 45221</p>
        <div className="mt-6 border-t-2 border-black pt-2">
          <h3 className="text-2xl font-bold underline decoration-1 underline-offset-4 tracking-widest">AGENDA PELAYANAN MASYARAKAT</h3>
        </div>
      </div>

      {/* FORM INPUT (HIDDEN SAAT PRINT) */}
      <div className="max-w-6xl mx-auto bg-white p-6 rounded-2xl shadow-xl mb-8 print:hidden border border-slate-200">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-xl font-bold flex items-center gap-2 text-indigo-700">
            {isEditing ? <Edit3 size={24} /> : <Plus size={24} />} 
            {isEditing ? 'Ubah Data Agenda' : 'Input Agenda Baru'}
          </h3>
          {isEditing && (
            <button onClick={cancelEdit} className="text-sm bg-red-50 text-red-600 px-3 py-1 rounded-full hover:bg-red-100 transition-all flex items-center gap-1 font-bold">
              <X size={16} /> Batalkan Perubahan
            </button>
          )}
        </div>

        <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div className="flex flex-col gap-1">
            <label className="text-xs font-black text-slate-400 uppercase tracking-wider">Hari / Tanggal</label>
            <input type="date" name="hariTanggal" value={formData.hariTanggal} onChange={handleInputChange} className="p-3 border-2 border-slate-100 rounded-xl focus:border-indigo-500 outline-none bg-slate-50 transition-all font-medium" />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs font-black text-slate-400 uppercase tracking-wider">Waktu</label>
            <input type="time" name="waktu" value={formData.waktu} onChange={handleInputChange} className="p-3 border-2 border-slate-100 rounded-xl focus:border-indigo-500 outline-none bg-slate-50 transition-all font-medium" />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs font-black text-slate-400 uppercase tracking-wider">Kegiatan & Lokasi</label>
            <input type="text" name="kegiatanLokasi" placeholder="Misal: Peninjauan RUTILAHU Blok B" value={formData.kegiatanLokasi} onChange={handleInputChange} className="p-3 border-2 border-slate-100 rounded-xl focus:border-indigo-500 outline-none transition-all" />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs font-black text-slate-400 uppercase tracking-wider">Nama Warga</label>
            <input type="text" name="namaDitinjau" placeholder="Nama warga yang ditinjau" value={formData.namaDitinjau} onChange={handleInputChange} className="p-3 border-2 border-slate-100 rounded-xl focus:border-indigo-500 outline-none transition-all" />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs font-black text-slate-400 uppercase tracking-wider">Kategori</label>
            <select name="keterangan" value={formData.keterangan} onChange={handleInputChange} className="p-3 border-2 border-slate-100 rounded-xl focus:border-indigo-500 outline-none bg-white transition-all font-bold text-indigo-900">
              <option value="RUTILAHU">RUTILAHU</option>
              <option value="ODGJ">ODGJ</option>
              <option value="WARGA SAKIT">WARGA SAKIT</option>
              <option value="WARGA KURANG MAMPU">WARGA KURANG MAMPU</option>
            </select>
          </div>
          <div className="flex items-end">
            <button type="submit" className={`w-full ${isEditing ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-indigo-600 hover:bg-indigo-700'} text-white font-black py-3 rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg active:scale-95`}>
              {isEditing ? <Save size={20} /> : <Plus size={20} />} 
              {isEditing ? 'Update Agenda' : 'Simpan ke Daftar'}
            </button>
          </div>
        </form>
      </div>

      {/* TOOLBAR AKSI (HIDDEN SAAT PRINT) */}
      <div className="max-w-6xl mx-auto mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
        <div className={`flex items-center gap-2 text-sm font-bold px-4 py-2 rounded-full transition-all duration-500 shadow-inner ${showSavedStatus ? 'bg-emerald-100 text-emerald-700' : 'bg-white text-slate-400 border border-slate-200'}`}>
          {showSavedStatus ? <CheckCircle size={18} className="animate-pulse" /> : <Save size={18} />}
          {showSavedStatus ? 'Data Tersimpan Otomatis!' : 'Data Tersimpan di Browser'}
        </div>
        
        <div className="flex gap-3">
          <button onClick={exportToCSV} className="bg-white border-2 border-emerald-500 text-emerald-600 hover:bg-emerald-500 hover:text-white px-5 py-2.5 rounded-xl flex items-center gap-2 transition-all font-bold shadow-sm">
            <Download size={20} /> Ekspor Excel
          </button>
          <button onClick={() => window.print()} className="bg-slate-900 hover:bg-black text-white px-6 py-2.5 rounded-xl flex items-center gap-2 transition-all shadow-xl font-bold">
            <Printer size={20} /> Cetak Agenda (A4)
          </button>
        </div>
      </div>

      {/* TABEL DATA UTAMA */}
      <div className="max-w-6xl mx-auto bg-white shadow-2xl rounded-2xl overflow-hidden print:shadow-none border border-slate-200">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse">
            <thead>
              <tr className="bg-slate-900 text-white print:bg-slate-100 print:text-black">
                <th className="p-4 border border-slate-700 text-center w-12 text-xs font-black uppercase">No</th>
                <th className="p-4 border border-slate-700 text-left text-xs font-black uppercase">Hari / Tanggal</th>
                <th className="p-4 border border-slate-700 text-center text-xs font-black uppercase w-24">Waktu</th>
                <th className="p-4 border border-slate-700 text-left text-xs font-black uppercase">Kegiatan & Lokasi</th>
                <th className="p-4 border border-slate-700 text-left text-xs font-black uppercase">Nama Warga</th>
                <th className="p-4 border border-slate-700 text-center text-xs font-black uppercase">Kategori</th>
                <th className="p-4 border border-slate-700 text-center print:hidden w-32 text-xs font-black uppercase">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {agendas.length > 0 ? (
                agendas.map((item, index) => (
                  <tr key={item.id} className="hover:bg-indigo-50/30 transition-colors odd:bg-white even:bg-slate-50/50">
                    <td className="p-4 border border-slate-200 text-center font-bold text-slate-400">{index + 1}</td>
                    <td className="p-4 border border-slate-200 font-bold text-slate-700">{formatTanggalIndo(item.hariTanggal)}</td>
                    <td className="p-4 border border-slate-200 text-center font-mono text-sm">{item.waktu || '-'}</td>
                    <td className="p-4 border border-slate-200 text-slate-600">{item.kegiatanLokasi}</td>
                    <td className="p-4 border border-slate-200 font-black text-indigo-900">{item.namaDitinjau}</td>
                    <td className="p-4 border border-slate-200 text-center">
                      <span className={`px-3 py-1 rounded-lg text-[10px] font-black uppercase border-2 shadow-sm inline-block whitespace-nowrap ${
                        item.keterangan === 'RUTILAHU' ? 'bg-orange-50 text-orange-700 border-orange-200' :
                        item.keterangan === 'ODGJ' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                        item.keterangan === 'WARGA SAKIT' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                        'bg-emerald-50 text-emerald-700 border-emerald-200'
                      }`}>
                        {item.keterangan}
                      </span>
                    </td>
                    <td className="p-4 border border-slate-200 text-center print:hidden">
                      <div className="flex justify-center gap-2">
                        <button onClick={() => startEdit(item)} className="p-2 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-all" title="Edit">
                          <Edit3 size={20} />
                        </button>
                        <button onClick={() => deleteAgenda(item.id)} className="p-2 text-rose-600 hover:bg-rose-100 rounded-lg transition-all" title="Hapus">
                          <Trash2 size={20} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="7" className="p-24 text-center text-slate-300 font-medium bg-white italic border border-slate-100">
                    <FileText size={48} className="mx-auto mb-3 opacity-20" />
                    Belum ada agenda terdaftar.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* FOOTER PENGESAHAN (ONLY FOR PRINT) */}
      <div className="hidden print:block max-w-6xl mx-auto mt-16">
        <div className="grid grid-cols-2 gap-24 text-center font-bold">
          <div className="flex flex-col items-center">
            <p className="mb-24">Mengetahui,</p>
            <p className="uppercase border-b-2 border-black pb-1 w-64">Kepala Desa Rambatan Wetan</p>
          </div>
          <div className="flex flex-col items-center">
            <p className="mb-24">Rambatan Wetan, {new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
            <p className="uppercase border-b-2 border-black pb-1 w-64">Sekretaris Desa</p>
          </div>
        </div>
      </div>

      {/* PRINT STYLING */}
      <style>{`
        @media print {
          @page { size: A4 landscape; margin: 12mm; }
          body { background: white !important; font-size: 10pt; }
          .max-w-6xl { max-width: 100% !important; margin: 0 !important; }
          thead { display: table-header-group !important; }
          tr { page-break-inside: avoid !important; }
          th, td { border: 1px solid black !important; padding: 6px !important; }
          .print\\:hidden { display: none !important; }
          * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
      `}</style>
    </div>
  );
};

export default App;
