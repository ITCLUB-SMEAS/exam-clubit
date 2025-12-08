import { usePage } from '@inertiajs/vue3';

export function useTranslation() {
    const page = usePage();
    
    const __ = (key, replacements = {}) => {
        const translations = page.props.translations || {};
        let translation = translations[key] || key;
        
        Object.keys(replacements).forEach(r => {
            translation = translation.replace(`:${r}`, replacements[r]);
        });
        
        return translation;
    };
    
    return { __ };
}
