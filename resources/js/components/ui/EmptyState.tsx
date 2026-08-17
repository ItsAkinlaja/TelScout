import type { LucideIcon } from 'lucide-react'

interface Props {
  icon?: LucideIcon
  title: string
  description?: string
  action?: React.ReactNode
}

export default function EmptyState({ icon: Icon, title, description, action }: Props) {
  return (
    <div style={{
      display: 'flex', flexDirection: 'column',
      alignItems: 'center', justifyContent: 'center',
      padding: '64px 24px', textAlign: 'center',
    }}>
      {Icon && (
        <div style={{
          width: 48, height: 48, borderRadius: 12,
          background: 'var(--surface2)',
          border: '1px solid var(--border)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          marginBottom: 16, color: 'var(--text4)',
        }}>
          <Icon size={22} strokeWidth={1.5} />
        </div>
      )}
      <p style={{ fontSize: 15, fontWeight: 600, color: 'var(--text)', marginBottom: 6 }}>{title}</p>
      {description && (
        <p style={{ fontSize: 13.5, color: 'var(--text4)', maxWidth: 360, lineHeight: 1.6 }}>{description}</p>
      )}
      {action && <div style={{ marginTop: 20 }}>{action}</div>}
    </div>
  )
}
