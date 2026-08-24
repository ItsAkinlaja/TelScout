import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Briefcase, Search, Plus, X, SlidersHorizontal } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import EmptyState from '../components/ui/EmptyState'
import StatusBadge from '../components/ui/StatusBadge'
import { formatCurrency } from '../lib/utils'
import { TsPageStyles } from '../components/ui/TsShared'

function SourceBadge({ source }: { source: string | null }) {
  const badges: Record<string, { label: string; color: string }> = {
    greenhouse: { label: 'Greenhouse', color: '#24a148' },
    lever:      { label: 'Lever',      color: '#0066cc' },
    ashby:      { label: 'Ashby',      color: '#7c3aed' },
    remoteok:   { label: 'RemoteOK',   color: '#28a745' },
    remotive:   { label: 'Remotive',   color: '#ef4444' },
    arbeitnow:  { label: 'Arbeitnow',  color: '#f59e0b' },
    adzuna:     { label: 'Adzuna',     color: '#e55a1d' },
    the_muse:   { label: 'The Muse',   color: '#e91e8c' },
    manual:     { label: 'Manual',     color: 'var(--text3)' },
  }
  const b = badges[source ?? 'manual'] ?? { label: source ?? 'Manual', color: 'var(--text3)' }
  return (
    <span style={{
      fontSize: 11, fontWeight: 600, padding: '2px 7px', borderRadius: 4,
      background: b.color + '18', color: b.color, letterSpacing: '0.02em', textTransform: 'uppercase',
    }}>
      {b.label}
    </span>
  )
}

