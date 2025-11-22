import clsx from 'clsx';
import { useMemo, useState, useEffect, useRef } from 'react';
import { CaretDown, DeviceMobile, MagnifyingGlassPlus, MagnifyingGlassMinus } from '@phosphor-icons/react';

import { usePageSnapshot } from '../../api/page';
import styles from './mobile-preview-bar.module.css';

interface DevicePreset {
  id: string;
  name: string;
  width: number;
  height: number;
  aspectRatio: string;
}

// Popular phones including all iPhone 15, 16, and 17 models
const DEVICE_PRESETS: DevicePreset[] = [
  // iPhone 17 line
  { id: 'iphone-17-pro-max', name: 'iPhone 17 Pro Max', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'iphone-17-pro', name: 'iPhone 17 Pro', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'iphone-17-plus', name: 'iPhone 17 Plus', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'iphone-17', name: 'iPhone 17', width: 390, height: 844, aspectRatio: '19.5:9' },
  // iPhone 16 line
  { id: 'iphone-16-pro-max', name: 'iPhone 16 Pro Max', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'iphone-16-pro', name: 'iPhone 16 Pro', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'iphone-16-plus', name: 'iPhone 16 Plus', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'iphone-16', name: 'iPhone 16', width: 390, height: 844, aspectRatio: '19.5:9' },
  // iPhone 15 line
  { id: 'iphone-15-pro-max', name: 'iPhone 15 Pro Max', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'iphone-15-pro', name: 'iPhone 15 Pro', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'iphone-15-plus', name: 'iPhone 15 Plus', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'iphone-15', name: 'iPhone 15', width: 390, height: 844, aspectRatio: '19.5:9' },
  // Samsung Galaxy S series (2023-2025)
  { id: 'samsung-s25-ultra', name: 'Samsung S25 Ultra', width: 412, height: 915, aspectRatio: '19.3:9' },
  { id: 'samsung-s25-plus', name: 'Samsung S25+', width: 412, height: 915, aspectRatio: '19.3:9' },
  { id: 'samsung-s25', name: 'Samsung S25', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'samsung-s24-ultra', name: 'Samsung S24 Ultra', width: 412, height: 915, aspectRatio: '19.3:9' },
  { id: 'samsung-s24-plus', name: 'Samsung S24+', width: 412, height: 915, aspectRatio: '19.3:9' },
  { id: 'samsung-s24', name: 'Samsung S24', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'samsung-s23-ultra', name: 'Samsung S23 Ultra', width: 412, height: 915, aspectRatio: '19.3:9' },
  { id: 'samsung-s23-plus', name: 'Samsung S23+', width: 412, height: 915, aspectRatio: '19.3:9' },
  { id: 'samsung-s23', name: 'Samsung S23', width: 393, height: 852, aspectRatio: '19.5:9' },
  // Samsung Galaxy A series (2023-2025)
  { id: 'galaxy-a55', name: 'Galaxy A55', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'galaxy-a54', name: 'Galaxy A54', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'galaxy-a53', name: 'Galaxy A53', width: 393, height: 852, aspectRatio: '19.5:9' },
  // Google Pixel series (2023-2025)
  { id: 'pixel-9-pro-xl', name: 'Pixel 9 Pro XL', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'pixel-9-pro', name: 'Pixel 9 Pro', width: 412, height: 915, aspectRatio: '19.5:9' },
  { id: 'pixel-9', name: 'Pixel 9', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'pixel-8a', name: 'Pixel 8a', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'pixel-8-pro', name: 'Pixel 8 Pro', width: 412, height: 915, aspectRatio: '19.5:9' },
  { id: 'pixel-8', name: 'Pixel 8', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'pixel-7a', name: 'Pixel 7a', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'pixel-7-pro', name: 'Pixel 7 Pro', width: 412, height: 915, aspectRatio: '19.5:9' },
  { id: 'pixel-7', name: 'Pixel 7', width: 393, height: 852, aspectRatio: '19.5:9' },
  // Other popular phones
  { id: 'iphone-14-pro', name: 'iPhone 14 Pro', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'iphone-14', name: 'iPhone 14', width: 390, height: 844, aspectRatio: '19.5:9' },
  { id: 'oneplus-12', name: 'OnePlus 12', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'xiaomi-14-pro', name: 'Xiaomi 14 Pro', width: 412, height: 915, aspectRatio: '19.5:9' },
  { id: 'iphone-se', name: 'iPhone SE', width: 375, height: 667, aspectRatio: '16:9' }
];

