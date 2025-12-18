
import { useState } from 'react';
import { Check, X, CircleNotch, Plus, Eye, EyeSlash, DotsSixVertical, PencilSimple } from '@phosphor-icons/react';
import {
    DndContext,
    PointerSensor,
    useSensor,
    useSensors,
    closestCenter,
    DragEndEvent
} from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
    arrayMove,
    useSortable
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import { usePageSnapshot, addSocialIcon, toggleSocialIconVisibility, reorderSocialIcons } from '../../api/page';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../api/utils';
import { useSocialIconSelection } from '../../state/socialIconSelection';

import styles from './social-icons-manager.module.css';
import { ALL_PLATFORMS } from './social-platforms';
import { getPlatformIcon } from './social-icons';

// --- Subcomponent: SortableIconCard ---
interface SortableIconCardProps {
    icon: { id: number | string; platform_name: string; url: string | null; is_active?: number };
    isSelected: boolean;
    onSelect: (iconId: number | string) => void;
    onEdit?: (iconId: number | string) => void;
    onToggleVisibility: (e: React.MouseEvent, iconId: number | string, currentActive: boolean) => void;
    toggleVisibilityPending: boolean;
    getPlatformIcon: (platformName: string) => JSX.Element;
}

function SortableIconCard({
    icon,
    isSelected,
    onSelect,
    onEdit,
    onToggleVisibility,
    toggleVisibilityPending,
    getPlatformIcon
}: SortableIconCardProps): JSX.Element {
    const isActive = (icon.is_active ?? 0) !== 0;

    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: String(icon.id)
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        zIndex: isDragging ? 2 : undefined
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`${styles.iconCard} ${isSelected ? styles.iconCardSelected : ''}`}
            data-dnd-kit-dragging={isDragging ? 'true' : undefined}
            onClick={() => onSelect(icon.id)}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    onSelect(icon.id);
                }
            }}
        >
            <span
                className={styles.gripIcon}
                {...attributes}
                {...listeners}
                aria-hidden="true"
            >
                <DotsSixVertical size={16} weight="regular" />
            </span>
            <div className={styles.iconIcon} data-active={isActive ? 'true' : 'false'}>
                {getPlatformIcon(icon.platform_name)}
            </div>
            <div className={styles.iconDetails}>
                <p className={styles.iconName}>
                    {ALL_PLATFORMS[icon.platform_name] || icon.platform_name}
                </p>
                <p className={styles.iconUrl}>
                    {icon.url || <em>No URL added</em>}
                </p>
            </div>
            <div className={styles.actions} onClick={(e) => e.stopPropagation()}>
                <button
                    type="button"
                    className={styles.visibilityButton}
                    onClick={(e) => onToggleVisibility(e, icon.id, isActive)}
                    disabled={toggleVisibilityPending}
                    aria-label={isActive ? `Hide ${ALL_PLATFORMS[icon.platform_name] || icon.platform_name}` : `Show ${ALL_PLATFORMS[icon.platform_name] || icon.platform_name}`}
                    title={isActive ? 'Hide' : 'Show'}
                    data-active={isActive ? 'true' : 'false'}
                >
                    {isActive ? <Eye aria-hidden="true" size={16} weight="regular" /> : <EyeSlash aria-hidden="true" size={16} weight="regular" />}
                </button>
                {onEdit && (
                    <button
                        type="button"
                        className={styles.visibilityButton} // Reuse button style for simplicity
                        onClick={(e) => {
                            e.stopPropagation();
                            onEdit(icon.id);
                        }}
                        aria-label={`Edit ${ALL_PLATFORMS[icon.platform_name] || icon.platform_name}`}
                        title="Edit"
                    >
                        <PencilSimple size={16} weight="regular" />
                    </button>
                )}
            </div>
        </div>
    );
}

