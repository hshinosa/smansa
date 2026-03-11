import React from 'react';
import { X, Plus } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import MiniTextEditor from '@/Components/MiniTextEditor';

export default function FaqSection({ data, addFaq, removeFaq, updateFaq }) {
    return (
        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
            <div className="border-b pb-3 flex justify-between items-center">
                <div>
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Daftar FAQ
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Pertanyaan yang sering diajukan seputar SPMB</p>
                </div>
                <button
                    type="button"
                    onClick={addFaq}
                    className="px-4 py-2 flex items-center gap-2 shadow-md hover:shadow-lg transition-all !bg-accent-yellow hover:!bg-[#EAB308] border-0 text-sm !text-gray-900 font-bold rounded-xl"
                >
                    <Plus size={16} />
                    Tambah FAQ
                </button>
            </div>

            <div className="space-y-4">
                    {(data.faq.items || []).map((item, index) => (
                        <div key={index} className="bg-gray-50 border-2 border-gray-200 rounded-xl p-5">
                            <div className="flex justify-between items-start mb-4">
                                <h4 className="text-md font-bold text-gray-800">FAQ {index + 1}</h4>
                                <button
                                    type="button"
                                    onClick={() => removeFaq(index)}
                                    className="text-red-500 hover:text-red-700 p-1 hover:bg-red-50 rounded-lg transition-colors"
                                >
                                    <X size={18} />
                                </button>
                            </div>
                            
                            <div className="space-y-4">
                                <div>
                                    <InputLabel value="Pertanyaan" />
                                    <TextInput
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={item.question}
                                        onChange={(e) => updateFaq(index, 'question', e.target.value)}
                                        placeholder="Tulis pertanyaan..."
                                    />
                                </div>
                                
                                <div className="bg-slate-50 p-4 rounded-xl border border-gray-100">
                                    <InputLabel value="Jawaban" />
                                    <MiniTextEditor
                                        initialValue={item.answer}
                                        onChange={(value) => updateFaq(index, 'answer', value)}
                                        placeholder="Tulis jawaban..."
                                    />
                                </div>
                            </div>
                        </div>
                    ))}
                    {(!data.faq.items || data.faq.items.length === 0) && (
                        <div className="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300 text-gray-400">
                            Belum ada FAQ yang ditambahkan.
                        </div>
                    )}
                </div>
        </div>
    );
}
