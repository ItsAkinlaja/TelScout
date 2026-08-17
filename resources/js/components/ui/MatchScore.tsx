function classify(score: number): string {
  if (score >= 90) return 'excellent'
  if (score >= 80) return 'strong'
  if (score >= 70) return 'good'
  if (score >= 60) return 'possible'
  return 'low'
}

const colors: Record<string, { bg: string; color: string }> = {
  excellent: { bg: 'rgba(34,197,94,0.12)',  color: '#4ade80' },
  strong:    { bg: 'rgba(59,130,246,0.12)', color: '#60a5fa' },
  good:      { bg: 'rgba(6,182,212,0.12)',  color: '#22d3ee' },
  possible:  { bg: 'rgba(245,158,11,0.12)', color: '#fbbf24' },
  low:       { bg: 'rgba(255,255,255,0.06)',color: '#71717a' },
}

interface Props {
  score: number
  classification?: string
  size?: 'sm' | 'md' | 'lg'
}

export default function MatchScore({ score, classification, size = 'md' }: Props) {
  const cls = classification ?? classify(score)
  const c   = colors[cls] ?? colors.low
  const fs  = size === 'sm' ? 11 : size === 'lg' ? 15 : 12.5
  const px  = size === 'sm' ? '5px 9px' : size === 'lg' ? '5px 12px' : '3px 10px'

  return (
    <span style={{
      display: 'inline-flex',
      alignItems: 'center',
      padding: px,
      borderRadius: 6,
      fontSize: fs,
      fontWeight: 700,
      background: c.bg,
      color: c.color,
      whiteSpace: 'nowrap',
      fontVariantNumeric: 'tabular-nums',
    }}>
      {Math.round(score)}%
    </span>
  )
}
