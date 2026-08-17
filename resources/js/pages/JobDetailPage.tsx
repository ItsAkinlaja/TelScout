import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ExternalLink, MapPin, DollarSign } from 'lucide-react'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import MatchScore from '../components/ui/MatchScore'
import { formatCurrency, formatDate } from '../lib/utils'
import { TsPageStyles } from '../components/ui/TsShared'

export default function JobDetailPage() {
  const { id } = useParams()
  const { data: job, isLoading } = useQuery({
    queryKey: ['job', id],
    queryFn: () => api.get(`/jobs/${id}`).then(r => r.data),
  })

  if (isLoading) return <LoadingSpinner />
  if (!job) return <div style={{ padding: 48, textAlign: 'center', color: 'var(--text3)' }}>Job not found.</div>

  const opp = job.opportunities?.[0]

  return (
    <>
      <div className="ts-page" style={{ maxWidth: 760 }}>
        <div className="ts-card">
          <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
            <div>
              <h1 style={{ fontSize: 20, fontWeight: 700, color: 'var(--text)', letterSpacing: '-0.02em' }}>{job.title}</h1>
              <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 10, marginTop: 6 }}>
                <Link to={`/companies/${job.company?.id}`} style={{ fontSize: 13.5, fontWeight: 500, color: 'var(--accent)', textDecoration: 'none' }}>
                  {job.company?.name}
                </Link>
                {job.location && <span style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 13, color: 'var(--text3)' }}><MapPin size={13} strokeWidth={1.75} />{job.location}</span>}
                {job.is_remote && <span className="ts-pill ts-pill-green">Remote</span>}
                {(job.salary_min || job.salary_max) && (
                  <span style={{ display: 'flex', alignItems: 'center', gap: 3, fontSize: 13, color: 'var(--text3)' }}>
                    <DollarSign size={13} strokeWidth={1.75} />
                    {formatCurrency(job.salary_min)} – {formatCurrency(job.salary_max)}
                  </span>
                )}
              </div>
              <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 6 }}>
                {job.source ?? 'manual'}{job.posted_at && <> · Posted {formatDate(job.posted_at)}</>}
              </p>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
              {opp && <MatchScore score={opp.match_score} classification={opp.match_classification} size="lg" />}
              {job.application_url && (
                <a href={job.application_url} target="_blank" rel="noopener noreferrer" className="ts-btn-secondary">
                  <ExternalLink size={13} strokeWidth={1.75} /> Apply
                </a>
              )}
            </div>
          </div>
        </div>

        {job.skills?.length > 0 && (
          <div className="ts-card">
            <p className="ts-card-title" style={{ marginBottom: 12 }}>Required Skills</p>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
              {job.skills.map((s: any) => (
                <span key={s.skill} className={`ts-pill ${s.is_required ? 'ts-pill-accent' : ''}`}>{s.skill}</span>
              ))}
            </div>
          </div>
        )}

        <div className="ts-card">
          <p className="ts-card-title" style={{ marginBottom: 12 }}>Description</p>
          <div style={{ fontSize: 13.5, color: 'var(--text2)', whiteSpace: 'pre-wrap', lineHeight: 1.75 }}>
            {job.description ?? 'No description available.'}
          </div>
        </div>

        {opp && (
          <Link to={`/opportunities/${opp.id}`}
            style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 18px', background: 'var(--accent-bg)', border: '1px solid rgba(59,130,246,0.2)', borderRadius: 10, textDecoration: 'none' }}>
            <span style={{ fontSize: 13.5, fontWeight: 600, color: 'var(--accent)' }}>View Opportunity & Generate Email →</span>
            <MatchScore score={opp.match_score} classification={opp.match_classification} />
          </Link>
        )}
      </div>
      <TsPageStyles />
    </>
  )
}
