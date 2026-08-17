import { useState, useEffect, useRef } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Search, X, SlidersHorizontal, CheckCircle, Loader2 } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../../lib/api'
import Button from './Button'

interface Props {
  onClose?: () => void
}

type RunStatus = 'idle' | 'pending' | 'running' | 'completed' | 'failed'

export default function SearchPanel({ onClose }: Props) {
  const qc = useQueryClient()
  const [keywords,   setKeywords]   = useState('react, laravel, node.js, typescript')
  const [locations,  setLocations]  = useState('Remote, Lagos Nigeria, Worldwide')
  const [daysOld,    setDaysOld]    = useState(30)
  const [minScore,   setMinScore]   = useState(0)
  const [remoteOnly, setRemoteOnly] = useState(false)
  const [runStatus,  setRunStatus]  = useState<RunStatus>('idle')
  const [runStats,   setRunStats]   = useState<{ new_jobs?: number; new_companies?: number } | null>(null)
  const [progress,   setProgress]   = useState('')
  const pollRef = useRef<ReturnType<typeof setInterval> | null>(null)
  const runIdRef = useRef<number | null>(null)

  // Clean up on unmount
  useEffect(() => () => { if (pollRef.current) clearInterval(pollRef.current) }, [])

  const startPolling = (runId: number) => {
    runIdRef.current = runId
    setRunStatus('running')
    setProgress('Searching RemoteOK, Remotive, Arbeitnow, Adzuna…')

    pollRef.current = setInterval(async () => {
      try {
        const res = await api.get(`/search/runs/${runId}`)
        const run = res.data

        if (run.status === 'completed') {
          clearInterval(pollRef.current!)
          setRunStatus('completed')
          setRunStats({ new_jobs: run.new_jobs, new_companies: run.new_companies })
          setProgress(`Done — found ${run.new_jobs ?? 0} new jobs from ${run.new_companies ?? 0} companies`)
          // Refresh dashboard + opportunities
          qc.invalidateQueries({ queryKey: ['opportunities'] })
          qc.invalidateQueries({ queryKey: ['dashboard'] })
          qc.invalidateQueries({ queryKey: ['jobs'] })
          toast.success(`Found ${run.new_jobs ?? 0} new jobs!`)
        } else if (run.status === 'failed') {
          clearInterval(pollRef.current!)
          setRunStatus('failed')
          setProgress(run.error_message ?? 'Search failed')
          toast.error('Search failed — check logs for details')
        }
      } catch {
        // Silently continue polling on transient errors
      }
    }, 3000) // poll every 3s
  }

  const run = useMutation({
    mutationFn: () => api.post('/search/run', {
      keywords:    keywords.split(',').map(k => k.trim()).filter(Boolean),
      locations:   locations.split(',').map(l => l.trim()).filter(Boolean),
      days_old:    daysOld,
      min_score:   minScore,
      remote_only: remoteOnly,
    }),
    onSuccess: (res) => {
      const runId  = res.data.search_run?.id
      const sources = (res.data.sources ?? []).join(', ')
      setRunStatus('pending')
      setProgress(`Queued — searching ${sources}…`)
      if (runId) startPolling(runId)
    },
    onError: (e: any) => {
      setRunStatus('failed')
      toast.error(e.response?.data?.message ?? 'Search failed to start')
    },
  })

  const isSearching = runStatus === 'pending' || runStatus === 'running'
  const isDone      = runStatus === 'completed'

  const reset = () => {
    if (pollRef.current) clearInterval(pollRef.current)
    setRunStatus('idle')
    setRunStats(null)
    setProgress('')
  }

  return (
    <div style={{
      background: 'var(--surface)',
      border: '1px solid var(--border2)',
      borderRadius: 12, padding: 20,
    }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <SlidersHorizontal size={16} strokeWidth={1.75} style={{ color: 'var(--accent)' }} />
          <p style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)' }}>Run Job Discovery</p>
        </div>
        {onClose && (
          <button onClick={onClose} style={{ background: 'none', border: 'none', color: 'var(--text4)', cursor: 'pointer', padding: 4 }}>
            <X size={16} strokeWidth={1.75} />
          </button>
        )}
      </div>

      {/* Live status bar */}
      {runStatus !== 'idle' && (
        <div style={{
          display: 'flex', alignItems: 'center', gap: 10,
          padding: '10px 14px', borderRadius: 8, marginBottom: 14,
          background: isDone
            ? 'rgba(34,197,94,0.08)'
            : runStatus === 'failed'
            ? 'rgba(239,68,68,0.08)'
            : 'var(--accent-bg)',
          border: `1px solid ${isDone ? 'rgba(34,197,94,0.2)' : runStatus === 'failed' ? 'rgba(239,68,68,0.2)' : 'rgba(59,130,246,0.2)'}`,
        }}>
          {isSearching && (
            <Loader2 size={15} strokeWidth={2} style={{ color: 'var(--accent)', animation: 'ts-spin 0.7s linear infinite', flexShrink: 0 }} />
          )}
          {isDone && <CheckCircle size={15} strokeWidth={2} style={{ color: '#4ade80', flexShrink: 0 }} />}
          <p style={{ fontSize: 13, color: isDone ? '#4ade80' : runStatus === 'failed' ? '#f87171' : 'var(--accent-t)' }}>
            {progress}
          </p>
          {isDone && (
            <button onClick={reset} style={{ marginLeft: 'auto', background: 'none', border: 'none', color: 'var(--text4)', cursor: 'pointer', fontSize: 12, fontFamily: 'inherit' }}>
              Run again
            </button>
          )}
        </div>
      )}

      {/* Progress track (animated while searching) */}
      {isSearching && (
        <div style={{ height: 3, background: 'var(--surface2)', borderRadius: 100, marginBottom: 14, overflow: 'hidden' }}>
          <div style={{
            height: '100%', borderRadius: 100,
            background: 'linear-gradient(90deg, var(--accent), #a78bfa)',
            animation: 'ts-progress 2s ease-in-out infinite',
          }} />
        </div>
      )}

      {/* Search form — show when idle or after completion */}
      {(runStatus === 'idle' || isDone) && (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 12 }}>
          <div style={{ gridColumn: '1/-1' }}>
            <label style={{ fontSize: 12.5, fontWeight: 500, color: 'var(--text2)', display: 'block', marginBottom: 5 }}>
              Keywords (comma separated)
            </label>
            <input
              value={keywords}
              onChange={e => setKeywords(e.target.value)}
              placeholder="react, laravel, full stack, node.js"
              style={{
                width: '100%', background: 'var(--surface2)', border: '1px solid var(--border2)',
                borderRadius: 8, padding: '9px 12px', fontSize: 13.5, color: 'var(--text)',
                fontFamily: 'inherit', outline: 'none',
              }}
            />
          </div>

          <div style={{ gridColumn: '1/-1' }}>
            <label style={{ fontSize: 12.5, fontWeight: 500, color: 'var(--text2)', display: 'block', marginBottom: 5 }}>
              Locations (comma separated)
            </label>
            <input
              value={locations}
              onChange={e => setLocations(e.target.value)}
              placeholder="Remote, Lagos Nigeria, London, Worldwide"
              style={{
                width: '100%', background: 'var(--surface2)', border: '1px solid var(--border2)',
                borderRadius: 8, padding: '9px 12px', fontSize: 13.5, color: 'var(--text)',
                fontFamily: 'inherit', outline: 'none',
              }}
            />
          </div>

          <div>
            <label style={{ fontSize: 12.5, fontWeight: 500, color: 'var(--text2)', display: 'block', marginBottom: 5 }}>
              Posted within
            </label>
            <select value={daysOld} onChange={e => setDaysOld(Number(e.target.value))}
              style={{
                width: '100%', background: 'var(--surface2)', border: '1px solid var(--border2)',
                borderRadius: 8, padding: '9px 12px', fontSize: 13.5, color: 'var(--text)',
                fontFamily: 'inherit', cursor: 'pointer',
              }}>
              <option value={7}>Last 7 days</option>
              <option value={14}>Last 14 days</option>
              <option value={30}>Last 30 days</option>
              <option value={60}>Last 60 days</option>
              <option value={90}>Last 90 days</option>
            </select>
          </div>

          <div>
            <label style={{ fontSize: 12.5, fontWeight: 500, color: 'var(--text2)', display: 'block', marginBottom: 5 }}>
              Min match score (%)
            </label>
            <input type="number" min={0} max={100} value={minScore}
              onChange={e => setMinScore(Number(e.target.value))}
              style={{
                width: '100%', background: 'var(--surface2)', border: '1px solid var(--border2)',
                borderRadius: 8, padding: '9px 12px', fontSize: 13.5, color: 'var(--text)',
                fontFamily: 'inherit', outline: 'none',
              }}
            />
          </div>

          <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', gridColumn: '1/-1' }}>
            <div onClick={() => setRemoteOnly(v => !v)} style={{
              width: 36, height: 20, borderRadius: 100, cursor: 'pointer',
              background: remoteOnly ? 'var(--accent)' : 'var(--surface3)',
              border: '1px solid var(--border2)', position: 'relative', transition: 'background .15s',
            }}>
              <div style={{
                position: 'absolute', top: 2, left: remoteOnly ? 17 : 2,
                width: 14, height: 14, borderRadius: '50%', background: '#fff',
                boxShadow: '0 1px 3px rgba(0,0,0,0.2)', transition: 'left .15s',
              }} />
            </div>
            <span style={{ fontSize: 13.5, fontWeight: 500, color: 'var(--text)' }}>Remote only</span>
          </label>

          <div style={{ gridColumn: '1/-1', display: 'flex', alignItems: 'center', gap: 10 }}>
            <Button
              loading={run.isPending}
              icon={<Search size={14} strokeWidth={2} />}
              onClick={() => run.mutate()}
            >
              Run Search
            </Button>
            <p style={{ fontSize: 12, color: 'var(--text4)' }}>
              Searches RemoteOK, Remotive, Arbeitnow, Adzuna — runs in background
            </p>
          </div>
        </div>
      )}

      <style>{`
        @keyframes ts-spin { to { transform: rotate(360deg); } }
        @keyframes ts-progress {
          0%   { width: 10%; margin-left: 0; }
          50%  { width: 60%; margin-left: 20%; }
          100% { width: 10%; margin-left: 90%; }
        }
      `}</style>
    </div>
  )
}
