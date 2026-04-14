import React from 'react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null, errorInfo: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('[ErrorBoundary] Caught an error:', error, errorInfo);
        this.setState({ errorInfo });
    }

    handleRefresh = () => {
        window.location.reload();
    };

    handleClose = () => {
        this.setState({ hasError: false, error: null, errorInfo: null });
    };

    render() {
        if (this.state.hasError) {
            return (
                <div className="min-h-screen bg-background px-4 py-10 text-foreground">
                    <div className="mx-auto flex min-h-[calc(100vh-5rem)] max-w-5xl items-center justify-center">
                        <div className="relative w-full max-w-2xl overflow-hidden rounded-[calc(var(--radius)*4)] border border-border bg-card shadow-2xl">
                            <div className="absolute inset-x-0 top-0 h-1.5 bg-destructive" />

                            <div className="space-y-8 p-8 sm:p-10">
                                <div className="space-y-5 text-center">
                                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-destructive/10 text-destructive shadow-sm">
                                        <svg
                                            className="h-8 w-8"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={1.75}
                                                d="M12 9v3.75m0 3.75h.01M10.29 3.86l-7.5 13A1.5 1.5 0 004.08 19h15.84a1.5 1.5 0 001.29-2.14l-7.5-13a1.5 1.5 0 00-2.42 0z"
                                            />
                                        </svg>
                                    </div>

                                    <div className="space-y-2">
                                        <p className="text-sm font-semibold uppercase tracking-[0.24em] text-destructive">
                                            Application Error
                                        </p>
                                        <h2 className="text-2xl font-bold tracking-tight text-card-foreground sm:text-3xl">
                                            Terjadi Kesalahan
                                        </h2>
                                        <p className="mx-auto max-w-xl text-sm leading-6 text-muted-foreground sm:text-base">
                                            Maaf, terjadi kesalahan saat memuat halaman. Silakan refresh halaman
                                            atau tutup pesan ini untuk mencoba kembali.
                                        </p>
                                    </div>
                                </div>

                                <div className="rounded-[calc(var(--radius)*2)] border border-border bg-muted/40 p-4 text-left">
                                    <p className="text-sm font-medium text-card-foreground">Pesan error</p>
                                    <p className="mt-2 break-words text-sm leading-6 text-muted-foreground">
                                        {this.state.error?.message || 'Komponen mengalami crash yang tidak terduga.'}
                                    </p>
                                </div>

                                {import.meta.env.DEV && this.state.error && (
                                    <details className="rounded-[calc(var(--radius)*2)] border border-border bg-muted/30 p-4 text-left text-sm text-muted-foreground">
                                        <summary className="cursor-pointer font-medium text-card-foreground">
                                            Error Details (Dev Only)
                                        </summary>
                                        <pre className="mt-3 overflow-auto whitespace-pre-wrap rounded-xl bg-background p-3 font-mono text-xs leading-5 text-destructive max-h-48">
                                            {this.state.error.toString()}
                                        </pre>
                                        {this.state.errorInfo?.componentStack && (
                                            <pre className="mt-3 overflow-auto whitespace-pre-wrap rounded-xl bg-background p-3 font-mono text-xs leading-5 text-muted-foreground max-h-48">
                                                {this.state.errorInfo.componentStack}
                                            </pre>
                                        )}
                                    </details>
                                )}

                                <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
                                    <button
                                        type="button"
                                        onClick={this.handleRefresh}
                                        className="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    >
                                        Refresh Page
                                    </button>
                                    <button
                                        type="button"
                                        onClick={this.handleClose}
                                        className="inline-flex items-center justify-center rounded-xl border border-border bg-secondary px-6 py-3 text-sm font-semibold text-secondary-foreground shadow-sm transition hover:bg-secondary/80 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    >
                                        Close Error
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
