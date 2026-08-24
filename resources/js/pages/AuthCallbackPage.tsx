/**
 * AuthCallbackPage
 *
 * Handles the redirect back from backend OAuth / magic-link flows.
 * The backend redirects to /auth/callback?token=...&user=...
 * We parse the params, persist them, and push the user to /dashboard.
 */
import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import toast from 'react-hot-toast'

export default function AuthCallbackPage() {
  const navigate = useNavigate()

  useEffect(() => {
    const params = new URLSearchParams(window.location.search)
    const token  = params.get('token')
    const userRaw = params.get('user')
    const error  = params.get('error')

    if (error) {
      const messages: Record<string, string> = {
        invalid_token: 'This sign-in link is invalid.',
        expired_token: 'This sign-in link has expired. Please request a new one.',
        google_failed: 'Google sign-in failed. Please try again.',
      }
      toast.error(messages[error] ?? 'Sign-in failed. Please try again.')
      navigate('/login', { replace: true })
      return
    }

    if (token && userRaw) {
      try {
        const user = JSON.parse(decodeURIComponent(userRaw))
        localStorage.setItem('auth_token', token)
        localStorage.setItem('auth_user', JSON.stringify(user))
        navigate('/dashboard', { replace: true })
      } catch {
        toast.error('Sign-in failed. Please try again.')
        navigate('/login', { replace: true })
      }
      return
    }

    // Fallback — should not normally reach here
    navigate('/login', { replace: true })
  }, [navigate])

  return (
    <div style={{
      minHeight: '100dvh',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      background: 'var(--bg)',
    }}>
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 14 }}>
        <span style={{
          width: 28, height: 28,
          borderRadius: '50%',
          border: '2.5px solid rgba(59,130,246,0.2)',
          borderTopColor: '#3b82f6',
          animation: 'ts-spin 0.7s linear infinite',
          display: 'block',
        }} />
        <p style={{ fontSize: 13.5, color: 'var(--text3)', margin: 0 }}>Signing you in…</p>
      </div>
      <style>{`@keyframes ts-spin { to { transform: rotate(360deg); } }`}</style>
    </div>
  )
}
