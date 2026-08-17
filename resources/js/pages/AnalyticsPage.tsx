import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  AreaChart, Area, BarChart, Bar, PieChart, Pie, Cell,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from 'recharts'
import api from '../lib/api'
import StatsCard from '../components/ui/StatsCard'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import { Send, MessageSquare, CalendarCheck, TrendingUp } from 'lucide-react'
import { TsPageStyles } from '../components/ui/TsShared'

const PIE_COLORS: Record<string, string> = {
  excellent: '#22c55e', strong: '#3b82f6', good: '#06b6d4',
  possible: '#f59e0b', low: '#71717a',
}
const STATUS_COLORS: Record<string, string> = {
  discovered: '#a1a1aa', shortlisted: '#3b82f6', contacted: '#6366f1',
  follow_up: '#f59e0b', replied: '#a855f7', interview: '#f97316',
  offer: '#22c55e', rejected: '#ef4444',
}

function ChartTooltip({ active, payload, label }: any) {
  if (!active || !payload?.length) return null
  return (
    <div style={{ background: 'var(--surface2)', border: '1px solid var(--border2)', borderRadius: 8, padding: '8px 12px', fontSize: 12.5, color: 'var(--text2)' }}>
      <p style={{ color: 'var(--text3)', marginBottom: 2 }}>{label}</p>
      <p style={{ fontWeight: 600, color: 'var(--text)' }}>{payload[0].value}</p>
    </div>
  )
}

