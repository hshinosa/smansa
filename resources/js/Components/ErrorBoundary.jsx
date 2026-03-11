// FILE: resources/js/Components/ErrorBoundary.jsx
// React Error Boundary for graceful error handling

import React from 'react';
import { AlertTriangle, RefreshCw, Home } from 'lucide-react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { 
            hasError: false, 
            error: null, 
            errorInfo: null 
        };
    }

    static getDerivedStateFromError(error) {
        // Update state so the next render will show the fallback UI
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        // Log error to console for debugging
        console.error('ErrorBoundary caught an error:', error, errorInfo);
        
        // You can also log to an error reporting service here
        this.setState({
            error: error,
            errorInfo: errorInfo
        });
    }

    handleReload = () => {
        window.location.reload();
    };

    handleGoHome = () => {
        window.location.href = this.props.homeUrl || '/admin/dashboard';
    };

    render() {
        if (this.state.hasError) {
            // Fallback UI
            return (
                <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
                    <div className="max-w-2xl w-full">
                        <div className="bg-white rounded-2xl shadow-xl p-8 sm:p-12">
                            {/* Icon */}
                            <div className="flex justify-center mb-6">
                                <div className="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                                    <AlertTriangle className="w-10 h-10 text-red-600" />
                                </div>
                            </div>

                            {/* Title */}
                            <h1 className="text-3xl font-bold text-gray-900 text-center mb-4">
                                Oops! Terjadi Kesalahan
                            </h1>

                            {/* Description */}
                            <p className="text-gray-600 text-center mb-8">
                                Maaf, terjadi kesalahan yang tidak terduga. Tim kami telah diberitahu dan sedang menangani masalah ini.
                            </p>

                            {/* Error Details (Development only) */}
                            {process.env.NODE_ENV === 'development' && this.state.error && (
                                <div className="mb-8 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                    <details className="cursor-pointer">
                                        <summary className="font-semibold text-gray-700 mb-2">
                                            Detail Error (Development Mode)
                                        </summary>
                                        <div className="mt-4 space-y-2">
                                            <div className="text-sm">
                                                <span className="font-semibold text-gray-700">Error:</span>
                                                <pre className="mt-1 text-xs text-red-600 bg-red-50 p-3 rounded overflow-x-auto">
                                                    {this.state.error.toString()}
                                                </pre>
                                            </div>
                                            {this.state.errorInfo && (
                                                <div className="text-sm">
                                                    <span className="font-semibold text-gray-700">Stack Trace:</span>
                                                    <pre className="mt-1 text-xs text-gray-600 bg-gray-100 p-3 rounded overflow-x-auto max-h-64">
                                                        {this.state.errorInfo.componentStack}
                                                    </pre>
                                                </div>
                                            )}
                                        </div>
                                    </details>
                                </div>
                            )}

                            {/* Actions */}
                            <div className="flex flex-col sm:flex-row gap-3 justify-center">
                                <button
                                    onClick={this.handleReload}
                                    className="flex items-center justify-center gap-2 px-6 py-3 bg-accent-yellow text-gray-900 font-semibold rounded-lg hover:bg-yellow-500 transition-colors shadow-sm"
                                >
                                    <RefreshCw size={18} />
                                    Muat Ulang Halaman
                                </button>
                                <button
                                    onClick={this.handleGoHome}
                                    className="flex items-center justify-center gap-2 px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border-2 border-gray-300 hover:bg-gray-50 transition-colors"
                                >
                                    <Home size={18} />
                                    Kembali ke Dashboard
                                </button>
                            </div>

                            {/* Support Info */}
                            <div className="mt-8 pt-6 border-t border-gray-200 text-center">
                                <p className="text-sm text-gray-500">
                                    Jika masalah terus berlanjut, silakan hubungi administrator sistem.
                                </p>
                            </div>
                        </div>

                        {/* Additional Info */}
                        <div className="mt-6 text-center">
                            <p className="text-sm text-gray-500">
                                Error ID: {Date.now().toString(36).toUpperCase()}
                            </p>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
