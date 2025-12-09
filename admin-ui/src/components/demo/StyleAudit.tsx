import styles from './StyleAudit.module.css';
import { Sparkle, Fingerprint, ArrowCounterClockwise, MagicWand, Star } from '@phosphor-icons/react';

export function StyleAudit() {
    console.log('Rendering StyleAudit Page');
    return (
        <div className={styles.container}>
            <header className={styles.header}>
                <h1>UI Style Audit</h1>
                <p>Comparison of legacy vs. new design patterns.</p>
            </header>

            <div className={styles.section}>
                <div className={styles.sectionTitle}>Buttons</div>
                <div className={styles.grid} style={{
                    gridTemplateColumns: '1fr 1fr 1fr'
                }}>
                    <div className={styles.column}>
                        <div className={styles.columnHeader}>Legacy (Page Settings)</div>
                        <button className={styles.legacyButton}>
                            Secondary Action
                        </button>
                        <button className={styles.legacyButton}>
                            <Sparkle /> With Icon
                        </button>
                    </div>
                    <div className={styles.column}>
                        <div className={styles.columnHeader}>New (Theme Wizard)</div>
                        <button className={styles.newButton}>
                            Primary Action
                        </button>
                        <button className={styles.newButton}>
                            <Sparkle weight="fill" /> With Icon
                        </button>
                    </div>
                    <div className={styles.column}>
                        <div className={styles.columnHeader}>Latest (Action Icons)</div>
                        <div style={{ display: 'flex', gap: '8px' }}>
                            <button className={styles.iconActionButton} title="Revert">
                                <ArrowCounterClockwise size={20} />
                            </button>
                            <button className={styles.iconActionButton} title="Apply">
                                <MagicWand size={20} weight="fill" />
                            </button>
                            <button className={styles.primaryIconActionButton} title="Save">
                                <Star size={20} weight="fill" />
                            </button>
                        </div>
                    </div>
                </div>
                <div className={styles.grid} style={{ marginTop: '32px' }}>
                    <div className={styles.column}>
                        <div className={styles.columnHeader}>Wizard Custom (To Refactor)</div>
                        <button className={styles.wizardSaveButton}>
                            <Sparkle weight="fill" /> Save Theme (Current)
                        </button>
                        <button className={styles.wizardShuffleButton}>
                            <Sparkle /> Shuffle Colors
                        </button>
                    </div>
                </div>
            </div>

            <div className={styles.section}>
                <div className={styles.sectionTitle}>Tabs</div>
                <div className={styles.grid}>
                    <div className={styles.column}>
                        <div className={styles.columnHeader}>Legacy (Pill Style)</div>
                        <div className={styles.legacyTabList}>
                            <button className={`${styles.legacyTab} ${styles.legacyTabActive}`}>Style</button>
                            <button className={styles.legacyTab}>Settings</button>
                        </div>
                    </div>
                    <div className={styles.column}>
                        <div className={styles.columnHeader}>New (Underline Style)</div>
                        <div className={styles.newTabList}>
                            <button className={`${styles.newTab} ${styles.newTabActive}`}>
                                <Fingerprint /> RSS Feed
                            </button>
                            <button className={styles.newTab}>
                                From Photo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className={styles.section}>
                <div className={styles.sectionTitle}>Inputs</div>
                <div className={styles.grid}>
                    <div className={styles.column}>
                        <div className={styles.columnHeader}>Legacy (Light Mode)</div>
                        <input type="text" className={styles.legacyInput} placeholder="Type something..." />
                    </div>
                    <div className={styles.column}>
                        <div className={styles.columnHeader}>New (Dark Mode Context)</div>
                        <input type="text" className={styles.newInput} placeholder="Type something..." />
                    </div>
                </div>
            </div>
        </div >
    );
}
