import { NavLink } from 'react-router-dom'
import {
  LayoutDashboard, Zap, Building2, Briefcase,
  FileText, Calendar, BarChart3, User, Settings, X, Search,
} from 'lucide-react'

const nav = [
  { to: '/dashboard',    icon: LayoutDashboard, label: 'Dashboard' },
  { to: '/discover',     icon: Search,          label: 'Discover Jobs' },
  { to: '/opportunities',icon: Zap,             label: 'Opportunities' },
  { to: '/companies',    icon: Building2,       label: 'Companies' },
  { to: '/jobs',         icon: Briefcase,       label: 'Jobs' },
  { to: '/applications', icon: FileText,        label: 'Applications' },
  { to: '/follow-ups',   icon: Calendar,        label: 'Follow-ups' },
  { to: '/analytics',    icon: BarChart3,       label: 'Analytics' },
]

const bottom = [
  { to: '/profile',  icon: User,     label: 'Profile' },
  { to: '/settings', icon: Settings, label: 'Settings' },
]

interface Props {
  open: boolean
  onClose: () => void
}

export default function Sidebar({ open, onClose }: Props) {
  return (
    <>
      <aside className={`ts-sidebar ${open ? 'ts-sidebar-open' : ''}`}>
        {/* Logo */}
        <div className="ts-s-logo">
          <a href="/" style={{ display: 'flex', alignItems: 'center', textDecoration: 'none' }}>
            <img
              src="https://ik.imagekit.io/ajide/Telscout%20logo"
              alt="TelScout"
              style={{ height: 36, width: 'auto', display: 'block' }}
            />
          </a>
          <button className="ts-s-close" onClick={onClose} aria-label="Close menu">
            <X size={18} strokeWidth={1.75} />
          </button>
        </div>

        {/* Main nav */}
        <nav className="ts-s-nav">
          <p className="ts-s-group">Workspace</p>
          {nav.map(({ to, icon: Icon, label }) => (
            <NavLink
              key={to}
              to={to}
              onClick={onClose}
              className={({ isActive }) => `ts-s-link${isActive ? ' ts-s-active' : ''}`}
            >
              <Icon size={16} strokeWidth={1.75} />
              <span>{label}</span>
            </NavLink>
          ))}
        </nav>

        {/* Bottom */}
        <div className="ts-s-footer">
          {bottom.map(({ to, icon: Icon, label }) => (
            <NavLink
              key={to}
              to={to}
              onClick={onClose}
              className={({ isActive }) => `ts-s-link${isActive ? ' ts-s-active' : ''}`}
            >
              <Icon size={16} strokeWidth={1.75} />
              <span>{label}</span>
            </NavLink>
          ))}
        </div>
      </aside>

      <style>{`
        .ts-sidebar {
          width: 220px;
          flex-shrink: 0;
          display: flex;
          flex-direction: column;
          background: var(--surface);
          border-right: 1px solid var(--border);
          height: 100dvh;
          overflow-y: auto;
          overflow-x: hidden;
          z-index: 50;
          transition: transform 0.22s ease, background 0.2s, border-color 0.2s;
        }

        .ts-s-logo {
          display: flex;
          align-items: center;
          justify-content: space-between;
          height: 60px;
          padding: 0 16px;
          border-bottom: 1px solid var(--border);
          flex-shrink: 0;
        }
        .ts-s-close {
          display: none;
          margin-left: auto;
          background: none;
          border: none;
          color: var(--text4);
          cursor: pointer;
          padding: 4px;
          border-radius: 6px;
          transition: background 0.12s, color 0.12s;
        }
        .ts-s-close:hover {
          background: var(--surface2);
          color: var(--text);
        }

        .ts-s-nav {
          flex: 1;
          padding: 14px 10px;
          display: flex;
          flex-direction: column;
          gap: 1px;
          overflow-y: auto;
        }
        .ts-s-group {
          font-size: 10px;
          font-weight: 600;
          letter-spacing: 0.08em;
          text-transform: uppercase;
          color: var(--text5);
          padding: 0 8px 8px;
        }
        .ts-s-link {
          display: flex;
          align-items: center;
          gap: 9px;
          padding: 7px 8px;
          border-radius: 7px;
          font-size: 13.5px;
          font-weight: 500;
          color: var(--text3);
          text-decoration: none;
          transition: background 0.12s, color 0.12s;
        }
        .ts-s-link:hover {
          background: var(--surface2);
          color: var(--text);
        }
        .ts-s-active {
          background: var(--accent-bg) !important;
          color: var(--accent-t) !important;
        }

        .ts-s-footer {
          padding: 10px 10px 16px;
          border-top: 1px solid var(--border);
          display: flex;
          flex-direction: column;
          gap: 1px;
        }

        @media (max-width: 768px) {
          .ts-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 260px;
            transform: translateX(-100%);
            box-shadow: 4px 0 32px rgba(0,0,0,0.4);
          }
          .ts-sidebar-open { transform: translateX(0); }
          .ts-s-close { display: flex; }
        }
      `}</style>
    </>
  )
}
