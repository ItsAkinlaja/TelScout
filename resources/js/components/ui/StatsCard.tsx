import type { LucideIcon } from 'lucide-react'

interface Props {
  title: string
  value: string | number
  icon?: LucideIcon
  iconColor?: string
  trend?: string
}

export default function StatsCard({ title, value, icon: Icon, iconColor = 'var(--accent)', trend }: Props) {
  return (
    <div style={{
      background: 'var(--surface)',
      border: '1px solid var(--border)',
      borderRadius: 10,
      padding: '16px',
      transition: 'background 0.2s, border-color 0.2s',
    }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 }}>
        <p style={{ fontSize: 11.5, fontWeight: 500, color: 'var(--text3)', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
          {title}
        </p>
        {Icon && <Icon size={15} strokeWidth={1.75} style={{ color: iconColor, opacity: 0.75 }} />}
      </div>
      <p style={{ fontSize: 26, fontWeight: 800, letterSpacing: '-0.03em', color: 'var(--text)', lineHeight: 1 }}>
        {value}
      </p>
      {trend && <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 4 }}>{trend}</p>}
    </div>
  )
}
