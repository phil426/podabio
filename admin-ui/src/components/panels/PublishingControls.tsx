import { useEffect, useMemo, useState } from 'react';

import { usePageSnapshot, usePublishStateMutation } from '../../api/page';
import styles from './publishing-controls.module.css';

export function PublishingControls(): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const { mutateAsync: updatePublishState, isPending } = usePublishStateMutation();
  const page = snapshot?.page;

  const publishStatus = (page?.publish_status ?? 'draft') as 'draft' | 'published' | 'scheduled';
  const publishedAt = page?.published_at ?? null;
  const scheduledAt = page?.scheduled_publish_at ?? null;

  const [scheduleInput, setScheduleInput] = useState('');
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const [statusTone, setStatusTone] = useState<'success' | 'error'>('success');

  useEffect(() => {
    if (scheduledAt) {
      setScheduleInput(toLocalInputValue(scheduledAt));
    } else {
      setScheduleInput('');
    }
  }, [scheduledAt]);

  useEffect(() => {
    if (!statusMessage) return;
    const timer = window.setTimeout(() => setStatusMessage(null), 3500);
    return () => window.clearTimeout(timer);
  }, [statusMessage]);

  const statusCopy = useMemo(() => {
    switch (publishStatus) {
      case 'published':
        return publishedAt ? `Live since ${formatDateTime(publishedAt)}` : 'Live on your PodaBio link.';
      case 'scheduled':
        return scheduledAt
          ? `Scheduled for ${formatDateTime(scheduledAt)}`
          : 'Scheduled publish time not set.';
      default:
        return 'Currently in draft. Publish when you are ready to share.';
    }
  }, [publishStatus, publishedAt, scheduledAt]);

  const handlePublishNow = async () => {
    try {
      await updatePublishState({ publish_status: 'published' });
      setStatusTone('success');
      setStatusMessage('Your page is now live!');
    } catch (error) {
      setStatusTone('error');
      setStatusMessage(error instanceof Error ? error.message : 'Unable to publish right now.');
    }
  };

  const handleRevertDraft = async () => {
    try {
      await updatePublishState({ publish_status: 'draft' });
      setStatusTone('success');
      setStatusMessage('Page moved back to draft.');
    } catch (error) {
      setStatusTone('error');
      setStatusMessage(error instanceof Error ? error.message : 'Unable to revert to draft.');
    }
  };

  const handleSchedule = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!scheduleInput) {
      setStatusTone('error');
      setStatusMessage('Choose a future date before scheduling.');
      return;
    }

    try {
      await updatePublishState({ publish_status: 'scheduled', scheduled_publish_at: scheduleInput });
      setStatusTone('success');
      setStatusMessage('Publish scheduled successfully.');
    } catch (error) {
      setStatusTone('error');
      setStatusMessage(error instanceof Error ? error.message : 'Unable to schedule publish.');
    }
  };

  return (
    <section className={styles.container} aria-labelledby="publishing-controls-title">
      <header className={styles.header}>
        <div>
          <h3 id="publishing-controls-title">Publishing controls</h3>
          <p>{statusCopy}</p>
        </div>
        <span className={styles[`status_${publishStatus}`]}>
          {publishStatus === 'published' ? 'Live' : publishStatus === 'scheduled' ? 'Scheduled' : 'Draft'}
        </span>
      </header>

      <div className={styles.actions}>
        <button type="button" onClick={handlePublishNow} disabled={publishStatus === 'published' || isPending}>
          {isPending && publishStatus === 'published' ? 'Publishing…' : 'Publish now'}
        </button>
        <form className={styles.scheduleForm} onSubmit={handleSchedule}>
          <label>
            <span>Schedule</span>
            <input
              type="datetime-local"
              value={scheduleInput}
              onChange={(event) => setScheduleInput(event.target.value)}
              min={toLocalInputValue(new Date().toISOString())}
            />
          </label>
          <button type="submit" disabled={isPending}>
            {isPending && publishStatus === 'scheduled' ? 'Scheduling…' : 'Set'}
          </button>
        </form>
        <button type="button" onClick={handleRevertDraft} disabled={publishStatus === 'draft' || isPending}>
          Revert to draft
        </button>
      </div>

      {statusMessage && (
        <p className={styles[`statusMessage_${statusTone}`]} role="status">
          {statusMessage}
        </p>
      )}

      {publishedAt && (
        <dl className={styles.metaList}>
          <div>
            <dt>Last published</dt>
            <dd>{formatDateTime(publishedAt)}</dd>
          </div>
          {scheduledAt && (
            <div>
              <dt>Next scheduled publish</dt>
              <dd>{formatDateTime(scheduledAt)}</dd>
            </div>
          )}
        </dl>
      )}
    </section>
  );
}

function toLocalInputValue(dateString: string): string {
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = `${date.getMonth() + 1}`.padStart(2, '0');
  const day = `${date.getDate()}`.padStart(2, '0');
  const hours = `${date.getHours()}`.padStart(2, '0');
  const minutes = `${date.getMinutes()}`.padStart(2, '0');
  return `${year}-${month}-${day}T${hours}:${minutes}`;
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
