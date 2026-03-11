import React from 'react';
import { X, Plus } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import MiniTextEditor from '@/Components/MiniTextEditor';

export default function ProcedureSection({ data, setData, addStep, removeStep, updateStep }) {
    return (
        <div className="space-y-6">
            {/* Langkah Prosedur */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3 flex justify-between items-center">
                    <div>
                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                            Langkah Prosedur
                        </h3>
                        <p className="text-sm text-gray-600 mt-1">Urutan langkah-langkah pendaftaran</p>
                    </div>
                    <button
                        type="button"
                        onClick={addStep}
                        className="px-4 py-2 flex items-center gap-2 shadow-md hover:shadow-lg transition-all !bg-accent-yellow hover:!bg-[#EAB308] border-0 text-sm !text-gray-900 font-bold rounded-xl"
                    >
                        <Plus size={16} />
                        Tambah Langkah
                    </button>
                </div>

                <div className="space-y-4">
                    {(data.prosedur.items || []).map((step, index) => (
                        <div key={index} className="bg-gray-50 border-2 border-gray-200 rounded-xl p-5">
                            <div className="flex justify-between items-start mb-4">
                                <div className="flex items-center">
                                    <span className="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold mr-3">
                                        {index + 1}
                                    </span>
                                    <h4 className="text-md font-bold text-gray-800">Langkah {index + 1}</h4>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => removeStep(index)}
                                    className="text-red-500 hover:text-red-700 p-1 hover:bg-red-50 rounded-lg transition-colors"
                                >
                                    <X size={18} />
                                </button>
                            </div>
                            
                            <div className="space-y-4">
                                <div>
                                    <InputLabel value="Judul Langkah" />
                                    <TextInput
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={step.title}
                                        onChange={(e) => updateStep(index, 'title', e.target.value)}
                                        placeholder="Contoh: Daftar Online"
                                    />
                                </div>
                                
                                <div className="bg-slate-50 p-4 rounded-xl border border-gray-100">
                                    <InputLabel value="Deskripsi Langkah" />
                                    <MiniTextEditor
                                        initialValue={step.description}
                                        onChange={(value) => updateStep(index, 'description', value)}
                                        placeholder="Jelaskan langkah-langkah detail..."
                                    />
                                </div>
                            </div>
                        </div>
                    ))}
                    {(!data.prosedur.items || data.prosedur.items.length === 0) && (
                        <div className="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300 text-gray-400">
                            Belum ada langkah prosedur yang ditambahkan.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
