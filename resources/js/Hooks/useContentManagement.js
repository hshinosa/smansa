import { useState, useEffect } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import toast from 'react-hot-toast';

export function useContentManagement(initialData, updateRoute, method = 'post') {
    const { success, errors: pageErrorsFromLaravel } = usePage().props;
    
    const { data, setData, post, put, processing, errors: formErrors, reset, transform } = useForm(initialData);

    const [selectedFiles, setSelectedFiles] = useState({});
    const [previewUrls, setPreviewUrls] = useState({});
    const [localSuccess, setLocalSuccess] = useState(null);
    const [localErrors, setLocalErrors] = useState({});

    useEffect(() => {
        if (success) {
            setLocalSuccess(success);
            const timer = setTimeout(() => setLocalSuccess(null), 5000);
            return () => clearTimeout(timer);
        }
    }, [success]);
    
    useEffect(() => {
        if (pageErrorsFromLaravel && Object.keys(pageErrorsFromLaravel).length > 0) {
            setLocalErrors(pageErrorsFromLaravel);
        } else {
            setLocalErrors({});
        }
    }, [pageErrorsFromLaravel]);

    const handleFileChange = (fileType, file, options = {}) => {
        if (file) {
            const { maxSize = 10 * 1024 * 1024, allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml'] } = options;

            if (file.size > maxSize) {
                setLocalErrors(prev => ({
                    ...prev,
                    [fileType]: `Ukuran file tidak boleh lebih dari ${maxSize / (1024 * 1024)}MB`
                }));
                return;
            }

            if (!allowedTypes.includes(file.type)) {
                setLocalErrors(prev => ({
                    ...prev,
                    [fileType]: 'File harus berupa gambar (JPEG, PNG, JPG, GIF, SVG)'
                }));
                return;
            }

            setLocalErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[fileType];
                return newErrors;
            });

            setSelectedFiles(prev => ({
                ...prev,
                [fileType]: file
            }));

            const reader = new FileReader();
            reader.onload = (e) => {
                setPreviewUrls(prev => ({
                    ...prev,
                    [fileType]: e.target.result
                }));
            };
            reader.readAsDataURL(file);
        }
    };

    const handleSectionInputChange = (section, field, value) => {
        setData(prevData => ({
            ...prevData,
            [section]: {
                ...prevData[section],
                [field]: value,
            }
        }));

        if (localErrors[`${section}.${field}`]) {
            setLocalErrors(prev => {
                const newErrors = {...prev};
                delete newErrors[`${section}.${field}`];
                return newErrors;
            });
        }
    };

    const handleSubmit = (e, additionalData = null) => {
        if (e) e.preventDefault();
        setLocalErrors({});

        // If additionalData is FormData, use router.post directly
        if (additionalData instanceof FormData) {
            router.post(updateRoute, additionalData, {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedFiles({});
                    toast.success('Perubahan berhasil disimpan');
                },
                onError: (serverErrors) => {
                    setLocalErrors(serverErrors);
                    toast.error('Gagal menyimpan perubahan');
                }
            });
            return;
        }

        // Otherwise use useForm's post/put methods
        if (additionalData) {
            transform((data) => ({
                ...data,
                ...additionalData
            }));
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setSelectedFiles({});
                toast.success('Perubahan berhasil disimpan');
            },
            onError: (serverErrors) => {
                setLocalErrors(serverErrors);
                toast.error('Gagal menyimpan perubahan');
            }
        };

        if (method === 'post') {
            post(updateRoute, options);
        } else {
            put(updateRoute, options);
        }
    };

    return {
        data,
        setData,
        processing,
        formErrors,
        localSuccess,
        localErrors,
        setLocalErrors,
        selectedFiles,
        setSelectedFiles,
        previewUrls,
        setPreviewUrls,
        handleFileChange,
        handleSectionInputChange,
        handleSubmit,
        reset
    };
}
