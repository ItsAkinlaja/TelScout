import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  Briefcase, Search, Plus, X, SlidersHorizontal,
  MapPin, Clock, ExternalLink, Globe, DollarSign,
  Trash2, CheckSquare, Square, AlertTriangle,
} from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import { formatCurrency, formatDate } from '../lib/utils'

// ── Helpers ───────────────────────────────────────────────────────────────────

function ensureUrl(url: string): string {
  if (!url) return ''
  return url.startsWith('http://') || url.startsWith('https://') ? url : 'https://' + url
}

function displayDomain(url: string): string {
  try {
    return new URL(ensureUrl(url)).hostname.replace(/^www\./, '')
  } catch {
    return url
  }
}

const SOURCE_BADGES: Record<string, { label: string; color: string }> = {
  greenhouse: { label: 'Greenhouse', color: '#24a148' },
  lever:      { label: 'Lever',      color: '#0066cc' },
  ashby:      { label: 'Ashby',      color: '#7c3aed' },
  remoteok:   { label: 'RemoteOK',   color: '#28a745' },
  remotive:   { label: 'Remotive',   color: '#ef4444' },
  arbeitnow:  { label: 'Arbeitnow',  color: '#f59e0b' },
  adzuna:     { label: 'Adzuna',     color: '#e55a1d' },
  the_muse:   { label: 'The Muse',   color: '#e91e8c' },
  jsearch:    { label: 'JSearch',    color: '#1a73e8' },
  reed:       { label: 'Reed',       color: '#cc0000' },
  manual:     { label: 'Manual',     color: '#6b7280' },
}

