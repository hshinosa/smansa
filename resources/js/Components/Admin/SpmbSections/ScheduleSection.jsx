import React from 'react';
import { X, Plus } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';

export default function ScheduleSection({ data, addEvent, removeEvent, updateEvent }) {
    return (
        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
            <div className="border-b pb-3 flex justify-between items-center">
                <div>
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Jadwal Kegiatan SPMB
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Daftar tanggal penting dalam proses SPMB</p>
                </div>
                <button
                    type="button"
                    onClick={addEvent}
                    className="px-4 py-2 flex items-center gap-2 shadow-md hover:shadow-lg transition-all !bg-accent-yellow hover:!bg-[#EAB308] border-0 text-sm !text-gray-900 font-bold rounded-xl"
                >
                    <Plus size={16} />
                    Tambah Jadwal
                </button>
            </div>

            <div className="space-y-4">
                    {(data.jadwal_penting.items || []).map((event, index) => (
                        <div key={index} className="bg-gray-50 border-2 border-gray-200 rounded-xl p-5">
                            <div className="flex justify-between items-start mb-4">
                                <h4 className="text-md font-bold text-gray-800">Kegiatan {index + 1}</h4>
                                <button
                                    type="button"
                                    onClick={() => removeEvent(index)}
                                    className="text-red-500 hover:text-red-700 p-1 hover:bg-red-50 rounded-lg transition-colors"
                                >
                                    <X size={18} />
                                </button>
                            </div>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel value="Nama Kegiatan" />
                                    <TextInput
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={event.title}
                                        onChange={(e) => updateEvent(index, 'title', e.target.value)}
                                        placeholder="Contoh: Pendaftaran Online"
                                    />
                                </div>
                                
                                <div>
                                    <InputLabel value="Tanggal" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={event.date}
                                        onChange={(e) => updateEvent(index, 'date', e.target.value)}
                                    />
                                </div>
                            </div>
                            
                            <div className="mt-4">
                                <InputLabel value="Deskripsi (Opsional)" />
                                <TextInput
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={event.description || ''}
                                    onChange={(e) => updateEvent(index, 'description', e.target.value)}
                                    placeholder="Deskripsi tambahan..."
                                />
                            </div>
                        </div>
                    ))}
                    {(!data.jadwal_penting.items || data.jadwal_penting.items.length === 0) && (
                        <div className="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300 text-gray-400">
                            Belum ada jadwal yang ditambahkan.
                        </div>
                    )}
                </div>
        </div>
    );
}
