/**
 * Podcast Theme Generator Component
 * Refactored modular version using useThemeWizardController
 */

import { WizardHeader } from './wizard/WizardHeader';
import { WizardTabs } from './wizard/WizardTabs';
import { RSSTabContent } from './wizard/RSSTabContent';
import { PhotoTabContent } from './wizard/PhotoTabContent';
import { ColorPalette } from './wizard/ColorPalette';
import { PreviewActions } from './wizard/PreviewActions';
import { ThemePreview } from './preview/ThemePreview';
import { MediaLibraryModal } from '../../overlays/MediaLibraryModal';
import { useThemeWizardController } from './hooks/useThemeWizardController';
import styles from './podcast-theme-generator.module.css';

interface PodcastThemeGeneratorProps {
  coverImageUrl: string | null;
  onClose: () => void;
  onThemeGenerated?: (themeId: number) => void;
}

export function PodcastThemeGenerator({
  coverImageUrl,
  onClose,
  onThemeGenerated
}: PodcastThemeGeneratorProps): JSX.Element {

  const {
    state,
    actions,
    previewCSSVars,
    previewContent,
    handleSearchPodcasts,
    handleSelectPodcast,
    handleImageUpload,
    handleSelectFromMediaLibrary,
    handleShuffle,
    handleDragStart,
    handleDragOver,
    handleDragLeave,
    handleDrop,
    handleDragEnd,
    handleGenerateTheme,
    applyPreview,
    revertPreview,
  } = useThemeWizardController(coverImageUrl, onClose, onThemeGenerated);

  return (
    <div className={styles.container}>
      {/* Left Panel: Controls - Wrapped in controlsPanel for flex layout */}
      <div className={styles.controlsPanel}>
        <WizardHeader onClose={onClose} />

        <WizardTabs
          activeTab={state.activeTab}
          onTabChange={actions.setActiveTab}
        />

        <div className={styles.scrollableContent}>
          {state.activeTab === 'rss' ? (
            <RSSTabContent
              searchQuery={state.searchQuery}
              isSearching={state.isSearching}
              searchResults={state.searchResults}
              selectedPodcast={state.selectedPodcast}
              uploadedImageUrl={state.uploadedImageUrl || state.activeImageUrl}
              onSearchQueryChange={actions.setSearchQuery}
              onSearch={handleSearchPodcasts}
              onSelectPodcast={handleSelectPodcast}
            />
          ) : (
            <PhotoTabContent
              uploadedImageUrl={state.activeImageUrl || state.uploadedImageUrl}
              isUploading={false} // Controller doesn't expose generic uploading state yet, usually fast enough
              onOpenMediaLibrary={() => actions.setMediaLibraryOpen(true)}
            />
          )}

          <ColorPalette
            colors={state.colors}
            isExtracting={false} // TODO: Add isExtracting to controller state/hook if needed (using isGenerating proxy currently in old code, but clean here)
            isShuffling={state.isShuffling}
            error={state.error}
            draggedIndex={state.draggedIndex}
            dragOverIndex={state.dragOverIndex}
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
            onDragEnd={handleDragEnd}
            onShuffle={handleShuffle}
            onColorsChange={actions.setColors}
          />
        </div>

        <PreviewActions
          isGenerating={state.isGenerating}
          colorsCount={state.colors.length}
          onGenerate={handleGenerateTheme}
          onCancel={onClose}
          onApply={applyPreview}
          onRevert={revertPreview}
        />
      </div>

      {/* Right Panel: Live Preview */}
      <div className={styles.previewPanel}>
        <div className={styles.previewWrapper}>
          <ThemePreview
            cssVars={previewCSSVars}
            hotspotsVisible={false}
          />
        </div>
      </div>

      {/* External Drawers */}
      {/* External Drawers */}
      <MediaLibraryModal
        open={state.mediaLibraryOpen}
        onClose={() => actions.setMediaLibraryOpen(false)}
        onSelect={(item) => {
          handleSelectFromMediaLibrary(item);
          actions.setMediaLibraryOpen(false);
        }}
      />
    </div>
  );
}
