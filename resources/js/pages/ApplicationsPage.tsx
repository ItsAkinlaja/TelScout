import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { FileText } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import EmptyState from '../components/ui/EmptyState'
import MatchScore from '../components/ui/MatchScore'
import { TsPageStyles } from '../components/ui/TsShared'

const COLUMNS = [
  { key: 'discovered',  label: 'Discovered' },
  { key: 'shortlisted', label: 'Shortlisted' },
  { key: 'contacted',   label: 'Contacted' },
  { key: 'follow_up',   label: 'Follow-up' },
  { key: 'replied',     label: 'Replied' },
  { key: 'interview',   label: 'Interview' },
  { key: 'offer',       label: 'Offer' },
  { key: 'rejected',    label: 'Rejected' },
]

const COL_ACCENT: Record<string, string> = {
  discovered: 'var(--text4)', shortlisted: '#60a5fa', contacted: '#818cf8',
  follow_up: '#fbbf24', replied: '#c084fc', interview: '#fb923c',
  offer: '#4ade80', rejected: '#f87171',
}

export default function ApplicationsPage() {
  const qc = useQueryClient()
  const { data, isLoading } = useQuery({
    queryKey: ['applications'],
    queryFn: () => api.get('/applications').then(r => r.data),
  })

  const updateStatus = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => api.patch(`/applications/${id}`, { status }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['applications'] }),
    onError: () => toast.error('Failed to update'),
  })

  const kanban: Record<string, any[]> = data?.kanban ?? {}

  if (isLoading) return <LoadingSpinner />
  if (!data?.applications?.length) return (
    <>
      <EmptyState icon={FileText} title="No applications yet"
        description="Opportunities you engage with will appear here as your CRM." />
      <TsPageStyles />
    </>
  )

  return (
    <>
      <div style={{ overflowX: 'auto', paddingBottom: 8 }}>
        <div style={{ display: 'flex', gap: 10, minWidth: COLUMNS.length * 220 + 'px' }}>
          {COLUMNS.map(col => {
            const cards: any[] = kanban[col.key] ?? []
            return (
              <div key={col.key} style={{ width: 210, flexShrink: 0 }}>
                {/* Column header */}
                <div style={{
                  display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                  padding: '8px 10px', marginBottom: 8,
                  background: 'var(--surface)', border: '1px solid var(--border)',
                  borderRadius: 8, borderLeft: `3px solid ${COL_ACCENT[col.key]}`,
                }}>
                  <span style={{ fontSize: 12.5, fontWeight: 600, color: 'var(--text2)' }}>{col.label}</span>
                  <span style={{ fontSize: 12, color: 'var(--text4)', fontWeight: 600 }}>{cards.length}</span>
                </div>

                {/* Cards */}
                <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                  {cards.map(app => (
                    <div key={app.id} style={{
                      background: 'var(--surface)', border: '1px solid var(--border)',
                      borderRadius: 8, padding: '10px 12px',
                      transition: 'background 0.15s, border-color 0.15s',
                    }}>
                      <Link to={`/opportunities/${app.opportunity_id}`} style={{ textDecoration: 'none' }}>
                        <p style={{ fontSize: 12.5, fontWeight: 500, color: 'var(--text)', lineHeight: 1.4 }}>
                          {app.opportunity?.job?.title ?? '—'}
                        </p>
                        <p style={{ fontSize: 11.5, color: 'var(--text4)', marginTop: 3 }}>
                          {app.opportunity?.company?.name ?? '—'}
                        </p>
                      </Link>
                      {app.opportunity?.match_score != null && (
                        <div style={{ marginTop: 8 }}>
                          <MatchScore score={app.opportunity.match_score} classification={app.opportunity.match_classification} size="sm" />
                        </div>
                      )}
                      <select
                        value={app.status}
                        onChange={e => updateStatus.mutate({ id: app.id, status: e.target.value })}
                        style={{
                          marginTop: 8, width: '100%',
                          background: 'var(--surface2)', border: '1px solid var(--border)',
                          borderRadius: 6, padding: '4px 6px',
                          fontSize: 11.5, color: 'var(--text2)',
                          cursor: 'pointer', fontFamily: 'inherit',
                        }}
                      >
                        {COLUMNS.map(c => <option key={c.key} value={c.key}>{c.label}</option>)}
                      </select>
                    </div>
                  ))}
                  {cards.length === 0 && (
                    <div style={{
                      height: 60, display: 'flex', alignItems: 'center', justifyContent: 'center',
                      border: '1px dashed var(--border2)', borderRadius: 8,
                      fontSize: 12, color: 'var(--text5)',
                    }}>
                      Empty
                    </div>
                  )}
                </div>
              </div>
            )
          })}
        </div>
      </div>
      <TsPageStyles />
    </>
  )
}
