import { TYPOGRAPHY } from '@/Utils/typography';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center rounded-lg px-4 py-2.5 font-semibold transition-all duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 ${
                    disabled 
                        ? 'opacity-50 cursor-not-allowed' 
                        : 'active:scale-[0.98] hover:shadow-md'
                } bg-accent-yellow text-gray-900 hover:bg-yellow-500 focus:ring-accent-yellow ${TYPOGRAPHY.buttonText} ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
