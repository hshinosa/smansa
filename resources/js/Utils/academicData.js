// FILE: resources/js/Utils/academicData.js
// Academic data and metadata utilities

export const programStudyData = {
    mipa: {
        title: 'MIPA',
        subtitle: 'Matematika & Ilmu Pengetahuan Alam',
        description: 'Program Matematika dan Ilmu Pengetahuan Alam',
        color: 'blue'
    },
    ips: {
        title: 'IPS',
        subtitle: 'Ilmu Pengetahuan Sosial',
        description: 'Program Ilmu Pengetahuan Sosial',
        color: 'green'
    },
    bahasa: {
        title: 'Bahasa',
        subtitle: 'Bahasa & Ilmu Budaya',
        description: 'Program Bahasa',
        color: 'purple'
    }
};

export const getPageMetadata = (pageName, customMeta = {}) => {
    const defaultMeta = {
        title: 'SMAN 1 Baleendah',
        description: 'Website resmi SMAN 1 Baleendah',
        keywords: 'SMAN 1 Baleendah, SMA, Pendidikan',
    };

    const pageMeta = {
        kurikulum: {
            title: 'Kurikulum - SMAN 1 Baleendah',
            description: 'Kurikulum dan program pembelajaran SMAN 1 Baleendah',
            keywords: 'kurikulum, program pembelajaran, SMAN 1 Baleendah',
        },
        mipa: {
            title: 'Program MIPA - SMAN 1 Baleendah',
            description: 'Program Matematika dan Ilmu Pengetahuan Alam SMAN 1 Baleendah',
            keywords: 'MIPA, matematika, IPA, SMAN 1 Baleendah',
        },
        ips: {
            title: 'Program IPS - SMAN 1 Baleendah',
            description: 'Program Ilmu Pengetahuan Sosial SMAN 1 Baleendah',
            keywords: 'IPS, sosial, SMAN 1 Baleendah',
        },
        bahasa: {
            title: 'Program Bahasa - SMAN 1 Baleendah',
            description: 'Program Bahasa SMAN 1 Baleendah',
            keywords: 'bahasa, linguistik, SMAN 1 Baleendah',
        },
        ekstrakurikuler: {
            title: 'Ekstrakurikuler - SMAN 1 Baleendah',
            description: 'Kegiatan ekstrakurikuler SMAN 1 Baleendah',
            keywords: 'ekstrakurikuler, kegiatan, SMAN 1 Baleendah',
        },
    };

    return {
        ...defaultMeta,
        ...pageMeta,
        ...(pageMeta[pageName] || {}),
        ...customMeta,
    };
};
