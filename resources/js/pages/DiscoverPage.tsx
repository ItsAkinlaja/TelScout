import { useState, useEffect, useRef } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  Search, Zap, MapPin, Clock, Briefcase,
  ChevronDown, ChevronUp, ExternalLink, RefreshCw, X,
  CheckCircle2, AlertCircle, Globe, DollarSign,
} from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import MatchScore from '../components/ui/MatchScore'
import { formatCurrency, formatDate } from '../lib/utils'

// ── Types ─────────────────────────────────────────────────────────────────────

interface SearchForm {
  keywords:   string
  locations:  string
  remote_only: boolean
  days_old:   number
  min_score:  number
}

const DEFAULT_FORM: SearchForm = {
  keywords:    '',
  locations:   '',
  remote_only: false,
  days_old:    30,
  min_score:   0,
}

// ── Source config ─────────────────────────────────────────────────────────────

const SOURCES = [
  { key: 'remoteok',  label: 'RemoteOK',  color: '#28a745' },
  { key: 'remotive',  label: 'Remotive',  color: '#ef4444' },
  { key: 'arbeitnow', label: 'Arbeitnow', color: '#f59e0b' },
  { key: 'adzuna',    label: 'Adzuna',    color: '#e55a1d' },
  { key: 'the_muse',  label: 'The Muse',  color: '#e91e8c' },
  { key: 'jsearch',   label: 'JSearch',   color: '#1a73e8' },
  { key: 'reed',      label: 'Reed',      color: '#cc0000' },
  { key: 'greenhouse',label: 'Greenhouse',color: '#24a148' },
  { key: 'lever',     label: 'Lever',     color: '#0066cc' },
  { key: 'ashby',     label: 'Ashby',     color: '#7c3aed' },
]

function SourceBadge({ source }: { source: string | null }) {
  const s = SOURCES.find(x => x.key === source)
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

// ── Animated progress UI while search runs ────────────────────────────────────

const STEPS = [
  'Queuing search…',
  'Fetching from RemoteOK & Remotive…',
  'Fetching from Arbeitnow & Adzuna…',
  'Fetching from The Muse & more…',
  'Processing & scoring results…',
  'Almost done…',
]

function SearchProgress({ runStatus, isQueued, elapsedSeconds }: {
  runStatus: string | undefined
  isQueued: boolean    // true = queued for cron (5-min window)
  elapsedSeconds: number
}) {
  const [stepIdx, setStepIdx] = useState(0)

  // Cycle steps every 4 seconds while running
  useEffect(() => {
    if (runStatus !== 'running' && runStatus !== 'pending') return
    const t = setInterval(() => setStepIdx(i => (i + 1) % STEPS.length), 4000)
    return () => clearInterval(t)
  }, [runStatus])

  if (runStatus === 'completed') {
    return (
      <div style={{
        display: 'flex', alignItems: 'center', gap: 10,
        padding: '12px 16px', background: '#22c55e08',
        border: '1px solid #22c55e30', borderRadius: 10,
      }}>
        <CheckCircle2 size={16} color="#22c55e" strokeWidth={2} />
        <span style={{ fontSize: 13.5, color: '#22c55e', fontWeight: 600 }}>Search complete — scroll down for results</span>
      </div>
    )
  }

  if (runStatus === 'failed') {
    return (
      <div style={{
        display: 'flex', alignItems: 'center', gap: 10,
        padding: '12px 16px', background: '#ef444408',
        border: '1px solid #ef444430', borderRadius: 10,
      }}>
        <AlertCircle size={16} color="#ef4444" strokeWidth={2} />
        <span style={{ fontSize: 13.5, color: '#ef4444', fontWeight: 500 }}>Search failed. Please try again.</span>
      </div>
    )
  }

  // After 60 seconds still pending/running → cron mode message
  if (elapsedSeconds > 60 || isQueued) {
    return (
      <div style={{
        padding: '16px 20px', background: 'var(--surface)',
        border: '1px solid var(--border)', borderRadius: 12,
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 8 }}>
          {/* Pulsing blue dot */}
          <span style={{
            width: 10, height: 10, borderRadius: '50%',
            background: 'var(--accent)',
            display: 'inline-block',
            animation: 'ts-dot-pulse 1.4s ease-in-out infinite',
            flexShrink: 0,
          }} />
          <span style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)' }}>
            Search queued for background processing
          </span>
        </div>
        <p style={{ fontSize: 13, color: 'var(--text3)', margin: 0, lineHeight: 1.6 }}>
          Your search is running in the background via our scheduler. Results will appear here
          automatically within <strong style={{ color: 'var(--text2)' }}>5 minutes</strong>.
          You can safely leave this page — come back and the results will be ready.
        </p>
        <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 8, marginBottom: 0 }}>
          Elapsed: {Math.floor(elapsedSeconds / 60)}m {elapsedSeconds % 60}s
        </p>
      </div>
    )
  }

  // Normal animated progress (first 60 seconds)
  return (
    <div style={{
      padding: '16px 20px', background: 'var(--surface)',
      border: '1px solid var(--border)', borderRadius: 12,
    }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 12 }}>
        {/* Spinning dots */}
        <span style={{ display: 'inline-flex', gap: 4 }}>
          {[0, 1, 2].map(i => (
            <span key={i} style={{
              width: 6, height: 6, borderRadius: '50%',
              background: 'var(--accent)',
              animation: `ts-bounce 1.2s ease-in-out ${i * 0.2}s infinite`,
              display: 'inline-block',
            }} />
          ))}
        </span>
        <span style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)' }}>
          {STEPS[stepIdx]}
        </span>
      </div>

      {/* Step progress bar */}
      <div style={{ height: 3, background: 'var(--border)', borderRadius: 2, overflow: 'hidden', marginBottom: 10 }}>
        <div style={{
          height: '100%',
          width: `${Math.min(100, (elapsedSeconds / 60) * 100)}%`,
          background: 'var(--accent)',
          borderRadius: 2,
          transition: 'width 1s linear',
        }} />
      </div>

      {/* Source chips */}
      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5 }}>
        {SOURCES.slice(0, 7).map((s, i) => (
          <span key={s.key} style={{
            fontSize: 10, fontWeight: 600, padding: '2px 7px', borderRadius: 4,
            background: s.color + '18', color: s.color,
            letterSpacing: '0.03em', textTransform: 'uppercase',
            opacity: elapsedSeconds > (i + 1) * 8 ? 1 : 0.4,
            transition: 'opacity 0.6s ease',
          }}>
            {s.label}
          </span>
        ))}
      </div>

      <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 10, marginBottom: 0 }}>
        Elapsed: {elapsedSeconds}s · Polling for results every 3 seconds
      </p>
    </div>
  )
}

