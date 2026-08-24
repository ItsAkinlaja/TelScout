import { useState, useEffect, useRef } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  Search, Zap, MapPin, Clock, Globe, Briefcase,
  ChevronDown, ChevronUp, ExternalLink, RefreshCw, X,
  CheckCircle2, AlertCircle, Loader2,
} from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import MatchScore from '../components/ui/MatchScore'
import { formatCurrency, formatDate } from '../lib/utils'
import { TsPageStyles } from '../components/ui/TsShared'

// ── Types ─────────────────────────────────────────────────────────────────────

interface SearchForm {
  keywords: string          // comma-separated input → array on submit
  locations: string         // comma-separated input → array on submit
  remote_only: boolean
  days_old: number
  min_score: number
}

const DEFAULT_FORM: SearchForm = {
  keywords:   '',
  locations:  '',
  remote_only: false,
  days_old:   30,
  min_score:  0,
}

// ── Source badge ──────────────────────────────────────────────────────────────

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
    jsearch:    { label: 'JSearch',    color: '#1a73e8' },
    reed:       { label: 'Reed',       color: '#cc0000' },
    manual:     { label: 'Manual',     color: 'var(--text3)' },
  }
  const b = badges[source ?? 'manual'] ?? { label: source ?? 'Manual', color: 'var(--text3)' }
  return (
    <span style={{
      fontSize: 10, fontWeight: 700, padding: '2px 6px', borderRadius: 3,
      background: b.color + '18', color: b.color,
      letterSpacing: '0.04em', textTransform: 'uppercase', whiteSpace: 'nowrap',
    }}>
      {b.label}
    </span>
  )
}

// ── Run status badge ──────────────────────────────────────────────────────────

function RunStatus({ status }: { status: string }) {
  const map: Record<string, { icon: React.ReactNode; color: string; label: string }> = {
    pending:   { icon: <Clock size={13} />,          color: 'var(--text3)',  label: 'Pending' },
    running:   { icon: <Loader2 size={13} className="ts-spin" />, color: '#f59e0b', label: 'Running…' },
    completed: { icon: <CheckCircle2 size={13} />,   color: '#22c55e',       label: 'Completed' },
    failed:    { icon: <AlertCircle size={13} />,    color: '#ef4444',       label: 'Failed' },
  }
  const s = map[status] ?? map['pending']
  return (
    <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, fontSize: 12.5, color: s.color, fontWeight: 500 }}>
      {s.icon} {s.label}
    </span>
  )
}

// ── Job card ──────────────────────────────────────────────────────────────────