export default function AnalyticsPage() {
  const [days, setDays] = useState(30)

  const { data, isLoading } = useQuery({
    queryKey: ['analytics', days],
    queryFn: () => api.get('/analytics', { params: { days } }).then(r => r.data),
  })

  if (isLoading) return <LoadingSpinner />

  const summary  = data?.summary ?? {}
  const charts   = data?.charts  ?? {}
  const scoreData  = Object.entries(charts.score_distribution      ?? {}).map(([name, value]) => ({ name, value }))
  const statusData = Object.entries(charts.applications_by_status  ?? {}).map(([name, value]) => ({ name, value }))

  return (
    <>
      <div className="ts-page">
        {/* Header + period selector */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 12 }}>
          <h2 style={{ fontSize: 18, fontWeight: 700, color: 'var(--text)', letterSpacing: '-0.015em' }}>Analytics</h2>
          <div style={{ display: 'flex', gap: 6 }}>
            {[7, 14, 30, 90].map(d => (
              <button key={d} onClick={() => setDays(d)} style={{
                padding: '6px 12px', borderRadius: 8, fontSize: 12.5, fontWeight: 600,
                cursor: 'pointer', border: '1px solid var(--border2)',
                background: days === d ? 'var(--accent)' : 'var(--surface)',
                color: days === d ? '#fff' : 'var(--text2)',
                transition: 'all .15s', fontFamily: 'inherit',
              }}>
                {d}d
              </button>
            ))}
          </div>
        </div>

        {/* Summary */}
        <div className="ts-stats-grid" style={{ gridTemplateColumns: 'repeat(4,1fr)' }}>
          <StatsCard title="Emails Sent"     value={summary.emails_sent      ?? 0}  icon={Send}          iconColor="#60a5fa" />
          <StatsCard title="Reply Rate"      value={`${summary.reply_rate    ?? 0}%`} icon={MessageSquare} iconColor="#c084fc" />
          <StatsCard title="Interview Rate"  value={`${summary.interview_rate?? 0}%`} icon={CalendarCheck} iconColor="#fb923c" />
          <StatsCard title="Avg Match Score" value={`${summary.avg_match_score??0}%`} icon={TrendingUp}    iconColor="#4ade80" />
        </div>

        {/* Charts 2x2 */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 12 }}>

          <div className="ts-card">
            <p className="ts-card-title" style={{ marginBottom: 16 }}>Outreach over time</p>
            {charts.outreach_over_time?.length > 0 ? (
              <ResponsiveContainer width="100%" height={180}>
                <AreaChart data={charts.outreach_over_time} margin={{ left: -20, right: 4, top: 4, bottom: 0 }}>
                  <defs>
                    <linearGradient id="ag1" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%"   stopColor="var(--accent)" stopOpacity={0.2} />
                      <stop offset="100%" stopColor="var(--accent)" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid stroke="var(--border)" strokeDasharray="4 4" />
                  <XAxis dataKey="date" tick={{ fontSize: 11, fill: 'var(--text4)' }} tickFormatter={d => d.slice(5)} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: 'var(--text4)' }} allowDecimals={false} axisLine={false} tickLine={false} />
                  <Tooltip content={<ChartTooltip />} />
                  <Area type="monotone" dataKey="emails_sent" stroke="var(--accent)" fill="url(#ag1)" strokeWidth={2} name="Sent" />
                </AreaChart>
              </ResponsiveContainer>
            ) : <EmptyChart />}
          </div>

          <div className="ts-card">
            <p className="ts-card-title" style={{ marginBottom: 16 }}>Replies over time</p>
            {charts.replies_over_time?.length > 0 ? (
              <ResponsiveContainer width="100%" height={180}>
                <AreaChart data={charts.replies_over_time} margin={{ left: -20, right: 4, top: 4, bottom: 0 }}>
                  <defs>
                    <linearGradient id="ag2" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%"   stopColor="#a855f7" stopOpacity={0.2} />
                      <stop offset="100%" stopColor="#a855f7" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid stroke="var(--border)" strokeDasharray="4 4" />
                  <XAxis dataKey="date" tick={{ fontSize: 11, fill: 'var(--text4)' }} tickFormatter={d => d.slice(5)} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: 'var(--text4)' }} allowDecimals={false} axisLine={false} tickLine={false} />
                  <Tooltip content={<ChartTooltip />} />
                  <Area type="monotone" dataKey="replies" stroke="#a855f7" fill="url(#ag2)" strokeWidth={2} name="Replies" />
                </AreaChart>
              </ResponsiveContainer>
            ) : <EmptyChart />}
          </div>

          <div className="ts-card">
            <p className="ts-card-title" style={{ marginBottom: 16 }}>Match score distribution</p>
            {scoreData.length > 0 ? (
              <ResponsiveContainer width="100%" height={180}>
                <PieChart>
                  <Pie data={scoreData} cx="50%" cy="50%" innerRadius={45} outerRadius={72} paddingAngle={3} dataKey="value"
                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`} labelLine={false}>
                    {scoreData.map(entry => <Cell key={entry.name} fill={PIE_COLORS[entry.name] ?? '#71717a'} />)}
                  </Pie>
                  <Tooltip content={<ChartTooltip />} />
                </PieChart>
              </ResponsiveContainer>
            ) : <EmptyChart />}
          </div>

          <div className="ts-card">
            <p className="ts-card-title" style={{ marginBottom: 16 }}>Applications by status</p>
            {statusData.length > 0 ? (
              <ResponsiveContainer width="100%" height={180}>
                <BarChart data={statusData} layout="vertical" margin={{ left: 16, right: 8 }}>
                  <XAxis type="number" tick={{ fontSize: 11, fill: 'var(--text4)' }} allowDecimals={false} axisLine={false} tickLine={false} />
                  <YAxis type="category" dataKey="name" tick={{ fontSize: 11, fill: 'var(--text4)' }} width={80} tickFormatter={n => n.replace(/_/g, ' ')} axisLine={false} tickLine={false} />
                  <Tooltip content={<ChartTooltip />} />
                  <Bar dataKey="value" radius={[0, 4, 4, 0]} name="Count">
                    {statusData.map(entry => <Cell key={entry.name} fill={STATUS_COLORS[entry.name] ?? '#71717a'} />)}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            ) : <EmptyChart />}
          </div>

        </div>
      </div>

      <style>{`
        .ts-stats-grid { display: grid; gap: 8px; }
        .ts-card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 18px; transition: background .2s, border-color .2s; }
        .ts-card-title { font-size: 13px; font-weight: 600; color: var(--text2); }
        @media (max-width: 768px) {
          div[style*="repeat(2,1fr)"] { grid-template-columns: 1fr !important; }
          .ts-stats-grid { grid-template-columns: repeat(2,1fr) !important; }
        }
      `}</style>
      <TsPageStyles />
    </>
  )
}

function EmptyChart() {
  return <div style={{ height: 160, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 13, color: 'var(--text4)' }}>No data for this period yet.</div>
}
