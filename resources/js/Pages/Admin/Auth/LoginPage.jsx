// FILE: resources/js/Pages/Admin/Auth/LoginPage.jsx
import React from 'react';
import { Head, useForm, Link, usePage } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel'; // Asumsi Anda punya komponen ini dari Breeze
import TextInput from '@/Components/TextInput';   // Asumsi Anda punya komponen ini dari Breeze
import InputError from '@/Components/InputError'; // Asumsi Anda punya komponen ini
import { GraduationCap } from 'lucide-react';   // Ikon untuk tombol

export default function LoginPage() {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const logoSekolah = siteSettings?.general?.site_logo || '/images/logo-sman1baleendah.png';
    const loginBgImage = '/images/hero-bg-sman1baleendah.jpeg'; // Default background image

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false, // Opsional
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.login.attempt'), { // Gunakan nama rute yang benar
            onFinish: () => reset('password'), // Reset password field setelah submit
        });
    };

    return (
        <>
            <Head title="Login Admin" />
            <div className="min-h-screen flex items-stretch text-gray-800">
                {/* Kolom Kiri - Gambar Latar */}
                <div
                    className="relative hidden w-3/5 bg-gray-500 bg-cover bg-center lg:block"
                    style={{ backgroundImage: `url(${loginBgImage})` }}
                >
                    <div className="absolute inset-0 bg-black opacity-50"></div>
                    <div className="absolute top-8 left-8 text-white text-xl font-semibold">
                        Login Admin
                    </div>
                </div>

                {/* Kolom Kanan - Form Login */}
                <div className="w-full lg:w-2/5 flex items-center justify-center bg-white p-8 sm:p-12">
                    <div className="w-full max-w-md">
                        <div className="flex justify-center mb-8">
                            <Link href="/">
                                <img src={logoSekolah} alt={`Logo ${siteName}`} className="h-16 w-auto" />
                            </Link>
                        </div>
                        <h2 className="text-2xl font-bold text-center text-gray-700 mb-1">
                            {siteName}
                        </h2>
                        <h1 className="text-3xl font-bold text-center text-gray-900 mb-8">
                            Masuk
                        </h1>

                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="email" value="Alamat Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block w-full"
                                    autoComplete="username"
                                    isFocused={true}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="password" value="Kata Sandi" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                />
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            {/* Opsional: Remember Me
                            <div className="flex items-center">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                />
                                <span className="ml-2 text-sm text-gray-600">Ingat saya</span>
                            </div>
                            */}

                            <div>
                                <button
                                    type="submit"
                                    className="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-darker focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                    disabled={processing}
                                >
                                    <GraduationCap size={18} className="mr-2" /> {/* Atau ikon lain yang sesuai */}
                                    Masuk
                                </button>
                            </div>
                        </form>
                        {/* Opsional: Link lupa password
                        <div className="mt-6 text-center">
                            <Link
                                href="#" // Ganti dengan rute lupa password admin jika ada
                                className="text-sm text-indigo-600 hover:text-indigo-500"
                            >
                                Lupa kata sandi?
                            </Link>
                        </div>
                         */}
                    </div>
                </div>
            </div>
        </>
    );
}