interface MobilePreviewBarProps {
  selectedDevice: DevicePreset;
  onDeviceChange: (device: DevicePreset) => void;
  previewScale: number;
  onScaleChange: (scale: number) => void;
  style?: React.CSSProperties;
}

export function MobilePreviewBar({ selectedDevice, onDeviceChange, previewScale, onScaleChange, style }: MobilePreviewBarProps): JSX.Element {
  const { data } = usePageSnapshot();
  const [showDeviceMenu, setShowDeviceMenu] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  const page = data?.page;
  const publishStatus = (page?.publish_status ?? 'draft') as 'draft' | 'published' | 'scheduled';
  const publishedAt = page?.published_at;
  const scheduledPublishAt = page?.scheduled_publish_at;

  const statusCopy = useMemo(() => {
    switch (publishStatus) {
      case 'published':
        return publishedAt ? `Live since ${formatDateTime(publishedAt)}` : 'Live on your PodaBio link.';
      case 'scheduled':
        return scheduledPublishAt
          ? `Scheduled for ${formatDateTime(scheduledPublishAt)}`
          : 'Scheduled publish time not set.';
      default:
        return 'Currently in draft. Publish when you are ready to share.';
    }
  }, [publishStatus, publishedAt, scheduledPublishAt]);

  // Close menu when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setShowDeviceMenu(false);
      }
    };

    if (showDeviceMenu) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [showDeviceMenu]);

  const handleDeviceSelect = (device: DevicePreset) => {
    onDeviceChange(device);
    setShowDeviceMenu(false);
  };

  return (
    <div className={styles.bar} style={style}>
      <div className={styles.barInfo}>
        <div className={styles.barHeadingRow}>
          <span className={styles.barLabel}>Mobile preview</span>
          <span className={clsx(styles.statusBadge, styles[`statusBadge_${publishStatus}`])}>
            {publishStatus === 'published' ? 'Live' : publishStatus === 'scheduled' ? 'Scheduled' : 'Draft'}
          </span>
        </div>
        <p className={styles.barMeta}>{statusCopy}</p>
      </div>
      <div className={styles.controls}>
        <div className={styles.scaleControl}>
          <button
            type="button"
            className={styles.scaleButton}
            onClick={() => onScaleChange(Math.max(0.25, previewScale - 0.1))}
            aria-label="Zoom out"
            title="Zoom out preview"
            disabled={previewScale <= 0.25}
          >
            <MagnifyingGlassMinus aria-hidden="true" size={16} weight="regular" />
          </button>
          <span className={styles.scaleValue}>{Math.round(previewScale * 100)}%</span>
          <button
            type="button"
            className={styles.scaleButton}
            onClick={() => onScaleChange(Math.min(1, previewScale + 0.1))}
            aria-label="Zoom in"
            title="Zoom in preview"
            disabled={previewScale >= 1}
          >
            <MagnifyingGlassPlus aria-hidden="true" size={16} weight="regular" />
          </button>
        </div>
        <div className={styles.deviceSelector} ref={menuRef}>
          <button
            type="button"
            className={styles.deviceButton}
            onClick={() => setShowDeviceMenu(!showDeviceMenu)}
            aria-label="Select device"
            title="Change device preset"
            aria-expanded={showDeviceMenu}
          >
            <DeviceMobile className={styles.deviceIcon} aria-hidden="true" size={16} weight="regular" />
            <span className={styles.deviceName}>{selectedDevice.name}</span>
            <CaretDown className={clsx(styles.chevronIcon, showDeviceMenu && styles.chevronIconOpen)} aria-hidden="true" size={16} weight="regular" />
          </button>
          {showDeviceMenu && (
            <div className={styles.deviceMenu}>
              {DEVICE_PRESETS.map((device) => (
                <button
                  key={device.id}
                  type="button"
                  className={clsx(styles.deviceMenuItem, selectedDevice.id === device.id && styles.deviceMenuItemActive)}
                  onClick={() => handleDeviceSelect(device)}
                >
                  <span className={styles.deviceMenuItemName}>{device.name}</span>
                  <span className={styles.deviceMenuItemSize}>
                    {device.width} × {device.height}
                  </span>
                </button>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function formatDateTime(dateString: string): string {
  try {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat(undefined, {
      dateStyle: 'medium',
      timeStyle: 'short'
    }).format(date);
  } catch {
    return dateString;
  }
}

