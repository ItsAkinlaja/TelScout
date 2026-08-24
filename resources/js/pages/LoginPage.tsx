import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { useTheme } from '../hooks/useTheme'
import { Eye, EyeOff, ArrowRight, Sun, Moon, Mail, CheckCircle2 } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'

// ─── Google icon (inline SVG — no extra dep needed) ──────────────────────────
function GoogleIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
      <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615Z" fill="#4285F4" />
      <path d="M9 18c2.43 0 4.467-.806 5.956-2.185l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18Z" fill="#34A853" />
      <path d="M3.964 10.706A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.706V4.962H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.038l3.007-2.332Z" fill="#FBBC05" />
      <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.962L3.964 6.294C4.672 4.169 6.656 3.58 9 3.58Z" fill="#EA4335" />
    </svg>
  )
}

// ─── Tab type ─────────────────────────────────────────────────────────────────
type Tab = 'password' | 'magic'

export default function LoginPage() {
  const { login }          = useAuth()
  const { isDark, toggle } = useTheme()
  const navigate           = useNavigate()

  // Active tab
  const [tab, setTab] = useState<Tab>('password')

  // Password form
  const [email,    setEmail]    = useState('')
  const [password, setPassword] = useState('')
  const [showPw,   setShowPw]   = useState(false)
  const [pwLoading, setPwLoading] = useState(false)

  // Magic-link form
  const [magicEmail,   setMagicEmail]   = useState('')
  const [magicLoading, setMagicLoading] = useState(false)
  const [magicSent,    setMagicSent]    = useState(false)

  // Google loading
  const [googleLoading, setGoogleLoading] = useState(false)

  // ── Handlers ────────────────────────────────────────────────────────────────

  const handlePasswordSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setPwLoading(true)
    try {
      await login(email, password)
      navigate('/dashboard')
    } catch (err: any) {
      toast.error(err.response?.data?.message ?? 'Invalid credentials')
    } finally {
      setPwLoading(false)
    }
  }

  const handleMagicSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setMagicLoading(true)
    try {
      await api.post('/auth/magic-link', { email: magicEmail })
      setMagicSent(true)
    } catch (err: any) {
      if (err.response?.status === 429) {
        toast.error('Too many requests — wait a few minutes and try again.')
      } else {
        toast.error(err.response?.data?.message ?? 'Something went wrong. Please try again.')
      }
    } finally {
      setMagicLoading(false)
    }
  }

  const handleGoogleLogin = () => {
    setGoogleLoading(true)
    // Full-page redirect to backend → Google consent screen → callback → SPA
    window.location.href = '/api/auth/google'
  }

  // ── Render ───────────────────────────────────────────────────────────────────
  return (
    <>
      <div className="ts-login-root">
        <div className="ts-login-glow" />
        <div className="ts-login-grid" />

        {/* Theme toggle */}
        <button className="ts-login-theme-btn" onClick={toggle} title={isDark ? 'Light mode' : 'Dark mode'}>
          {isDark ? <Sun size={16} strokeWidth={1.75} /> : <Moon size={16} strokeWidth={1.75} />}
        </button>

        <div className="ts-login-wrap">
          {/* Logo */}
          <div className="ts-login-logo">
            <img
              src="https://ik.imagekit.io/ajide/Telscout%20logo"
              alt="TelScout"
              style={{ height: 48, width: 'auto', display: 'block' }}
            />
          </div>

          {/* Card */}
          <div className="ts-login-card">
            <div className="ts-lc-header">
              <h1 className="ts-lc-title">Welcome back</h1>
              <p className="ts-lc-sub">Sign in to your account to continue</p>
            </div>

            {/* ── Google SSO ────────────────────────────────────────────── */}
            <button
              className="ts-google-btn"
              onClick={handleGoogleLogin}
              disabled={googleLoading}
              type="button"
            >
              {googleLoading
                ? <span className="ts-spinner ts-spinner-dark" />
                : <><GoogleIcon /><span>Continue with Google</span></>
              }
            </button>

            {/* ── Divider ───────────────────────────────────────────────── */}
            <div className="ts-divider">
              <span className="ts-divider-line" />
              <span className="ts-divider-text">or sign in with</span>
              <span className="ts-divider-line" />
            </div>

            {/* ── Tabs ──────────────────────────────────────────────────── */}
            <div className="ts-tabs" role="tablist">
              <button
                role="tab"
                aria-selected={tab === 'password'}
                className={`ts-tab${tab === 'password' ? ' ts-tab--active' : ''}`}
                onClick={() => { setTab('password'); setMagicSent(false) }}
                type="button"
              >
                Password
              </button>
              <button
                role="tab"
                aria-selected={tab === 'magic'}
                className={`ts-tab${tab === 'magic' ? ' ts-tab--active' : ''}`}
                onClick={() => setTab('magic')}
                type="button"
              >
                Email link
              </button>
            </div>

            {/* ── Password form ─────────────────────────────────────────── */}
            {tab === 'password' && (
              <form onSubmit={handlePasswordSubmit} className="ts-lc-form">
                <div className="ts-lf-field">
                  <label className="ts-lf-label">Email address</label>
                  <input
                    type="email"
                    className="ts-lf-input"
                    value={email}
                    onChange={e => setEmail(e.target.value)}
                    placeholder="you@example.com"
                    required
                    autoFocus
                    autoComplete="email"
                  />
                </div>

                <div className="ts-lf-field">
                  <label className="ts-lf-label">Password</label>
                  <div className="ts-lf-pw-wrap">
                    <input
                      type={showPw ? 'text' : 'password'}
                      className="ts-lf-input ts-lf-input-pw"
                      value={password}
                      onChange={e => setPassword(e.target.value)}
                      placeholder="••••••••"
                      required
                      autoComplete="current-password"
                    />
                    <button type="button" className="ts-lf-eye" onClick={() => setShowPw(v => !v)} tabIndex={-1}>
                      {showPw ? <EyeOff size={15} strokeWidth={1.75} /> : <Eye size={15} strokeWidth={1.75} />}
                    </button>
                  </div>
                </div>

                <button type="submit" disabled={pwLoading} className="ts-lf-submit">
                  {pwLoading
                    ? <span className="ts-spinner" />
                    : <><span>Sign in</span><ArrowRight size={15} strokeWidth={2} /></>
                  }
                </button>
              </form>
            )}

            {/* ── Magic-link form ───────────────────────────────────────── */}
            {tab === 'magic' && !magicSent && (
              <form onSubmit={handleMagicSubmit} className="ts-lc-form">
                <div className="ts-lf-field">
                  <label className="ts-lf-label">Email address</label>
                  <input
                    type="email"
                    className="ts-lf-input"
                    value={magicEmail}
                    onChange={e => setMagicEmail(e.target.value)}
                    placeholder="you@example.com"
                    required
                    autoFocus
                    autoComplete="email"
                  />
                </div>
                <p className="ts-magic-hint">
                  We'll send a one-click sign-in link to your inbox. No password needed.
                </p>
                <button type="submit" disabled={magicLoading} className="ts-lf-submit">
                  {magicLoading
                    ? <span className="ts-spinner" />
                    : <><Mail size={15} strokeWidth={2} /><span>Send sign-in link</span></>
                  }
                </button>
              </form>
            )}

            {/* ── Magic-link sent confirmation ──────────────────────────── */}
            {tab === 'magic' && magicSent && (
              <div className="ts-magic-sent">
                <CheckCircle2 size={28} strokeWidth={1.75} className="ts-magic-sent-icon" />
                <p className="ts-magic-sent-title">Check your inbox</p>
                <p className="ts-magic-sent-sub">
                  A sign-in link was sent to <strong>{magicEmail}</strong>.<br />
                  The link expires in 15 minutes.
                </p>
                <button
                  type="button"
                  className="ts-magic-resend"
                  onClick={() => setMagicSent(false)}
                >
                  Resend link
                </button>
              </div>
            )}
          </div>

          <p className="ts-login-footer">
            TelScout &mdash; Find jobs. Send emails. Get hired.
          </p>
        </div>
      </div>

      <style>{`
        /* ── Layout ──────────────────────────────────────────────────────── */
        .ts-login-root {
          position: relative;
          min-height: 100dvh;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 24px 16px;
          background: var(--bg);
          overflow: hidden;
          transition: background 0.2s;
        }
        .ts-login-glow {
          position: absolute;
          top: -5%;
          left: 50%;
          transform: translateX(-50%);
          width: 600px;
          height: 400px;
          border-radius: 50%;
          background: radial-gradient(ellipse at center, rgba(59,130,246,0.05) 0%, transparent 70%);
          pointer-events: none;
        }
        .ts-login-grid {
          position: absolute;
          inset: 0;
          background-image: radial-gradient(circle, var(--border2) 1px, transparent 1px);
          background-size: 28px 28px;
          mask-image: radial-gradient(ellipse 80% 70% at 50% 40%, black 30%, transparent 100%);
          pointer-events: none;
          opacity: 0.6;
        }
        .ts-login-theme-btn {
          position: absolute;
          top: 20px; right: 20px;
          width: 34px; height: 34px;
          border-radius: 8px;
          background: var(--surface);
          border: 1px solid var(--border2);
          color: var(--text3);
          display: flex; align-items: center; justify-content: center;
          cursor: pointer;
          transition: background 0.12s, color 0.12s;
          z-index: 10;
        }
        .ts-login-theme-btn:hover { background: var(--surface2); color: var(--text); }

        .ts-login-wrap {
          position: relative;
          z-index: 10;
          width: 100%;
          max-width: 400px;
          display: flex;
          flex-direction: column;
          align-items: center;
          gap: 28px;
        }

        /* ── Logo ────────────────────────────────────────────────────────── */
        .ts-login-logo { display: flex; align-items: center; gap: 10px; }

        /* ── Card ────────────────────────────────────────────────────────── */
        .ts-login-card {
          width: 100%;
          background: var(--surface);
          border: 1px solid var(--border2);
          border-radius: 14px;
          padding: 28px 24px;
          transition: background 0.2s, border-color 0.2s;
        }
        .ts-lc-header { margin-bottom: 20px; }
        .ts-lc-title {
          font-size: 20px; font-weight: 700;
          letter-spacing: -0.02em; color: var(--text);
          margin-bottom: 4px;
        }
        .ts-lc-sub { font-size: 13.5px; color: var(--text3); }

        /* ── Google button ───────────────────────────────────────────────── */
        .ts-google-btn {
          width: 100%;
          display: flex; align-items: center; justify-content: center; gap: 9px;
          padding: 10px 0;
          background: var(--surface2);
          border: 1px solid var(--border2);
          border-radius: 8px;
          color: var(--text);
          font-size: 14px; font-weight: 500;
          cursor: pointer;
          transition: background 0.14s, border-color 0.14s, transform 0.1s;
          font-family: inherit;
          margin-bottom: 0;
          min-height: 40px;
        }
        .ts-google-btn:hover:not(:disabled) {
          background: var(--surface);
          border-color: rgba(59,130,246,0.35);
          transform: translateY(-1px);
        }
        .ts-google-btn:disabled { opacity: 0.55; cursor: not-allowed; }

        /* ── Divider ─────────────────────────────────────────────────────── */
        .ts-divider {
          display: flex; align-items: center; gap: 10px;
          margin: 18px 0 16px;
        }
        .ts-divider-line {
          flex: 1; height: 1px;
          background: var(--border2);
        }
        .ts-divider-text {
          font-size: 12px; color: var(--text5);
          white-space: nowrap;
        }

        /* ── Tabs ────────────────────────────────────────────────────────── */
        .ts-tabs {
          display: flex;
          background: var(--surface2);
          border: 1px solid var(--border2);
          border-radius: 8px;
          padding: 3px;
          margin-bottom: 18px;
          gap: 2px;
        }
        .ts-tab {
          flex: 1;
          padding: 7px 0;
          font-size: 13px; font-weight: 500;
          border: none; border-radius: 6px;
          background: transparent;
          color: var(--text3);
          cursor: pointer;
          transition: background 0.13s, color 0.13s;
          font-family: inherit;
        }
        .ts-tab:hover:not(.ts-tab--active) { color: var(--text2); }
        .ts-tab--active {
          background: var(--surface);
          color: var(--text);
          box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }

        /* ── Form ────────────────────────────────────────────────────────── */
        .ts-lc-form { display: flex; flex-direction: column; gap: 16px; }
        .ts-lf-field { display: flex; flex-direction: column; gap: 6px; }
        .ts-lf-label { font-size: 12.5px; font-weight: 500; color: var(--text2); }
        .ts-lf-input {
          width: 100%; box-sizing: border-box;
          background: var(--surface2);
          border: 1px solid var(--border2);
          border-radius: 8px;
          padding: 10px 14px;
          font-size: 14px; color: var(--text);
          transition: border-color 0.15s, background 0.15s;
          font-family: inherit;
        }
        .ts-lf-input::placeholder { color: var(--text5); }
        .ts-lf-input:focus {
          outline: none;
          border-color: rgba(59,130,246,0.5);
          background: var(--surface);
        }
        .ts-lf-pw-wrap { position: relative; }
        .ts-lf-input-pw { padding-right: 40px; }
        .ts-lf-eye {
          position: absolute; right: 12px; top: 50%;
          transform: translateY(-50%);
          background: none; border: none;
          color: var(--text4); cursor: pointer;
          display: flex; align-items: center;
          transition: color 0.12s; padding: 0;
        }
        .ts-lf-eye:hover { color: var(--text2); }

        /* ── Submit button ───────────────────────────────────────────────── */
        .ts-lf-submit {
          margin-top: 4px;
          width: 100%;
          display: flex; align-items: center; justify-content: center; gap: 7px;
          padding: 11px 0;
          background: var(--accent);
          border: none; border-radius: 8px;
          color: #fff; font-size: 14px; font-weight: 600;
          cursor: pointer;
          transition: background 0.15s, transform 0.1s;
          font-family: inherit;
          min-height: 42px;
        }
        .ts-lf-submit:hover:not(:disabled) { background: var(--accent-h); transform: translateY(-1px); }
        .ts-lf-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        /* ── Magic link ──────────────────────────────────────────────────── */
        .ts-magic-hint {
          font-size: 12.5px; color: var(--text4);
          margin: -4px 0 0; line-height: 1.55;
        }
        .ts-magic-sent {
          display: flex; flex-direction: column; align-items: center;
          gap: 10px; padding: 8px 0 4px; text-align: center;
        }
        .ts-magic-sent-icon { color: #22c55e; }
        .ts-magic-sent-title {
          font-size: 15px; font-weight: 600; color: var(--text);
          margin: 0;
        }
        .ts-magic-sent-sub {
          font-size: 13px; color: var(--text3); line-height: 1.6; margin: 0;
        }
        .ts-magic-resend {
          margin-top: 6px;
          background: none; border: none;
          color: var(--accent); font-size: 13px; font-weight: 500;
          cursor: pointer; padding: 0;
          font-family: inherit;
          transition: opacity 0.12s;
        }
        .ts-magic-resend:hover { opacity: 0.75; }

        /* ── Spinners ────────────────────────────────────────────────────── */
        .ts-spinner {
          width: 16px; height: 16px;
          border: 2px solid rgba(255,255,255,0.3);
          border-top-color: #fff;
          border-radius: 50%;
          animation: ts-spin 0.7s linear infinite;
          flex-shrink: 0;
        }
        .ts-spinner-dark {
          border-color: rgba(0,0,0,0.12);
          border-top-color: var(--text3);
        }
        @keyframes ts-spin { to { transform: rotate(360deg); } }

        /* ── Footer ──────────────────────────────────────────────────────── */
        .ts-login-footer {
          font-size: 12.5px; color: var(--text5); text-align: center;
        }
      `}</style>
    </>
  )
}
