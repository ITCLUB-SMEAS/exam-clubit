/**
 * Accessibility utilities for Vue components
 */

export function useAccessibility() {
    /**
     * Announce message to screen readers
     */
    const announce = (message, priority = 'polite') => {
        const el = document.createElement('div');
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', priority);
        el.setAttribute('aria-atomic', 'true');
        el.className = 'sr-only';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 1000);
    };

    /**
     * Handle keyboard navigation for lists
     */
    const handleListKeyboard = (event, items, currentIndex, onSelect) => {
        let newIndex = currentIndex;
        
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                newIndex = Math.min(currentIndex + 1, items.length - 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                newIndex = Math.max(currentIndex - 1, 0);
                break;
            case 'Home':
                event.preventDefault();
                newIndex = 0;
                break;
            case 'End':
                event.preventDefault();
                newIndex = items.length - 1;
                break;
            case 'Enter':
            case ' ':
                event.preventDefault();
                onSelect?.(items[currentIndex], currentIndex);
                return currentIndex;
        }
        
        return newIndex;
    };

    /**
     * Focus trap for modals
     */
    const trapFocus = (container) => {
        const focusable = container.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        const handler = (e) => {
            if (e.key !== 'Tab') return;
            
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        };

        container.addEventListener('keydown', handler);
        first?.focus();

        return () => container.removeEventListener('keydown', handler);
    };

    return { announce, handleListKeyboard, trapFocus };
}

/**
 * Accessibility directive for Vue
 * Usage: v-a11y="{ label: 'Button label', description: 'Description' }"
 */
export const a11yDirective = {
    mounted(el, binding) {
        const { label, description, role } = binding.value || {};
        
        if (label) el.setAttribute('aria-label', label);
        if (description) {
            const id = `desc-${Math.random().toString(36).substr(2, 9)}`;
            const descEl = document.createElement('span');
            descEl.id = id;
            descEl.className = 'sr-only';
            descEl.textContent = description;
            el.appendChild(descEl);
            el.setAttribute('aria-describedby', id);
        }
        if (role) el.setAttribute('role', role);
    }
};
