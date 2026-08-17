import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Zap, Search } from 'lucide-react'
import api from '../lib/api'
import MatchScore from '../components/ui/MatchScore'
import StatusBadge from '../components/ui/StatusBadge'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import EmptyState from '../components/ui/EmptyState'
import { formatDate } from '../lib/utils'

export default function OpportunitiesPage() {
  const [search, setSearch]         = useState('')
  const [classification, setClass]  = useState('')
  const [status, setStatus]         = useState('')
  const [minScore, setMinScore]     = useState('')
  const [page, setPage]             = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['opportunities', search, classification, status, minScore, page],
    queryFn: () => api.get('/opportunities', {
      params: { search, classification, status, min_score: minScore, page, per_page: 25 },
    }).then(r => r.data),
  })

  const opps = data?.data ?? []
  const meta = { total: data?.total, current_page: data?.current_page, last_page: data?.last_page }

  return (
    <>
      <div className="ts-page">
        {/* Filters */}
        <div className="ts-filters">
          <div className="ts-search-wrap">
            <Search size={15} strokeWidth={1.75} className="ts-search-icon" />
            <input
              className="ts-input ts-search-input"
              placeholder="Search jobs or companies…"
              value={search}
              onChange={e => { setSearch(e.target.value); setPage(1) }}
            />
          </div>
          <select className="ts-select" value={classification}
            onChange={e => { setClass(e.target.value); setPage(1) }}>
            <option value="">All scores</option>
            {['excellent','strong','good','possible','low'].map(c => (
              <option key={c} value={c}>{c.charAt(0).toUpperCase() + c.slice(1)}</option>
            ))}
          </select>
          <select className="ts-select" value={status}
            onChange={e => { setStatus(e.target.value); setPage(1) }}>
            <option value="">All statuses</option>
            {['discovered','shortlisted','contacted','replied','interview','offer','rejected'].map(s => (
              <option key={s} value={s}>{s.replace(/_/g,' ')}</option>
            ))}
          </select>
          <input className="ts-input ts-min-score" type="number" placeholder="Min %" value={minScore}
            onChange={e => { setMinScore(e.target.value); setPage(1) }} />
          <span className="ts-count">{meta.total ?? 0} results</span>
        </div>

        {isLoading ? <LoadingSpinner /> : opps.length === 0 ? (
          <EmptyState icon={Zap} title="No opportunities yet"
            description="Run a job search or add companies manually to get started."
            action={<Link to="/jobs" className="ts-btn-primary">Add a Job</Link>}
          />
        ) : (
          <div className="ts-table-wrap">
            <table className="ts-table">
              <thead>
                <tr>
                  {['Role','Company','Location','Score','Status','Discovered'].map(h => (
                    <th key={h} className="ts-th">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {opps.map((opp: any) => (
                  <tr key={opp.id} className="ts-tr">
                    <td className="ts-td">
                      <Link to={`/opportunities/${opp.id}`} className="ts-row-link">
                        {opp.job?.title ?? '—'}
                      </Link>
                    </td>
                    <td className="ts-td ts-td-muted">{opp.company?.name ?? '—'}</td>
                    <td className="ts-td ts-td-muted">{opp.job?.location ?? (opp.job?.is_remote ? 'Remote' : '—')}</td>
                    <td className="ts-td"><MatchScore score={opp.match_score} classification={opp.match_classification} /></td>
                    <td className="ts-td"><StatusBadge status={opp.status} /></td>
                    <td className="ts-td ts-td-dim">{formatDate(opp.discovered_at)}</td>
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
      <PageStyles />
    </>
  )
}

function PageStyles() {
  return (
    <style>{`
      .ts-page { display: flex; flex-direction: column; gap: 16px; }

      /* Filters */
      .ts-filters {
        display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
      }
      .ts-search-wrap { position: relative; flex: 1; min-width: 200px; }
      .ts-search-icon {
        position: absolute; left: 10px; top: 50%;
        transform: translateY(-50%); color: var(--text4); pointer-events: none;
      }
      .ts-search-input { padding-left: 32px !important; }
      .ts-input {
        background: var(--surface); border: 1px solid var(--border2);
        border-radius: 8px; padding: 8px 12px;
        font-size: 13.5px; color: var(--text);
        transition: border-color 0.15s; width: 100%;
        font-family: inherit;
      }
      .ts-input::placeholder { color: var(--text5); }
      .ts-input:focus { outline: none; border-color: rgba(59,130,246,0.5); }
      .ts-select {
        background: var(--surface); border: 1px solid var(--border2);
        border-radius: 8px; padding: 8px 12px;
        font-size: 13.5px; color: var(--text2);
        cursor: pointer; font-family: inherit;
      }
      .ts-select:focus { outline: none; border-color: rgba(59,130,246,0.5); }
      .ts-min-score { width: 88px; }
      .ts-count { font-size: 12.5px; color: var(--text4); white-space: nowrap; margin-left: 4px; }

      /* Table */
      .ts-table-wrap {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 10px; overflow: hidden;
        transition: background 0.2s, border-color 0.2s;
      }
      .ts-table { width: 100%; border-collapse: collapse; }
      .ts-th {
        padding: 10px 14px; text-align: left;
        font-size: 11.5px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.04em;
        color: var(--text4); background: var(--surface2);
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
      }
      .ts-tr { border-bottom: 1px solid var(--border); transition: background 0.1s; }
      .ts-tr:last-child { border-bottom: none; }
      .ts-tr:hover { background: var(--surface2); }
      .ts-td { padding: 11px 14px; vertical-align: middle; }
      .ts-td-muted { font-size: 13px; color: var(--text2); }
      .ts-td-dim   { font-size: 12px; color: var(--text4); }
      .ts-row-link {
        font-size: 13.5px; font-weight: 500; color: var(--text);
        text-decoration: none; transition: color 0.12s;
      }
      .ts-row-link:hover { color: var(--accent-t); }

      /* Pagination */
      .ts-pagination {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 14px; border-top: 1px solid var(--border);
      }
      .ts-pg-info { font-size: 12.5px; color: var(--text4); }
      .ts-pg-btns { display: flex; gap: 6px; }
      .ts-pg-btn {
        background: var(--surface2); border: 1px solid var(--border2);
        border-radius: 7px; padding: 6px 12px;
        font-size: 12.5px; color: var(--text2);
        cursor: pointer; transition: background 0.12s;
        font-family: inherit;
      }
      .ts-pg-btn:hover:not(:disabled) { background: var(--surface3); color: var(--text); }
      .ts-pg-btn:disabled { opacity: 0.35; cursor: not-allowed; }

      .ts-btn-primary {
        display: inline-flex; align-items: center;
        padding: 8px 16px; background: var(--accent);
        border: none; border-radius: 8px;
        font-size: 13.5px; font-weight: 600; color: #fff;
        text-decoration: none; cursor: pointer; font-family: inherit;
        transition: background 0.12s;
      }
      .ts-btn-primary:hover { background: var(--accent-h); }
    `}</style>
  )
}
