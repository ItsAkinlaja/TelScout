import { LogOut, Menu, Sun, Moon } from 'lucide-react'
import { useAuth } from '../../hooks/useAuth'
import { useTheme } from '../../hooks/useTheme'
import { useNavigate, useLocation } from 'react-router-dom'

const TITLES: Record<string, string> = {
  '/dashboard':     'Dashboard',
  '/opportunities': 'Opportunities',
  '/companies':     'Companies',
  '/jobs':          'Jobs',
  '/applications':  'Applications',
  '/follow-ups':    'Follow-ups',
  '/analytics':     'Analytics',
  '/profile':       'Profile',
  '/settings':      'Settings',
}

interface Props {
  onMenuClick: () => void
}

export default function Topbar({ onMenuClick }: Props) {
  const { user, logout }  = useAuth()
  const { isDark, toggle } = useTheme()
  const navigate          = useNavigate()
  const { pathname }      = useLocation()

  const title = Object.entries(TITLES).find(
    ([path]) => pathname === path || pathname.startsWith(path + '/')
  )?.[1] ?? 'TelScout'

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const initials = user?.name
    ?.split(' ')
    .map(n => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase() ?? 'U'

  return (
    <>
      <header className="ts-topbar">
        <div className="ts-topbar-left">
          <button className="ts-menu-btn" onClick={onMenuClick} aria-label="Toggle menu">
            <Menu size={18} strokeWidth={1.75} />
          </button>
          <h1 className="ts-topbar-title">{title}</h1>
        </div>

        <div className="ts-topbar-right">
          {/* Theme toggle */}
          <button
            className="ts-icon-btn"
            onClick={toggle}
            aria-label={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
            title={isDark ? 'Light mode' : 'Dark mode'}
          >
            {isDark
              ? <Sun  size={16} strokeWidth={1.75} />
              : <Moon size={16} strokeWidth={1.75} />
            }
          </button>

          {/* Divider */}
          <span className="ts-topbar-divider" />

          {/* User */}
          <div className="ts-user">
            <div className="ts-avatar">{initials}</div>
            <span className="ts-username">{user?.name}</span>
          </div>

          {/* Logout */}
          <button
            className="ts-icon-btn"
            onClick={handleLogout}
            title="Sign out"
            aria-label="Sign out"
          >
            <LogOut size={16} strokeWidth={1.75} />
          </button>
        </div>
      </header>

      <style>{`
        .ts-topbar {
          display: flex;
          align-items: center;
          justify-content: space-between;
          height: 60px;
          padding: 0 24px;
          border-bottom: 1px solid var(--border);
          background: var(--surface);
          flex-shrink: 0;
          transition: background 0.2s, border-color 0.2s;
        }
        .ts-topbar-left {
          display: flex;
          align-items: center;
          gap: 12px;
        }
        .ts-topbar-title {
          font-size: 15px;
          font-weight: 600;
          color: var(--text);
          letter-spacing: -0.01em;
        }
        .ts-menu-btn {
          display: none;
          background: none;
          border: none;
          color: var(--text3);
          cursor: pointer;
          padding: 6px;
          border-radius: 7px;
          transition: background 0.12s, color 0.12s;
        }
        .ts-menu-btn:hover {
          background: var(--surface2);
          color: var(--text);
        }
        .ts-topbar-right {
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .ts-topbar-divider {
          width: 1px;
          height: 20px;
          background: var(--border2);
          margin: 0 4px;
        }
        .ts-user {
          display: flex;
          align-items: center;
          gap: 8px;
        }
        .ts-avatar {
          width: 30px;
          height: 30px;
          border-radius: 50%;
          background: var(--accent-bg);
          border: 1px solid rgba(59,130,246,0.25);
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 11px;
          font-weight: 700;
          color: var(--accent-t);
          flex-shrink: 0;
        }
        .ts-username {
          font-size: 13.5px;
          font-weight: 500;
          color: var(--text2);
        }
        .ts-icon-btn {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 32px;
          height: 32px;
          border-radius: 7px;
          background: none;
          border: none;
          color: var(--text3);
          cursor: pointer;
          transition: background 0.12s, color 0.12s;
        }
        .ts-icon-btn:hover {
          background: var(--surface2);
          color: var(--text);
        }

        @media (max-width: 768px) {
          .ts-menu-btn  { display: flex; }
          .ts-username  { display: none; }
          .ts-topbar    { padding: 0 16px; }
          .ts-topbar-divider { display: none; }
        }
      `}</style>
    </>
  )
}