// --- Main Component: SocialIconsManager ---
export function SocialIconsManager({ onEdit }: { onEdit?: (id: string | number) => void }): JSX.Element {
    const { data: snapshot, isLoading } = usePageSnapshot();
    const queryClient = useQueryClient();
    const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);
    const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);
    const [addingPlatform, setAddingPlatform] = useState('');
    const [addingUrl, setAddingUrl] = useState('');
    const [status, setStatus] = useState<string | null>(null);

    const socialIcons = snapshot?.social_icons || [];
    const pageId = snapshot?.page?.id;

    const addMutation = useMutation({
        mutationFn: (payload: { platform_name: string; url: string }) => addSocialIcon(payload),
        onSuccess: () => {
            // ... existing success logic
            queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
            setAddingPlatform('');
            setAddingUrl('');
            setStatus('Social icon added successfully.');
            setTimeout(() => setStatus(null), 3000);
        },
        onError: () => {
            // ... existing error logic
            setStatus('Failed to add social icon.');
            setTimeout(() => setStatus(null), 3000);
        }
    });

    const handleAdd = () => {
        if (!addingPlatform || !addingUrl || !pageId) return;
        addMutation.mutate({
            platform_name: addingPlatform,
            url: addingUrl
        });
    };

    const handleSelect = (iconId: number | string) => {
        const idString = String(iconId);
        if (selectedSocialIconId === idString) {
            selectSocialIcon(null);
        } else {
            selectSocialIcon(idString);
        }
    };

    const toggleVisibilityMutation = useMutation({
        mutationFn: (payload: { icon_id: number | string; is_active: boolean }) =>
            toggleSocialIconVisibility({
                icon_id: String(payload.icon_id),
                is_active: String(payload.is_active)
            }),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
        }
    });

    const handleToggleVisibility = (e: React.MouseEvent, iconId: number | string, currentActive: boolean) => {
        e.stopPropagation();
        toggleVisibilityMutation.mutate({
            icon_id: iconId,
            is_active: !currentActive
        });
    };

    const reorderMutation = useMutation({
        mutationFn: (iconOrders: Array<{ icon_id: number; display_order: number }>) =>
            reorderSocialIcons(iconOrders),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
        }
    });

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { distance: 6 }
        })
    );

    const handleDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over || active.id === over.id) return;

        const oldIndex = socialIcons.findIndex((icon) => String(icon.id) === active.id);
        const newIndex = socialIcons.findIndex((icon) => String(icon.id) === over.id);

        if (oldIndex === -1 || newIndex === -1) return;

        const reordered = arrayMove(socialIcons, oldIndex, newIndex);

        const iconOrders = reordered.map((icon, index) => ({
            icon_id: typeof icon.id === 'string' ? parseInt(icon.id, 10) : icon.id,
            display_order: index + 1
        }));

        reorderMutation.mutate(iconOrders);
    };

    const existingPlatforms = new Set(socialIcons.map(icon => icon.platform_name));
    const availablePlatforms = Object.entries(ALL_PLATFORMS).filter(
        ([key]) => !existingPlatforms.has(key)
    );

    if (isLoading) {
        return (
            <div className={styles.container}>
                <div className={styles.loadingState}>
                    <CircleNotch className={styles.spinner} size={20} weight="regular" />
                    <p>Loading settings…</p>
                </div>
            </div>
        );
    }

    return (
        <div className={styles.container} style={{ padding: '0px' }}> {/* Override padding for modal use */}

            {status && (
                <div className={styles.statusBanner}>
                    {status.includes('successfully') ? (
                        <Check aria-hidden="true" size={16} weight="regular" />
                    ) : (
                        <X aria-hidden="true" size={16} weight="regular" />
                    )}
                    <span>{status}</span>
                </div>
            )}

            <div className={styles.iconsList}>
                {/* Add Form */}
                {availablePlatforms.length > 0 && (
                    <div className={styles.addCard}>
                        <div className={styles.addCardHeader}>
                            <Plus className={styles.addCardIcon} aria-hidden="true" size={16} weight="regular" />
                            <span className={styles.addCardTitle}>Add Social Icon</span>
                        </div>
                        <div className={styles.addCardForm}>
                            <div className={styles.addCardField}>
                                <select
                                    value={addingPlatform}
                                    onChange={(e) => setAddingPlatform(e.target.value)}
                                    className={styles.addCardSelect}
                                >
                                    <option value="">Select platform</option>
                                    {availablePlatforms.map(([key, name]) => (
                                        <option key={key} value={key}>
                                            {name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className={styles.addCardField}>
                                <input
                                    type="url"
                                    value={addingUrl}
                                    onChange={(e) => setAddingUrl(e.target.value)}
                                    placeholder="https://..."
                                    className={styles.addCardInput}
                                />
                            </div>
                            <button
                                type="button"
                                onClick={handleAdd}
                                className={styles.addCardButton}
                                disabled={!addingPlatform || !addingUrl || addMutation.isPending}
                            >
                                {addMutation.isPending ? (
                                    <>
                                        <CircleNotch className={styles.buttonSpinner} size={16} weight="regular" />
                                        <span>Adding…</span>
                                    </>
                                ) : (
                                    <>
                                        <Plus aria-hidden="true" size={16} weight="regular" />
                                        <span>Add</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                )}

                {/* Sortable List */}
                {socialIcons.length > 0 && (
                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                        <SortableContext items={socialIcons.map(icon => String(icon.id))} strategy={verticalListSortingStrategy}>
                            {socialIcons.map((icon) => (
                                <SortableIconCard
                                    key={icon.id}
                                    icon={icon}
                                    isSelected={selectedSocialIconId === String(icon.id)}
                                    onSelect={handleSelect}
                                    onEdit={onEdit}
                                    onToggleVisibility={handleToggleVisibility}
                                    toggleVisibilityPending={toggleVisibilityMutation.isPending}
                                    getPlatformIcon={getPlatformIcon}
                                />
                            ))}
                        </SortableContext>
                    </DndContext>
                )}

                {availablePlatforms.length === 0 && socialIcons.length > 0 && (
                    <p className={styles.allAdded}>All available platforms have been added.</p>
                )}

                {socialIcons.length === 0 && availablePlatforms.length > 0 && (
                    <p className={styles.emptyList}>No social icons added yet.</p>
                )}
            </div>
        </div>
    );
}
