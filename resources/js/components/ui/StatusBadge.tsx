const COLORS: Record<string, { bg: string; color: string }> = {
  discovered:   { bg: 'rgba(255,255,255,0.06)',    color: '#a1a1aa' },
  shortlisted:  { bg: 'rgba(59,130,246,0.12)',     color: '#60a5fa' },
  contacted:    { bg: 'rgba(99,102,241,0.12)',     color: '#818cf8' },
  follow_up:    { bg: 'rgba(245,158,11,0.12)',     color: '#fbbf24' },
  follow_up_due:{ bg: 'rgba(245,158,11,0.12)',     color: '#fbbf24' },
  replied:      { bg: 'rgba(168,85,247,0.12)',     color: '#c084fc' },
  interview:    { bg: 'rgba(249,115,22,0.12)',     color: '#fb923c' },
  offer:        { bg: 'rgba(34,197,94,0.12)',      color: '#4ade80' },
  rejected:     { bg: 'rgba(239,68,68,0.1)',       color: '#f87171' },
  closed:       { bg: 'rgba(255,255,255,0.04)',    color: '#52525b' },
  draft:        { bg: 'rgba(255,255,255,0.06)',    color: '#71717a' },
  approved:     { bg: 'rgba(59,130,246,0.12)',     color: '#60a5fa' },
  queued:       { bg: 'rgba(99,102,241,0.12)',     color: '#818cf8' },
  sending:      { bg: 'rgba(6,182,212,0.12)',      color: '#22d3ee' },
  sent:         { bg: 'rgba(34,197,94,0.12)',      color: '#4ade80' },
  failed:       { bg: 'rgba(239,68,68,0.1)',       color: '#f87171' },
  pending:      { bg: 'rgba(245,158,11,0.12)',     color: '#fbbf24' },
  completed:    { bg: 'rgba(34,197,94,0.12)',      color: '#4ade80' },
  cancelled:    { bg: 'rgba(255,255,255,0.04)',    color: '#52525b' },
  active:       { bg: 'rgba(34,197,94,0.12)',      color: '#4ade80' },
  expired:      { bg: 'rgba(239,68,68,0.1)',       color: '#f87171' },
}

function label(status: string) {
  return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

export default function StatusBadge({ status, className }: { status: string; className?: string }) {
  const c = COLORS[status] ?? { bg: 'rgba(255,255,255,0.06)', color: '#a1a1aa' }
  return (
    <span
      className={className}
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        padding: '2px 8px',
        borderRadius: 100,
        fontSize: 11.5,
        fontWeight: 600,
        background: c.bg,
        color: c.color,
        whiteSpace: 'nowrap',
      }}
    >
      {label(status)}
    </span>
  )
}
