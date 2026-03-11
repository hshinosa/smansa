import { TYPOGRAPHY } from '@/Utils/typography';

export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={
                `block ${TYPOGRAPHY.formLabel} ` +
                className
            }
        >
            {value ? value : children}
        </label>
    );
}