function JobCard({ job }: { job: any }) {
  const opp = job.opportunities?.[0]

  return (
    <div style={{
      background: 'var(--surface)', border: '1px solid var(--border)',
      borderRadius: 10, padding: '16px 18px',
      transition: 'border-color 0.12s, box-shadow 0.12s',
    }}
      onMouseEnter={e => { (e.currentTarget as HTMLDivElement).style.borderColor = 'var(--border2)'; (e.currentTarget as HTMLDivElement).style.boxShadow = '0 2px 12px rgba(0,0,0,0.08)' }}
      onMouseLeave={e => { (e.currentTarget as HTMLDivElement).style.borderColor = 'var(--border)'; (e.currentTarget as HTMLDivElement).style.boxShadow = 'none' }}
    >
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
        <div style={{ flex: 1, minWidth: 0 }}>
          {/* Title + company */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap', marginBottom: 4 }}>
            <Link to={`/jobs/${job.id}`} style={{ fontSize: 14.5, fontWeight: 600, color: 'var(--text)', textDecoration: 'none' }}
              onMouseEnter={e => (e.currentTarget.style.color = 'var(--accent)')}
              onMouseLeave={e => (e.currentTarget.style.color = 'var(--text)')}>
              {job.title}
            </Link>
            <SourceBadge source={job.source} />
          </div>

          {/* Company */}
          <p style={{ fontSize: 13, color: 'var(--accent)', fontWeight: 500, marginBottom: 6 }}>
            {job.company?.name ?? job.company_name ?? '—'}
          </p>

          {/* Meta row */}
          <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 10, fontSize: 12.5, color: 'var(--text3)' }}>
            {job.location && (
              <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                <MapPin size={11} strokeWidth={1.75} />
                {job.location}
              </span>
            )}
            {job.workplace_type && job.workplace_type !== 'unknown' && (
              <span className={`ts-pill ${job.workplace_type === 'remote' ? 'ts-pill-green' : ''}`} style={{ fontSize: 10.5 }}>
                {job.workplace_type}
              </span>
            )}
            {job.employment_type && (
              <span style={{ fontSize: 11.5 }}>{job.employment_type}</span>
            )}
            {(job.salary_min || job.salary_max) && (
              <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                {formatCurrency(job.salary_min)} – {formatCurrency(job.salary_max)}
                {job.salary_currency && <span style={{ fontSize: 11 }}>{job.salary_currency}</span>}
              </span>
            )}
            {job.posted_at && (
              <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                <Clock size={11} strokeWidth={1.75} />
                {formatDate(job.posted_at)}
              </span>
            )}
          </div>

          {/* Skills */}
          {job.skills?.length > 0 && (
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5, marginTop: 8 }}>
              {job.skills.slice(0, 6).map((s: any) => (
                <span key={s.skill} className="ts-pill" style={{ fontSize: 11 }}>{s.skill}</span>
              ))}
              {job.skills.length > 6 && (
                <span style={{ fontSize: 11, color: 'var(--text4)' }}>+{job.skills.length - 6} more</span>
              )}
            </div>
          )}
        </div>

        {/* Right: score + apply */}
        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 8, flexShrink: 0 }}>
          {opp && <MatchScore score={opp.match_score} classification={opp.match_classification} size="sm" />}
          {job.application_url && (
            <a href={job.application_url} target="_blank" rel="noopener noreferrer"
              style={{
                display: 'inline-flex', alignItems: 'center', gap: 5, fontSize: 12.5,
                fontWeight: 600, padding: '6px 12px', borderRadius: 7,
                background: 'var(--accent)', color: '#fff', textDecoration: 'none',
              }}>
              <ExternalLink size={12} strokeWidth={2} /> Apply
            </a>
          )}
        </div>
      </div>
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function DiscoverPage() {
  const qc = useQueryClient()
  const [form, setForm]           = useState<SearchForm>(DEFAULT_FORM)
  const [showAdvanced, setAdv]    = useState(false)
  const [activeRunId, setActiveRunId] = useState<number | null>(null)
  const pollingRef = useRef<ReturnType<typeof setInterval> | null>(null)

  // Load profile to pre-fill search form
  const { data: profile } = useQuery({
    queryKey: ['profile'],
    queryFn: () => api.get('/profile').then(r => r.data),
    staleTime: 60_000,
  })

  // Pre-fill form from profile prefs on first load
  useEffect(() => {
    if (!profile || form.keywords) return
    const keywords = [
      ...(profile.preferred_roles ?? []),
      ...(profile.preferred_technologies ?? []),
    ].slice(0, 5).join(', ')

    const locations = (profile.preferred_locations ?? []).join(', ')

    setForm(f => ({
      ...f,
      keywords,
      locations,
      remote_only: profile.work_preference === 'remote',
    }))
  }, [profile])

  // Poll active run status
  const { data: activeRun } = useQuery({
    queryKey: ['search-run', activeRunId],
    queryFn: () => api.get(`/search/runs/${activeRunId}`).then(r => r.data),
    enabled: !!activeRunId,
    refetchInterval: (query) => {
      const status = query.state.data?.status
      return status === 'running' || status === 'pending' ? 3000 : false
    },
  })

  // Load results when run completes
  const [resultsPage, setResultsPage] = useState(1)
  const { data: resultsData, isLoading: resultsLoading } = useQuery({
    queryKey: ['discover-results', activeRunId, resultsPage],
    queryFn: () => api.get('/jobs', {
      params: {
        page: resultsPage,
        per_page: 20,
        status: 'active',
      },
    }).then(r => r.data),
    enabled: activeRun?.status === 'completed',
  })

  const jobs: any[] = resultsData?.data ?? []
  const meta = { total: resultsData?.total, current_page: resultsData?.current_page, last_page: resultsData?.last_page }

  const runSearch = useMutation({
    mutationFn: (payload: any) => api.post('/search/run', payload),
    onSuccess: (res) => {
      const { search_run: run, inline } = res.data
      setActiveRunId(run.id)
      setResultsPage(1)

      if (inline) {
        // Ran synchronously — run is already complete
        toast.success(`Search complete — ${run.new_jobs ?? 0} new jobs found`)
        qc.invalidateQueries({ queryKey: ['discover-results', run.id] })
      } else {
        toast.success('Search started — results will appear shortly')
        qc.invalidateQueries({ queryKey: ['search-run', run.id] })
      }
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Search failed'),
  })

  function handleSearch() {
    const payload = {
      keywords:   form.keywords.split(',').map(s => s.trim()).filter(Boolean),
      locations:  form.locations.split(',').map(s => s.trim()).filter(Boolean),
      remote_only: form.remote_only,
      days_old:   form.days_old,
      min_score:  form.min_score,
    }
    runSearch.mutate(payload)
  }

  const set = (k: keyof SearchForm, v: any) => setForm(f => ({ ...f, [k]: v }))
  const runStatus = activeRun?.status
  // Show spinner while: request is in-flight (inline search) OR run is queued
  const isRunning = runSearch.isPending || runStatus === 'running' || runStatus === 'pending'

  return (
    <>
      <div className="ts-page" style={{ maxWidth: 860 }}>

        {/* Header */}
        <div style={{ marginBottom: 24 }}>
          <h1 style={{ fontSize: 22, fontWeight: 700, color: 'var(--text)', letterSpacing: '-0.03em', margin: 0 }}>
            Discover Jobs
          </h1>
          <p style={{ fontSize: 13.5, color: 'var(--text3)', marginTop: 5 }}>
            Search across RemoteOK, Remotive, Arbeitnow, Adzuna, Reed, JSearch, The Muse and more — all at once.
          </p>
        </div>

        {/* Search form */}
        <div style={{
          background: 'var(--surface)', border: '1px solid var(--border)',
          borderRadius: 12, padding: '20px 22px', marginBottom: 24,
        }}>

          {/* Primary row */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14, marginBottom: 14 }}>
            <div className="ts-field" style={{ margin: 0 }}>
              <label className="ts-label" style={{ marginBottom: 6 }}>
                <Briefcase size={12} strokeWidth={2} style={{ display: 'inline', marginRight: 4 }} />
                Keywords / Job Titles
              </label>
              <input
                type="text"
                className="ts-input"
                placeholder="react developer, laravel, full stack…"
                value={form.keywords}
                onChange={e => set('keywords', e.target.value)}
                onKeyDown={e => e.key === 'Enter' && handleSearch()}
              />
              <p style={{ fontSize: 11, color: 'var(--text4)', marginTop: 4 }}>Comma-separated. Pre-filled from your profile.</p>
            </div>

            <div className="ts-field" style={{ margin: 0 }}>
              <label className="ts-label" style={{ marginBottom: 6 }}>
                <MapPin size={12} strokeWidth={2} style={{ display: 'inline', marginRight: 4 }} />
                Location
              </label>
              <input
                type="text"
                className="ts-input"
                placeholder="Lagos, Remote, Worldwide…"
                value={form.locations}
                onChange={e => set('locations', e.target.value)}
                onKeyDown={e => e.key === 'Enter' && handleSearch()}
              />
              <p style={{ fontSize: 11, color: 'var(--text4)', marginTop: 4 }}>Comma-separated. Use "Remote" or "Worldwide" for remote-only.</p>
            </div>
          </div>

          {/* Remote toggle */}
          <label style={{ display: 'inline-flex', alignItems: 'center', gap: 7, fontSize: 13.5, color: 'var(--text2)', cursor: 'pointer', marginBottom: 14, userSelect: 'none' }}>
            <input
              type="checkbox"
              checked={form.remote_only}
              onChange={e => set('remote_only', e.target.checked)}
            />
            Remote only
          </label>

          {/* Advanced toggle */}
          <div style={{ marginBottom: 14 }}>
            <button
              onClick={() => setAdv(v => !v)}
              style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 12.5, color: 'var(--text3)', background: 'none', border: 'none', cursor: 'pointer', fontFamily: 'inherit', padding: 0 }}
            >
              {showAdvanced ? <ChevronUp size={14} strokeWidth={1.75} /> : <ChevronDown size={14} strokeWidth={1.75} />}
              Advanced options
            </button>
          </div>

          {showAdvanced && (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14, marginBottom: 16, paddingTop: 4, borderTop: '1px solid var(--border)', paddingTop: 14 }}>
              <div className="ts-field" style={{ margin: 0 }}>
                <label className="ts-label" style={{ marginBottom: 6 }}>Max Age (days)</label>
                <select
                  className="ts-input"
                  value={form.days_old}
                  onChange={e => set('days_old', parseInt(e.target.value))}
                >
                  {[7, 14, 30, 60, 90].map(d => (
                    <option key={d} value={d}>Last {d} days</option>
                  ))}
                </select>
              </div>
              <div className="ts-field" style={{ margin: 0 }}>
                <label className="ts-label" style={{ marginBottom: 6 }}>Minimum match score (%)</label>
                <select
                  className="ts-input"
                  value={form.min_score}
                  onChange={e => set('min_score', parseInt(e.target.value))}
                >
                  {[0, 40, 50, 60, 70, 80].map(s => (
                    <option key={s} value={s}>{s === 0 ? 'Any match' : `${s}%+`}</option>
                  ))}
                </select>
              </div>
            </div>
          )}

          {/* Search button */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
            <button
              className="ts-btn-primary"
              onClick={handleSearch}
              disabled={runSearch.isPending || isRunning || !form.keywords.trim()}
              style={{ display: 'flex', alignItems: 'center', gap: 7, padding: '10px 20px', fontSize: 14, fontWeight: 600 }}
            >
              {runSearch.isPending
                ? <><Loader2 size={15} className="ts-spin" /> Fetching jobs…</>
                : isRunning
                  ? <><Loader2 size={15} className="ts-spin" /> Searching…</>
                  : <><Search size={15} strokeWidth={2} /> Search Jobs</>
              }
            </button>
            {form.keywords !== (profile?.preferred_roles?.slice(0, 2).join(', ') ?? '') && (
              <button
                onClick={() => {
                  const keywords = [...(profile?.preferred_roles ?? []), ...(profile?.preferred_technologies ?? [])].slice(0, 5).join(', ')
                  const locations = (profile?.preferred_locations ?? []).join(', ')
                  setForm(f => ({ ...f, keywords, locations, remote_only: profile?.work_preference === 'remote' }))
                }}
                style={{ fontSize: 12, color: 'var(--accent)', background: 'none', border: 'none', cursor: 'pointer', textDecoration: 'underline', fontFamily: 'inherit' }}
              >
                Reset to profile defaults
              </button>
            )}
          </div>

        </div>

        {/* Active run status */}
        {(activeRun || runSearch.isPending) && (
          <div style={{
            display: 'flex', alignItems: 'center', justifyContent: 'space-between',
            padding: '12px 16px', background: 'var(--surface)',
            border: '1px solid var(--border)', borderRadius: 10, marginBottom: 20,
          }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
              {runSearch.isPending
                ? <RunStatus status="running" />
                : <RunStatus status={runStatus ?? 'pending'} />
              }
              {activeRun?.new_jobs != null && (
                <span style={{ fontSize: 12.5, color: 'var(--text3)' }}>
                  {activeRun.new_jobs} new job{activeRun.new_jobs !== 1 ? 's' : ''} found
                  {activeRun.results_count != null && ` · ${activeRun.results_count} total fetched`}
                </span>
              )}
              {runSearch.isPending && (
                <span style={{ fontSize: 12.5, color: 'var(--text3)' }}>
                  Querying all sources — this takes up to 60 seconds…
                </span>
              )}
              {activeRun?.error_message && (
                <span style={{ fontSize: 12, color: '#ef4444' }}>{activeRun.error_message}</span>
              )}
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              {runStatus === 'completed' && (
                <button
                  onClick={() => qc.invalidateQueries({ queryKey: ['discover-results'] })}
                  style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 12, color: 'var(--accent)', background: 'none', border: 'none', cursor: 'pointer' }}
                >
                  <RefreshCw size={12} strokeWidth={2} /> Refresh
                </button>
              )}
              {!runSearch.isPending && (
                <button
                  onClick={() => setActiveRunId(null)}
                  style={{ color: 'var(--text4)', background: 'none', border: 'none', cursor: 'pointer', padding: 2 }}
                >
                  <X size={14} strokeWidth={1.75} />
                </button>
              )}
            </div>
          </div>
        )}

        {/* Results */}
        {runStatus === 'completed' && (
          <>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 }}>
              <p style={{ fontSize: 13.5, fontWeight: 600, color: 'var(--text)', margin: 0 }}>
                {meta.total ?? 0} jobs in your database
              </p>
              <Link to="/jobs" style={{ fontSize: 12.5, color: 'var(--accent)', textDecoration: 'none', fontWeight: 500 }}>
                View all jobs →
              </Link>
            </div>

            {resultsLoading ? (
              <LoadingSpinner />
            ) : jobs.length === 0 ? (
              <div style={{ padding: '32px', textAlign: 'center', color: 'var(--text3)', fontSize: 13.5 }}>
                No jobs matched your criteria. Try broader keywords or a longer time range.
              </div>
            ) : (
              <>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                  {jobs.map((job: any) => (
                    <JobCard key={job.id} job={job} />
                  ))}
                </div>

                {(meta.last_page ?? 1) > 1 && (
                  <div className="ts-pagination" style={{ marginTop: 20 }}>
                    <span className="ts-pg-info">Page {meta.current_page} of {meta.last_page}</span>
                    <div className="ts-pg-btns">
                      <button disabled={resultsPage <= 1} onClick={() => setResultsPage(p => p - 1)} className="ts-pg-btn">← Prev</button>
                      <button disabled={resultsPage >= (meta.last_page ?? 1)} onClick={() => setResultsPage(p => p + 1)} className="ts-pg-btn">Next →</button>
                    </div>
                  </div>
                )}
              </>
            )}
          </>
        )}

        {/* Pre-search empty state */}
        {!activeRunId && (
          <div style={{ padding: '48px 0', textAlign: 'center' }}>
            <div style={{ marginBottom: 16, color: 'var(--text4)' }}>
              <Zap size={32} strokeWidth={1.25} />
            </div>
            <p style={{ fontSize: 15, fontWeight: 600, color: 'var(--text2)', marginBottom: 8 }}>
              Ready to discover jobs
            </p>
            <p style={{ fontSize: 13.5, color: 'var(--text3)', maxWidth: 360, margin: '0 auto' }}>
              Your search keywords and locations have been pre-filled from your profile.
              Hit <strong>Search Jobs</strong> to start discovering across all connected sources.
            </p>
          </div>
        )}

      </div>

      <style>{`.ts-spin { animation: spin 1s linear infinite; } @keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }`}</style>
      <TsPageStyles />
    </>
  )
}
