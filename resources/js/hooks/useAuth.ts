import { useState, useEffect } from 'react'
import api from '../lib/api'

interface User {
  id: number
  name: string
  email: string
}

interface AuthState {
  user: User | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
}

export function useAuth(): AuthState {
  const [user, setUser] = useState<User | null>(() => {
    try {
      const stored = localStorage.getItem('auth_user')
      return stored ? JSON.parse(stored) : null
    } catch {
      return null
    }
  })
  const [isLoading, setIsLoading] = useState(false)

  const login = async (email: string, password: string) => {
    const res = await api.post('/auth/login', { email, password })
    const { user: u, token } = res.data
    localStorage.setItem('auth_token', token)
    localStorage.setItem('auth_user', JSON.stringify(u))
    setUser(u)
  }

  const logout = async () => {
    try {
      await api.post('/auth/logout')
    } finally {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      setUser(null)
    }
  }

  return {
    user,
    isAuthenticated: !!user && !!localStorage.getItem('auth_token'),
    isLoading,
    login,
    logout,
  }
}
