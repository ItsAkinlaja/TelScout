import { Loader2 } from 'lucide-react'
import type { CSSProperties } from 'react'

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'success'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  icon?: React.ReactNode
}

const variantStyles: Record<string, CSSProperties> = {
  primary:   { background: 'var(--accent)',   color: '#fff',             border: '1px solid transparent' },
  secondary: { background: 'var(--surface2)', color: 'var(--text)',      border: '1px solid var(--border2)' },
  ghost:     { background: 'transparent',      color: 'var(--text3)',     border: '1px solid transparent' },
  danger:    { background: 'rgba(239,68,68,0.1)',  color: '#f87171', border: '1px solid rgba(239,68,68,0.2)' },
  success:   { background: 'rgba(34,197,94,0.1)',  color: '#4ade80', border: '1px solid rgba(34,197,94,0.2)' },
}

const sizeStyles: Record<string, CSSProperties> = {
  sm: { fontSize: 12.5, padding: '6px 11px', borderRadius: 7, gap: 5 },
  md: { fontSize: 13.5, padding: '8px 14px', borderRadius: 8, gap: 6 },
  lg: { fontSize: 14.5, padding: '10px 18px', borderRadius: 9, gap: 7 },
}

export default function Button({
  variant = 'primary',
  size = 'md',
  loading,
  icon,
  children,
  disabled,
  style,
  ...props
}: Props) {
  return (
    <button
      {...props}
      disabled={disabled || loading}
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        fontFamily: 'inherit',
        fontWeight: 600,
        cursor: disabled || loading ? 'not-allowed' : 'pointer',
        opacity: disabled || loading ? 0.55 : 1,
        transition: 'background 0.12s, opacity 0.12s, transform 0.1s',
        ...variantStyles[variant],
        ...sizeStyles[size],
        ...style,
      }}
    >
      {loading
        ? <Loader2 size={14} strokeWidth={2} style={{ animation: 'ts-spin 0.7s linear infinite' }} />
        : icon}
      {children}
    </button>
  )
}
