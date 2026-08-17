import { useState } from 'react'
import { Outlet } from 'react-router-dom'
import Sidebar from './Sidebar'
import Topbar from './Topbar'

export default function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(false)

  return (
    <div className="ts-layout">
      {sidebarOpen && (
        <div className="ts-overlay" onClick={() => setSidebarOpen(false)} />
      )}

      <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <div className="ts-main">
        <Topbar onMenuClick={() => setSidebarOpen(v => !v)} />
        <main className="ts-content">
          <Outlet />
        </main>
      </div>

      <style>{`
        .ts-layout {
          display: flex;
          height: 100dvh;
          overflow: hidden;
          background: var(--bg);
        }
        .ts-overlay {
          position: fixed;
          inset: 0;
          z-index: 40;
          background: rgba(0,0,0,0.45);
          backdrop-filter: blur(2px);
        }
        .ts-main {
          flex: 1;
          display: flex;
          flex-direction: column;
          overflow: hidden;
          min-width: 0;
          background: var(--bg);
        }
        .ts-content {
          flex: 1;
          overflow-y: auto;
          padding: 28px;
        }
        @media (max-width: 768px) {
          .ts-content { padding: 20px 16px; }
        }
      `}</style>
    </div>
  )
}
