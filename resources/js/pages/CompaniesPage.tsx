import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  Building2, Search, ExternalLink, Globe, Briefcase,
  MapPin, Trash2, CheckSquare, Square, AlertTriangle,
} from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'

// ── Helpers ───────────────────────────────────────────────────────────────────

/** Ensure a URL has an http/https scheme so it opens correctly in a new tab */
function ensureUrl(url: string): string {
  if (!url) return ''
  return url.startsWith('http://') || url.startsWith('https://') ? url : 'https://' + url
}

/** Extract the bare domain for display (strip scheme + www) */
function displayDomain(url: string): string {
  try {
    const u = new URL(ensureUrl(url))
    return u.hostname.replace(/^www\./, '')
  } catch {
    return url
  }
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

// ── Company Card ──────────────────────────────────────────────────────────────

function CompanyCard({
  c, selected, onToggle,
}: { c: any; selected: boolean; onToggle: () => void }) {
  const website  = c.website  ? ensureUrl(c.website)  : null
  const careers  = c.careers_url ? ensureUrl(c.careers_url) : null

  return (
    <div style={{
      background: 'var(--surface)',
      border: `1px solid ${selected ? 'rgba(59,130,246,0.5)' : 'var(--border)'}`,
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
        padding: '2px 0', flexShrink: 0, marginTop: 2,
        transition: 'color 0.12s',
      }}>
        {selected ? <CheckSquare size={17} strokeWidth={2} /> : <Square size={17} strokeWidth={1.75} />}
      </button>

      {/* Logo placeholder */}
      <div style={{
        width: 40, height: 40, borderRadius: 10, flexShrink: 0,
        background: 'var(--surface2)', border: '1px solid var(--border)',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        fontSize: 16, fontWeight: 700, color: 'var(--text3)',
        overflow: 'hidden',
      }}>
        {c.logo_url
          ? <img src={c.logo_url} alt={c.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
          : (c.name?.[0] ?? '?').toUpperCase()
        }
      </div>

      {/* Body */}
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
          <div style={{ flex: 1, minWidth: 0 }}>
            {/* Name */}
            <Link to={`/companies/${c.id}`} style={{
              fontSize: 14.5, fontWeight: 600, color: 'var(--text)',
              textDecoration: 'none', display: 'block', marginBottom: 2,
            }}
              onMouseEnter={e => (e.currentTarget.style.color = 'var(--accent)')}
              onMouseLeave={e => (e.currentTarget.style.color = 'var(--text)')}
            >
              {c.name}
            </Link>

            {/* Meta row */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10, fontSize: 12.5, color: 'var(--text3)', marginTop: 4, alignItems: 'center' }}>
              {c.industry && (
                <span style={{
                  fontSize: 11, fontWeight: 600, padding: '2px 7px', borderRadius: 4,
                  background: 'var(--surface2)', color: 'var(--text3)',
                  border: '1px solid var(--border)',
                }}>
                  {c.industry}
                </span>
              )}
              {c.location && (
                <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                  <MapPin size={11} strokeWidth={1.75} />{c.location}
                </span>
              )}
              {c.size && (
                <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                  {c.size}
                </span>
              )}
              <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                <Briefcase size={11} strokeWidth={1.75} />
                {c.jobs_count ?? 0} job{c.jobs_count !== 1 ? 's' : ''}
              </span>
            </div>

            {/* Links row */}
            {(website || careers) && (
              <div style={{ display: 'flex', gap: 12, marginTop: 10, flexWrap: 'wrap' }}>
                {website && (
                  <a
                    href={website}
                    target="_blank"
                    rel="noopener noreferrer"
                    onClick={e => e.stopPropagation()}
                    style={{
                      display: 'inline-flex', alignItems: 'center', gap: 4,
                      fontSize: 12.5, color: 'var(--accent)', textDecoration: 'none',
                      fontWeight: 500,
                    }}
                    onMouseEnter={e => (e.currentTarget.style.textDecoration = 'underline')}
                    onMouseLeave={e => (e.currentTarget.style.textDecoration = 'none')}
                  >
                    <Globe size={12} strokeWidth={1.75} />
                    {displayDomain(c.website)}
                    <ExternalLink size={10} strokeWidth={2} style={{ opacity: 0.6 }} />
                  </a>
                )}
                {careers && (
                  <a
                    href={careers}
                    target="_blank"
                    rel="noopener noreferrer"
                    onClick={e => e.stopPropagation()}
                    style={{
                      display: 'inline-flex', alignItems: 'center', gap: 4,
                      fontSize: 12.5, color: 'var(--text3)', textDecoration: 'none',
                      fontWeight: 500,
                    }}
                    onMouseEnter={e => (e.currentTarget.style.color = 'var(--accent)')}
                    onMouseLeave={e => (e.currentTarget.style.color = 'var(--text3)')}
                  >
                    <Briefcase size={12} strokeWidth={1.75} />
                    Careers
                    <ExternalLink size={10} strokeWidth={2} style={{ opacity: 0.6 }} />
                  </a>
                )}
              </div>
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
      borderRadius: 12, padding: '16px 20px', display: 'flex', gap: 14, alignItems: 'flex-start',
    }}>
      <div style={{ width: 40, height: 40, borderRadius: 10, background: 'var(--surface2)', animation: 'ts-pulse 1.5s ease-in-out infinite', flexShrink: 0 }} />
      <div style={{ flex: 1 }}>
        {[['55%', 14], ['35%', 11], ['70%', 11]].map(([w, h], i) => (
          <div key={i} style={{
            height: h as number, width: w as string, borderRadius: 6,
            background: 'var(--surface2)', marginBottom: i < 2 ? 8 : 0,
            animation: 'ts-pulse 1.5s ease-in-out infinite',
          }} />
        ))}
      </div>
    </div>
  )
}

// ── Main ──────────────────────────────────────────────────────────────────────

export default function CompaniesPage() {
  const qc = useQueryClient()
  const [search, setSearch]     = useState('')
  const [page, setPage]         = useState(1)
  const [selected, setSelected] = useState<number[]>([])
  const [confirm, setConfirm]   = useState<'all' | 'selected' | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['companies', search, page],
    queryFn: () => api.get('/companies', { params: { search, page, per_page: 20 } }).then(r => r.data),
  })

  const companies: any[] = data?.data ?? []
  const meta = { total: data?.total, current_page: data?.current_page, last_page: data?.last_page }

  const bulkDelete = useMutation({
    mutationFn: (payload: { all?: boolean; ids?: number[] }) =>
      api.delete('/companies/bulk', { data: payload }),
    onSuccess: (res) => {
      toast.success(res.data.message ?? 'Deleted')
      setSelected([])
      setConfirm(null)
      qc.invalidateQueries({ queryKey: ['companies'] })
    },
    onError: (e: any) => {
      toast.error(e.response?.data?.message ?? 'Delete failed')
      setConfirm(null)
    },
  })

  const allSelected = companies.length > 0 && companies.every(c => selected.includes(c.id))
  const toggleAll   = () => setSelected(allSelected ? [] : companies.map(c => c.id))
  const toggle      = (id: number) => setSelected(s => s.includes(id) ? s.filter(x => x !== id) : [...s, id])

  return (
    <>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

        {/* Filter bar */}
        <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 8 }}>
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
              placeholder="Search companies…"
              value={search}
              onChange={e => { setSearch(e.target.value); setPage(1) }}
            />
          </div>

          <span style={{ fontSize: 12.5, color: 'var(--text4)', whiteSpace: 'nowrap' }}>
            {meta.total ?? 0} companies
          </span>

          <div style={{ marginLeft: 'auto', display: 'flex', gap: 8 }}>
            {selected.length > 0 && (
              <button onClick={() => setConfirm('selected')} style={dangerBtnStyle}>
                <Trash2 size={13} strokeWidth={2} /> Delete {selected.length}
              </button>
            )}
            <button onClick={() => setConfirm('all')} style={dangerBtnStyle}>
              <Trash2 size={13} strokeWidth={2} /> Clear All
            </button>
          </div>
        </div>

        {/* Select all */}
        {companies.length > 0 && (
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <button onClick={toggleAll} style={{
              display: 'flex', alignItems: 'center', gap: 7,
              background: 'none', border: 'none', cursor: 'pointer',
              fontSize: 12.5, color: 'var(--text3)', fontFamily: 'inherit', padding: 0,
            }}>
              {allSelected
                ? <CheckSquare size={15} strokeWidth={2} color="var(--accent)" />
                : <Square size={15} strokeWidth={1.75} />}
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
            {[1, 2, 3].map(i => <CardSkeleton key={i} />)}
          </div>
        ) : companies.length === 0 ? (
          <div style={{
            padding: '60px 0', textAlign: 'center',
            background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 14,
          }}>
            <div style={{ color: 'var(--text4)', marginBottom: 14 }}>
              <Building2 size={36} strokeWidth={1.25} />
            </div>
            <p style={{ fontSize: 15, fontWeight: 600, color: 'var(--text2)', marginBottom: 6 }}>No companies yet</p>
            <p style={{ fontSize: 13.5, color: 'var(--text3)' }}>
              Companies are added automatically when you run a job search.
            </p>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {companies.map((c: any) => (
              <CompanyCard
                key={c.id} c={c}
                selected={selected.includes(c.id)}
                onToggle={() => toggle(c.id)}
              />
            ))}
          </div>
        )}

        {/* Pagination */}
        {(meta.last_page ?? 1) > 1 && (
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '8px 0' }}>
            <span style={{ fontSize: 12.5, color: 'var(--text4)' }}>
              Page {meta.current_page} of {meta.last_page}
            </span>
            <div style={{ display: 'flex', gap: 6 }}>
              {[['← Prev', page <= 1, () => setPage(p => p - 1)],
                ['Next →', page >= (meta.last_page ?? 1), () => setPage(p => p + 1)],
              ].map(([label, disabled, handler]: any) => (
                <button key={label} disabled={disabled} onClick={handler} style={{
                  background: 'var(--surface)', border: '1px solid var(--border2)',
                  borderRadius: 7, padding: '7px 14px', fontSize: 12.5,
                  color: 'var(--text2)', cursor: 'pointer', fontFamily: 'inherit',
                  opacity: disabled ? 0.35 : 1,
                }}>
                  {label}
                </button>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Dialogs */}
      {confirm === 'all' && (
        <ConfirmDialog
          message={`This will permanently delete all ${meta.total ?? 0} companies. This cannot be undone.`}
          onConfirm={() => bulkDelete.mutate({ all: true })}
          onCancel={() => setConfirm(null)}
          loading={bulkDelete.isPending}
        />
      )}
      {confirm === 'selected' && (
        <ConfirmDialog
          message={`This will permanently delete ${selected.length} selected companies.`}
          onConfirm={() => bulkDelete.mutate({ ids: selected })}
          onCancel={() => setConfirm(null)}
          loading={bulkDelete.isPending}
        />
      )}

      <style>{`
        @keyframes ts-pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
      `}</style>
    </>
  )
}

const dangerBtnStyle: React.CSSProperties = {
  display: 'flex', alignItems: 'center', gap: 6,
  padding: '7px 14px', borderRadius: 8,
  border: '1px solid #ef444440', background: '#ef444412',
  color: '#ef4444', fontSize: 13, fontWeight: 600,
  cursor: 'pointer', fontFamily: 'inherit',
}