function SourceBadge({ source }: { source: string | null }) {
  const b = SOURCE_BADGES[source ?? 'manual'] ?? { label: source ?? 'Manual', color: '#6b7280' }
  return (
    <span style={{
      fontSize: 10, fontWeight: 700, padding: '2px 6px', borderRadius: 4,
      background: b.color + '18', color: b.color,
      letterSpacing: '0.04em', textTransform: 'uppercase', whiteSpace: 'nowrap',
    }}>
      {b.label}
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

// ── Job Card ──────────────────────────────────────────────────────────────────

function JobCard({
  job, selected, onToggle,
}: { job: any; selected: boolean; onToggle: () => void }) {
  const website = job.company?.website ? ensureUrl(job.company.website) : null

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
            {/* Title row */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap', marginBottom: 3 }}>
              <Link to={`/jobs/${job.id}`} style={{
                fontSize: 14.5, fontWeight: 600, color: 'var(--text)', textDecoration: 'none',
              }}
                onMouseEnter={e => (e.currentTarget.style.color = 'var(--accent)')}
                onMouseLeave={e => (e.currentTarget.style.color = 'var(--text)')}
              >
                {job.title}
              </Link>
              <SourceBadge source={job.source} />
              {job.workplace_type && job.workplace_type !== 'unknown' && (
                <span style={{
                  fontSize: 10, fontWeight: 600, padding: '2px 6px', borderRadius: 4,
                  background: job.workplace_type === 'remote' ? '#22c55e18' : 'var(--surface2)',
                  color: job.workplace_type === 'remote' ? '#22c55e' : 'var(--text3)',
                  border: '1px solid var(--border)',
                }}>
                  {job.workplace_type}
                </span>
              )}
            </div>

            {/* Company row */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
              <Link to={`/companies/${job.company?.id}`} style={{
                fontSize: 13, color: 'var(--accent)', fontWeight: 500, textDecoration: 'none',
              }}
                onMouseEnter={e => (e.currentTarget.style.textDecoration = 'underline')}
                onMouseLeave={e => (e.currentTarget.style.textDecoration = 'none')}
              >
                {job.company?.name ?? '—'}
              </Link>
              {/* Company website — the fix */}
              {website && (
                <a
                  href={website}
                  target="_blank"
                  rel="noopener noreferrer"
                  onClick={e => e.stopPropagation()}
                  style={{
                    display: 'inline-flex', alignItems: 'center', gap: 3,
                    fontSize: 11.5, color: 'var(--text4)', textDecoration: 'none',
                    transition: 'color 0.12s',
                  }}
                  onMouseEnter={e => (e.currentTarget.style.color = 'var(--accent)')}
                  onMouseLeave={e => (e.currentTarget.style.color = 'var(--text4)')}
                  title={`Visit ${displayDomain(job.company.website)}`}
                >
                  <Globe size={11} strokeWidth={1.75} />
                  {displayDomain(job.company.website)}
                  <ExternalLink size={9} strokeWidth={2} style={{ opacity: 0.6 }} />
                </a>
              )}
            </div>

            {/* Meta row */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10, fontSize: 12.5, color: 'var(--text3)', alignItems: 'center' }}>
              {job.location && (
                <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                  <MapPin size={11} strokeWidth={1.75} />{job.location}
                </span>
              )}
              {(job.salary_min || job.salary_max) && (
                <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                  <DollarSign size={11} strokeWidth={1.75} />
                  {formatCurrency(job.salary_min)} – {formatCurrency(job.salary_max)}
                  {job.salary_currency && ` ${job.salary_currency}`}
                </span>
              )}
              {job.posted_at && (
                <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                  <Clock size={11} strokeWidth={1.75} />{formatDate(job.posted_at)}
                </span>
              )}
            </div>
          </div>

          {/* Right: apply button */}
          {job.application_url && (
            <a
              href={ensureUrl(job.application_url)}
              target="_blank"
              rel="noopener noreferrer"
              style={{
                display: 'inline-flex', alignItems: 'center', gap: 5,
                fontSize: 12.5, fontWeight: 600, padding: '6px 12px',
                borderRadius: 7, background: 'var(--accent)', color: '#fff',
                textDecoration: 'none', flexShrink: 0,
              }}
            >
              <ExternalLink size={11} strokeWidth={2} /> Apply
            </a>
          )}
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
      {[['60%', 14], ['35%', 12], ['75%', 11]].map(([w, h], i) => (
        <div key={i} style={{
          height: h as number, width: w as string, borderRadius: 6,
          background: 'var(--surface2)', marginBottom: i < 2 ? 8 : 0,
          animation: 'ts-pulse 1.5s ease-in-out infinite',
        }} />
      ))}
    </div>
  )
}

// ── Add Job Form ──────────────────────────────────────────────────────────────

function AddJobForm({ onSubmit, loading, onCancel }: { onSubmit: (d: any) => void; loading: boolean; onCancel: () => void }) {
  const [form, setForm] = useState({
    title: '', company_name: '', company_website: '',
    location: '', is_remote: false, application_url: '', description: '',
  })
  const set = (k: string, v: any) => setForm(f => ({ ...f, [k]: v }))

  return (
    <div style={{
      background: 'var(--surface)', border: '1px solid var(--border2)',
      borderRadius: 12, padding: 20,
    }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 }}>
        <p style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)', margin: 0 }}>Add a Job Manually</p>
        <button onClick={onCancel} style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text4)', padding: 2 }}>
          <X size={16} strokeWidth={1.75} />
        </button>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 12 }}>
        {[
          ['title', 'Job Title *', 'text'],
          ['company_name', 'Company Name *', 'text'],
          ['company_website', 'Company Website', 'url'],
          ['location', 'Location', 'text'],
          ['application_url', 'Application URL', 'url'],
        ].map(([k, label, type]) => (
          <div key={k}>
            <label style={{ display: 'block', fontSize: 12, fontWeight: 500, color: 'var(--text3)', marginBottom: 5 }}>{label}</label>
            <input
              type={type}
              value={(form as any)[k]}
              onChange={e => set(k, e.target.value)}
              style={{
                width: '100%', boxSizing: 'border-box',
                background: 'var(--surface2)', border: '1px solid var(--border2)',
                borderRadius: 7, padding: '8px 12px',
                fontSize: 13.5, color: 'var(--text)', fontFamily: 'inherit',
              }}
            />
          </div>
        ))}
        <div style={{ gridColumn: '1/-1' }}>
          <label style={{ display: 'block', fontSize: 12, fontWeight: 500, color: 'var(--text3)', marginBottom: 5 }}>Description</label>
          <textarea
            value={form.description}
            onChange={e => set('description', e.target.value)}
            rows={3}
            style={{
              width: '100%', boxSizing: 'border-box',
              background: 'var(--surface2)', border: '1px solid var(--border2)',
              borderRadius: 7, padding: '8px 12px',
              fontSize: 13.5, color: 'var(--text)', fontFamily: 'inherit', resize: 'vertical',
            }}
          />
        </div>
        <label style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 13.5, color: 'var(--text2)', cursor: 'pointer' }}>
          <input type="checkbox" checked={form.is_remote} onChange={e => set('is_remote', e.target.checked)} />
          Remote position
        </label>
      </div>
      <button
        onClick={() => onSubmit(form)}
        disabled={loading || !form.title.trim()}
        style={{
          marginTop: 16, padding: '9px 20px', background: 'var(--accent)',
          border: 'none', borderRadius: 8, color: '#fff', fontSize: 13.5,
          fontWeight: 600, cursor: 'pointer', fontFamily: 'inherit',
          opacity: loading || !form.title.trim() ? 0.6 : 1,
        }}
      >
        {loading ? 'Adding…' : 'Add & Score'}
      </button>
    </div>
  )
}