// ── Job card ──────────────────────────────────────────────────────────────────

function JobCard({ job }: { job: any }) {
  const opp = job.opportunities?.[0]

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
          {/* Title */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap', marginBottom: 3 }}>
            <Link to={`/jobs/${job.id}`} style={{ fontSize: 14.5, fontWeight: 600, color: 'var(--text)', textDecoration: 'none' }}
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

          {/* Company */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
            <span style={{ fontSize: 13, color: 'var(--accent)', fontWeight: 500 }}>
              {job.company?.name ?? job.company_name ?? '—'}
            </span>
            {job.company?.website && (
              <a
                href={job.company.website.startsWith('http') ? job.company.website : 'https://' + job.company.website}
                target="_blank" rel="noopener noreferrer"
                style={{ display: 'inline-flex', alignItems: 'center', gap: 3, fontSize: 11.5, color: 'var(--text4)', textDecoration: 'none' }}
                onMouseEnter={e => (e.currentTarget.style.color = 'var(--accent)')}
                onMouseLeave={e => (e.currentTarget.style.color = 'var(--text4)')}
              >
                <Globe size={11} strokeWidth={1.75} />
                Visit
                <ExternalLink size={9} strokeWidth={2} style={{ opacity: 0.6 }} />
              </a>
            )}
          </div>

          {/* Meta */}
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

        {/* Right */}
        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 8, flexShrink: 0 }}>
          {opp && <MatchScore score={opp.match_score} classification={opp.match_classification} size="sm" />}
          {job.application_url && (
            <a href={job.application_url.startsWith('http') ? job.application_url : 'https://' + job.application_url}
              target="_blank" rel="noopener noreferrer"
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

// ── Skeleton cards ────────────────────────────────────────────────────────────

function ResultSkeleton() {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
      {[1, 2, 3].map(i => (
        <div key={i} style={{
          background: 'var(--surface)', border: '1px solid var(--border)',
          borderRadius: 12, padding: '16px 20px',
        }}>
          {[['60%', 14], ['35%', 12], ['80%', 11]].map(([w, h], j) => (
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
  const [form, setForm]              = useState<SearchForm>(DEFAULT_FORM)
  const [showAdvanced, setAdv]       = useState(false)
  const [activeRunId, setActiveRunId] = useState<number | null>(null)
  const [startedAt, setStartedAt]    = useState<number | null>(null)
  const [elapsed, setElapsed]        = useState(0)
  const [resultsPage, setResultsPage] = useState(1)

  // Elapsed timer
  useEffect(() => {
    if (!startedAt) { setElapsed(0); return }
    const t = setInterval(() => setElapsed(Math.floor((Date.now() - startedAt) / 1000)), 1000)
    return () => clearInterval(t)
  }, [startedAt])

  // Profile pre-fill
  const { data: profile } = useQuery({
    queryKey: ['profile'],
    queryFn: () => api.get('/profile').then(r => r.data),
    staleTime: 60_000,
  })
  useEffect(() => {
    if (!profile || form.keywords) return
    const keywords  = [...(profile.preferred_roles ?? []), ...(profile.preferred_technologies ?? [])].slice(0, 5).join(', ')
    const locations = (profile.preferred_locations ?? []).join(', ')
    setForm(f => ({ ...f, keywords, locations, remote_only: profile.work_preference === 'remote' }))
  }, [profile])

  // Poll active run — every 3 seconds while pending/running
  const { data: activeRun } = useQuery({
    queryKey: ['search-run', activeRunId],
    queryFn: () => api.get(`/search/runs/${activeRunId}`).then(r => r.data),
    enabled: !!activeRunId,
    refetchInterval: (query) => {
      const status = query.state.data?.status
      return (status === 'running' || status === 'pending') ? 3000 : false
    },
  })

  // Stop timer when run completes
  const runStatus = activeRun?.status
  useEffect(() => {
    if (runStatus === 'completed' || runStatus === 'failed') setStartedAt(null)
  }, [runStatus])

  // Load results when completed
  const { data: resultsData, isLoading: resultsLoading } = useQuery({
    queryKey: ['discover-results', activeRunId, resultsPage],
    queryFn: () => api.get('/jobs', { params: { page: resultsPage, per_page: 20 } }).then(r => r.data),
    enabled: runStatus === 'completed',
  })

  const jobs: any[] = resultsData?.data ?? []
  const meta = { total: resultsData?.total, current_page: resultsData?.current_page, last_page: resultsData?.last_page }

  const runSearch = useMutation({
    mutationFn: (payload: any) => api.post('/search/run', payload),
    onSuccess: (res) => {
      const run = res.data.search_run
      setActiveRunId(run.id)
      setStartedAt(Date.now())
      setResultsPage(1)
      qc.invalidateQueries({ queryKey: ['search-run', run.id] })
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Search failed'),
  })

  function handleSearch() {
    if (!form.keywords.trim()) return
    runSearch.mutate({
      keywords:    form.keywords.split(',').map(s => s.trim()).filter(Boolean),
      locations:   form.locations.split(',').map(s => s.trim()).filter(Boolean),
      remote_only: form.remote_only,
      days_old:    form.days_old,
      min_score:   form.min_score,
    })
  }

  const set = (k: keyof SearchForm, v: any) => setForm(f => ({ ...f, [k]: v }))
  const isRunning = runSearch.isPending || runStatus === 'running' || runStatus === 'pending'

  return (
    <>
      <div style={{ maxWidth: 860, display: 'flex', flexDirection: 'column', gap: 20 }}>

        {/* Header */}
        <div>
          <h1 style={{ fontSize: 22, fontWeight: 700, color: 'var(--text)', letterSpacing: '-0.03em', margin: '0 0 4px' }}>
            Discover Jobs
          </h1>
          <p style={{ fontSize: 13.5, color: 'var(--text3)', margin: 0 }}>
            Search across RemoteOK, Remotive, Arbeitnow, Adzuna, The Muse and more — all at once.
          </p>
        </div>

        {/* Search form */}
        <div style={{
          background: 'var(--surface)', border: '1px solid var(--border)',
          borderRadius: 12, padding: '20px 22px',
        }}>
          {/* Primary row */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14, marginBottom: 14 }}>
            <div>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: 'var(--text3)', marginBottom: 6, letterSpacing: '0.02em', textTransform: 'uppercase' }}>
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
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: 'var(--text3)', marginBottom: 6, letterSpacing: '0.02em', textTransform: 'uppercase' }}>
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
                Comma-separated. Use "Remote" for remote-only.
              </p>
            </div>
          </div>

          {/* Remote toggle */}
          <label style={{ display: 'inline-flex', alignItems: 'center', gap: 7, fontSize: 13.5, color: 'var(--text2)', cursor: 'pointer', marginBottom: 14, userSelect: 'none' }}>
            <input type="checkbox" checked={form.remote_only} onChange={e => set('remote_only', e.target.checked)} />
            Remote only
          </label>

          {/* Advanced toggle */}
          <div style={{ marginBottom: showAdvanced ? 14 : 0 }}>
            <button
              onClick={() => setAdv(v => !v)}
              style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 12.5, color: 'var(--text3)', background: 'none', border: 'none', cursor: 'pointer', fontFamily: 'inherit', padding: 0 }}
            >
              {showAdvanced ? <ChevronUp size={14} strokeWidth={1.75} /> : <ChevronDown size={14} strokeWidth={1.75} />}
              Advanced options
            </button>
          </div>

          {showAdvanced && (
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14, paddingTop: 14, borderTop: '1px solid var(--border)', marginBottom: 16 }}>
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

          {/* Search button */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginTop: showAdvanced ? 0 : 14 }}>
            <button
              onClick={handleSearch}
              disabled={isRunning || !form.keywords.trim()}
              style={{
                display: 'flex', alignItems: 'center', gap: 8,
                padding: '10px 22px', background: 'var(--accent)',
                border: 'none', borderRadius: 8, color: '#fff',
                fontSize: 14, fontWeight: 600, cursor: isRunning ? 'not-allowed' : 'pointer',
                fontFamily: 'inherit', opacity: (isRunning || !form.keywords.trim()) ? 0.65 : 1,
                transition: 'background 0.15s, transform 0.1s',
              }}
              onMouseEnter={e => { if (!isRunning) (e.currentTarget as HTMLButtonElement).style.transform = 'translateY(-1px)' }}
              onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.transform = 'none' }}
            >
              {runSearch.isPending
                ? <><span style={spinnerStyle} /> Queuing…</>
                : isRunning
                  ? <><span style={spinnerStyle} /> Searching…</>
                  : <><Search size={15} strokeWidth={2} /> Search Jobs</>
              }
            </button>

            {/* Reset */}
            {profile && (
              <button
                onClick={() => {
                  const keywords  = [...(profile?.preferred_roles ?? []), ...(profile?.preferred_technologies ?? [])].slice(0, 5).join(', ')
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

        {/* Search progress */}
        {(activeRunId || runSearch.isPending) && runStatus !== 'completed' && (
          <div style={{ position: 'relative' }}>
            <SearchProgress
              runStatus={runSearch.isPending ? 'pending' : runStatus}
              isQueued={false}
              elapsedSeconds={elapsed}
            />
            {!runSearch.isPending && runStatus !== 'failed' && (
              <button
                onClick={() => { setActiveRunId(null); setStartedAt(null) }}
                style={{
                  position: 'absolute', top: 12, right: 12,
                  background: 'none', border: 'none', cursor: 'pointer',
                  color: 'var(--text4)', padding: 2,
                }}
              >
                <X size={14} strokeWidth={1.75} />
              </button>
            )}
          </div>
        )}

        {/* Completed status bar */}
        {runStatus === 'completed' && (
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <SearchProgress runStatus="completed" isQueued={false} elapsedSeconds={elapsed} />
            <div style={{ display: 'flex', gap: 10, marginLeft: 12 }}>
              <button
                onClick={() => qc.invalidateQueries({ queryKey: ['discover-results'] })}
                style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 12.5, color: 'var(--accent)', background: 'none', border: 'none', cursor: 'pointer', fontFamily: 'inherit' }}
              >
                <RefreshCw size={13} strokeWidth={2} /> Refresh
              </button>
              <button
                onClick={() => { setActiveRunId(null); setStartedAt(null) }}
                style={{ color: 'var(--text4)', background: 'none', border: 'none', cursor: 'pointer', padding: 2 }}
              >
                <X size={14} strokeWidth={1.75} />
              </button>
            </div>
          </div>
        )}

        {/* Results */}
        {runStatus === 'completed' && (
          <div style={{ animation: 'ts-fade-in 0.4s ease' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 }}>
              <p style={{ fontSize: 13.5, fontWeight: 600, color: 'var(--text)', margin: 0 }}>
                {activeRun?.new_jobs ?? 0} new jobs found
                {activeRun?.results_count != null && ` · ${activeRun.results_count} total fetched`}
              </p>
              <Link to="/jobs" style={{ fontSize: 12.5, color: 'var(--accent)', textDecoration: 'none', fontWeight: 500 }}>
                View all jobs →
              </Link>
            </div>

            {resultsLoading ? <ResultSkeleton /> : jobs.length === 0 ? (
              <div style={{
                padding: '40px', textAlign: 'center',
                background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 12,
                color: 'var(--text3)', fontSize: 13.5,
              }}>
                No jobs matched your criteria. Try broader keywords or a longer time range.
              </div>
            ) : (
              <>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                  {jobs.map((job: any) => <JobCard key={job.id} job={job} />)}
                </div>
                {(meta.last_page ?? 1) > 1 && (
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: 20 }}>
                    <span style={{ fontSize: 12.5, color: 'var(--text4)' }}>Page {meta.current_page} of {meta.last_page}</span>
                    <div style={{ display: 'flex', gap: 6 }}>
                      {[['← Prev', resultsPage <= 1, () => setResultsPage(p => p - 1)],
                        ['Next →', resultsPage >= (meta.last_page ?? 1), () => setResultsPage(p => p + 1)],
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

        {/* Empty state */}
        {!activeRunId && !runSearch.isPending && (
          <div style={{
            padding: '60px 0', textAlign: 'center',
            background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 14,
          }}>
            <div style={{ marginBottom: 16, color: 'var(--text4)' }}>
              <Zap size={36} strokeWidth={1.25} />
            </div>
            <p style={{ fontSize: 15, fontWeight: 600, color: 'var(--text2)', marginBottom: 8 }}>
              Ready to discover jobs
            </p>
            <p style={{ fontSize: 13.5, color: 'var(--text3)', maxWidth: 380, margin: '0 auto 20px' }}>
              Your keywords and locations are pre-filled from your profile. Hit <strong>Search Jobs</strong> to start.
            </p>
            {/* Source chips */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, justifyContent: 'center', maxWidth: 480, margin: '0 auto' }}>
              {SOURCES.slice(0, 7).map(s => (
                <span key={s.key} style={{
                  fontSize: 10, fontWeight: 700, padding: '3px 8px', borderRadius: 4,
                  background: s.color + '18', color: s.color,
                  letterSpacing: '0.04em', textTransform: 'uppercase',
                }}>
                  {s.label}
                </span>
              ))}
            </div>
          </div>
        )}
      </div>

      <style>{`
        @keyframes ts-bounce {
          0%, 80%, 100% { transform: scale(0); opacity: 0.5; }
          40% { transform: scale(1); opacity: 1; }
        }
        @keyframes ts-dot-pulse {
          0%, 100% { opacity: 1; transform: scale(1); }
          50% { opacity: 0.4; transform: scale(0.7); }
        }
        @keyframes ts-pulse {
          0%, 100% { opacity: 1; } 50% { opacity: 0.4; }
        }
        @keyframes ts-spin {
          to { transform: rotate(360deg); }
        }
        @keyframes ts-fade-in {
          from { opacity: 0; transform: translateY(8px); }
          to   { opacity: 1; transform: translateY(0); }
        }
      `}</style>
    </>
  )
}

const inputStyle: React.CSSProperties = {
  width: '100%', boxSizing: 'border-box',
  background: 'var(--surface2)', border: '1px solid var(--border2)',
  borderRadius: 8, padding: '9px 13px',
  fontSize: 13.5, color: 'var(--text)', fontFamily: 'inherit',
  transition: 'border-color 0.15s',
}

const labelStyle: React.CSSProperties = {
  display: 'block', fontSize: 12, fontWeight: 600,
  color: 'var(--text3)', marginBottom: 6,
  letterSpacing: '0.02em', textTransform: 'uppercase',
}

const spinnerStyle: React.CSSProperties = {
  width: 14, height: 14, display: 'inline-block',
  borderRadius: '50%',
  border: '2px solid rgba(255,255,255,0.3)',
  borderTopColor: '#fff',
  animation: 'ts-spin 0.7s linear infinite',
}
