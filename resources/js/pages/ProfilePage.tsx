import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Trash2, Upload } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import Button from '../components/ui/Button'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import { TsPageStyles } from '../components/ui/TsShared'

export default function ProfilePage() {
  const qc = useQueryClient()
  const { data: profile, isLoading } = useQuery({
    queryKey: ['profile'],
    queryFn: () => api.get('/profile').then(r => r.data),
  })

  const [form, setForm]         = useState<any>({})
  const [skills, setSkills]     = useState<any[]>([])
  const [experiences, setExps]  = useState<any[]>([])
  const [newSkill, setNewSkill] = useState('')

  useEffect(() => {
    if (!profile) return
    setForm({
      full_name: profile.full_name ?? '',
      primary_title: profile.primary_title ?? '',
      location: profile.location ?? '',
      portfolio_url: profile.portfolio_url ?? '',
      summary: profile.summary ?? '',
      work_preference: profile.work_preference ?? 'any',
      minimum_salary: profile.minimum_salary ?? '',
      years_of_experience: profile.years_of_experience ?? '',
      preferred_roles: (profile.preferred_roles ?? []).join(', '),
      preferred_locations: (profile.preferred_locations ?? []).join(', '),
      preferred_industries: (profile.preferred_industries ?? []).join(', '),
      excluded_industries: (profile.excluded_industries ?? []).join(', '),
      preferred_technologies: (profile.preferred_technologies ?? []).join(', '),
    })
    setSkills(profile.skills ?? [])
    setExps(profile.experiences ?? [])
  }, [profile])

  const save = useMutation({
    mutationFn: () => api.put('/profile', {
      ...form,
      preferred_roles: csv(form.preferred_roles),
      preferred_locations: csv(form.preferred_locations),
      preferred_industries: csv(form.preferred_industries),
      excluded_industries: csv(form.excluded_industries),
      preferred_technologies: csv(form.preferred_technologies),
      minimum_salary: form.minimum_salary || null,
      years_of_experience: form.years_of_experience || null,
      skills,
      experiences: experiences,
    }),
    onSuccess: () => { toast.success('Profile saved'); qc.invalidateQueries({ queryKey: ['profile'] }) },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Save failed'),
  })

  const uploadCv = useMutation({
    mutationFn: (file: File) => {
      const fd = new FormData(); fd.append('cv', file)
      return api.post('/profile/cv', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    },
    onSuccess: () => toast.success('CV uploaded'),
    onError: () => toast.error('Upload failed'),
  })

  if (isLoading) return <LoadingSpinner />

  const set = (k: string, v: any) => setForm((f: any) => ({ ...f, [k]: v }))
  const addSkill = () => {
    if (!newSkill.trim()) return
    setSkills(s => [...s, { skill: newSkill.trim(), level: 'intermediate' }])
    setNewSkill('')
  }
  const removeSkill = (i: number) => setSkills(s => s.filter((_, idx) => idx !== i))
  const addExp = () => setExps(e => [...e, { company: '', title: '', description: '', start_date: '', end_date: '', is_current: false }])
  const removeExp = (i: number) => setExps(e => e.filter((_, idx) => idx !== i))
  const setExp = (i: number, k: string, v: any) => setExps(e => e.map((ex, idx) => idx === i ? { ...ex, [k]: v } : ex))

  return (
    <>
      <div className="ts-page" style={{ maxWidth: 760 }}>

        {/* Basic info */}
        <Section title="Basic Information">
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 14 }}>
            <Field label="Full Name *" value={form.full_name} onChange={v => set('full_name', v)} />
            <Field label="Primary Title" value={form.primary_title} onChange={v => set('primary_title', v)} />
            <Field label="Location" value={form.location} onChange={v => set('location', v)} />
            <Field label="Portfolio URL" value={form.portfolio_url} onChange={v => set('portfolio_url', v)} type="url" />
            <div style={{ gridColumn: '1/-1' }}>
              <label className="ts-label">Summary</label>
              <textarea rows={3} value={form.summary} onChange={e => set('summary', e.target.value)} className="ts-textarea" style={{ marginTop: 6 }} />
            </div>
          </div>
        </Section>

        {/* Preferences */}
        <Section title="Job Preferences">
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 14 }}>
            <div>
              <label className="ts-label">Work Preference</label>
              <select value={form.work_preference} onChange={e => set('work_preference', e.target.value)}
                className="ts-select" style={{ marginTop: 6, width: '100%' }}>
                {['any','remote','hybrid','onsite'].map(p => <option key={p} value={p}>{p.charAt(0).toUpperCase()+p.slice(1)}</option>)}
              </select>
            </div>
            <Field label="Years of Experience" value={form.years_of_experience} onChange={v => set('years_of_experience', v)} type="number" />
            <Field label="Minimum Salary" value={form.minimum_salary} onChange={v => set('minimum_salary', v)} type="number" />
            <Field label="Preferred Roles (comma separated)" value={form.preferred_roles} onChange={v => set('preferred_roles', v)} />
            <Field label="Preferred Locations (comma separated)" value={form.preferred_locations} onChange={v => set('preferred_locations', v)} />
            <Field label="Preferred Industries (comma separated)" value={form.preferred_industries} onChange={v => set('preferred_industries', v)} />
            <Field label="Excluded Industries (comma separated)" value={form.excluded_industries} onChange={v => set('excluded_industries', v)} />
            <Field label="Preferred Technologies (comma separated)" value={form.preferred_technologies} onChange={v => set('preferred_technologies', v)} />
          </div>
        </Section>

        {/* Skills */}
        <Section title="Skills">
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, marginBottom: 12 }}>
            {skills.map((s, i) => (
              <span key={i} className="ts-pill ts-pill-accent" style={{ display: 'inline-flex', alignItems: 'center', gap: 5 }}>
                {s.skill}
                <button onClick={() => removeSkill(i)} style={{ background: 'none', border: 'none', color: 'var(--accent)', cursor: 'pointer', padding: 0, display: 'flex', lineHeight: 1 }}>
                  <Trash2 size={11} strokeWidth={2} />
                </button>
              </span>
            ))}
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <input value={newSkill} onChange={e => setNewSkill(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && addSkill()}
              placeholder="Add a skill…" className="ts-input" style={{ flex: 1 }} />
            <Button size="sm" onClick={addSkill} icon={<Plus size={13} strokeWidth={2} />}>Add</Button>
          </div>
        </Section>

        {/* Experience */}
        <Section title="Work Experience">
          {experiences.map((exp, i) => (
            <div key={i} style={{ border: '1px solid var(--border)', borderRadius: 8, padding: 14, marginBottom: 12, background: 'var(--surface2)' }}>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 12 }}>
                <Field label="Company *" value={exp.company} onChange={v => setExp(i, 'company', v)} />
                <Field label="Title *" value={exp.title} onChange={v => setExp(i, 'title', v)} />
                <Field label="Start Date" value={exp.start_date} onChange={v => setExp(i, 'start_date', v)} type="date" />
                <Field label="End Date" value={exp.end_date} onChange={v => setExp(i, 'end_date', v)} type="date" />
                <div style={{ gridColumn: '1/-1' }}>
                  <label className="ts-label">Description</label>
                  <textarea rows={2} value={exp.description} onChange={e => setExp(i, 'description', e.target.value)}
                    className="ts-textarea" style={{ marginTop: 6 }} />
                </div>
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 13.5, color: 'var(--text2)', cursor: 'pointer' }}>
                  <input type="checkbox" checked={exp.is_current} onChange={e => setExp(i, 'is_current', e.target.checked)} />
                  Current position
                </label>
              </div>
              <button onClick={() => removeExp(i)}
                style={{ marginTop: 8, display: 'flex', alignItems: 'center', gap: 4, fontSize: 12, color: '#f87171', background: 'none', border: 'none', cursor: 'pointer', fontFamily: 'inherit' }}>
                <Trash2 size={13} strokeWidth={1.75} /> Remove
              </button>
            </div>
          ))}
          <Button size="sm" variant="secondary" onClick={addExp} icon={<Plus size={13} strokeWidth={2} />}>Add Experience</Button>
        </Section>

        {/* CV */}
        <Section title="CV / Resume">
          <p style={{ fontSize: 13, color: 'var(--text3)', marginBottom: 10 }}>
            {profile?.cv_path ? `On file: ${profile.cv_path.split('/').pop()}` : 'No CV uploaded yet.'}
          </p>
          <label style={{ display: 'inline-flex', alignItems: 'center', gap: 7, padding: '8px 14px', background: 'var(--surface2)', border: '1px solid var(--border2)', borderRadius: 8, fontSize: 13.5, fontWeight: 500, color: 'var(--text2)', cursor: 'pointer' }}>
            <Upload size={14} strokeWidth={1.75} />
            {uploadCv.isPending ? 'Uploading…' : 'Upload PDF'}
            <input type="file" accept=".pdf" style={{ display: 'none' }}
              onChange={e => { const f = e.target.files?.[0]; if (f) uploadCv.mutate(f) }} />
          </label>
        </Section>

        <div style={{ display: 'flex', justifyContent: 'flex-end', paddingBottom: 24 }}>
          <Button loading={save.isPending} onClick={() => save.mutate()}>Save Profile</Button>
        </div>
      </div>
      <TsPageStyles />
    </>
  )
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div style={{ background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 10, padding: 20 }}>
      <p style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)', marginBottom: 16, letterSpacing: '-0.01em' }}>{title}</p>
      {children}
    </div>
  )
}

function Field({ label, value, onChange, type = 'text' }: { label: string; value: string; onChange: (v: string) => void; type?: string }) {
  return (
    <div>
      <label className="ts-label">{label}</label>
      <input type={type} value={value} onChange={e => onChange(e.target.value)}
        className="ts-input" style={{ marginTop: 6 }} />
    </div>
  )
}

function csv(str: string): string[] {
  return str.split(',').map(s => s.trim()).filter(Boolean)
}
