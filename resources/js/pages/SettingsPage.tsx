import { NavLink, Outlet, Navigate } from 'react-router-dom'
import { Mail, Zap, SlidersHorizontal } from 'lucide-react'
import { TsPageStyles } from '../components/ui/TsShared'

const tabs = [
  { to: '/settings/mail',       icon: Mail,              label: 'Mail Accounts' },
  { to: '/settings/automation', icon: Zap,               label: 'Automation' },
  { to: '/settings/preferences',icon: SlidersHorizontal, label: 'Preferences' },
]

export default function SettingsPage() {
  return (
    <>
      <div style={{ display: 'flex', gap: 24, alignItems: 'flex-start' }}>
        {/* Sidebar nav */}
        <nav style={{ width: 200, flexShrink: 0 }}>
          <p style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.07em', color: 'var(--text4)', marginBottom: 8, padding: '0 4px' }}>Settings</p>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            {tabs.map(({ to, icon: Icon, label }) => (
              <NavLink key={to} to={to}
                style={({ isActive }) => ({
                  display: 'flex', alignItems: 'center', gap: 9,
                  padding: '8px 10px', borderRadius: 8,
                  fontSize: 13.5, fontWeight: 500, textDecoration: 'none',
                  transition: 'background 0.12s, color 0.12s',
                  background: isActive ? 'var(--accent-bg)' : 'transparent',
                  color: isActive ? 'var(--accent-t)' : 'var(--text3)',
                })}>
                <Icon size={15} strokeWidth={1.75} />
                {label}
              </NavLink>
            ))}
          </div>
        </nav>
        <div style={{ flex: 1, minWidth: 0 }}>
          <Outlet />
        </div>
      </div>
      <TsPageStyles />
    </>
  )
}
