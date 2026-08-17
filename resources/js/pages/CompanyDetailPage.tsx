import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ExternalLink, MapPin } from 'lucide-react'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import MatchScore from '../components/ui/MatchScore'
import { TsPageStyles } from '../components/ui/TsShared'

export default function CompanyDetailPage() {
  const { id } = useParams()
  const { data: company, isLoading } = useQuery({
    queryKey: ['company', id],
    queryFn: () => api.get(`/companies/${id}`).then(r => r.data),
  })

  if (isLoading) return <LoadingSpinner />
  if (!company) return <div style={{ padding: 48, textAlign: 'center', color: 'var(--text3)' }}>Company not found.</div>

  return (
    <>
      <div className="ts-page" style={{ maxWidth: 800 }}>
        {/* Header */}
        <div className="ts-card">
          <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
            <div>
              <h1 style={{ fontSize: 20, fontWeight: 700, color: 'var(--text)', letterSpacing: '-0.02em' }}>{company.name}</h1>
              <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 12, marginTop: 6 }}>
                {company.industry && <span style={{ fontSize: 13, color: 'var(--text3)' }}>{company.industry}</span>}
                {company.location && (
                  <span style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 13, color: 'var(--text3)' }}>
                    <MapPin size={13} strokeWidth={1.75} />{company.location}
                  </span>
                )}
              </div>
            </div>
            {company.website && (
              <a href={company.website} target="_blank" rel="noopener noreferrer" className="ts-btn-secondary">
                <ExternalLink size={14} strokeWidth={1.75} /> Website
              </a>
            )}
          </div>
          {company.description && (
            <p style={{ marginTop: 16, fontSize: 13.5, color: 'var(--text2)', lineHeight: 1.7 }}>{company.description}</p>
          )}
          {company.tech_stack?.length > 0 && (
            <div style={{ marginTop: 16 }}>
              <p style={{ fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.06em', color: 'var(--text4)', marginBottom: 8 }}>Tech Stack</p>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                {company.tech_stack.map((t: string) => (
                  <span key={t} className="ts-pill ts-pill-accent">{t}</span>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Jobs */}
        {company.jobs?.length > 0 && (
          <div className="ts-table-wrap">
            <div style={{ padding: '14px 16px', borderBottom: '1px solid var(--border)' }}>
              <p className="ts-card-title">Open Roles ({company.jobs_count})</p>
            </div>
            <div>
              {company.jobs.map((job: any) => (
                <Link key={job.id} to={`/jobs/${job.id}`}
                  style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 16px', borderBottom: '1px solid var(--border)', textDecoration: 'none', transition: 'background 0.1s' }}
                  onMouseEnter={e => (e.currentTarget.style.background = 'var(--surface2)')}
                  onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}
                >
                  <div>
                    <p style={{ fontSize: 13.5, fontWeight: 500, color: 'var(--text)' }}>{job.title}</p>
                    <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 2 }}>{job.location ?? (job.is_remote ? 'Remote' : '—')}</p>
                  </div>
                  {job.opportunities?.[0] && (
                    <MatchScore score={job.opportunities[0].match_score} classification={job.opportunities[0].match_classification} />
                  )}
                </Link>
              ))}
            </div>
          </div>
        )}

        {/* Contacts */}
        {company.contacts?.length > 0 && (
          <div className="ts-card">
            <p className="ts-card-title" style={{ marginBottom: 12 }}>Contacts</p>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
              {company.contacts.map((c: any) => (
                <div key={c.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 12px', background: 'var(--surface2)', borderRadius: 8 }}>
                  <div>
                    <p style={{ fontSize: 13.5, fontWeight: 500, color: 'var(--text)' }}>{c.name ?? '—'}</p>
                    <p style={{ fontSize: 12, color: 'var(--text3)', marginTop: 2 }}>{c.role} · {c.contact_type?.replace(/_/g, ' ')}</p>
                  </div>
                  {c.email && <a href={`mailto:${c.email}`} style={{ fontSize: 12.5, color: 'var(--accent)', textDecoration: 'none' }}>{c.email}</a>}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
      <TsPageStyles />
    </>
  )
}
