import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  ExternalLink, CheckCircle, XCircle, Wand2, Send, MapPin,
  DollarSign, Building2, Mail,
} from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import MatchScore from '../components/ui/MatchScore'
import StatusBadge from '../components/ui/StatusBadge'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import Button from '../components/ui/Button'
import { formatDate, formatCurrency } from '../lib/utils'
import { TsPageStyles } from '../components/ui/TsShared'

export default function OpportunityDetailPage() {
  const { id } = useParams()
  const qc = useQueryClient()
  const [emailBody, setEmailBody] = useState('')
  const [emailSubject, setEmailSubject] = useState('')
  const [emailEditing, setEmailEditing] = useState(false)

  const { data: opp, isLoading } = useQuery({
    queryKey: ['opportunity', id],
    queryFn: () => api.get(`/opportunities/${id}`).then(r => r.data),
  })

  const generateEmail = useMutation({
    mutationFn: () => api.post(`/opportunities/${id}/generate-email`),
    onSuccess: (res) => {
      setEmailSubject(res.data.subject)
      setEmailBody(res.data.body)
      setEmailEditing(true)
      toast.success('Email generated')
      qc.invalidateQueries({ queryKey: ['opportunity', id] })
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to generate email'),
  })

  const approveEmail = useMutation({
    mutationFn: (emailId: number) => api.post(`/emails/${emailId}/approve`),
    onSuccess: () => { toast.success('Email approved'); qc.invalidateQueries({ queryKey: ['opportunity', id] }) },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed'),
  })

  const sendEmail = useMutation({
    mutationFn: (emailId: number) => api.post(`/emails/${emailId}/send`),
    onSuccess: () => { toast.success('Email queued for sending'); qc.invalidateQueries({ queryKey: ['opportunity', id] }) },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to send'),
  })

  const updateEmail = useMutation({
    mutationFn: ({ emailId, subject, body }: any) => api.patch(`/emails/${emailId}`, { subject, body_text: body }),
    onSuccess: () => { toast.success('Email updated'); setEmailEditing(false) },
  })

  const rejectOpp = useMutation({
    mutationFn: () => api.post(`/opportunities/${id}/reject`),
    onSuccess: () => { toast.success('Opportunity rejected'); qc.invalidateQueries({ queryKey: ['opportunity', id] }) },
  })

  if (isLoading) return <LoadingSpinner />
  if (!opp) return <div style={{ padding: 48, textAlign: 'center', color: 'var(--text3)' }}>Opportunity not found.</div>

  const job     = opp.job
  const company = opp.company
  const contact = opp.contact
  const latestEmail = opp.emails?.[0]

  return (
    <>
      <div style={{ maxWidth: 1100, display: 'flex', flexDirection: 'column', gap: 16 }}>

        {/* Header */}
        <div className="ts-card">
          <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
            <div style={{ flex: 1, minWidth: 0 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexWrap: 'wrap', marginBottom: 8 }}>
                <h1 style={{ fontSize: 20, fontWeight: 700, color: 'var(--text)', letterSpacing: '-0.02em' }}>{job?.title}</h1>
                <MatchScore score={opp.match_score} classification={opp.match_classification} size="lg" />
                <StatusBadge status={opp.status} />
              </div>
              <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 12 }}>
                <span style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 13, color: 'var(--text2)' }}>
                  <Building2 size={13} strokeWidth={1.75} />{company?.name}
                </span>
                {job?.location && (
                  <span style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 13, color: 'var(--text3)' }}>
                    <MapPin size={13} strokeWidth={1.75} />{job.location}
                  </span>
                )}
                {job?.is_remote && <span className="ts-pill ts-pill-green">Remote</span>}
                {(job?.salary_min || job?.salary_max) && (
                  <span style={{ display: 'flex', alignItems: 'center', gap: 3, fontSize: 13, color: 'var(--text3)' }}>
                    <DollarSign size={13} strokeWidth={1.75} />
                    {formatCurrency(job.salary_min)} – {formatCurrency(job.salary_max)}
                  </span>
                )}
              </div>
            </div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
              {opp.application_url && (
                <a href={opp.application_url} target="_blank" rel="noopener noreferrer">
                  <Button variant="secondary" size="sm" icon={<ExternalLink size={13} strokeWidth={1.75} />}>Apply</Button>
                </a>
              )}
              {opp.status === 'discovered' && (
                <Button size="sm" variant="success" icon={<CheckCircle size={13} strokeWidth={2} />}
                  onClick={() => api.post(`/opportunities/${id}/approve`).then(() => { toast.success('Shortlisted'); qc.invalidateQueries({ queryKey: ['opportunity', id] }) })}>
                  Shortlist
                </Button>
              )}
              <Button size="sm" variant="danger" icon={<XCircle size={13} strokeWidth={2} />} onClick={() => rejectOpp.mutate()}>Reject</Button>
            </div>
          </div>
        </div>

        {/* Two-column layout */}
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 320px', gap: 16, alignItems: 'start' }}>

          {/* Left */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

            {/* Match analysis */}
            <div className="ts-card">
              <p className="ts-card-title" style={{ marginBottom: 16 }}>Match Analysis</p>
              {opp.match_reasoning && (
                <p style={{ fontSize: 13.5, color: 'var(--text2)', lineHeight: 1.7, marginBottom: 16 }}>{opp.match_reasoning}</p>
              )}
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: opp.score_breakdown ? 16 : 0 }}>
                <div>
                  <p style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.06em', color: 'var(--text4)', marginBottom: 8 }}>Matched Skills</p>
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5 }}>
                    {(opp.matched_skills ?? []).map((s: string) => <span key={s} className="ts-pill ts-pill-green">{s}</span>)}
                    {!opp.matched_skills?.length && <span style={{ fontSize: 12.5, color: 'var(--text4)' }}>None</span>}
                  </div>
                </div>
                <div>
                  <p style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.06em', color: 'var(--text4)', marginBottom: 8 }}>Missing Skills</p>
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5 }}>
                    {(opp.missing_skills ?? []).map((s: string) => <span key={s} className="ts-pill ts-pill-red">{s}</span>)}
                    {!opp.missing_skills?.length && <span style={{ fontSize: 12.5, color: 'var(--text4)' }}>None</span>}
                  </div>
                </div>
              </div>
              {opp.score_breakdown && (
                <div style={{ borderTop: '1px solid var(--border)', paddingTop: 16, display: 'flex', flexDirection: 'column', gap: 8 }}>
                  {Object.entries(opp.score_breakdown as Record<string, number>).map(([dim, score]) => (
                    <div key={dim} style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                      <span style={{ width: 120, fontSize: 12, color: 'var(--text3)', textTransform: 'capitalize', flexShrink: 0 }}>{dim.replace(/_/g, ' ')}</span>
                      <div style={{ flex: 1, height: 5, borderRadius: 100, background: 'var(--surface2)', overflow: 'hidden' }}>
                        <div style={{ height: '100%', borderRadius: 100, background: 'var(--accent)', width: `${score}%`, transition: 'width .4s' }} />
                      </div>
                      <span style={{ width: 36, textAlign: 'right', fontSize: 12, fontWeight: 600, color: 'var(--text2)' }}>{Math.round(score)}%</span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Job description */}
            <div className="ts-card">
              <p className="ts-card-title" style={{ marginBottom: 12 }}>Job Description</p>
              <div style={{ fontSize: 13.5, color: 'var(--text2)', whiteSpace: 'pre-wrap', lineHeight: 1.75 }}>
                {job?.description ?? 'No description available.'}
              </div>
            </div>

            {/* Email section */}
            <div className="ts-card">
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 }}>
                <p className="ts-card-title">Outreach Email</p>
                <Button size="sm" variant="secondary" icon={<Wand2 size={13} strokeWidth={1.75} />}
                  loading={generateEmail.isPending} onClick={() => generateEmail.mutate()}>
                  {latestEmail ? 'Regenerate' : 'Generate Email'}
                </Button>
              </div>

              {(emailEditing || latestEmail) ? (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                  <input
                    value={emailSubject || latestEmail?.subject || ''}
                    onChange={e => setEmailSubject(e.target.value)}
                    placeholder="Subject"
                    className="ts-input"
                  />
                  <textarea
                    rows={10}
                    value={emailBody || latestEmail?.body_text || ''}
                    onChange={e => setEmailBody(e.target.value)}
                    placeholder="Email body…"
                    className="ts-textarea"
                    style={{ fontFamily: 'inherit', fontSize: 13.5 }}
                  />
                  <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
                    {emailEditing && (
                      <Button size="sm" onClick={() => updateEmail.mutate({ emailId: latestEmail?.id, subject: emailSubject, body: emailBody })}>
                        Save Changes
                      </Button>
                    )}
                    {latestEmail?.status === 'draft' && (
                      <Button size="sm" variant="success" icon={<CheckCircle size={13} strokeWidth={2} />}
                        loading={approveEmail.isPending} onClick={() => approveEmail.mutate(latestEmail.id)}>
                        Approve
                      </Button>
                    )}
                    {latestEmail?.status === 'approved' && (
                      <Button size="sm" icon={<Send size={13} strokeWidth={2} />}
                        loading={sendEmail.isPending} onClick={() => sendEmail.mutate(latestEmail.id)}>
                        Send Email
                      </Button>
                    )}
                    {latestEmail && <StatusBadge status={latestEmail.status} />}
                  </div>
                </div>
              ) : (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 80, borderRadius: 8, background: 'var(--surface2)', fontSize: 13, color: 'var(--text4)' }}>
                  Click "Generate Email" to create a personalized outreach message.
                </div>
              )}
            </div>

            {/* Timeline */}
            {opp.emails?.some((e: any) => e.events?.length) && (
              <div className="ts-card">
                <p className="ts-card-title" style={{ marginBottom: 14 }}>Activity Timeline</p>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                  {opp.emails.flatMap((e: any) => e.events ?? []).map((ev: any) => (
                    <div key={ev.id} style={{ display: 'flex', alignItems: 'flex-start', gap: 10 }}>
                      <div style={{ marginTop: 5, width: 7, height: 7, borderRadius: '50%', background: 'var(--accent)', flexShrink: 0 }} />
                      <div>
                        <p style={{ fontSize: 13, color: 'var(--text2)' }}>{ev.description}</p>
                        <p style={{ fontSize: 11.5, color: 'var(--text4)', marginTop: 2 }}>{formatDate(ev.occurred_at)}</p>
                      </div>
                    </div>
                  ))}
                  <div style={{ display: 'flex', alignItems: 'flex-start', gap: 10 }}>
                    <div style={{ marginTop: 5, width: 7, height: 7, borderRadius: '50%', background: 'var(--border2)', flexShrink: 0 }} />
                    <div>
                      <p style={{ fontSize: 13, color: 'var(--text2)' }}>Opportunity discovered</p>
                      <p style={{ fontSize: 11.5, color: 'var(--text4)', marginTop: 2 }}>{formatDate(opp.discovered_at)}</p>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Right sidebar */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>

            <div className="ts-card">
              <p className="ts-card-title" style={{ marginBottom: 12 }}>Company</p>
              <p style={{ fontSize: 15, fontWeight: 600, color: 'var(--text)' }}>{company?.name}</p>
              {company?.industry && <p style={{ fontSize: 12.5, color: 'var(--text3)', marginTop: 2 }}>{company.industry}</p>}
              {company?.location && (
                <p style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 12.5, color: 'var(--text3)', marginTop: 2 }}>
                  <MapPin size={12} strokeWidth={1.75} />{company.location}
                </p>
              )}
              {company?.description && (
                <p style={{ fontSize: 13, color: 'var(--text2)', lineHeight: 1.65, marginTop: 10 }}>{company.description}</p>
              )}
              {company?.website && (
                <a href={company.website} target="_blank" rel="noopener noreferrer"
                  style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontSize: 12.5, color: 'var(--accent)', textDecoration: 'none', marginTop: 10 }}>
                  <ExternalLink size={12} strokeWidth={1.75} /> Website
                </a>
              )}
              {company?.tech_stack?.length > 0 && (
                <div style={{ marginTop: 12 }}>
                  <p style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.06em', color: 'var(--text4)', marginBottom: 6 }}>Tech Stack</p>
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5 }}>
                    {company.tech_stack.map((t: string) => <span key={t} className="ts-pill">{t}</span>)}
                  </div>
                </div>
              )}
            </div>

            {contact && (
              <div className="ts-card">
                <p className="ts-card-title" style={{ marginBottom: 10 }}>Contact</p>
                <p style={{ fontSize: 14, fontWeight: 500, color: 'var(--text)' }}>{contact.name ?? '—'}</p>
                <p style={{ fontSize: 12.5, color: 'var(--text3)', marginTop: 2 }}>{contact.role} · {contact.contact_type?.replace(/_/g, ' ')}</p>
                {contact.email && (
                  <a href={`mailto:${contact.email}`} style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 12.5, color: 'var(--accent)', textDecoration: 'none', marginTop: 8 }}>
                    <Mail size={12} strokeWidth={1.75} />{contact.email}
                  </a>
                )}
              </div>
            )}

            {job?.skills?.length > 0 && (
              <div className="ts-card">
                <p className="ts-card-title" style={{ marginBottom: 10 }}>Required Skills</p>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5 }}>
                  {job.skills.map((s: any) => (
                    <span key={s.skill} className={`ts-pill ${s.is_required ? 'ts-pill-accent' : ''}`}>{s.skill}</span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
      <TsPageStyles />
      <style>{`
        @media (max-width: 900px) {
          div[style*="1fr 320px"] { grid-template-columns: 1fr !important; }
        }
      `}</style>
    </>
  )
}