// ── Main ──────────────────────────────────────────────────────────────────────

export default function JobsPage() {
  const qc = useQueryClient()
  const [search, setSearch]         = useState('')
  const [remote, setRemote]         = useState(false)
  const [page, setPage]             = useState(1)
  const [showAdd, setShowAdd]       = useState(false)
  const [showFilters, setShowFilters] = useState(false)
  const [workplaceType, setWt]      = useState('')
  const [source, setSrc]            = useState('')
  const [selected, setSelected]     = useState<number[]>([])
  const [confirm, setConfirm]       = useState<'all' | 'selected' | null>(null)

  const { data: filterOptions } = useQuery({
    queryKey: ['job-filters'],
    queryFn: () => api.get('/jobs/filters').then(r => r.data),
    staleTime: 5 * 60_000,
  })

  const { data, isLoading } = useQuery({
    queryKey: ['jobs', search, remote, page, workplaceType, source],
    queryFn: () => api.get('/jobs', { params: {
      search, remote: remote || undefined, page, per_page: 20,
      workplace_type: workplaceType || undefined,
      source: source || undefined,
    }}).then(r => r.data),
  })

  const addJob = useMutation({
    mutationFn: (d: any) => api.post('/jobs', d),
    onSuccess: () => {
      toast.success('Job added & scored')
      setShowAdd(false)
      qc.invalidateQueries({ queryKey: ['jobs'] })
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed'),
  })

  const bulkDelete = useMutation({
    mutationFn: (payload: { all?: boolean; ids?: number[] }) =>
      api.delete('/jobs/bulk', { data: payload }),
    onSuccess: (res) => {
      toast.success(res.data.message ?? 'Deleted')
      setSelected([])
      setConfirm(null)
      qc.invalidateQueries({ queryKey: ['jobs'] })
    },
    onError: (e: any) => {
      toast.error(e.response?.data?.message ?? 'Delete failed')
      setConfirm(null)
    },
  })

  const jobs: any[]  = data?.data ?? []
  const meta = { total: data?.total, current_page: data?.current_page, last_page: data?.last_page }

  const allSelected = jobs.length > 0 && jobs.every(j => selected.includes(j.id))
  const toggleAll   = () => setSelected(allSelected ? [] : jobs.map(j => j.id))
  const toggle      = (id: number) => setSelected(s => s.includes(id) ? s.filter(x => x !== id) : [...s, id])
  const hasFilters  = !!(workplaceType || source)

  return (
    <>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

        {/* Top bar */}
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
              placeholder="Search jobs…"
              value={search}
              onChange={e => { setSearch(e.target.value); setPage(1) }}
            />
          </div>

          {/* Remote toggle */}
          <label style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 13.5, color: 'var(--text2)', cursor: 'pointer', userSelect: 'none', whiteSpace: 'nowrap' }}>
            <input type="checkbox" checked={remote} onChange={e => { setRemote(e.target.checked); setPage(1) }} />
            Remote only
          </label>

          {/* Filters toggle */}
          <button
            onClick={() => setShowFilters(v => !v)}
            style={{
              display: 'flex', alignItems: 'center', gap: 6,
              padding: '8px 12px', borderRadius: 8,
              border: `1px solid ${showFilters ? 'rgba(59,130,246,0.4)' : 'var(--border2)'}`,
              background: showFilters ? 'rgba(59,130,246,0.08)' : 'var(--surface)',
              color: showFilters ? 'var(--accent)' : 'var(--text2)',
              fontSize: 13, cursor: 'pointer', fontFamily: 'inherit', fontWeight: 500,
              transition: 'all 0.12s',
            }}
          >
            <SlidersHorizontal size={13} strokeWidth={1.75} />
            Filters
            {hasFilters && (
              <span style={{
                fontSize: 10, fontWeight: 700, background: 'var(--accent)', color: '#fff',
                borderRadius: 10, padding: '1px 6px', lineHeight: 1.4,
              }}>
                {[workplaceType, source].filter(Boolean).length}
              </span>
            )}
          </button>

          {/* Add job */}
          <button
            onClick={() => setShowAdd(v => !v)}
            style={{
              display: 'flex', alignItems: 'center', gap: 6,
              padding: '8px 14px', borderRadius: 8, border: 'none',
              background: 'var(--accent)', color: '#fff',
              fontSize: 13, cursor: 'pointer', fontFamily: 'inherit', fontWeight: 600,
            }}
          >
            {showAdd ? <X size={13} strokeWidth={2} /> : <Plus size={13} strokeWidth={2} />}
            {showAdd ? 'Cancel' : 'Add Job'}
          </button>

          <span style={{ fontSize: 12.5, color: 'var(--text4)', whiteSpace: 'nowrap' }}>
            {meta.total ?? 0} jobs
          </span>

          {/* Bulk actions */}
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

        {/* Filter row */}
        {showFilters && (
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10, alignItems: 'center', padding: '12px 16px', background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 10 }}>
            <select style={selectStyle} value={workplaceType} onChange={e => { setWt(e.target.value); setPage(1) }}>
              <option value="">All workplace types</option>
              {['remote', 'hybrid', 'onsite'].map(v => (
                <option key={v} value={v}>{v[0].toUpperCase() + v.slice(1)}</option>
              ))}
            </select>
            <select style={selectStyle} value={source} onChange={e => { setSrc(e.target.value); setPage(1) }}>
              <option value="">All sources</option>
              {(filterOptions?.sources ?? []).map((s: string) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
            {hasFilters && (
              <button onClick={() => { setWt(''); setSrc(''); setPage(1) }} style={{
                fontSize: 12.5, color: 'var(--accent)', background: 'none',
                border: 'none', cursor: 'pointer', textDecoration: 'underline', fontFamily: 'inherit',
              }}>
                Clear filters
              </button>
            )}
          </div>
        )}

        {/* Add job form */}
        {showAdd && (
          <AddJobForm
            onSubmit={(d: any) => addJob.mutate(d)}
            loading={addJob.isPending}
            onCancel={() => setShowAdd(false)}
          />
        )}

        {/* Select all */}
        {jobs.length > 0 && (
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
            {[1, 2, 3, 4].map(i => <CardSkeleton key={i} />)}
          </div>
        ) : jobs.length === 0 ? (
          <div style={{
            padding: '60px 0', textAlign: 'center',
            background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 14,
          }}>
            <div style={{ color: 'var(--text4)', marginBottom: 14 }}>
              <Briefcase size={36} strokeWidth={1.25} />
            </div>
            <p style={{ fontSize: 15, fontWeight: 600, color: 'var(--text2)', marginBottom: 6 }}>No jobs yet</p>
            <p style={{ fontSize: 13.5, color: 'var(--text3)', marginBottom: 20 }}>
              Add a job manually or run a discovery search.
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
            {jobs.map((job: any) => (
              <JobCard
                key={job.id} job={job}
                selected={selected.includes(job.id)}
                onToggle={() => toggle(job.id)}
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
          message={`This will permanently delete all ${meta.total ?? 0} jobs. This cannot be undone.`}
          onConfirm={() => bulkDelete.mutate({ all: true })}
          onCancel={() => setConfirm(null)}
          loading={bulkDelete.isPending}
        />
      )}
      {confirm === 'selected' && (
        <ConfirmDialog
          message={`This will permanently delete ${selected.length} selected jobs.`}
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

const selectStyle: React.CSSProperties = {
  background: 'var(--surface)', border: '1px solid var(--border2)',
  borderRadius: 8, padding: '7px 10px',
  fontSize: 13, color: 'var(--text2)', cursor: 'pointer', fontFamily: 'inherit',
}

const dangerBtnStyle: React.CSSProperties = {
  display: 'flex', alignItems: 'center', gap: 6,
  padding: '7px 14px', borderRadius: 8,
  border: '1px solid #ef444440', background: '#ef444412',
  color: '#ef4444', fontSize: 13, fontWeight: 600,
  cursor: 'pointer', fontFamily: 'inherit',
}
