import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  Search, Zap, MapPin, Clock, Briefcase,
  ChevronDown, ChevronUp, ExternalLink, RefreshCw, X,
  CheckCircle2, AlertCircle, Globe, DollarSign, Loader2,
} from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import MatchScore from '../components/ui/MatchScore'
import { formatCurrency, formatDate } from '../lib/utils'

// ── Types ─────────────────────────────────────────────────────────────────────

interface SearchForm {
  keywords:    string
  locations:   string
  remote_only: boolean
  days_old:    number
  min_score:   number
}

const DEFAULT_FORM: SearchForm = {
  keywords:    '',
  locations:   '',
  remote_only: false,
  days_old:    30,
  min_score:   0,
}

// ── Source registry ───────────────────────────────────────────────────────────

const SOURCES: Record<string, { label: string; color: string }> = {
  remoteok:          { label: 'RemoteOK',      color: '#28a745' },
  remotive:          { label: 'Remotive',      color: '#ef4444' },
  arbeitnow:         { label: 'Arbeitnow',     color: '#f59e0b' },
  adzuna:            { label: 'Adzuna',        color: '#e55a1d' },
  the_muse:          { label: 'The Muse',      color: '#e91e8c' },
  jsearch:           { label: 'JSearch',       color: '#1a73e8' },
  reed:              { label: 'Reed',          color: '#cc0000' },
  serpapi:           { label: 'Google Jobs',   color: '#4285f4' },
  openwebninja:      { label: 'Google Jobs+',  color: '#0f9d58' },
  jobicy:            { label: 'Jobicy',        color: '#7c3aed' },
  jobicy_tagged:     { label: 'Jobicy',        color: '#7c3aed' },
  jobicy_africa:     { label: 'Jobicy Africa', color: '#db7c26' },
  careerjet:         { label: 'CareerJet',     color: '#005b99' },
  careerjet_nigeria: { label: 'CareerJet NG',  color: '#007a33' },
  greenhouse:        { label: 'Greenhouse',    color: '#24a148' },
  lever:             { label: 'Lever',         color: '#0066cc' },
  ashby:             { label: 'Ashby',         color: '#7c3aed' },
}

function SourceChip({ sourceKey, active, done }: { sourceKey: string; active: boolean; done: boolean }) {
  const s = SOURCES[sourceKey] ?? { label: sourceKey, color: '#6b7280' }
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center', gap: 5,
      fontSize: 11, fontWeight: 700,
      padding: '3px 9px', borderRadius: 20,
      background: done ? s.color + '22' : active ? s.color + '15' : 'var(--surface2)',
      color: done || active ? s.color : 'var(--text4)',
      border: `1px solid ${done || active ? s.color + '40' : 'var(--border)'}`,
      letterSpacing: '0.03em', textTransform: 'uppercase',
      transition: 'all 0.35s ease',
    }}>
      {done && <CheckCircle2 size={10} strokeWidth={2.5} />}
      {active && !done && <Loader2 size={10} strokeWidth={2.5} style={{ animation: 'ts-spin 0.8s linear infinite' }} />}
      {s.label}
    </span>
  )
}

// ── Live search progress panel ────────────────────────────────────────────────

