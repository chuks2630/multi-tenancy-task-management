'use client';

import { useEffect } from 'react';

export function useKeyboardShortcuts() {
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // Ctrl/Cmd + K for search
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        // TODO: Open search modal
        console.log('Open search');
      }

      // Ctrl/Cmd + B for new board
      if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        // TODO: Open create board modal
        console.log('Create board');
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, []);
}