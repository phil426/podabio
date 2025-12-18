
import { useState, useRef, useEffect } from 'react';
import { ArrowLeft } from '@phosphor-icons/react';
import { SocialIconsManager } from './SocialIconsManager';
import { SocialIconInspector } from './SocialIconInspector';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import type { TabColorTheme } from '../layout/tab-colors';

interface SocialIconsUnifiedEditorProps {
    activeColor: TabColorTheme;
    isOpen?: boolean;
}

export function SocialIconsUnifiedEditor({ activeColor, isOpen = true }: SocialIconsUnifiedEditorProps): JSX.Element {
    const [view, setView] = useState<'list' | 'edit'>('list');
    // Manage selection locally to handle back/forth, but also sync with global for legacy compatibility
    const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);
    const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);

    // When editing starts/stops, update local view state
    useEffect(() => {
        if (selectedSocialIconId) {
            setView('edit');
        } else {
            // Only switch to list if we are not explicitly adding? 
            // Actually, adding sets ID to new:..., so it is truthy.
            setView('list');
        }
    }, [selectedSocialIconId]);


    const handleEdit = (id: string | number) => {
        selectSocialIcon(String(id));
        setView('edit');
    };

    const handleBack = () => {
        selectSocialIcon(null);
        setView('list');
    };

    // If modal is closed or unmounted, reset? 
    // We rely on parent to mount/unmount us.

    if (view === 'edit') {
        return (
            <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
                <button
                    onClick={handleBack}
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '4px',
                        padding: '8px 4px',
                        background: 'none',
                        border: 'none',
                        color: 'var(--admin-text-secondary, #64748b)',
                        fontSize: '0.85rem',
                        cursor: 'pointer',
                        width: 'fit-content',
                        marginBottom: '8px'
                    }}
                >
                    <ArrowLeft size={16} />
                    Back to list
                </button>
                <SocialIconInspector
                    activeColor={activeColor}
                    selectedId={selectedSocialIconId}
                    onSelect={selectSocialIcon}
                />
            </div>
        );
    }

    return (
        <SocialIconsManager onEdit={handleEdit} />
    );
}
