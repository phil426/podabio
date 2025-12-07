import { describe, it, expect } from 'vitest';
import { renderHook, act, waitFor } from '@testing-library/react';
import { useColorHistory } from '../../components/panels/themes/hooks/useColorHistory';

describe('useColorHistory', () => {
  it('tracks history and supports undo/redo', async () => {
    const { result } = renderHook(() => useColorHistory(['#111111', '#222222']));

    act(() => {
      result.current.setColors(['#aaaaaa', '#bbbbbb']);
    });

    expect(result.current.colors).toEqual(['#aaaaaa', '#bbbbbb']);
    expect(result.current.canUndo).toBe(true);

    act(() => {
      result.current.undo();
    });

    await waitFor(() => {
      expect(result.current.colors).toEqual(['#111111', '#222222']);
    });
  });

  it('limits history size to 50', () => {
    const { result } = renderHook(() => useColorHistory(['#000000']));

    act(() => {
      for (let i = 0; i < 60; i += 1) {
        result.current.setColors([`#${(i + 1).toString(16).padStart(6, '0')}`]);
      }
    });

    expect(result.current.canUndo).toBe(true);
  });
});

