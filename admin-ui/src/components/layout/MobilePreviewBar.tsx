import clsx from 'clsx';
import { useMemo, useState, useEffect, useRef } from 'react';
import { LuChevronDown, LuSmartphone } from 'react-icons/lu';

import { usePageSnapshot } from '../../api/page';
import styles from './mobile-preview-bar.module.css';

interface DevicePreset {
  id: string;
  name: string;
  width: number;
  height: number;
  aspectRatio: string;
}

// Top 5 most popular non-folding phones (2024)
const DEVICE_PRESETS: DevicePreset[] = [
  { id: 'iphone-15-pro-max', name: 'iPhone 15 Pro Max', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'iphone-15-pro', name: 'iPhone 15 Pro', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'samsung-s24-ultra', name: 'Samsung S24 Ultra', width: 412, height: 915, aspectRatio: '19.3:9' },
  { id: 'pixel-8-pro', name: 'Pixel 8 Pro', width: 412, height: 915, aspectRatio: '19.5:9' },
  { id: 'iphone-15', name: 'iPhone 15', width: 390, height: 844, aspectRatio: '19.5:9' }
];

interface MobilePreviewBarProps {
  selectedDevice: DevicePreset;
  onDeviceChange: (device: DevicePreset) => void;
  style?: React.CSSProperties;
}

export function MobilePreviewBar({ selectedDevice, onDeviceChange, style }: MobilePreviewBarProps): JSX.Element {
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
      <div className={styles.deviceSelector} ref={menuRef}>
        <button
          type="button"
          className={styles.deviceButton}
          onClick={() => setShowDeviceMenu(!showDeviceMenu)}
          aria-label="Select device"
          aria-expanded={showDeviceMenu}
        >
          <LuSmartphone className={styles.deviceIcon} aria-hidden="true" />
          <span className={styles.deviceName}>{selectedDevice.name}</span>
          <LuChevronDown className={clsx(styles.chevronIcon, showDeviceMenu && styles.chevronIconOpen)} aria-hidden="true" />
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

