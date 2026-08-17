import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  Building2, Zap, Send, MessageSquare,
  CalendarCheck, TrendingUp, Clock, Star, ArrowRight, Search,
} from 'lucide-react'
import {
  AreaChart, Area, XAxis, YAxis, Tooltip,
  ResponsiveContainer, CartesianGrid,
} from 'recharts'
import { useState } from 'react'
import api from '../lib/api'
import StatsCard from '../components/ui/StatsCard'
import MatchScore from '../components/ui/MatchScore'
import StatusBadge from '../components/ui/StatusBadge'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import SearchPanel from '../components/ui/SearchPanel'
import { formatDate } from '../lib/utils'

function ChartTooltip({ active, payload, label }: any) {
  if (!active || !payload?.length) return null
  return (
    <div style={{
      background: 'var(--surface2)',
      border: '1px solid var(--border2)',
      borderRadius: 8, padding: '8px 12px',
      fontSize: 12.5, color: 'var(--text2)',
    }}>
      <p style={{ color: 'var(--text3)', marginBottom: 2 }}>{label}</p>
      <p style={{ fontWeight: 600, color: 'var(--text)' }}>{payload[0].value} sent</p>
    </div>
  )
}

const DIST_ROWS = [
  { key: 'excellent', label: 'Excellent (90–100%)', color: '#4ade80' },
  { key: 'strong',    label: 'Strong (80–89%)',     color: '#60a5fa' },
  { key: 'good',      label: 'Good (70–79%)',       color: '#22d3ee' },
  { key: 'possible',  label: 'Possible (60–69%)',   color: '#fbbf24' },
  { key: 'low',       label: 'Low (<60%)',           color: 'var(--text5)' },
]

