import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { useTheme } from '../hooks/useTheme'
import { Eye, EyeOff, ArrowRight, Sun, Moon } from 'lucide-react'
import toast from 'react-hot-toast'

export default function LoginPage() {
  const { login }          = useAuth()
  const { isDark, toggle } = useTheme()
  const navigate           = useNavigate()
  const [email,    setEmail]    = useState('')
  const [password, setPassword] = useState('')
  const [showPw,   setShowPw]   = useState(false)
  const [loading,  setLoading]  = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    try {
      await login(email, password)
      navigate('/dashboard')
    } catch (err: any) {
      toast.error(err.response?.data?.message ?? 'Invalid credentials')
    } finally {
      setLoading(false)
    }
  }

  return (
    <>
      <div className="ts-login-root">
        {/* Subtle glow */}
        <div className="ts-login-glow" />
        {/* Dot grid */}
        <div className="ts-login-grid" />

        {/* Theme toggle — top right */}
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

            <form onSubmit={handleSubmit} className="ts-lc-form">
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

              <button type="submit" disabled={loading} className="ts-lf-submit">
                {loading
                  ? <span className="ts-spinner" />
                  : <><span>Sign in</span><ArrowRight size={15} strokeWidth={2} /></>
                }
              </button>
            </form>
          </div>

          <p className="ts-login-footer">
            TelScout &mdash; Find jobs. Send emails. Get hired.
          </p>
        </div>
      </div>

      <style>{`
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
          top: 20px;
          right: 20px;
          width: 34px;
          height: 34px;
          border-radius: 8px;
          background: var(--surface);
          border: 1px solid var(--border2);
          color: var(--text3);
          display: flex;
          align-items: center;
          justify-content: center;
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

        /* Logo */
        .ts-login-logo { display: flex; align-items: center; gap: 10px; }
        .ts-ll-icon {
          width: 36px; height: 36px; border-radius: 9px;
          background: var(--accent);
          display: flex; align-items: center; justify-content: center;
        }
        .ts-ll-name { font-size: 18px; font-weight: 700; letter-spacing: -0.025em; color: var(--text); }

        /* Card */
        .ts-login-card {
          width: 100%;
          background: var(--surface);
          border: 1px solid var(--border2);
          border-radius: 14px;
          padding: 28px 24px;
          transition: background 0.2s, border-color 0.2s;
        }
        .ts-lc-header { margin-bottom: 24px; }
        .ts-lc-title {
          font-size: 20px; font-weight: 700;
          letter-spacing: -0.02em; color: var(--text);
          margin-bottom: 4px;
        }
        .ts-lc-sub { font-size: 13.5px; color: var(--text3); }

        /* Form */
        .ts-lc-form { display: flex; flex-direction: column; gap: 16px; }
        .ts-lf-field { display: flex; flex-direction: column; gap: 6px; }
        .ts-lf-label { font-size: 12.5px; font-weight: 500; color: var(--text2); }
        .ts-lf-input {
          width: 100%;
          background: var(--surface2);
          border: 1px solid var(--border2);
          border-radius: 8px;
          padding: 10px 14px;
          font-size: 14px;
          color: var(--text);
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
        }
        .ts-lf-submit:hover:not(:disabled) { background: var(--accent-h); transform: translateY(-1px); }
        .ts-lf-submit:disabled { opacity: 0.6; cursor: not-allowed; }
        .ts-spinner {
          width: 16px; height: 16px;
          border: 2px solid rgba(255,255,255,0.3);
          border-top-color: #fff; border-radius: 50%;
          animation: ts-spin 0.7s linear infinite;
        }
        @keyframes ts-spin { to { transform: rotate(360deg); } }

        .ts-login-footer {
          font-size: 12.5px;
          color: var(--text5);
          text-align: center;
        }
      `}</style>
    </>
  )
}
