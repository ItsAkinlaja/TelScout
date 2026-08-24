import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  Zap, Search, MapPin, Clock, ExternalLink,
  Trash2, CheckSquare, Square, AlertTriangle,
} from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import { formatDate } from '../lib/utils'

// ── Helpers ───────────────────────────────────────────────────────────────────

const SCORE_COLORS: Record<string, { bg: string; text: string; label: string }> = {
  excellent: { bg: '#22c55e18', text: '#22c55e', label: 'Excellent' },
  strong:    { bg: '#3b82f618', text: '#3b82f6', label: 'Strong'    },
  good:      { bg: '#f59e0b18', text: '#f59e0b', label: 'Good'      },
  possible:  { bg: '#f9731618', text: '#f97316', label: 'Possible'  },
  low:       { bg: '#ef444418', text: '#ef4444', label: 'Low'       },
}

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  discovered:  { bg: '#94a3b818', text: '#94a3b8' },
  shortlisted: { bg: '#3b82f618', text: '#3b82f6' },
  contacted:   { bg: '#a855f718', text: '#a855f7' },
  follow_up:   { bg: '#06b6d418', text: '#06b6d4' },
  replied:     { bg: '#06b6d418', text: '#06b6d4' },
  interview:   { bg: '#f59e0b18', text: '#f59e0b' },
  offer:       { bg: '#22c55e18', text: '#22c55e' },
  rejected:    { bg: '#ef444418', text: '#ef4444' },
  closed:      { bg: '#6b728018', text: '#6b7280' },
}

function ScorePill({ score, cls }: { score: number | null; cls: string | null }) {
  const c = SCORE_COLORS[cls ?? 'low'] ?? SCORE_COLORS.low
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center', gap: 4,
      fontSize: 11.5, fontWeight: 700, padding: '3px 8px', borderRadius: 20,
      background: c.bg, color: c.text, whiteSpace: 'nowrap',
    }}>
      {score != null ? `${score}%` : '—'} {c.label}
    </span>
  )
}

function StatusPill({ status }: { status: string | null }) {
  const c = STATUS_COLORS[status ?? 'discovered'] ?? STATUS_COLORS.discovered
  return (
    <span style={{
      display: 'inline-block', fontSize: 11, fontWeight: 600,
      padding: '2px 8px', borderRadius: 20,
      background: c.bg, color: c.text,
      textTransform: 'capitalize', letterSpacing: '0.02em',
    }}>
      {(status ?? 'discovered').replace(/_/g, ' ')}
    </span>
  )
}

// ── Confirm Dialog ────────────────────────────────────────────────────────────

