import { useState, useEffect } from 'react';
import { X, Check, MagicWand, Palette, TextT, ArrowsOutSimple, Layout, GridFour, Article, Cards, Spinner } from '@phosphor-icons/react';
import * as Dialog from '@radix-ui/react-dialog';
import styles from './theme-property-drawer.module.css'; // Reusing existing drawer styles for now
import type { ShapePreset, ColorPreset, TypographyPreset, SpacingPreset, EasyModeConfig } from '../../../design-system/presets/types';
import { normalizeImageUrl } from '../../../api/utils';

interface EasyThemeDrawerProps {
    isOpen: boolean;
    onClose: () => void;
    activePresets: EasyModeConfig;
    presets: {
        shapes: ShapePreset[];
        colors: ColorPreset[];
        typography: TypographyPreset[];
        spacing: SpacingPreset[];
    };
    onApplyShapePreset: (presetId: string) => void;
    onApplyColorPreset: (presetId: string) => void;
    onApplyTypographyPreset: (presetId: string) => void;
    onApplySpacingPreset: (presetId: string) => void;
    onApplyLayoutPreset: (layoutId: string) => void;
    onApplyAutoPreset: () => void;
    isAutoGenerating: boolean;
    profileImageUrl?: string | null;
    showOnly?: 'layout' | 'all' | 'shape' | 'spacing' | 'vibe' | 'typography';
}

