import { useQuery } from '@tanstack/react-query'
import { Database } from 'lucide-react'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import EmptyState from '../components/ui/EmptyState'
import { formatDate } from '../lib/utils'
import { TsPageStyles } from '../components/ui/TsShared'

function StatusPill({ active }: { active: boolean }) {
  return (
    <span className={`ts-pill ${active ? 'ts-pill-green' : ''}`} style={{ fontSize: 11 }}>
      {active ? 'Active' : 'Inactive'}
    </span>
  )
}

export default function JobSourcesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['job-sources'],
    queryFn: () => api.get('/job-sources').then(r => r.data),
  })

  const sources: any[] = Array.isArray(data) ? data : (data?.data ?? [])

  return (
    <>
      <div className="ts-page">
        <div className="ts-filters" style={{ marginBottom: 16 }}>
          <h1 style={{ fontSize: 18, fontWeight: 700, color: 'var(--text)', letterSpacing: '-0.02em', margin: 0 }}>
            Job Sources
          </h1>
          <span className="ts-count">{sources.length} source{sources.length !== 1 ? 's' : ''}</span>
        </div>

        {isLoading ? (
          <LoadingSpinner />
        ) : sources.length === 0 ? (
          <EmptyState
            icon={Database}
            title="No job sources registered yet."
            description="Job sources are added automatically when jobs are fetched from external boards."
          />
        ) : (
          <div className="ts-table-wrap">
            <table className="ts-table">
              <thead>
                <tr>
                  {['Company', 'Source Type', 'ATS', 'Status', 'Last Fetched', 'Next Fetch', 'Failures'].map(h => (
                    <th key={h} className="ts-th">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {sources.map((s: any) => (
                  <tr key={s.id} className="ts-tr">
                    <td className="ts-td" style={{ fontWeight: 500 }}>
                      {s.company?.name ?? s.company_name ?? '—'}
                    </td>
                    <td className="ts-td ts-td-muted">{s.source_type ?? '—'}</td>
                    <td className="ts-td ts-td-muted">{s.ats ?? '—'}</td>
                    <td className="ts-td">
                      <StatusPill active={!!(s.is_active ?? s.active)} />
                    </td>
                    <td className="ts-td ts-td-muted">
                      {s.last_fetched_at ? formatDate(s.last_fetched_at) : '—'}
                    </td>
                    <td className="ts-td ts-td-muted">
                      {s.next_fetch_at ? formatDate(s.next_fetch_at) : '—'}
                    </td>
                    <td className="ts-td ts-td-muted">
                      {s.failure_count ?? 0}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
      <TsPageStyles />
    </>
  )
}
