/**
 * Shared CSS variable-based styles used across all pages.
 * Import TsPageStyles at the bottom of any page that uses tables/filters.
 */
export function TsPageStyles() {
  return (
    <style>{`
      .ts-page { display: flex; flex-direction: column; gap: 16px; }

      /* ── Filters ─────────────────────────────── */
      .ts-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
      .ts-search-wrap { position: relative; flex: 1; min-width: 180px; }
      .ts-search-icon {
        position: absolute; left: 10px; top: 50%;
        transform: translateY(-50%); color: var(--text4); pointer-events: none;
      }
      .ts-search-input { padding-left: 32px !important; }
      .ts-input {
        background: var(--surface); border: 1px solid var(--border2);
        border-radius: 8px; padding: 8px 12px;
        font-size: 13.5px; color: var(--text);
        transition: border-color 0.15s, background 0.15s;
        width: 100%; font-family: inherit;
      }
      .ts-input::placeholder { color: var(--text5); }
      .ts-input:focus { outline: none; border-color: rgba(59,130,246,0.45); }
      .ts-select {
        background: var(--surface); border: 1px solid var(--border2);
        border-radius: 8px; padding: 8px 12px;
        font-size: 13.5px; color: var(--text2);
        cursor: pointer; font-family: inherit;
        transition: border-color 0.15s;
      }
      .ts-select:focus { outline: none; border-color: rgba(59,130,246,0.45); }
      .ts-count { font-size: 12.5px; color: var(--text4); white-space: nowrap; }

      /* ── Table ───────────────────────────────── */
      .ts-table-wrap {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 10px; overflow: hidden;
        transition: background 0.2s, border-color 0.2s;
      }
      .ts-table { width: 100%; border-collapse: collapse; }
      .ts-th {
        padding: 10px 14px; text-align: left;
        font-size: 11.5px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.04em;
        color: var(--text4); background: var(--surface2);
        border-bottom: 1px solid var(--border); white-space: nowrap;
      }
      .ts-tr { border-bottom: 1px solid var(--border); transition: background 0.1s; }
      .ts-tr:last-child { border-bottom: none; }
      .ts-tr:hover { background: var(--surface2); }
      .ts-td { padding: 11px 14px; vertical-align: middle; font-size: 13.5px; color: var(--text); }
      .ts-td-muted { color: var(--text2); font-size: 13px; }
      .ts-td-dim   { color: var(--text4); font-size: 12px; }
      .ts-row-link { font-weight: 500; color: var(--text); text-decoration: none; transition: color 0.12s; }
      .ts-row-link:hover { color: var(--accent); }

      /* ── Pagination ──────────────────────────── */
      .ts-pagination {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 14px; border-top: 1px solid var(--border);
      }
      .ts-pg-info { font-size: 12.5px; color: var(--text4); }
      .ts-pg-btns { display: flex; gap: 6px; }
      .ts-pg-btn {
        background: var(--surface2); border: 1px solid var(--border2);
        border-radius: 7px; padding: 6px 12px;
        font-size: 12.5px; color: var(--text2);
        cursor: pointer; transition: background 0.12s; font-family: inherit;
      }
      .ts-pg-btn:hover:not(:disabled) { background: var(--surface3); color: var(--text); }
      .ts-pg-btn:disabled { opacity: 0.35; cursor: not-allowed; }

      /* ── Card ────────────────────────────────── */
      .ts-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 10px; padding: 20px;
        transition: background 0.2s, border-color 0.2s;
      }
      .ts-card-title { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 4px; }
      .ts-card-sub   { font-size: 13px; color: var(--text3); }

      /* ── Section label ───────────────────────── */
      .ts-section-label {
        font-size: 11px; font-weight: 700;
        letter-spacing: 0.08em; text-transform: uppercase; color: var(--accent);
        margin-bottom: 6px;
      }

      /* ── Shared button ───────────────────────── */
      .ts-btn-primary {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px; background: var(--accent);
        border: none; border-radius: 8px;
        font-size: 13.5px; font-weight: 600; color: #fff;
        text-decoration: none; cursor: pointer; font-family: inherit;
        transition: background 0.12s;
      }
      .ts-btn-primary:hover { background: var(--accent-h); }
      .ts-btn-secondary {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 14px;
        background: var(--surface); border: 1px solid var(--border2);
        border-radius: 8px; font-size: 13px; font-weight: 500;
        color: var(--text2); text-decoration: none; cursor: pointer;
        font-family: inherit; transition: background 0.12s;
      }
      .ts-btn-secondary:hover { background: var(--surface2); color: var(--text); }

      /* ── Tag / skill pill ────────────────────── */
      .ts-pill {
        display: inline-flex; align-items: center;
        padding: 2px 9px; border-radius: 100px;
        font-size: 12px; font-weight: 500;
        background: var(--surface2); color: var(--text2);
        border: 1px solid var(--border);
      }
      .ts-pill-accent { background: var(--accent-bg); color: var(--accent-t); border-color: transparent; }
      .ts-pill-green  { background: rgba(34,197,94,0.08); color: #16a34a; border-color: transparent; }
      .ts-pill-red    { background: rgba(239,68,68,0.08); color: #dc2626; border-color: transparent; }

      /* ── Form field ──────────────────────────── */
      .ts-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
      .ts-label { font-size: 12.5px; font-weight: 500; color: var(--text2); }
      .ts-textarea {
        background: var(--surface); border: 1px solid var(--border2);
        border-radius: 8px; padding: 10px 12px;
        font-size: 13.5px; color: var(--text);
        resize: vertical; font-family: inherit; min-height: 80px;
        transition: border-color 0.15s;
      }
      .ts-textarea:focus { outline: none; border-color: rgba(59,130,246,0.45); }

      /* ── Spinner ─────────────────────────────── */
      @keyframes ts-spin { to { transform: rotate(360deg); } }
    `}</style>
  )
}

// Re-export for convenience
export function TsTable() { return null }