export function EasyThemeDrawer({
    isOpen,
    onClose,
    activePresets,
    presets,
    onApplyShapePreset,
    onApplyColorPreset,
    onApplyTypographyPreset,
    onApplySpacingPreset,
    onApplyLayoutPreset,
    onApplyAutoPreset,
    isAutoGenerating,
    profileImageUrl,
    showOnly = 'all'
}: EasyThemeDrawerProps): JSX.Element {
    const hasProfileImage = !!profileImageUrl;

    const [activeTypographyTab, setActiveTypographyTab] = useState<'Clean' | 'Elegant' | 'Creative'>('Clean');
    const [activeColorTab, setActiveColorTab] = useState<'Clean' | 'Elegant' | 'Creative'>('Clean');
    // Dynamically load fonts for preview
    useEffect(() => {
        if (!presets.typography || presets.typography.length === 0) return;

        // Collect all unique fonts
        const uniqueFonts = new Set<string>();
        presets.typography.forEach(preset => {
            if (preset.fonts.heading) uniqueFonts.add(preset.fonts.heading);
            if (preset.fonts.body) uniqueFonts.add(preset.fonts.body);
        });

        if (uniqueFonts.size === 0) return;

        const fontFamilies = Array.from(uniqueFonts);
        const BATCH_SIZE = 5;
        const createdLinks: HTMLLinkElement[] = [];

        // Helper to clean font name
        const cleanFont = (font: string) => font.split(',')[0].replace(/['"]/g, '').trim();

        // Batch requests to avoid URL length limits
        for (let i = 0; i < fontFamilies.length; i += BATCH_SIZE) {
            const batch = fontFamilies.slice(i, i + BATCH_SIZE);
            const fontQuery = batch.map(font =>
                `${cleanFont(font).replace(/ /g, '+')}:wght@400;600;700`
            ).join('&family=');

            const href = `https://fonts.googleapis.com/css2?family=${fontQuery}&display=swap`;

            // Check if already exists
            if (!document.querySelector(`link[href="${href}"]`)) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                link.dataset.generatedFont = "true";
                document.head.appendChild(link);
                createdLinks.push(link);
            }
        }

        return () => {
            // Optional: cleanup if needed. 
            // We usually keep fonts to avoid flickering if reopened, 
            // but strict cleanup would involve removing createdLinks.
        };
    }, [presets.typography]);

    return (
        <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()} modal={true}>
            <Dialog.Portal>
                <Dialog.Overlay className={styles.overlay} onClick={onClose} />
                <Dialog.Content className={`${styles.modal} glassPanel`} aria-label="Easy Mode">
                    {/* ... header ... */}
                    <header className={styles.header}>
                        <div className={styles.headerContent}>
                            <Dialog.Title className={styles.title}>
                                {showOnly === 'layout' ? 'Page Layout' :
                                    showOnly === 'shape' ? 'Card Shapes' :
                                        showOnly === 'spacing' ? 'Spacing & Gap' :
                                            showOnly === 'vibe' ? 'Color Vibes' :
                                                showOnly === 'typography' ? 'Typography' :
                                                    'Easy Mode'}
                            </Dialog.Title>
                            <Dialog.Description className={styles.description}>
                                {showOnly === 'layout' ? 'Choose a layout structure for your page.' :
                                    showOnly === 'shape' ? 'Define the geometry of your cards.' :
                                        showOnly === 'spacing' ? 'Adjust the density of your content.' :
                                            showOnly === 'vibe' ? 'Select a color palette that matches your brand.' :
                                                showOnly === 'typography' ? 'Choose a font pairing.' :
                                                    'Style your page with curated presets.'}
                            </Dialog.Description>
                        </div>
                        <Dialog.Close asChild>
                            <button
                                type="button"
                                className={styles.closeButton}
                                aria-label="Close modal"
                            >
                                <X aria-hidden="true" size={20} weight="regular" />
                            </button>
                        </Dialog.Close>
                    </header>

                    <div className={styles.body}>
                        <div style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '2rem' }}>

                            {/* STASHED: Layout Feature
                            <section>
                                <h3 className={styles.sectionTitle}>Layout</h3>
                                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '0.75rem', marginTop: '1rem' }}>
                                    {[
                                        { id: 'standard', label: 'Stack', icon: Layout },
                                        { id: 'bento', label: 'Bento', icon: GridFour },
                                        { id: 'magazine', label: 'Magazine', icon: Article },
                                        { id: 'spotlight', label: 'Spotlight', icon: Cards }
                                    ].map(layout => {
                                        const isActive = (activePresets.activeLayoutId || 'standard') === layout.id;
                                        const Icon = layout.icon;
                                        return (
                                            <button
                                                key={layout.id}
                                                onClick={() => onApplyLayoutPreset(layout.id)}
                                                style={{
                                                    background: isActive ? 'rgba(255,255,255,0.15)' : 'rgba(255,255,255,0.05)',
                                                    border: isActive ? '1px solid rgba(255,255,255,0.5)' : '1px solid transparent',
                                                    borderRadius: '12px',
                                                    padding: '0.75rem',
                                                    cursor: 'pointer',
                                                    textAlign: 'center',
                                                    transition: 'all 0.2s',
                                                    color: 'white',
                                                    display: 'flex',
                                                    flexDirection: 'column',
                                                    alignItems: 'center',
                                                    gap: '0.5rem',
                                                    minHeight: '80px',
                                                    justifyContent: 'center'
                                                }}
                                            >
                                                <Icon size={24} weight={isActive ? "fill" : "regular"} />
                                                <span style={{ fontSize: '0.8rem', fontWeight: 600 }}>{layout.label}</span>
                                                {isActive && <div style={{
                                                    width: '4px',
                                                    height: '4px',
                                                    background: '#4ade80',
                                                    borderRadius: '50%',
                                                    marginTop: '2px'
                                                }} />}
                                            </button>
                                        );
                                    })}
                                </div>
                            </section>
                            */}

                            {(showOnly === 'all' || showOnly === 'shape') && (
                                <section>
                                    <h3 className={styles.sectionTitle}>Shape</h3>
                                    <div className={styles.gridShapes}>
                                        {presets.shapes.map(preset => {
                                            const isActive = activePresets.activeShapeId === preset.id;
                                            return (
                                                <button
                                                    key={preset.id}
                                                    onClick={() => onApplyShapePreset(preset.id)}
                                                    style={{
                                                        background: isActive ? 'rgba(255,255,255,0.15)' : 'rgba(255,255,255,0.05)',
                                                        border: isActive ? '1px solid rgba(255,255,255,0.5)' : '1px solid transparent',
                                                        borderRadius: preset.id === 'sharp' ? '2px' : preset.id === 'soft' ? '50px' : '12px',
                                                        padding: '1rem',
                                                        cursor: 'pointer',
                                                        textAlign: 'center',
                                                        transition: 'all 0.2s',
                                                        color: 'white',
                                                        display: 'flex',
                                                        flexDirection: 'column',
                                                        alignItems: 'center',
                                                        gap: '0.5rem'
                                                    }}
                                                >
                                                    <span style={{ fontWeight: 600 }}>{preset.label}</span>
                                                    <span style={{ fontSize: '0.8rem', opacity: 0.7 }}>{preset.id === 'soft' ? 'Pill' : preset.id === 'sharp' ? 'Square' : 'Card'}</span>
                                                    {isActive && <Check weight="bold" style={{ color: '#4ade80' }} />}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </section>

                            )}

                            {(showOnly === 'all' || showOnly === 'spacing') && (
                                <section>
                                    <h3 className={styles.sectionTitle}>Spacing</h3>
                                    <div className={styles.gridThree}>
                                        {presets.spacing.map(preset => {
                                            const isActive = activePresets.activeSpacingId === preset.id;
                                            return (
                                                <button
                                                    key={preset.id}
                                                    onClick={() => onApplySpacingPreset(preset.id)}
                                                    style={{
                                                        background: isActive ? 'rgba(255,255,255,0.15)' : 'rgba(255,255,255,0.05)',
                                                        border: isActive ? '1px solid rgba(255,255,255,0.5)' : '1px solid transparent',
                                                        borderRadius: '12px',
                                                        padding: '1rem',
                                                        cursor: 'pointer',
                                                        textAlign: 'center',
                                                        transition: 'all 0.2s',
                                                        color: 'white',
                                                        display: 'flex',
                                                        flexDirection: 'column',
                                                        alignItems: 'center',
                                                        gap: '0.5rem'
                                                    }}
                                                >
                                                    <div style={{
                                                        display: 'flex',
                                                        gap: preset.id === 'tight' ? '4px' : preset.id === 'cozy' ? '8px' : '12px',
                                                        marginBottom: '4px'
                                                    }}>
                                                        <div style={{ width: '4px', height: '12px', background: 'currentColor', opacity: 0.5, borderRadius: '2px' }} />
                                                        <div style={{ width: '4px', height: '12px', background: 'currentColor', opacity: 0.5, borderRadius: '2px' }} />
                                                        <div style={{ width: '4px', height: '12px', background: 'currentColor', opacity: 0.5, borderRadius: '2px' }} />
                                                    </div>
                                                    <span style={{ fontWeight: 600 }}>{preset.label}</span>
                                                    {isActive && <Check weight="bold" style={{ color: '#4ade80' }} size={14} />}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </section>

                            )}

                            {(showOnly === 'all' || showOnly === 'vibe') && (
                                <section>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                                        <h3 className={styles.sectionTitle}>Vibe</h3>
                                        <div style={{
                                            display: 'flex',
                                            background: 'rgba(255,255,255,0.1)',
                                            borderRadius: '8px',
                                            padding: '2px',
                                            gap: '2px'
                                        }}>
                                            {(['Clean', 'Elegant', 'Creative'] as const).map((tab) => (
                                                <button
                                                    key={tab}
                                                    onClick={() => setActiveColorTab(tab)}
                                                    style={{
                                                        background: activeColorTab === tab ? 'rgba(255,255,255,0.2)' : 'transparent',
                                                        border: 'none',
                                                        borderRadius: '6px',
                                                        padding: '4px 12px',
                                                        color: 'white',
                                                        fontSize: '0.85rem',
                                                        fontWeight: 600,
                                                        cursor: 'pointer',
                                                        transition: 'all 0.2s'
                                                    }}
                                                >
                                                    {tab}
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div className={styles.gridFour}>
                                        {presets.colors
                                            .filter(preset => {
                                                const cleanIds = ['light-clean', 'writer-parchment', 'light-sky', 'light-ocean', 'dark-classic', 'dark-midnight', 'dark-ocean', 'dark-charcoal', 'writer-paper', 'writer-notepad', 'writer-sepia', 'writer-ink'];
                                                const elegantIds = ['light-lavender', 'light-rose', 'light-coffee', 'light-mint', 'dark-amethyst', 'dark-gold', 'dark-forest', 'light-ivory', 'light-sage', 'light-periwinkle', 'dark-royal', 'dark-velvet'];
                                                const creativeIds = ['light-peach', 'light-lemon', 'light-lime', 'light-indigo', 'dark-crimson', 'dark-sunset', 'dark-ember', 'dark-neon', 'dark-terminal', 'light-berry', 'light-glacier', 'dark-acid'];

                                                if (activeColorTab === 'Clean') return cleanIds.includes(preset.id);
                                                if (activeColorTab === 'Elegant') return elegantIds.includes(preset.id);
                                                if (activeColorTab === 'Creative') return creativeIds.includes(preset.id);
                                                return false;
                                            })
                                            .map(preset => {
                                                const isActive = activePresets.activeColorId === preset.id;
                                                return (
                                                    <button
                                                        key={preset.id}
                                                        onClick={() => onApplyColorPreset(preset.id)}
                                                        style={{
                                                            background: 'rgba(255,255,255,0.05)',
                                                            border: isActive ? '1px solid #4ade80' : '1px solid transparent',
                                                            borderRadius: '8px',
                                                            padding: '0.5rem',
                                                            cursor: 'pointer',
                                                            transition: 'all 0.2s',
                                                            display: 'flex',
                                                            flexDirection: 'column',
                                                            alignItems: 'center',
                                                            gap: '0.4rem'
                                                        }}
                                                        aria-label={`Select ${preset.label} theme`}
                                                    >
                                                        {/* Color Swatch Preview */}
                                                        <div style={{
                                                            width: '100%',
                                                            height: '32px',
                                                            borderRadius: '6px',
                                                            background: preset.palette.background,
                                                            display: 'flex',
                                                            alignItems: 'center',
                                                            justifyContent: 'center',
                                                            position: 'relative',
                                                            overflow: 'hidden',
                                                            boxShadow: '0 2px 8px rgba(0,0,0,0.2)'
                                                        }}>
                                                            {/* Surface Card representation */}
                                                            <div style={{
                                                                width: '60%',
                                                                height: '60%',
                                                                background: preset.palette.surface,
                                                                borderRadius: '4px',
                                                                display: 'flex',
                                                                alignItems: 'center',
                                                                justifyContent: 'center'
                                                            }}>
                                                                <div style={{
                                                                    width: '50%',
                                                                    height: '4px',
                                                                    background: preset.palette.primary,
                                                                    borderRadius: '2px'
                                                                }} />
                                                            </div>
                                                        </div>

                                                        <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                                                            <span style={{ fontWeight: 600, fontSize: '0.75rem', color: 'white' }}>{preset.label}</span>
                                                            {isActive && <Check weight="bold" style={{ color: '#4ade80' }} size={14} />}
                                                        </div>
                                                    </button>
                                                );
                                            })}
                                    </div>
                                </section>

                            )}

                            {(showOnly === 'all' || showOnly === 'typography') && (
                                <section>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                                        <h3 className={styles.sectionTitle}>Typography</h3>
                                        <div style={{
                                            display: 'flex',
                                            background: 'rgba(255,255,255,0.1)',
                                            borderRadius: '8px',
                                            padding: '2px',
                                            gap: '2px'
                                        }}>
                                            {(['Clean', 'Elegant', 'Creative'] as const).map((tab) => (
                                                <button
                                                    key={tab}
                                                    onClick={() => setActiveTypographyTab(tab)}
                                                    style={{
                                                        background: activeTypographyTab === tab ? 'rgba(255,255,255,0.2)' : 'transparent',
                                                        border: 'none',
                                                        borderRadius: '6px',
                                                        padding: '4px 12px',
                                                        color: 'white',
                                                        fontSize: '0.85rem',
                                                        fontWeight: 600,
                                                        cursor: 'pointer',
                                                        transition: 'all 0.2s'
                                                    }}
                                                >
                                                    {tab}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                    <div className={styles.gridTypography}>
                                        {[
                                            {
                                                label: 'Clean',
                                                ids: ['modern', 'geometric', 'rounded', 'minimal-mono', 'editorial', 'corporate', 'tech']
                                            },
                                            {
                                                label: 'Elegant',
                                                ids: ['elegant', 'stark', 'display', 'vintage', 'bold', 'luxe', 'classic']
                                            },
                                            {
                                                label: 'Creative',
                                                ids: ['creative', 'handwritten', 'brush', 'script', 'journal', 'retro', 'quirky']
                                            },
                                        ]
                                            .filter(column => column.label === activeTypographyTab)
                                            .map((column) => (
                                                <div key={column.label} style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', gridColumn: '1 / -1' }}>
                                                    {column.ids.map(id => {
                                                        const preset = presets.typography.find(p => p.id === id);
                                                        if (!preset) return null;

                                                        const isActive = activePresets.activeTypographyId === preset.id;
                                                        return (
                                                            <button
                                                                key={preset.id}
                                                                onClick={() => onApplyTypographyPreset(preset.id)}
                                                                style={{
                                                                    background: 'rgba(255,255,255,0.05)',
                                                                    border: isActive ? '1px solid #4ade80' : '1px solid transparent',
                                                                    borderRadius: '8px',
                                                                    padding: '0.6rem',
                                                                    cursor: 'pointer',
                                                                    textAlign: 'left',
                                                                    transition: 'all 0.2s',
                                                                    color: 'white',
                                                                    display: 'flex',
                                                                    justifyContent: 'space-between',
                                                                    alignItems: 'center',
                                                                    minHeight: 'auto'
                                                                }}
                                                            >
                                                                <div style={{ width: '100%', display: 'flex', flexDirection: 'column', gap: '0px' }}>
                                                                    {/* Row 1: Preset Label */}
                                                                    <div style={{
                                                                        fontSize: '0.55rem',
                                                                        textTransform: 'uppercase',
                                                                        letterSpacing: '0.05em',
                                                                        opacity: 0.5,
                                                                        marginBottom: '2px'
                                                                    }}>
                                                                        {preset.label}
                                                                    </div>

                                                                    {/* Row 2: Primary Font */}
                                                                    <div style={{
                                                                        fontFamily: preset.fonts.heading,
                                                                        fontSize: '1rem',
                                                                        lineHeight: 1.2
                                                                    }}>
                                                                        {preset.fonts.heading}
                                                                    </div>

                                                                    {/* Row 3: Secondary Font */}
                                                                    <div style={{
                                                                        fontFamily: preset.fonts.body,
                                                                        fontSize: '0.75rem',
                                                                        opacity: 0.7,
                                                                        marginTop: '1px'
                                                                    }}>
                                                                        {preset.fonts.body}
                                                                    </div>
                                                                </div>
                                                                {isActive && <Check weight="bold" style={{ color: '#4ade80', flexShrink: 0, marginLeft: '6px' }} size={14} />}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            ))}
                                    </div>
                                </section>
                            )}

                        </div>
                    </div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