function ConfirmDialog({
  message, onConfirm, onCancel, loading,
}: { message: string; onConfirm: () => void; onCancel: () => void; loading: boolean }) {
  return (
    <div style={{
      position: 'fixed', inset: 0, zIndex: 100,
      background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(4px)',
      display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 16,
    }}>
      <div style={{
        background: 'var(--surface)', border: '1px solid var(--border2)',
        borderRadius: 14, padding: '28px 28px 24px', maxWidth: 400, width: '100%',
        boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 12 }}>
          <AlertTriangle size={20} color="#ef4444" strokeWidth={1.75} />
          <p style={{ fontSize: 15, fontWeight: 700, color: 'var(--text)', margin: 0 }}>Confirm deletion</p>
        </div>
        <p style={{ fontSize: 13.5, color: 'var(--text3)', marginBottom: 24, lineHeight: 1.6 }}>{message}</p>
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <button onClick={onCancel} style={{
            padding: '8px 18px', borderRadius: 8, border: '1px solid var(--border2)',
            background: 'var(--surface2)', color: 'var(--text2)', fontSize: 13.5,
            cursor: 'pointer', fontFamily: 'inherit', fontWeight: 500,
          }}>Cancel</button>
          <button onClick={onConfirm} disabled={loading} style={{
            padding: '8px 18px', borderRadius: 8, border: 'none',
            background: '#ef4444', color: '#fff', fontSize: 13.5,
            cursor: loading ? 'not-allowed' : 'pointer', fontFamily: 'inherit', fontWeight: 600,
            opacity: loading ? 0.6 : 1,
          }}>
            {loading ? 'Deleting…' : 'Yes, delete'}
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Opportunity Card ──────────────────────────────────────────────────────────

function OppCard({
  opp, selected, onToggle,
}: { opp: any; selected: boolean; onToggle: () => void }) {
  return (
    <div style={{
      background: 'var(--surface)', border: `1px solid ${selected ? 'rgba(59,130,246,0.5)' : 'var(--border)'}`,
      borderRadius: 12, padding: '16px 20px',
      transition: 'border-color 0.12s, box-shadow 0.12s',
      boxShadow: selected ? '0 0 0 2px rgba(59,130,246,0.12)' : 'none',
      display: 'flex', gap: 14, alignItems: 'flex-start',
    }}
      onMouseEnter={e => { if (!selected) (e.currentTarget as HTMLDivElement).style.borderColor = 'var(--border2)' }}
      onMouseLeave={e => { if (!selected) (e.currentTarget as HTMLDivElement).style.borderColor = 'var(--border)' }}
    >
      {/* Checkbox */}
      <button onClick={onToggle} style={{
        background: 'none', border: 'none', cursor: 'pointer',
        color: selected ? 'var(--accent)' : 'var(--text4)',
        padding: '2px 0', flexShrink: 0, marginTop: 1,
        transition: 'color 0.12s',
      }}>
        {selected ? <CheckSquare size={17} strokeWidth={2} /> : <Square size={17} strokeWidth={1.75} />}
      </button>

      {/* Body */}
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
          {/* Left */}
          <div style={{ flex: 1, minWidth: 0 }}>
            <Link to={`/opportunities/${opp.id}`} style={{
              fontSize: 14.5, fontWeight: 600, color: 'var(--text)',
              textDecoration: 'none', display: 'block', marginBottom: 2,
            }}
              onMouseEnter={e => (e.currentTarget.style.color = 'var(--accent)')}
              onMouseLeave={e => (e.currentTarget.style.color = 'var(--text)')}
            >
              {opp.job?.title ?? '—'}
            </Link>
            <p style={{ fontSize: 13, color: 'var(--accent)', fontWeight: 500, margin: '0 0 6px' }}>
              {opp.company?.name ?? '—'}
            </p>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10, fontSize: 12.5, color: 'var(--text3)', alignItems: 'center' }}>
              {(opp.job?.location || opp.job?.is_remote) && (
                <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                  <MapPin size={11} strokeWidth={1.75} />
                  {opp.job?.location ?? 'Remote'}
                </span>
              )}
              {opp.discovered_at && (
                <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                  <Clock size={11} strokeWidth={1.75} />
                  {formatDate(opp.discovered_at)}
                </span>
              )}
            </div>
          </div>

          {/* Right */}
          <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 8, flexShrink: 0 }}>
            <ScorePill score={opp.match_score} cls={opp.match_classification} />
            <StatusPill status={opp.status} />
            {opp.application_url && (
              <a href={opp.application_url} target="_blank" rel="noopener noreferrer"
                style={{
                  display: 'inline-flex', alignItems: 'center', gap: 4,
                  fontSize: 12, fontWeight: 600, padding: '5px 10px',
                  borderRadius: 7, background: 'var(--accent)', color: '#fff',
                  textDecoration: 'none',
                }}>
                <ExternalLink size={11} strokeWidth={2} /> Apply
              </a>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

// ── Skeleton ──────────────────────────────────────────────────────────────────

function CardSkeleton() {
  return (
    <div style={{
      background: 'var(--surface)', border: '1px solid var(--border)',
      borderRadius: 12, padding: '16px 20px',
    }}>
      {[['60%', 14], ['35%', 12], ['80%', 11]].map(([w, h], i) => (
        <div key={i} style={{
          height: h as number, width: w as string, borderRadius: 6,
          background: 'var(--surface2)',
          marginBottom: i < 2 ? 8 : 0,
          animation: 'ts-pulse 1.5s ease-in-out infinite',
        }} />
      ))}
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function OpportunitiesPage() {
  const qc = useQueryClient()
  const [search, setSearch]        = useState('')
  const [classification, setClass] = useState('')
  const [status, setStatus]        = useState('')
  const [minScore, setMinScore]    = useState('')
  const [page, setPage]            = useState(1)
  const [selected, setSelected]    = useState<number[]>([])
  const [confirm, setConfirm]      = useState<'all' | 'selected' | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['opportunities', search, classification, status, minScore, page],
    queryFn: () => api.get('/opportunities', {
      params: { search, classification, status, min_score: minScore, page, per_page: 20 },
    }).then(r => r.data),
  })

  const opps: any[]  = data?.data ?? []
  const meta = { total: data?.total, current_page: data?.current_page, last_page: data?.last_page }

  const bulkDelete = useMutation({
    mutationFn: (payload: { all?: boolean; ids?: number[] }) =>
      api.delete('/opportunities/bulk', { data: payload }),
    onSuccess: (res) => {
      toast.success(res.data.message ?? 'Deleted successfully')
      setSelected([])
      setConfirm(null)
      qc.invalidateQueries({ queryKey: ['opportunities'] })
    },
    onError: (e: any) => {
      toast.error(e.response?.data?.message ?? 'Delete failed')
      setConfirm(null)
    },
  })

  const allSelected = opps.length > 0 && opps.every(o => selected.includes(o.id))
  const toggleAll   = () => setSelected(allSelected ? [] : opps.map(o => o.id))
  const toggle      = (id: number) => setSelected(s => s.includes(id) ? s.filter(x => x !== id) : [...s, id])

  return (
    <>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

        {/* Filter bar */}
        <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 8 }}>
          {/* Search */}
          <div style={{ position: 'relative', flex: 1, minWidth: 200 }}>
            <Search size={14} strokeWidth={1.75} style={{
              position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)',
              color: 'var(--text4)', pointerEvents: 'none',
            }} />
            <input
              style={{
                width: '100%', boxSizing: 'border-box',
                paddingLeft: 32, paddingRight: 12, paddingTop: 8, paddingBottom: 8,
                background: 'var(--surface)', border: '1px solid var(--border2)',
                borderRadius: 8, fontSize: 13.5, color: 'var(--text)', fontFamily: 'inherit',
              }}
              placeholder="Search jobs or companies…"
              value={search}
              onChange={e => { setSearch(e.target.value); setPage(1) }}
            />
          </div>

          {/* Score filter */}
          <select style={selectStyle} value={classification} onChange={e => { setClass(e.target.value); setPage(1) }}>
            <option value="">All scores</option>
            {['excellent', 'strong', 'good', 'possible', 'low'].map(c => (
              <option key={c} value={c}>{c[0].toUpperCase() + c.slice(1)}</option>
            ))}
          </select>

          {/* Status filter */}
          <select style={selectStyle} value={status} onChange={e => { setStatus(e.target.value); setPage(1) }}>
            <option value="">All statuses</option>
            {['discovered', 'shortlisted', 'contacted', 'replied', 'interview', 'offer', 'rejected'].map(s => (
              <option key={s} value={s}>{s.replace(/_/g, ' ')}</option>
            ))}
          </select>

          {/* Min score */}
          <input
            type="number" placeholder="Min %" value={minScore}
            onChange={e => { setMinScore(e.target.value); setPage(1) }}
            style={{ ...selectStyle, width: 80 }}
          />

          <span style={{ fontSize: 12.5, color: 'var(--text4)', whiteSpace: 'nowrap', marginLeft: 4 }}>
            {meta.total ?? 0} results
          </span>

          <div style={{ marginLeft: 'auto', display: 'flex', gap: 8, alignItems: 'center' }}>
            {selected.length > 0 && (
              <button onClick={() => setConfirm('selected')} style={{
                display: 'flex', alignItems: 'center', gap: 6,
                padding: '7px 14px', borderRadius: 8, border: '1px solid #ef444440',
                background: '#ef444412', color: '#ef4444', fontSize: 13, fontWeight: 600,
                cursor: 'pointer', fontFamily: 'inherit',
              }}>
                <Trash2 size={13} strokeWidth={2} /> Delete {selected.length} selected
              </button>
            )}
            <button onClick={() => setConfirm('all')} style={{
              display: 'flex', alignItems: 'center', gap: 6,
              padding: '7px 14px', borderRadius: 8, border: '1px solid #ef444440',
              background: '#ef444412', color: '#ef4444', fontSize: 13, fontWeight: 600,
              cursor: 'pointer', fontFamily: 'inherit',
            }}>
              <Trash2 size={13} strokeWidth={2} /> Clear All
            </button>
          </div>
        </div>

        {/* Select all row */}
        {opps.length > 0 && (
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <button onClick={toggleAll} style={{
              display: 'flex', alignItems: 'center', gap: 7,
              background: 'none', border: 'none', cursor: 'pointer',
              fontSize: 12.5, color: 'var(--text3)', fontFamily: 'inherit', padding: 0,
              transition: 'color 0.12s',
            }}>
              {allSelected
                ? <CheckSquare size={15} strokeWidth={2} color="var(--accent)" />
                : <Square size={15} strokeWidth={1.75} />
              }
              {allSelected ? 'Deselect all' : 'Select all on this page'}
            </button>
            {selected.length > 0 && (
              <span style={{ fontSize: 12, color: 'var(--accent)', fontWeight: 500 }}>
                {selected.length} selected
              </span>
            )}
          </div>
        )}

        {/* Cards */}
        {isLoading ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {[1, 2, 3, 4].map(i => <CardSkeleton key={i} />)}
          </div>
        ) : opps.length === 0 ? (
          <div style={{
            padding: '60px 0', textAlign: 'center',
            background: 'var(--surface)', border: '1px solid var(--border)',
            borderRadius: 14,
          }}>
            <div style={{ marginBottom: 14, color: 'var(--text4)' }}>
              <Zap size={36} strokeWidth={1.25} />
            </div>
            <p style={{ fontSize: 15, fontWeight: 600, color: 'var(--text2)', marginBottom: 6 }}>
              No opportunities yet
            </p>
            <p style={{ fontSize: 13.5, color: 'var(--text3)', marginBottom: 20 }}>
              Run a job search or add a job manually to get started.
            </p>
            <Link to="/discover" style={{
              display: 'inline-flex', alignItems: 'center', gap: 6,
              padding: '9px 20px', background: 'var(--accent)', borderRadius: 8,
              color: '#fff', fontSize: 13.5, fontWeight: 600, textDecoration: 'none',
            }}>
              Discover Jobs
            </Link>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {opps.map((opp: any) => (
              <OppCard
                key={opp.id}
                opp={opp}
                selected={selected.includes(opp.id)}
                onToggle={() => toggle(opp.id)}
              />
            ))}
          </div>
        )}

        {/* Pagination */}
        {(meta.last_page ?? 1) > 1 && (
          <div style={{
            display: 'flex', alignItems: 'center', justifyContent: 'space-between',
            padding: '12px 0',
          }}>
            <span style={{ fontSize: 12.5, color: 'var(--text4)' }}>
              Page {meta.current_page} of {meta.last_page}
            </span>
            <div style={{ display: 'flex', gap: 6 }}>
              {[['← Prev', page <= 1, () => setPage(p => p - 1)],
                ['Next →', page >= (meta.last_page ?? 1), () => setPage(p => p + 1)]
              ].map(([label, disabled, handler]: any) => (
                <button key={label} disabled={disabled} onClick={handler} style={{
                  background: 'var(--surface)', border: '1px solid var(--border2)',
                  borderRadius: 7, padding: '7px 14px',
                  fontSize: 12.5, color: 'var(--text2)', cursor: 'pointer',
                  fontFamily: 'inherit', opacity: disabled ? 0.35 : 1,
                  transition: 'background 0.12s',
                }}>
                  {label}
                </button>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Confirm dialogs */}
      {confirm === 'all' && (
        <ConfirmDialog
          message={`This will permanently delete all ${meta.total ?? 0} opportunities. This cannot be undone.`}
          onConfirm={() => bulkDelete.mutate({ all: true })}
          onCancel={() => setConfirm(null)}
          loading={bulkDelete.isPending}
        />
      )}
      {confirm === 'selected' && (
        <ConfirmDialog
          message={`This will permanently delete ${selected.length} selected opportunities. This cannot be undone.`}
          onConfirm={() => bulkDelete.mutate({ ids: selected })}
          onCancel={() => setConfirm(null)}
          loading={bulkDelete.isPending}
        />
      )}

      <style>{`
        @keyframes ts-pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.4; }
        }
        input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; }
      `}</style>
    </>
  )
}

const selectStyle: React.CSSProperties = {
  background: 'var(--surface)',
  border: '1px solid var(--border2)',
  borderRadius: 8,
  padding: '8px 12px',
  fontSize: 13.5,
  color: 'var(--text2)',
  cursor: 'pointer',
  fontFamily: 'inherit',
}
