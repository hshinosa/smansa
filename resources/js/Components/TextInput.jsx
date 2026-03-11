import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef();

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm sm:text-base shadow-sm transition-colors duration-150 ' +
                'focus:outline-none focus:ring-2 focus:ring-offset-0 ' +
                'focus:border-accent-yellow focus:ring-accent-yellow ' +
                'placeholder:text-gray-400 ' +
                className
            }
            ref={localRef}
        />
    );
});
