import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Building2, Search, ExternalLink } from 'lucide-react'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import EmptyState from '../components/ui/EmptyState'
import { TsTable, TsPageStyles } from '../components/ui/TsShared'

export default function CompaniesPage() {
  const [search, setSearch] = useState('')
  const [page, setPage]     = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['companies', search, page],
    queryFn: () => api.get('/companies', { params: { search, page, per_page: 25 } }).then(r => r.data),
  })

  const companies = data?.data ?? []
  const meta = { total: data?.total, current_page: data?.current_page, last_page: data?.last_page }

  return (
    <>
      <div className="ts-page">
        <div className="ts-filters">
          <div className="ts-search-wrap">
            <Search size={15} strokeWidth={1.75} className="ts-search-icon" />
            <input className="ts-input ts-search-input" placeholder="Search companies…"
              value={search} onChange={e => { setSearch(e.target.value); setPage(1) }} />
          </div>
          <span className="ts-count">{meta.total ?? 0} companies</span>
        </div>

        {isLoading ? <LoadingSpinner /> : companies.length === 0 ? (
          <EmptyState icon={Building2} title="No companies yet"
            description="Companies are added automatically when you run a job search." />
        ) : (
          <div className="ts-table-wrap">
            <table className="ts-table">
              <thead>
                <tr>
                  {['Company','Industry','Location','Jobs','Website'].map(h => (
                    <th key={h} className="ts-th">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {companies.map((c: any) => (
                  <tr key={c.id} className="ts-tr">
                    <td className="ts-td">
                      <Link to={`/companies/${c.id}`} className="ts-row-link">{c.name}</Link>
                      {c.normalized_domain && <p style={{ fontSize: 11.5, color: 'var(--text4)', marginTop: 2 }}>{c.normalized_domain}</p>}
                    </td>
                    <td className="ts-td ts-td-muted">{c.industry ?? '—'}</td>
                    <td className="ts-td ts-td-muted">{c.location ?? '—'}</td>
                    <td className="ts-td ts-td-muted">{c.jobs_count ?? 0}</td>
                    <td className="ts-td">
                      {c.website ? (
                        <a href={c.website} target="_blank" rel="noopener noreferrer"
                          style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontSize: 12.5, color: 'var(--accent)', textDecoration: 'none' }}>
                          <ExternalLink size={13} strokeWidth={1.75} /> Visit
                        </a>
                      ) : <span className="ts-td-dim">—</span>}
                    </td>
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
      <TsPageStyles />
    </>
  )
}
