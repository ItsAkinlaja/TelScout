import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Calendar, CheckCircle, XCircle } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import EmptyState from '../components/ui/EmptyState'
import StatusBadge from '../components/ui/StatusBadge'
import Button from '../components/ui/Button'
import { formatDate } from '../lib/utils'
import { TsPageStyles } from '../components/ui/TsShared'

export default function FollowUpsPage() {
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['follow-ups'],
    queryFn: () => api.get('/follow-ups').then(r => r.data),
  })

  const complete = useMutation({
    mutationFn: (id: number) => api.post(`/follow-ups/${id}/complete`),
    onSuccess: () => { toast.success('Follow-up completed'); qc.invalidateQueries({ queryKey: ['follow-ups'] }) },
  })
  const cancel = useMutation({
    mutationFn: (id: number) => api.post(`/follow-ups/${id}/cancel`),
    onSuccess: () => { toast.success('Cancelled'); qc.invalidateQueries({ queryKey: ['follow-ups'] }) },
  })

  const followUps = data?.data ?? []

  if (isLoading) return <LoadingSpinner />
  if (!followUps.length) return (
    <>
      <EmptyState icon={Calendar} title="No pending follow-ups"
        description="Follow-ups are scheduled automatically after sending emails." />
      <TsPageStyles />
    </>
  )

  return (
    <>
      <div className="ts-page">
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
          {followUps.map((fu: any) => {
            const isOverdue = new Date(fu.scheduled_at) < new Date()
            return (
              <div key={fu.id} style={{
                display: 'flex', alignItems: 'center', gap: 14, padding: '14px 16px',
                background: 'var(--surface)', border: '1px solid var(--border)',
                borderRadius: 10, flexWrap: 'wrap',
                transition: 'background 0.2s, border-color 0.2s',
              }}>
                <div style={{
                  width: 36, height: 36, borderRadius: 8, flexShrink: 0,
                  background: isOverdue ? 'rgba(239,68,68,0.08)' : 'rgba(245,158,11,0.08)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  color: isOverdue ? '#f87171' : '#fbbf24',
                }}>
                  <Calendar size={17} strokeWidth={1.75} />
                </div>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <Link to={`/opportunities/${fu.opportunity_id}`}
                    style={{ fontSize: 13.5, fontWeight: 500, color: 'var(--text)', textDecoration: 'none' }}>
                    {fu.opportunity?.job?.title ?? '—'}
                  </Link>
                  <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 2 }}>
                    {fu.opportunity?.company?.name} · Follow-up #{fu.follow_up_number}
                  </p>
                </div>
                <div style={{ textAlign: 'right', flexShrink: 0 }}>
                  <p style={{ fontSize: 12.5, fontWeight: 500, color: isOverdue ? '#f87171' : 'var(--text2)' }}>
                    {formatDate(fu.scheduled_at)}
                  </p>
                  <StatusBadge status={fu.status} />
                </div>
                <div style={{ display: 'flex', gap: 6 }}>
                  <Button size="sm" variant="success" icon={<CheckCircle size={13} strokeWidth={2} />}
                    loading={complete.isPending} onClick={() => complete.mutate(fu.id)}>
                    Done
                  </Button>
                  <Button size="sm" variant="ghost" icon={<XCircle size={13} strokeWidth={2} />}
                    onClick={() => cancel.mutate(fu.id)}>
                    Cancel
                  </Button>
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