export default function JobsPage() {
  const [search, setSearch]           = useState('')
  const [remote, setRemote]           = useState(false)
  const [page, setPage]               = useState(1)
  const [showAdd, setShowAdd]         = useState(false)
  const [showFilters, setShowFilters] = useState(false)
  const [workplaceType, setWorkplaceType]       = useState('')
  const [experienceLevel, setExperienceLevel]   = useState('')
  const [source, setSource]                     = useState('')
  const [country, setCountry]                   = useState('')
  const qc = useQueryClient()

  const { data: filterOptions } = useQuery({
    queryKey: ['job-filters'],
    queryFn: () => api.get('/jobs/filters').then(r => r.data),
    staleTime: 5 * 60 * 1000,
  })

  const { data, isLoading } = useQuery({
    queryKey: ['jobs', search, remote, page, workplaceType, experienceLevel, source, country],
    queryFn: () => api.get('/jobs', { params: {
      search,
      remote: remote || undefined,
      page,
      per_page: 25,
      workplace_type: workplaceType || undefined,
      experience_level: experienceLevel || undefined,
      source: source || undefined,
      country: country || undefined,
    } }).then(r => r.data),
  })

  const addJob = useMutation({
    mutationFn: (d: any) => api.post('/jobs', d),
    onSuccess: () => { toast.success('Job added & scored'); setShowAdd(false); qc.invalidateQueries({ queryKey: ['jobs'] }) },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to add job'),
  })

  const jobs = data?.data ?? []
  const meta = { total: data?.total, current_page: data?.current_page, last_page: data?.last_page }

  const hasActiveFilters = !!(workplaceType || experienceLevel || source || country)

  function clearFilters() {
    setWorkplaceType('')
    setExperienceLevel('')
    setSource('')
    setCountry('')
    setPage(1)
  }

  const selectStyle: React.CSSProperties = { fontSize: 13, padding: '6px 10px', minWidth: 140 }

  return (
    <>
      <div className="ts-page">
        <div className="ts-filters">
          <div className="ts-search-wrap">
            <Search size={15} strokeWidth={1.75} className="ts-search-icon" />
            <input className="ts-input ts-search-input" placeholder="Search jobs…"
              value={search} onChange={e => { setSearch(e.target.value); setPage(1) }} />
          </div>
          <label style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 13.5, color: 'var(--text2)', cursor: 'pointer', userSelect: 'none' }}>
            <input type="checkbox" checked={remote} onChange={e => setRemote(e.target.checked)} />
            Remote only
          </label>
          <button
            className={`ts-btn-secondary${showFilters ? ' ts-btn-active' : ''}`}
            onClick={() => setShowFilters(v => !v)}
            style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '8px 12px', fontSize: 13 }}
          >
            <SlidersHorizontal size={14} strokeWidth={1.75} />
            Filters
            {hasActiveFilters && (
              <span style={{ fontSize: 11, fontWeight: 700, background: 'var(--accent)', color: '#fff', borderRadius: 10, padding: '1px 6px', lineHeight: 1.4 }}>
                {[workplaceType, experienceLevel, source, country].filter(Boolean).length}
              </span>
            )}
          </button>
          <button className="ts-btn-primary" onClick={() => setShowAdd(v => !v)}
            style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '8px 12px', fontSize: 13 }}>
            {showAdd ? <X size={14} strokeWidth={2} /> : <Plus size={14} strokeWidth={2} />}
            {showAdd ? 'Cancel' : 'Add Job'}
          </button>
          <span className="ts-count">{meta.total ?? 0} jobs</span>
        </div>

        {showFilters && (
          <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 10, padding: '12px 0 4px' }}>
            <select
              className="ts-input"
              style={selectStyle}
              value={workplaceType}
              onChange={e => { setWorkplaceType(e.target.value); setPage(1) }}
            >
              <option value="">All workplace types</option>
              {['remote', 'hybrid', 'onsite'].map(v => (
                <option key={v} value={v}>{v.charAt(0).toUpperCase() + v.slice(1)}</option>
              ))}
            </select>

            <select
              className="ts-input"
              style={selectStyle}
              value={experienceLevel}
              onChange={e => { setExperienceLevel(e.target.value); setPage(1) }}
            >
              <option value="">All experience levels</option>
              {['entry', 'mid', 'senior', 'lead', 'executive'].map(v => (
                <option key={v} value={v}>{v.charAt(0).toUpperCase() + v.slice(1)}</option>
              ))}
            </select>

            <select
              className="ts-input"
              style={selectStyle}
              value={source}
              onChange={e => { setSource(e.target.value); setPage(1) }}
            >
              <option value="">All sources</option>
              {(filterOptions?.sources ?? []).map((s: string) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>

            <select
              className="ts-input"
              style={selectStyle}
              value={country}
              onChange={e => { setCountry(e.target.value); setPage(1) }}
            >
              <option value="">All countries</option>
              {(filterOptions?.countries ?? []).map((c: string) => (
                <option key={c} value={c}>{c}</option>
              ))}
            </select>

            {hasActiveFilters && (
              <button
                onClick={clearFilters}
                style={{ fontSize: 12.5, color: 'var(--accent)', background: 'none', border: 'none', cursor: 'pointer', padding: '0 4px', textDecoration: 'underline' }}
              >
                Clear filters
              </button>
            )}
          </div>
        )}

        {showAdd && <AddJobForm onSubmit={(d: any) => addJob.mutate(d)} loading={addJob.isPending} />}

        {isLoading ? <LoadingSpinner /> : jobs.length === 0 ? (
          <EmptyState icon={Briefcase} title="No jobs yet" description="Add jobs manually or run a discovery search." />
        ) : (
          <div className="ts-table-wrap">
            <table className="ts-table">
              <thead>
                <tr>
                  {['Role', 'Company', 'Location', 'Salary', 'Source', 'Status'].map(h => (
                    <th key={h} className="ts-th">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {jobs.map((job: any) => (
                  <tr key={job.id} className="ts-tr">
                    <td className="ts-td">
                      <Link to={`/jobs/${job.id}`} className="ts-row-link">{job.title}</Link>
                    </td>
                    <td className="ts-td ts-td-muted">{job.company?.name ?? '—'}</td>
                    <td className="ts-td ts-td-muted">
                      <span style={{ display: 'flex', alignItems: 'center', flexWrap: 'wrap', gap: 5 }}>
                        {job.location ?? (job.is_remote ? 'Remote' : '—')}
                        {job.workplace_type && job.workplace_type !== 'unknown' && (
                          <span className={`ts-pill ${job.workplace_type === 'remote' ? 'ts-pill-green' : ''}`} style={{ fontSize: 11 }}>
                            {job.workplace_type}
                          </span>
                        )}
                      </span>
                    </td>
                    <td className="ts-td ts-td-muted">
                      {(job.salary_min || job.salary_max)
                        ? `${formatCurrency(job.salary_min)} – ${formatCurrency(job.salary_max)}`
                        : '—'}
                    </td>
                    <td className="ts-td ts-td-dim">
                      <SourceBadge source={job.source} />
                    </td>
                    <td className="ts-td"><StatusBadge status={job.status} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
            {(meta.last_page ?? 1) > 1 && (
              <div className="ts-pagination">
                <span className="ts-pg-info">Page {meta.current_page} of {meta.last_page}</span>
                <div className="ts-pg-btns">
                  <button disabled={page <= 1} onClick={() => setPage(p => p - 1)} className="ts-pg-btn">← Prev</button>
                  <button disabled={page >= (meta.last_page ?? 1)} onClick={() => setPage(p => p + 1)} className="ts-pg-btn">Next →</button>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
      <TsPageStyles />
    </>
  )
}

function AddJobForm({ onSubmit, loading }: { onSubmit: (d: any) => void; loading: boolean }) {
  const [form, setForm] = useState({
    title: '', company_name: '', company_website: '',
    location: '', is_remote: false, application_url: '',
    source_url: '', description: '',
  })
  const set = (k: string, v: any) => setForm(f => ({ ...f, [k]: v }))

  return (
    <div style={{ background: 'var(--surface)', border: '1px solid var(--border2)', borderRadius: 10, padding: 20 }}>
      <p style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)', marginBottom: 16 }}>Add a Job Manually</p>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 12 }}>
        {[
          ['title', 'Job Title *', 'text'], ['company_name', 'Company Name *', 'text'],
          ['company_website', 'Company Website', 'url'], ['location', 'Location', 'text'],
          ['application_url', 'Application URL', 'url'], ['source_url', 'Source URL', 'url'],
        ].map(([k, label, type]) => (
          <div key={k} className="ts-field" style={{ margin: 0 }}>
            <label className="ts-label">{label}</label>
            <input type={type} value={(form as any)[k]} onChange={e => set(k, e.target.value)}
              className="ts-input" />
          </div>
        ))}
        <div className="ts-field" style={{ gridColumn: '1/-1', margin: 0 }}>
          <label className="ts-label">Description</label>
          <textarea value={form.description} onChange={e => set('description', e.target.value)}
            rows={4} className="ts-textarea" />
        </div>
        <label style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 13.5, color: 'var(--text2)', cursor: 'pointer' }}>
          <input type="checkbox" checked={form.is_remote} onChange={e => set('is_remote', e.target.checked)} />
          Remote position
        </label>
      </div>
      <button className="ts-btn-primary" style={{ marginTop: 16 }} onClick={() => onSubmit(form)} disabled={loading}>
        {loading ? 'Adding…' : 'Add & Score'}
      </button>
    </div>
  )
}
