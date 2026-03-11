import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import ChatWidget from '@/Components/ChatWidget';
import { Toaster } from 'react-hot-toast';
import { route as ziggyRoute } from 'ziggy-js';
import { usePerformanceMonitoring } from '@/Utils/performance';

const route = (name, params, absolute) => {
    return ziggyRoute(name, params, absolute, window.Ziggy);
};

window.route = route;

function AppWrapper({ Component, props }) {
    usePerformanceMonitoring();
    return <Component {...props} />;
}

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <AppWrapper Component={App} props={props} />
                <Toaster position="top-right" />
            </>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