export default function DashboardPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['dashboard'],
    queryFn: () => api.get('/dashboard').then(r => r.data),
    refetchInterval: 30_000,
  })

  const [showSearch, setShowSearch] = useState(false)

  if (isLoading) return <LoadingSpinner />

  const today  = data?.stats?.today  ?? {}
  const totals = data?.stats?.totals ?? {}
  const chart  = data?.outreach_chart ?? []
  const recent = data?.recent_opportunities ?? []
  const dist   = data?.stats?.match_distribution ?? {}
  const totalDist = Object.values(dist as Record<string, number>).reduce((a, b) => a + b, 0)

  return (
    <>
      <div className="ts-dash">

        {/* Header */}
        <div className="ts-dash-hd">
          <div>
            <h2 className="ts-dash-title">Today's Outreach</h2>
            <p className="ts-dash-date">
              {new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}
            </p>
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <button
              onClick={() => setShowSearch(v => !v)}
              style={{
                display: 'inline-flex', alignItems: 'center', gap: 6,
                padding: '8px 14px', background: showSearch ? 'var(--accent)' : 'var(--surface)',
                border: '1px solid var(--border2)', borderRadius: 8,
                fontSize: 13, fontWeight: 500, color: showSearch ? '#fff' : 'var(--text2)',
                cursor: 'pointer', transition: 'all .15s', fontFamily: 'inherit',
              }}>
              <Search size={14} strokeWidth={2} />
              {showSearch ? 'Close Search' : 'Run Search'}
            </button>
            <Link to="/opportunities" className="ts-dash-cta">
              View opportunities <ArrowRight size={14} strokeWidth={2} />
            </Link>
          </div>
        </div>

        {/* Search panel */}
        {showSearch && <SearchPanel onClose={() => setShowSearch(false)} />}

        {/* Stats grid */}
        <div className="ts-dash-grid">
          <StatsCard title="Discovered"      value={today.companies_discovered ?? 0} icon={Building2}    iconColor="#818cf8" />
          <StatsCard title="Opportunities"   value={today.opportunities_found  ?? 0} icon={Zap}           iconColor="#60a5fa" />
          <StatsCard title="Strong Matches"  value={today.strong_matches       ?? 0} icon={Star}          iconColor="#fbbf24" />
          <StatsCard title="Awaiting Review" value={today.awaiting_approval    ?? 0} icon={Clock}         iconColor="#fb923c" />
          <StatsCard title="Emails Sent"     value={today.emails_sent          ?? 0} icon={Send}          iconColor="#4ade80" />
          <StatsCard title="Replies"         value={today.replies              ?? 0} icon={MessageSquare} iconColor="#c084fc" />
          <StatsCard title="Interviews"      value={today.interviews           ?? 0} icon={CalendarCheck} iconColor="#f87171" />
          <StatsCard title="Follow-ups Due"  value={totals.follow_ups_due      ?? 0} icon={TrendingUp}    iconColor="#fbbf24" />
        </div>

        {/* Charts */}
        <div className="ts-dash-charts">

          <div className="ts-card">
            <div className="ts-card-hd">
              <p className="ts-card-title">Emails sent — last 14 days</p>
            </div>
            {chart.length > 0 ? (
              <ResponsiveContainer width="100%" height={160}>
                <AreaChart data={chart} margin={{ left: -20, right: 4, top: 4, bottom: 0 }}>
                  <defs>
                    <linearGradient id="aGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%"   stopColor="var(--accent)" stopOpacity={0.2} />
                      <stop offset="100%" stopColor="var(--accent)" stopOpacity={0}   />
                    </linearGradient>
                  </defs>
                  <CartesianGrid stroke="var(--border)" strokeDasharray="4 4" />
                  <XAxis dataKey="date" tick={{ fontSize: 11, fill: 'var(--text4)' }}
                    tickFormatter={d => d.slice(5)} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: 'var(--text4)' }}
                    allowDecimals={false} axisLine={false} tickLine={false} />
                  <Tooltip content={<ChartTooltip />} />
                  <Area type="monotone" dataKey="count" stroke="var(--accent)"
                    strokeWidth={2} fill="url(#aGrad)" />
                </AreaChart>
              </ResponsiveContainer>
            ) : (
              <div className="ts-chart-empty">No data yet — send your first email</div>
            )}
          </div>

          <div className="ts-card">
            <div className="ts-card-hd">
              <p className="ts-card-title">Match score distribution</p>
              <span className="ts-card-sub">{totalDist} total</span>
            </div>
            <div className="ts-dist-list">
              {DIST_ROWS.map(({ key, label, color }) => {
                const count = (dist as any)[key] ?? 0
                const pct   = totalDist > 0 ? Math.round((count / totalDist) * 100) : 0
                return (
                  <div key={key} className="ts-dist-row">
                    <div className="ts-dist-meta">
                      <span className="ts-dist-lbl">{label}</span>
                      <span style={{ fontSize: 12, fontWeight: 600, color }}>{count}</span>
                    </div>
                    <div className="ts-dist-track">
                      <div className="ts-dist-fill" style={{ width: `${pct}%`, background: color }} />
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        </div>

        {/* Totals strip */}
        <div className="ts-totals">
          {[
            { label: 'Total opportunities', value: totals.total_opportunities ?? 0 },
            { label: 'Contacted',           value: totals.contacted           ?? 0 },
            { label: 'Replied',             value: totals.replied             ?? 0 },
            { label: 'Interviews',          value: totals.interviews          ?? 0 },
            { label: 'Offers',              value: totals.offers              ?? 0 },
            { label: 'Emails sent',         value: totals.emails_sent_total   ?? 0 },
          ].map(({ label, value }) => (
            <div key={label} className="ts-total-item">
              <p className="ts-total-val">{value}</p>
              <p className="ts-total-lbl">{label}</p>
            </div>
          ))}
        </div>

        {/* Recent */}
        {recent.length > 0 && (
          <div className="ts-card ts-recent">
            <div className="ts-card-hd" style={{ borderBottom: '1px solid var(--border)', paddingBottom: 14 }}>
              <p className="ts-card-title">Top opportunities</p>
              <Link to="/opportunities" className="ts-link-sm">
                View all <ArrowRight size={12} strokeWidth={2} />
              </Link>
            </div>
            <div>
              {recent.map((opp: any) => (
                <Link key={opp.id} to={`/opportunities/${opp.id}`} className="ts-recent-row">
                  <div className="ts-recent-info">
                    <p className="ts-recent-title">{opp.job?.title ?? '—'}</p>
                    <p className="ts-recent-meta">
                      {opp.company?.name}
                      {opp.job?.location && <> &middot; {opp.job.location}</>}
                      &nbsp;&middot;&nbsp;{formatDate(opp.discovered_at)}
                    </p>
                  </div>
                  <div className="ts-recent-badges">
                    <MatchScore score={opp.match_score} classification={opp.match_classification} />
                    <StatusBadge status={opp.status} />
                  </div>
                </Link>
              ))}
            </div>
          </div>
        )}
      </div>

      <style>{`
        .ts-dash { display: flex; flex-direction: column; gap: 16px; max-width: 1200px; }

        /* Header */
        .ts-dash-hd {
          display: flex; align-items: flex-start;
          justify-content: space-between; gap: 12px; flex-wrap: wrap;
        }
        .ts-dash-title { font-size: 18px; font-weight: 700; color: var(--text); letter-spacing: -0.015em; }
        .ts-dash-date  { font-size: 13px; color: var(--text4); margin-top: 2px; }
        .ts-dash-cta {
          display: inline-flex; align-items: center; gap: 6px;
          padding: 8px 14px;
          background: var(--surface); border: 1px solid var(--border2);
          border-radius: 8px; font-size: 13px; font-weight: 500; color: var(--text2);
          text-decoration: none; transition: background 0.12s; white-space: nowrap;
        }
        .ts-dash-cta:hover { background: var(--surface2); color: var(--text); }

        /* Stats */
        .ts-dash-grid {
          display: grid; grid-template-columns: repeat(4,1fr); gap: 8px;
        }
        @media (max-width: 1024px) { .ts-dash-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 480px)  { .ts-dash-grid { grid-template-columns: repeat(2,1fr); } }

        /* Charts */
        .ts-dash-charts {
          display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        }
        @media (max-width: 768px) { .ts-dash-charts { grid-template-columns: 1fr; } }

        /* Card */
        .ts-card {
          background: var(--surface); border: 1px solid var(--border);
          border-radius: 10px; padding: 18px;
          transition: background 0.2s, border-color 0.2s;
        }
        .ts-card-hd {
          display: flex; align-items: center; justify-content: space-between;
          margin-bottom: 16px;
        }
        .ts-card-title { font-size: 13px; font-weight: 600; color: var(--text2); }
        .ts-card-sub   { font-size: 12px; color: var(--text4); }
        .ts-chart-empty {
          height: 160px; display: flex; align-items: center;
          justify-content: center; font-size: 13px; color: var(--text4);
        }

        /* Distribution */
        .ts-dist-list { display: flex; flex-direction: column; gap: 10px; }
        .ts-dist-row  { display: flex; flex-direction: column; gap: 4px; }
        .ts-dist-meta { display: flex; justify-content: space-between; align-items: center; }
        .ts-dist-lbl  { font-size: 12px; color: var(--text4); }
        .ts-dist-track {
          height: 4px; background: var(--surface2);
          border-radius: 100px; overflow: hidden;
        }
        .ts-dist-fill { height: 100%; border-radius: 100px; min-width: 2px; transition: width 0.3s ease; }

        /* Totals */
        .ts-totals {
          display: grid; grid-template-columns: repeat(6,1fr);
          background: var(--surface); border: 1px solid var(--border);
          border-radius: 10px; overflow: hidden;
          transition: background 0.2s, border-color 0.2s;
        }
        @media (max-width: 768px) { .ts-totals { grid-template-columns: repeat(3,1fr); } }
        @media (max-width: 480px) { .ts-totals { grid-template-columns: repeat(2,1fr); } }
        .ts-total-item {
          padding: 16px 12px; text-align: center;
          border-right: 1px solid var(--border);
        }
        .ts-total-item:last-child { border-right: none; }
        .ts-total-val { font-size: 22px; font-weight: 800; letter-spacing: -0.03em; color: var(--text); line-height: 1; }
        .ts-total-lbl { font-size: 11px; color: var(--text4); margin-top: 4px; }

        /* Recent */
        .ts-recent { padding: 18px; }
        .ts-link-sm {
          display: inline-flex; align-items: center; gap: 4px;
          font-size: 12.5px; font-weight: 500; color: var(--accent);
          text-decoration: none; transition: color 0.12s;
        }
        .ts-link-sm:hover { color: var(--accent-t); }
        .ts-recent-row {
          display: flex; align-items: center;
          justify-content: space-between; gap: 12px;
          padding: 11px 0;
          border-bottom: 1px solid var(--border);
          text-decoration: none; transition: background 0.1s;
          flex-wrap: wrap;
        }
        .ts-recent-row:last-child { border-bottom: none; }
        .ts-recent-row:hover .ts-recent-title { color: var(--text); }
        .ts-recent-info { flex: 1; min-width: 0; }
        .ts-recent-title {
          font-size: 13.5px; font-weight: 500; color: var(--text2);
          overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
          transition: color 0.12s;
        }
        .ts-recent-meta { font-size: 12px; color: var(--text4); margin-top: 2px; }
        .ts-recent-badges { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
      `}</style>
    </>
  )
}
