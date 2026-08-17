import { useState, useEffect } from 'react'

type Theme = 'dark' | 'light'

function getInitialTheme(): Theme {
  // 1. Check localStorage
  const stored = localStorage.getItem('ts-theme') as Theme | null
  if (stored === 'dark' || stored === 'light') return stored

  // 2. Fall back to system preference — default to light if no preference
  if (window.matchMedia('(prefers-color-scheme: dark)').matches) return 'dark'
  return 'light'
}

function applyTheme(theme: Theme) {
  document.documentElement.setAttribute('data-theme', theme)
  localStorage.setItem('ts-theme', theme)
}

export function useTheme() {
  const [theme, setTheme] = useState<Theme>(() => {
    // Safe SSR guard — won't run on server, only browser
    if (typeof window === 'undefined') return 'dark'
    return getInitialTheme()
  })

  // Apply on mount and when theme changes
  useEffect(() => {
    applyTheme(theme)
  }, [theme])

  const toggle = () => setTheme(t => (t === 'dark' ? 'light' : 'dark'))

  return { theme, toggle, isDark: theme === 'dark' }
}