function LiveProgress({
  run, isPending, onDismiss,
}: { run: any; isPending: boolean; onDismiss: () => void }) {
  const status     = run?.status ?? (isPending ? 'pending' : 'idle')
  const meta       = run?.meta ?? {}
  const sourcesDone: number   = meta.sources_done  ?? 0
  const sourcesTotal: number  = meta.sources_total ?? 5
  const currentSource: string = meta.current_source ?? ''
  const sourceNames: string[] = meta.source_names  ?? []
  const newJobs: number       = run?.new_jobs       ?? 0
  const fetched: number       = run?.results_count  ?? 0
  const pct = sourcesTotal > 0 ? Math.round((sourcesDone / sourcesTotal) * 100) : 0

  if (status === 'completed') {
    return (
      <div style={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        padding: '13px 18px',
        background: 'linear-gradient(135deg, #22c55e08, #16a34a05)',
        border: '1px solid #22c55e35', borderRadius: 12,
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          <CheckCircle2 size={18} color="#22c55e" strokeWidth={2} />
          <div>
            <p style={{ fontSize: 14, fontWeight: 700, color: '#22c55e', margin: 0 }}>
              Search complete
            </p>
            <p style={{ fontSize: 12.5, color: 'var(--text3)', margin: 0 }}>
              {newJobs > 0
                ? `✨ ${newJobs} fresh job${newJobs !== 1 ? 's' : ''} added to your board · ${fetched} total fetched`
                : fetched > 0
                  ? `${fetched} jobs checked — your board is up to date`
                  : 'Search complete — check results below'}
            </p>
          </div>
        </div>
        <button onClick={onDismiss} style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text4)', padding: 4 }}>
          <X size={14} strokeWidth={1.75} />
        </button>
      </div>
    )
  }

  if (status === 'failed') {
    return (
      <div style={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        padding: '13px 18px', background: '#ef444408',
        border: '1px solid #ef444430', borderRadius: 12,
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          <AlertCircle size={18} color="#ef4444" strokeWidth={2} />
          <p style={{ fontSize: 14, fontWeight: 600, color: '#ef4444', margin: 0 }}>
            Search failed — please try again.
          </p>
        </div>
        <button onClick={onDismiss} style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text4)', padding: 4 }}>
          <X size={14} strokeWidth={1.75} />
        </button>
      </div>
    )
  }

  // Running / pending
  return (
    <div style={{
      padding: '18px 20px',
      background: 'var(--surface)',
      border: '1px solid var(--border)',
      borderRadius: 12,
    }}>
      {/* Header row */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          {/* Bouncing dots */}
          <span style={{ display: 'inline-flex', gap: 3 }}>
            {[0, 1, 2].map(i => (
              <span key={i} style={{
                width: 6, height: 6, borderRadius: '50%',
                background: 'var(--accent)',
                display: 'inline-block',
                animation: `ts-bounce 1.2s ease-in-out ${i * 0.18}s infinite`,
              }} />
            ))}
          </span>
          <span style={{ fontSize: 14, fontWeight: 700, color: 'var(--text)' }}>
            {isPending
              ? 'Warming up search engines…'
              : currentSource
                ? `Scanning ${SOURCES[currentSource]?.label ?? currentSource}…`
                : 'Searching across job boards…'}
          </span>
        </div>
        {run?.new_jobs != null && run.new_jobs > 0 && (
          <span style={{ fontSize: 12, color: 'var(--accent)', fontWeight: 600 }}>
            {run.new_jobs} new so far
          </span>
        )}
      </div>

      {/* Progress bar */}
      <div style={{ height: 4, background: 'var(--border)', borderRadius: 4, overflow: 'hidden', marginBottom: 14 }}>
        <div style={{
          height: '100%',
          width: isPending ? '5%' : `${Math.max(5, pct)}%`,
          background: 'linear-gradient(90deg, var(--accent), #60a5fa)',
          borderRadius: 4,
          transition: 'width 0.8s ease',
        }} />
      </div>

      {/* Per-source chips */}
      {sourceNames.length > 0 && (
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
          {sourceNames.map((key, idx) => (
            <SourceChip
              key={key}
              sourceKey={key}
              done={idx < sourcesDone}
              active={key === currentSource}
            />
          ))}
        </div>
      )}

      {/* Fallback chips when meta not yet populated */}
      {sourceNames.length === 0 && (
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
          {Object.entries(SOURCES).slice(0, 5).map(([key], idx) => (
            <SourceChip key={key} sourceKey={key} done={false} active={idx === 0 && !isPending} />
          ))}
        </div>
      )}

      <p style={{ fontSize: 11.5, color: 'var(--text4)', marginTop: 12, marginBottom: 0 }}>
        {isPending
          ? 'Connecting to live job sources…'
          : sourcesDone === sourcesTotal && sourcesTotal > 0
            ? `All ${sourcesTotal} sources scanned`
            : `${sourcesDone} of ${sourcesTotal} source${sourcesTotal !== 1 ? 's' : ''} done`
        }
        {fetched > 0 && <span style={{ color: 'var(--accent)' }}> · {fetched} jobs found so far</span>}
      </p>
    </div>
  )
}

// ── Source badge (for job cards) ──────────────────────────────────────────────

function SourceBadge({ source }: { source: string | null }) {
  const s = SOURCES[source ?? '']
  const color = s?.color ?? '#6b7280'
  const label = s?.label ?? (source ?? 'Manual')
  return (
    <span style={{
      fontSize: 10, fontWeight: 700, padding: '2px 6px', borderRadius: 3,
      background: color + '18', color,
      letterSpacing: '0.04em', textTransform: 'uppercase', whiteSpace: 'nowrap',
    }}>
      {label}
    </span>
  )
}

// ── Job card ──────────────────────────────────────────────────────────────────

function JobCard({ job }: { job: any }) {
  const opp = job.opportunities?.[0]
  const website = job.company?.website
    ? (job.company.website.startsWith('http') ? job.company.website : 'https://' + job.company.website)
    : null
  const applyUrl = job.application_url
    ? (job.application_url.startsWith('http') ? job.application_url : 'https://' + job.application_url)
    : null

  return (
    <div style={{
      background: 'var(--surface)', border: '1px solid var(--border)',
      borderRadius: 12, padding: '16px 20px',
      transition: 'border-color 0.12s, box-shadow 0.12s',
    }}
      onMouseEnter={e => {
        (e.currentTarget as HTMLDivElement).style.borderColor = 'var(--border2)'
        ;(e.currentTarget as HTMLDivElement).style.boxShadow = '0 2px 16px rgba(0,0,0,0.08)'
      }}
      onMouseLeave={e => {
        (e.currentTarget as HTMLDivElement).style.borderColor = 'var(--border)'
        ;(e.currentTarget as HTMLDivElement).style.boxShadow = 'none'
      }}
    >
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
        <div style={{ flex: 1, minWidth: 0 }}>
          {/* Title row */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap', marginBottom: 3 }}>
            <Link
              to={`/jobs/${job.id}`}
              style={{ fontSize: 14.5, fontWeight: 600, color: 'var(--text)', textDecoration: 'none' }}
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

          {/* Company + website */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
            <span style={{ fontSize: 13, color: 'var(--accent)', fontWeight: 500 }}>
              {job.company?.name ?? job.company_name ?? '—'}
            </span>
            {website && (
              <a href={website} target="_blank" rel="noopener noreferrer"
                style={{ display: 'inline-flex', alignItems: 'center', gap: 3, fontSize: 11.5, color: 'var(--text4)', textDecoration: 'none' }}
                onMouseEnter={e => (e.currentTarget.style.color = 'var(--accent)')}
                onMouseLeave={e => (e.currentTarget.style.color = 'var(--text4)')}
              >
                <Globe size={11} strokeWidth={1.75} /> Visit
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
                {formatCurrency(job.salary_min)}–{formatCurrency(job.salary_max)}
                {job.salary_currency && ` ${job.salary_currency}`}
              </span>
            )}
            {job.posted_at && (
              <span style={{ display: 'flex', alignItems: 'center', gap: 3 }}>
                <Clock size={11} strokeWidth={1.75} />{formatDate(job.posted_at)}
              </span>
            )}
          </div>

          {/* Skills */}
          {job.skills?.length > 0 && (
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5, marginTop: 8 }}>
              {job.skills.slice(0, 6).map((s: any) => (
                <span key={s.skill} style={{
                  fontSize: 11, padding: '2px 7px', borderRadius: 4,
                  background: 'var(--surface2)', color: 'var(--text3)',
                  border: '1px solid var(--border)',
                }}>
                  {s.skill}
                </span>
              ))}
              {job.skills.length > 6 && (
                <span style={{ fontSize: 11, color: 'var(--text4)' }}>+{job.skills.length - 6}</span>
              )}
            </div>
          )}
        </div>

        {/* Right column */}
        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 8, flexShrink: 0 }}>
          {opp && <MatchScore score={opp.match_score} classification={opp.match_classification} size="sm" />}
          {applyUrl && (
            <a href={applyUrl} target="_blank" rel="noopener noreferrer"
              style={{
                display: 'inline-flex', alignItems: 'center', gap: 5,
                fontSize: 12.5, fontWeight: 600, padding: '6px 12px', borderRadius: 7,
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

// ── Skeleton ──────────────────────────────────────────────────────────────────

function ResultSkeleton() {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
      {[1, 2, 3, 4].map(i => (
        <div key={i} style={{
          background: 'var(--surface)', border: '1px solid var(--border)',
          borderRadius: 12, padding: '16px 20px',
        }}>
          {[['60%', 14], ['35%', 12], ['75%', 11]].map(([w, h], j) => (
            <div key={j} style={{
              height: h as number, width: w as string, borderRadius: 6,
              background: 'var(--surface2)', marginBottom: j < 2 ? 8 : 0,
              animation: 'ts-pulse 1.5s ease-in-out infinite',
            }} />
          ))}
        </div>
      ))}
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function DiscoverPage() {
  const qc = useQueryClient()
  const [form, setForm]               = useState<SearchForm>(DEFAULT_FORM)
  const [showAdvanced, setAdv]        = useState(false)
  const [activeRunId, setActiveRunId] = useState<number | null>(null)
  const [resultsPage, setResultsPage] = useState(1)
  // Filters for the results panel
  const [resultSearch, setResultSearch]   = useState('')
  const [resultRemote, setResultRemote]   = useState(false)
  // Store the last submitted criteria so results query can use it
  const [lastCriteria, setLastCriteria]   = useState<{
    keywords: string[]; locations: string[]; remote_only: boolean; days_old: number; min_score: number
  } | null>(null)

  // Profile pre-fill
  const { data: profile } = useQuery({
    queryKey: ['profile'],
    queryFn:  () => api.get('/profile').then(r => r.data),
    staleTime: 60_000,
  })
  useEffect(() => {
    if (!profile || form.keywords) return
    const keywords  = [...(profile.preferred_roles ?? []), ...(profile.preferred_technologies ?? [])].slice(0, 5).join(', ')
    const locations = (profile.preferred_locations ?? []).join(', ')
    // Note: we intentionally do NOT pre-fill remote_only from the profile.
    // remote_only=true would filter out all on-site/hybrid Nigerian jobs.
    // The user can toggle it manually if they want remote-only results.
    setForm(f => ({ ...f, keywords, locations }))
  }, [profile])

  // Poll run status — 1 s while running/pending for live source progress, 3 s otherwise
  const { data: activeRun } = useQuery({
    queryKey: ['search-run', activeRunId],
    queryFn:  () => api.get(`/search/runs/${activeRunId}`).then(r => r.data),
    enabled:  !!activeRunId,
    refetchInterval: (query) => {
      const s = query.state.data?.status
      if (s === 'running')  return 1500  // fast poll for live per-source updates
      if (s === 'pending')  return 2000
      return false                        // stop polling when done/failed
    },
  })

  const runStatus = activeRun?.status

  // Load results once completed — scoped to the search criteria (location, keywords, remote)
  const { data: resultsData, isLoading: resultsLoading } = useQuery({
    queryKey: ['discover-results', activeRunId, resultsPage, resultSearch, resultRemote],
    queryFn:  () => {
      const params: Record<string, any> = {
        page:     resultsPage,
        per_page: 20,
        status:   'active',
      }

      // Scope results to exactly this search run — shows only what was fetched now,
      // not jobs from previous searches. Most precise scoping possible.
      if (activeRunId) {
        params.search_run_id = activeRunId
      }

      // Manual search box — user-typed filter on title/company
      if (resultSearch.trim()) {
        params.search = resultSearch.trim()
      }

      // Remote filter — only when user explicitly toggles it in the results panel
      if (resultRemote) {
        params.remote = true
      }

      return api.get('/jobs', { params }).then(r => r.data)
    },
    enabled: runStatus === 'completed',
  })

  const jobs: any[] = resultsData?.data ?? []
  const meta = {
    total:        resultsData?.total,
    current_page: resultsData?.current_page,
    last_page:    resultsData?.last_page,
  }

  const runSearch = useMutation({
    mutationFn: (payload: any) => api.post('/search/run', payload),
    onSuccess: (res) => {
      const run    = res.data.search_run
      const inline = res.data.inline as boolean

      setActiveRunId(run.id)
      setResultsPage(1)

      if (inline) {
        // sync mode: job already finished — load results immediately
        qc.invalidateQueries({ queryKey: ['discover-results', run.id] })
        const n = run.new_jobs ?? 0
        toast.success(n > 0 ? `${n} new job${n !== 1 ? 's' : ''} found!` : 'Search done — no new jobs this time.')
      } else {
        qc.invalidateQueries({ queryKey: ['search-run', run.id] })
      }
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Search failed'),
  })

  function handleSearch() {
    if (!form.keywords.trim()) return
    const criteria = {
      keywords:    form.keywords.split(',').map(s => s.trim()).filter(Boolean),
      locations:   form.locations.split(',').map(s => s.trim()).filter(Boolean),
      remote_only: form.remote_only,
      days_old:    form.days_old,
      min_score:   form.min_score,
    }
    setLastCriteria(criteria)
    setResultSearch('')
    setResultRemote(form.remote_only)
    runSearch.mutate(criteria)
  }

  const set       = (k: keyof SearchForm, v: any) => setForm(f => ({ ...f, [k]: v }))
  const isRunning = runSearch.isPending || runStatus === 'running' || runStatus === 'pending'
  const showProgress = (activeRunId || runSearch.isPending) && runStatus !== 'completed'

  return (
    <>
      <div style={{ maxWidth: 860, display: 'flex', flexDirection: 'column', gap: 20 }}>

        {/* Page header */}
        <div>
          <h1 style={{ fontSize: 22, fontWeight: 700, color: 'var(--text)', letterSpacing: '-0.03em', margin: '0 0 4px' }}>
            Discover Jobs
          </h1>
          <p style={{ fontSize: 13.5, color: 'var(--text3)', margin: 0 }}>
            Searches <strong style={{ color: 'var(--text2)' }}>{Object.keys(SOURCES).filter(k => !['greenhouse','lever','ashby'].includes(k)).length}+ job boards</strong> simultaneously — Google Jobs, Jobberman, Reed, Adzuna, RemoteOK and more.
          </p>
        </div>

        {/* Search form card */}
        <div style={{ background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 12, padding: '20px 22px' }}>

          {/* Keywords + Location */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14, marginBottom: 14 }}>
            <div>
              <label style={labelStyle}>
                <Briefcase size={11} style={{ display: 'inline', marginRight: 4 }} strokeWidth={2} />
                Keywords / Job Titles
              </label>
              <input
                style={inputStyle}
                placeholder="react developer, laravel, full stack…"
                value={form.keywords}
                onChange={e => set('keywords', e.target.value)}
                onKeyDown={e => e.key === 'Enter' && handleSearch()}
              />
              <p style={{ fontSize: 11, color: 'var(--text4)', marginTop: 4, marginBottom: 0 }}>
                Comma-separated. Pre-filled from your profile.
              </p>
            </div>
            <div>
              <label style={labelStyle}>
                <MapPin size={11} style={{ display: 'inline', marginRight: 4 }} strokeWidth={2} />
                Location
              </label>
              <input
                style={inputStyle}
                placeholder="Lagos, Remote, Worldwide…"
                value={form.locations}
                onChange={e => set('locations', e.target.value)}
                onKeyDown={e => e.key === 'Enter' && handleSearch()}
              />
              <p style={{ fontSize: 11, color: 'var(--text4)', marginTop: 4, marginBottom: 0 }}>
                Use "Remote" or "Worldwide" to search globally.
              </p>
            </div>
          </div>

          {/* Remote toggle */}
          <label style={{ display: 'inline-flex', alignItems: 'center', gap: 7, fontSize: 13.5, color: 'var(--text2)', cursor: 'pointer', marginBottom: 12, userSelect: 'none' }}>
            <input type="checkbox" checked={form.remote_only} onChange={e => set('remote_only', e.target.checked)} />
            Remote only
          </label>

          {/* Advanced toggle */}
          <div>
            <button
              onClick={() => setAdv(v => !v)}
              style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 12.5, color: 'var(--text3)', background: 'none', border: 'none', cursor: 'pointer', fontFamily: 'inherit', padding: 0 }}
            >
              {showAdvanced ? <ChevronUp size={14} strokeWidth={1.75} /> : <ChevronDown size={14} strokeWidth={1.75} />}
              Advanced options
            </button>
          </div>

          {showAdvanced && (
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14, paddingTop: 14, borderTop: '1px solid var(--border)', marginTop: 14, marginBottom: 16 }}>
              <div>
                <label style={labelStyle}>Max Age (days)</label>
                <select style={inputStyle} value={form.days_old} onChange={e => set('days_old', parseInt(e.target.value))}>
                  {[7, 14, 30, 60, 90].map(d => <option key={d} value={d}>Last {d} days</option>)}
                </select>
              </div>
              <div>
                <label style={labelStyle}>Minimum match score</label>
                <select style={inputStyle} value={form.min_score} onChange={e => set('min_score', parseInt(e.target.value))}>
                  {[0, 40, 50, 60, 70, 80].map(s => <option key={s} value={s}>{s === 0 ? 'Any match' : `${s}%+`}</option>)}
                </select>
              </div>
            </div>
          )}

          {/* Action row */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginTop: 18 }}>
            <button
              onClick={handleSearch}
              disabled={isRunning || !form.keywords.trim()}
              style={{
                display: 'flex', alignItems: 'center', gap: 8,
                padding: '10px 22px', background: 'var(--accent)',
                border: 'none', borderRadius: 8, color: '#fff',
                fontSize: 14, fontWeight: 600,
                cursor: (isRunning || !form.keywords.trim()) ? 'not-allowed' : 'pointer',
                fontFamily: 'inherit',
                opacity: (isRunning || !form.keywords.trim()) ? 0.65 : 1,
                transition: 'transform 0.1s, background 0.15s',
              }}
              onMouseEnter={e => { if (!isRunning) (e.currentTarget as HTMLButtonElement).style.transform = 'translateY(-1px)' }}
              onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.transform = 'none' }}
            >
              {isRunning
                ? <><span style={spinnerStyle} /> Searching…</>
                : <><Search size={15} strokeWidth={2} /> Search Jobs</>
              }
            </button>

            {profile && (
              <button
                onClick={() => {
                  const keywords  = [...(profile.preferred_roles ?? []), ...(profile.preferred_technologies ?? [])].slice(0, 5).join(', ')
                  const locations = (profile.preferred_locations ?? []).join(', ')
                  // Note: we intentionally do NOT pre-fill remote_only from the profile.
    // remote_only=true would filter out all on-site/hybrid Nigerian jobs.
    // The user can toggle it manually if they want remote-only results.
    setForm(f => ({ ...f, keywords, locations }))
                }}
                style={{ fontSize: 12.5, color: 'var(--accent)', background: 'none', border: 'none', cursor: 'pointer', textDecoration: 'underline', fontFamily: 'inherit' }}
              >
                Reset to profile defaults
              </button>
            )}
          </div>
        </div>

        {/* Live progress panel */}
        {showProgress && (
          <LiveProgress
            run={activeRun}
            isPending={runSearch.isPending && !activeRun}
            onDismiss={() => setActiveRunId(null)}
          />
        )}

        {/* Completed banner */}
        {runStatus === 'completed' && (
          <LiveProgress
            run={activeRun}
            isPending={false}
            onDismiss={() => { setActiveRunId(null); qc.removeQueries({ queryKey: ['discover-results'] }) }}
          />
        )}

        {/* Results */}
        {runStatus === 'completed' && (
          <div style={{ animation: 'ts-fade-in 0.4s ease' }}>
            {/* Results header + search bar */}
            <div style={{ marginBottom: 14 }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 }}>
              <p style={{ fontSize: 13.5, fontWeight: 600, color: 'var(--text)', margin: 0 }}>
                  {(meta.total ?? 0) === 0 ? (
                    <span style={{ color: 'var(--text3)' }}>No matching jobs</span>
                  ) : (
                    <>
                      <span style={{ color: 'var(--accent)' }}>{meta.total}</span>
                      {' '}job{(meta.total ?? 0) !== 1 ? 's' : ''} found
                      {resultRemote
                        ? <span style={{ color: 'var(--text4)', fontWeight: 400, fontSize: 12.5 }}> · remote & worldwide</span>
                        : lastCriteria?.locations?.length
                          ? <span style={{ color: 'var(--text4)', fontWeight: 400, fontSize: 12.5 }}> · {lastCriteria.locations.slice(0,2).join(', ')} + remote</span>
                          : null
                      }
                    </>
                  )}
                </p>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  <button
                    onClick={() => { setResultsPage(1); qc.invalidateQueries({ queryKey: ['discover-results'] }) }}
                    style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 12.5, color: 'var(--text3)', background: 'none', border: 'none', cursor: 'pointer', fontFamily: 'inherit' }}
                  >
                    <RefreshCw size={12} strokeWidth={2} /> Refresh
                  </button>
                  <Link to="/jobs" style={{ fontSize: 12.5, color: 'var(--accent)', textDecoration: 'none', fontWeight: 500 }}>
                    View all jobs →
                  </Link>
                </div>
              </div>

              {/* Search bar for results */}
              <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
                <div style={{ position: 'relative', flex: 1 }}>
                  <Search size={13} strokeWidth={1.75} style={{
                    position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)',
                    color: 'var(--text4)', pointerEvents: 'none',
                  }} />
                  <input
                    style={{
                      ...inputStyle,
                      paddingLeft: 32, paddingTop: 8, paddingBottom: 8,
                      fontSize: 13,
                    }}
                    placeholder="Filter results by title or company…"
                    value={resultSearch}
                    onChange={e => { setResultSearch(e.target.value); setResultsPage(1) }}
                  />
                </div>
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 13, color: 'var(--text2)', cursor: 'pointer', userSelect: 'none', whiteSpace: 'nowrap' }}>
                  <input
                    type="checkbox"
                    checked={resultRemote}
                    onChange={e => { setResultRemote(e.target.checked); setResultsPage(1) }}
                  />
                  Remote only
                </label>
                {(resultSearch || resultRemote !== form.remote_only) && (
                  <button
                    onClick={() => { setResultSearch(''); setResultRemote(form.remote_only); setResultsPage(1) }}
                    style={{ fontSize: 12, color: 'var(--text4)', background: 'none', border: 'none', cursor: 'pointer', fontFamily: 'inherit', display: 'flex', alignItems: 'center', gap: 4, whiteSpace: 'nowrap' }}
                  >
                    <X size={12} strokeWidth={2} /> Clear
                  </button>
                )}
              </div>
            </div>

            {resultsLoading ? <ResultSkeleton /> : jobs.length === 0 ? (
              <div style={{
                padding: '40px', textAlign: 'center',
                background: 'var(--surface)', border: '1px solid var(--border)',
                borderRadius: 12, color: 'var(--text3)', fontSize: 13.5,
              }}>
                No jobs matched your filters. Try broader keywords or a longer date range.
              </div>
            ) : (
              <>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                  {jobs.map((job: any, idx: number) => (
                    <div
                      key={job.id}
                      style={{
                        animation: `ts-fade-in 0.35s ease both`,
                        animationDelay: `${Math.min(idx * 0.04, 0.4)}s`,
                      }}
                    >
                      <JobCard job={job} />
                    </div>
                  ))}
                </div>
                {(meta.last_page ?? 1) > 1 && (
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: 20 }}>
                    <span style={{ fontSize: 12.5, color: 'var(--text4)' }}>
                      Page {meta.current_page} of {meta.last_page}
                    </span>
                    <div style={{ display: 'flex', gap: 6 }}>
                      {[
                        ['← Prev', resultsPage <= 1,                         () => setResultsPage(p => p - 1)],
                        ['Next →', resultsPage >= (meta.last_page ?? 1),     () => setResultsPage(p => p + 1)],
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
              </>
            )}
          </div>
        )}

        {/* Empty / pre-search state */}
        {!activeRunId && !runSearch.isPending && (
          <div style={{
            padding: '52px 24px', textAlign: 'center',
            background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 14,
            animation: 'ts-fade-in 0.5s ease',
          }}>
            <div style={{ marginBottom: 16 }}>
              <div style={{
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                width: 64, height: 64, borderRadius: '50%',
                background: 'linear-gradient(135deg, var(--accent)22, var(--accent)08)',
                border: '1px solid var(--accent)30',
              }}>
                <Zap size={28} strokeWidth={1.5} color="var(--accent)" />
              </div>
            </div>
            <p style={{ fontSize: 18, fontWeight: 700, color: 'var(--text)', marginBottom: 8, letterSpacing: '-0.03em' }}>
              Ready to find your next role
            </p>
            <p style={{ fontSize: 13.5, color: 'var(--text3)', maxWidth: 400, margin: '0 auto 6px', lineHeight: 1.6 }}>
              Your keywords and location are pre-filled from your profile.
            </p>
            <p style={{ fontSize: 13, color: 'var(--text4)', maxWidth: 360, margin: '0 auto 24px', lineHeight: 1.6 }}>
              Hit <strong style={{ color: 'var(--accent)' }}>Search Jobs</strong> to scan {Object.keys(SOURCES).filter(k => !['greenhouse','lever','ashby'].includes(k)).length}+ live job boards at once.
            </p>
            {/* Source chips in a flowing row */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 7, justifyContent: 'center', maxWidth: 520, margin: '0 auto' }}>
              {Object.entries(SOURCES).filter(([k]) => !['greenhouse','lever','ashby','jobicy_africa','careerjet_nigeria'].includes(k)).map(([key, s], i) => (
                <span
                  key={key}
                  style={{
                    fontSize: 10.5, fontWeight: 700, padding: '3px 10px', borderRadius: 20,
                    background: s.color + '18', color: s.color,
                    border: `1px solid ${s.color}30`,
                    letterSpacing: '0.04em', textTransform: 'uppercase',
                    animation: `ts-fade-in 0.4s ease both`,
                    animationDelay: `${0.1 + i * 0.05}s`,
                  }}
                >
                  {s.label}
                </span>
              ))}
            </div>
          </div>
        )}
      </div>

      <style>{`
        @keyframes ts-bounce {
          0%, 80%, 100% { transform: scale(0.4); opacity: 0.4; }
          40%            { transform: scale(1);   opacity: 1;   }
        }
        @keyframes ts-spin  { to { transform: rotate(360deg); } }
        @keyframes ts-pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
        @keyframes ts-fade-in {
          from { opacity: 0; transform: translateY(8px); }
          to   { opacity: 1; transform: translateY(0);   }
        }
        @keyframes ts-slide-up {
          from { opacity: 0; transform: translateY(16px); }
          to   { opacity: 1; transform: translateY(0); }
        }
      `}</style>
    </>
  )
}

// ── Shared styles ─────────────────────────────────────────────────────────────

const inputStyle: React.CSSProperties = {
  width: '100%', boxSizing: 'border-box',
  background: 'var(--surface2)', border: '1px solid var(--border2)',
  borderRadius: 8, padding: '9px 13px',
  fontSize: 13.5, color: 'var(--text)', fontFamily: 'inherit',
  transition: 'border-color 0.15s',
}

const labelStyle: React.CSSProperties = {
  display: 'block', fontSize: 11.5, fontWeight: 600,
  color: 'var(--text3)', marginBottom: 6,
  letterSpacing: '0.04em', textTransform: 'uppercase',
}

const spinnerStyle: React.CSSProperties = {
  width: 14, height: 14, display: 'inline-block', flexShrink: 0,
  borderRadius: '50%',
  border: '2px solid rgba(255,255,255,0.3)',
  borderTopColor: '#fff',
  animation: 'ts-spin 0.7s linear infinite',
}
