/* Agenda de Missões — 25º BC
   Interface (vanilla JS) que consome a API interna do Laravel. */
(function () {
  'use strict';

  // ---------- config vinda do Blade ----------
  const CFG = window.__PAINEL__ || {};
  const PEOPLE = CFG.people || [];
  const OM_NAME = CFG.omName || '25º Batalhão de Caçadores';
  const OM_SIGLA = CFG.omSigla || '25º BC';
  const CSRF = CFG.csrf || '';
  const ROUTES = CFG.routes || {};
  const TV_ROTATE_MS = (CFG.tvRotateSeconds || 12) * 1000;
  const CAL_START = 7, CAL_END = 18;

  const ICONS = {
    grid: '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>',
    calendar: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>',
    clipboard: '<svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="3"/><path d="M9 4.5V3h6v1.5M9 10h6M9 14h6M9 18h4"/></svg>',
    check: '<svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>',
    plus: '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>',
    refresh: '<svg viewBox="0 0 24 24"><path d="M20 7v5h-5M4 17v-5h5"/><path d="M6.1 9A7 7 0 0 1 18.5 7.5L20 12M4 12l1.5 4.5A7 7 0 0 0 17.9 15"/></svg>',
    clock: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    edit: '<svg viewBox="0 0 24 24"><path d="m4 20 4.5-1 10-10a2 2 0 0 0-3-3l-10 10L4 20Zm10-13 3 3"/></svg>',
    flag: '<svg viewBox="0 0 24 24"><path d="M5 21V4M5 5h11l-2 4 2 4H5"/></svg>',
    shield: '<svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.6 2.9 8 7 10 4.1-2 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>'
  };

  // ---------- estado ----------
  let missions = [];
  let view = 'dashboard';
  let filter = 'todas';
  let calMonday = mondayOf(new Date());
  let editingId = null;
  let tvScreen = 0;
  let lastRotate = Date.now();

  // ---------- helpers de data ----------
  const $ = (s, r = document) => r.querySelector(s);
  const pad = (n) => String(n).padStart(2, '0');
  function isoLocal(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function fromISO(date, time) { return new Date(date + 'T' + (time || '12:00') + ':00'); }
  function addDays(d, n) { return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n); }
  function mondayOf(date) { const d = new Date(date.getFullYear(), date.getMonth(), date.getDate()); const day = d.getDay() || 7; d.setDate(d.getDate() - day + 1); return d; }
  function initials(name) { return String(name || '').split(' ').filter(x => !['da', 'de', 'do', 'ep'].includes(x.toLowerCase())).map(x => x[0]).join('').slice(0, 2).toUpperCase(); }
  function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

  const STATUS_LABEL = { pendente: 'Pendente', andamento: 'Em andamento', concluida: 'Concluída', atrasada: 'Atrasada' };
  const PRIORITY_LABEL = { baixa: 'Baixa', media: 'Média', alta: 'Alta' };
  function statusLabel(s) { return STATUS_LABEL[s] || s; }
  function priorityLabel(p) { return PRIORITY_LABEL[p] || p; }
  function dateLabel(d) { return fromISO(d).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' }).replace('.', ''); }
  function actualStatus(m) { return (m.status !== 'concluida' && fromISO(m.date, m.time) < new Date()) ? 'atrasada' : m.status; }
  function sorted(list) { return list.slice().sort((a, b) => fromISO(a.date, a.time) - fromISO(b.date, b.time)); }

  // ---------- API ----------
  async function api(url, opts = {}) {
    const res = await fetch(url, Object.assign({
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF,
        'X-Requested-With': 'XMLHttpRequest'
      }
    }, opts));
    if (!res.ok) {
      let msg = 'Erro na comunicação com o servidor.';
      try { const j = await res.json(); if (j.message) msg = j.message; if (j.errors) msg = Object.values(j.errors)[0][0]; } catch (e) {}
      throw new Error(msg);
    }
    return res.status === 204 ? null : res.json();
  }
  const loadMissions = () => api(ROUTES.index).then(d => { missions = d || []; });

  // ---------- render ----------
  function render() {
    renderDashboard();
    renderCalendar();
    renderTables();
    if (document.body.classList.contains('monitor-mode')) renderTV();
  }

  function missionRowHTML(m, kind) {
    const st = actualStatus(m);
    const left = kind === 'upcoming'
      ? '<div class="mission-time mono">' + dateLabel(m.date) + ' ' + m.time + '</div>'
      : '<div class="mission-time mono">' + m.time + '</div>';
    const right = kind === 'upcoming'
      ? '<span class="status-pill s-' + st + '">' + statusLabel(st) + '</span>'
      : '<select class="status-select s-' + st + '" data-status="' + m.id + '">'
        + opt('pendente', m.status) + opt('andamento', m.status) + opt('concluida', m.status) + '</select>';
    return '<div class="mission-row p-' + m.priority + (kind === 'upcoming' ? ' upcoming' : '') + '" data-edit="' + m.id + '">'
      + left
      + '<div class="mission-main"><strong>' + esc(m.title) + '</strong><div class="mission-meta">' + dateLabel(m.date) + (m.requester ? ' · ' + esc(m.requester) : '') + '</div></div>'
      + '<div class="responsible"><div class="mini-avatar">' + initials(m.responsible) + '</div><span>' + esc(m.responsible) + '</span></div>'
      + right + '</div>';
  }
  function opt(val, cur) { return '<option value="' + val + '"' + (val === cur ? ' selected' : '') + '>' + statusLabel(val) + '</option>'; }

  function countdownText(m) {
    const diff = fromISO(m.date, m.time) - new Date();
    if (diff < 0) return '<strong>Prazo ultrapassado</strong> — requer atualização';
    const mins = Math.floor(diff / 60000), days = Math.floor(mins / 1440), hours = Math.floor((mins % 1440) / 60);
    if (days > 0) return 'Começa em <strong>' + days + ' dia' + (days > 1 ? 's' : '') + (hours ? ' e ' + hours + 'h' : '') + '</strong>';
    if (hours > 0) return 'Começa em <strong>' + hours + 'h ' + (mins % 60) + 'min</strong>';
    return 'Começa em <strong>' + Math.max(0, mins) + ' minutos</strong>';
  }

  function weekData() {
    const now = new Date(), weekStart = mondayOf(now), weekEnd = addDays(weekStart, 7);
    const week = missions.filter(m => { const d = fromISO(m.date); return d >= weekStart && d < weekEnd; });
    const doneWeek = week.filter(m => m.status === 'concluida').length;
    return { week, doneWeek, pct: week.length ? Math.round(doneWeek / week.length * 100) : 0 };
  }

  function renderDashboard() {
    const now = new Date(), today = isoLocal(now);
    const todays = sorted(missions.filter(m => m.date === today && m.status !== 'concluida'));
    const { week, doneWeek, pct } = weekData();
    const overdue = missions.filter(m => actualStatus(m) === 'atrasada').length;

    $('#stats').innerHTML = [
      ['clipboard', 'Missões hoje', missions.filter(m => m.date === today).length, 'programadas', 'tone-blue'],
      ['clock', 'Em andamento', missions.filter(m => m.status === 'andamento').length, 'na seção', 'tone-amber'],
      ['check', 'Concluídas', doneWeek, 'nesta semana', 'tone-green'],
      ['flag', 'Atrasadas', overdue, overdue === 1 ? 'requer atenção' : 'requerem atenção', 'tone-red']
    ].map(s => '<article class="stat"><div class="stat-top"><span class="stat-label">' + s[1] + '</span><span class="stat-icon ' + s[4] + '"><span class="icon">' + ICONS[s[0]] + '</span></span></div><div class="stat-value"><strong class="mono">' + s[2] + '</strong><span>' + s[3] + '</span></div></article>').join('');

    $('#todayMissions').innerHTML = todays.length ? todays.map(m => missionRowHTML(m, 'today')).join('') : '<div class="empty">Nenhuma missão pendente para hoje.</div>';

    const upcoming = sorted(missions.filter(m => m.date > today && fromISO(m.date) < addDays(now, 8) && m.status !== 'concluida')).slice(0, 4);
    $('#upcomingMissions').innerHTML = upcoming.length ? upcoming.map(m => missionRowHTML(m, 'upcoming')).join('') : '<div class="empty">Nenhuma missão prevista para os próximos dias.</div>';

    const next = sorted(missions.filter(m => m.status !== 'concluida' && fromISO(m.date, m.time) >= now))[0] || sorted(missions.filter(m => m.status !== 'concluida'))[0];
    $('#nextMission').innerHTML = next
      ? '<div class="next-label"><span>Próxima missão</span><i class="live-dot"></i></div><h2>' + esc(next.title) + '</h2><p>' + esc(next.notes || 'Sem observações registradas.') + '</p><div class="next-info"><div><span>Quando</span><strong>' + dateLabel(next.date) + ', ' + next.time + '</strong></div><div><span>Responsável</span><strong>' + esc(next.responsible) + '</strong></div></div><div class="countdown">' + countdownText(next) + '</div>'
      : '<div class="next-label"><span>Próxima missão</span></div><h2>Nenhuma missão pendente</h2><p>A seção está com o planejamento em dia.</p>';

    $('#progressPercent').textContent = pct + '%';
    $('#progressBar').style.width = pct + '%';
    $('#progressDone').textContent = doneWeek + ' concluída' + (doneWeek === 1 ? '' : 's');
    $('#progressTotal').textContent = week.length + ' no total';

    const people = {};
    missions.filter(m => m.status !== 'concluida').forEach(m => people[m.responsible] = (people[m.responsible] || 0) + 1);
    $('#teamList').innerHTML = Object.entries(people).sort((a, b) => b[1] - a[1]).slice(0, 6)
      .map(([name, c]) => '<div class="team-row"><div class="mini-avatar">' + initials(name) + '</div><div><strong>' + esc(name) + '</strong><span>' + c + (c === 1 ? ' missão ativa' : ' missões ativas') + '</span></div><div class="team-count mono">' + c + '</div></div>').join('')
      || '<div class="empty">Sem missões ativas.</div>';
  }

  function renderCalendar() {
    const days = Array.from({ length: 7 }, (_, i) => addDays(calMonday, i));
    const names = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'], today = isoLocal(new Date());
    $('#weekLabel').textContent = dateLabel(isoLocal(days[0])) + ' — ' + dateLabel(isoLocal(days[6]));
    let html = '<div class="cal-corner"></div>';
    days.forEach((d, i) => { const t = isoLocal(d) === today; html += '<div class="cal-head' + (t ? ' today' : '') + '">' + names[i] + '<strong>' + d.getDate() + '</strong></div>'; });
    for (let h = CAL_START; h < CAL_END; h++) {
      html += '<div class="time-cell">' + pad(h) + ':00</div>';
      days.forEach(d => {
        const date = isoLocal(d), items = sorted(missions.filter(m => m.date === date && Number(m.time.slice(0, 2)) === h));
        html += '<div class="day-cell' + (date === today ? ' today' : '') + '" data-new="' + date + '|' + pad(h) + ':00">'
          + items.map(m => '<div class="cal-mission ' + m.priority + '" data-edit="' + m.id + '"><strong>' + m.time + ' · ' + esc(m.title) + '</strong>' + esc(m.responsible) + '</div>').join('') + '</div>';
      });
    }
    $('#calendarGrid').innerHTML = html;
  }

  function renderTables() {
    let list = sorted(missions.filter(m => m.status !== 'concluida'));
    if (filter !== 'todas') list = list.filter(m => actualStatus(m) === filter);
    $('#missionsTable').innerHTML = list.length ? list.map(tableRow).join('') : '<tr><td colspan="6" class="empty">Nenhuma missão neste filtro.</td></tr>';

    const history = sorted(missions.filter(m => m.status === 'concluida')).reverse();
    $('#historyTable').innerHTML = history.length ? history.map(historyRow).join('') : '<tr><td colspan="5" class="empty">Nenhuma missão concluída ainda.</td></tr>';
  }
  function tableRow(m) {
    const st = actualStatus(m);
    return '<tr><td class="table-title"><strong>' + esc(m.title) + '</strong><span>' + esc(m.requester || 'Sem demandante') + '</span></td>'
      + '<td class="mono">' + dateLabel(m.date) + ', ' + m.time + '</td>'
      + '<td>' + esc(m.responsible) + '</td>'
      + '<td><span class="badge b-' + m.priority + '">' + priorityLabel(m.priority) + '</span></td>'
      + '<td><span class="badge s-' + st + '">' + statusLabel(st) + '</span></td>'
      + '<td><div class="row-actions"><button class="small-btn" data-edit="' + m.id + '" title="Editar"><span class="icon">' + ICONS.edit + '</span></button></div></td></tr>';
  }
  function historyRow(m) {
    const done = m.completed_at ? new Date(m.completed_at) : null;
    const doneTxt = done ? done.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' }).replace('.', '') + ', ' + done.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : '—';
    return '<tr><td class="table-title"><strong>' + esc(m.title) + '</strong><span>' + esc(m.requester || 'Sem demandante') + '</span></td>'
      + '<td class="mono">' + dateLabel(m.date) + ', ' + m.time + '</td>'
      + '<td>' + esc(m.completed_by || m.responsible) + '</td>'
      + '<td class="mono">' + doneTxt + '</td>'
      + '<td class="row-actions"><button class="small-btn" data-reopen="' + m.id + '">Reabrir</button></td></tr>';
  }

  function renderTV() {
    const now = new Date(), today = isoLocal(now);
    $('#tvClock').textContent = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    $('#tvDate').textContent = cap(now.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' }));
    $('#tvScreenTitle').textContent = tvScreen === 0 ? 'Missões de hoje' : 'Visão da semana';
    $('#tvDot0').classList.toggle('on', tvScreen === 0);
    $('#tvDot1').classList.toggle('on', tvScreen === 1);

    const names = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
    if (tvScreen === 0) {
      $('#tvToday').style.display = '';
      $('#tvWeek').style.display = 'none';
      const list = sorted(missions.filter(m => m.date === today)).slice(0, 6);
      $('#tvToday').innerHTML = list.length ? list.map(m => {
        const st = actualStatus(m);
        return '<div class="tv-mission p-' + m.priority + (st === 'concluida' ? ' done' : '') + '"><div class="t">' + m.time + '</div><div class="m"><strong>' + esc(m.title) + '</strong><span>' + esc(m.responsible) + '</span></div><span class="pill tv-pill s-' + st + '">' + statusLabel(st) + '</span></div>';
      }).join('') : '<div class="tv-empty">Nenhuma missão para hoje. Seção em dia. ✓</div>';
    } else {
      $('#tvToday').style.display = 'none';
      $('#tvWeek').style.display = 'grid';
      const weekStart = mondayOf(now);
      $('#tvWeek').innerHTML = Array.from({ length: 5 }, (_, i) => addDays(weekStart, i)).map((d, i) => {
        const date = isoLocal(d), t = date === today;
        const items = sorted(missions.filter(m => m.date === date)).slice(0, 4);
        return '<div class="tv-day' + (t ? ' today' : '') + '"><div class="tv-day-head"><strong>' + names[i] + '</strong><span>' + pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '</span></div><div class="tv-day-list">'
          + (items.map(m => '<div class="tv-chip p-' + m.priority + (m.status === 'concluida' ? ' done' : '') + '"><span class="h">' + m.time + '</span><strong>' + esc(m.title) + '</strong><span class="r">' + esc(m.responsible) + '</span></div>').join('') || '<span style="color:#6f8378;font-size:13px">—</span>')
          + '</div></div>';
      }).join('');
    }

    const next = sorted(missions.filter(m => m.status !== 'concluida' && fromISO(m.date, m.time) >= now))[0] || sorted(missions.filter(m => m.status !== 'concluida'))[0];
    $('#tvNext').innerHTML = next
      ? '<div class="label"><span>Próxima missão</span><i class="live-dot"></i></div><h2>' + esc(next.title) + '</h2><p>' + esc(next.notes || 'Sem observações registradas.') + '</p><div class="grid"><div><span>Quando</span><strong class="mono">' + dateLabel(next.date) + ', ' + next.time + '</strong></div><div><span>Responsável</span><strong>' + esc(next.responsible) + '</strong></div></div><div class="cd">' + countdownText(next) + '</div>'
      : '<div class="label"><span>Próxima missão</span></div><h2>Nenhuma missão pendente</h2><p>A seção está com o planejamento em dia.</p>';

    const { week, doneWeek, pct } = weekData();
    $('#tvProgPct').textContent = pct + '%';
    $('#tvProgBar').style.width = pct + '%';
    $('#tvProgDone').textContent = doneWeek + ' concluída' + (doneWeek === 1 ? '' : 's');
    $('#tvProgTotal').textContent = week.length + ' no total';
  }
  function cap(s) { return s.replace(/^./, c => c.toUpperCase()); }

  // ---------- modal ----------
  function openNew(date, time) {
    editingId = null;
    $('#modalTitle').textContent = 'Nova missão';
    $('#deleteBtn').classList.add('hidden');
    $('#formError').textContent = '';
    const f = $('#missionForm');
    f.reset();
    f.date.value = date || isoLocal(new Date());
    f.time.value = time || '08:00';
    f.priority.value = 'media';
    f.status.value = 'pendente';
    toggleCompletedBy();
    $('#modalBackdrop').classList.add('open');
    setTimeout(() => f.title.focus(), 50);
  }
  function openEdit(id) {
    const m = missions.find(x => x.id === id); if (!m) return;
    editingId = id;
    $('#modalTitle').textContent = 'Editar missão';
    $('#deleteBtn').classList.remove('hidden');
    $('#formError').textContent = '';
    const f = $('#missionForm');
    f.title.value = m.title; f.date.value = m.date; f.time.value = m.time;
    f.responsible.value = m.responsible; f.priority.value = m.priority; f.status.value = m.status;
    f.requester.value = m.requester || ''; f.notes.value = m.notes || '';
    f.completed_by.value = m.completed_by || (m.responsible === 'Toda a seção' ? PEOPLE[0] : m.responsible);
    toggleCompletedBy();
    $('#modalBackdrop').classList.add('open');
  }
  function closeModal() { $('#modalBackdrop').classList.remove('open'); }
  function toggleCompletedBy() { $('#completedByField').style.display = $('#missionForm').status.value === 'concluida' ? '' : 'none'; }

  async function submitForm(e) {
    e.preventDefault();
    const f = e.target;
    const payload = {
      title: f.title.value.trim(), date: f.date.value, time: f.time.value,
      responsible: f.responsible.value, priority: f.priority.value, status: f.status.value,
      requester: f.requester.value.trim(), notes: f.notes.value.trim(),
      completed_by: f.status.value === 'concluida' ? f.completed_by.value : null
    };
    if (!payload.title || !payload.date || !payload.time) { $('#formError').textContent = 'Preencha missão, data e horário.'; return; }
    try {
      if (editingId) await api(ROUTES.update.replace('__ID__', editingId), { method: 'PUT', body: JSON.stringify(payload) });
      else await api(ROUTES.store, { method: 'POST', body: JSON.stringify(payload) });
      await loadMissions();
      closeModal(); render();
      toast(editingId ? 'Missão atualizada com sucesso.' : 'Missão adicionada ao painel.');
    } catch (err) { $('#formError').textContent = err.message; }
  }

  async function changeStatus(id, status) {
    const m = missions.find(x => x.id === id); if (!m) return;
    const payload = {
      title: m.title, date: m.date, time: m.time, responsible: m.responsible,
      priority: m.priority, status: status, requester: m.requester || '', notes: m.notes || '',
      completed_by: status === 'concluida' ? (m.completed_by || (m.responsible === 'Toda a seção' ? PEOPLE[0] : m.responsible)) : null
    };
    try {
      await api(ROUTES.update.replace('__ID__', id), { method: 'PUT', body: JSON.stringify(payload) });
      await loadMissions(); render();
      toast('“' + m.title + '” atualizada para ' + statusLabel(status).toLowerCase() + '.');
    } catch (err) { toast(err.message); await loadMissions(); render(); }
  }
  async function deleteMission() {
    const m = missions.find(x => x.id === editingId); if (!m) return;
    if (!confirm('Excluir a missão “' + m.title + '”?')) return;
    try { await api(ROUTES.destroy.replace('__ID__', editingId), { method: 'DELETE' }); await loadMissions(); closeModal(); render(); toast('Missão excluída.'); }
    catch (err) { $('#formError').textContent = err.message; }
  }
  async function reopen(id) {
    const m = missions.find(x => x.id === id); if (!m) return;
    await changeStatusRaw(id, 'pendente'); toast('“' + m.title + '” reaberta.');
  }
  async function changeStatusRaw(id, status) {
    const m = missions.find(x => x.id === id); if (!m) return;
    const payload = { title: m.title, date: m.date, time: m.time, responsible: m.responsible, priority: m.priority, status, requester: m.requester || '', notes: m.notes || '', completed_by: null };
    await api(ROUTES.update.replace('__ID__', id), { method: 'PUT', body: JSON.stringify(payload) });
    await loadMissions(); render();
  }
  async function resetData() {
    if (!confirm('Restaurar os dados iniciais da demonstração? As missões atuais serão apagadas.')) return;
    try { missions = await api(ROUTES.reset, { method: 'POST' }); render(); toast('Dados de demonstração restaurados.'); }
    catch (err) { toast(err.message); }
  }

  // ---------- navegação / monitor ----------
  function switchView(v) {
    view = v;
    document.querySelectorAll('.view').forEach(el => el.classList.toggle('active', el.id === 'view-' + v));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.toggle('active', b.dataset.view === v));
    $('#pageTitle').textContent = { dashboard: 'Bom dia, Seção.', calendar: 'Planejamento semanal', missions: 'Controle de missões', history: 'Histórico da seção' }[v];
  }
  async function setMonitor(on) {
    document.body.classList.toggle('monitor-mode', on);
    tvScreen = 0; lastRotate = Date.now();
    if (on) { renderTV(); if (document.documentElement.requestFullscreen) { try { await document.documentElement.requestFullscreen(); } catch (e) {} } }
    else if (document.fullscreenElement && document.exitFullscreen) { try { await document.exitFullscreen(); } catch (e) {} }
  }

  function toast(text) {
    const el = $('#toast');
    $('#toastText').textContent = text;
    el.classList.add('show');
    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(() => el.classList.remove('show'), 2800);
  }

  // ---------- relógio + rotação TV ----------
  function tick() {
    const n = new Date();
    $('#clock').textContent = n.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    $('#todayText').textContent = cap(n.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }));
    if (document.body.classList.contains('monitor-mode')) {
      if (Date.now() - lastRotate >= TV_ROTATE_MS) { lastRotate = Date.now(); tvScreen = (tvScreen + 1) % 2; }
      renderTV();
    }
  }

  // ---------- eventos ----------
  function bind() {
    // ícones estáticos
    document.querySelectorAll('[data-icon]').forEach(el => el.innerHTML = ICONS[el.dataset.icon] || '');
    $('#brandIcon').innerHTML = ICONS.shield;

    document.querySelectorAll('.nav-btn').forEach(b => b.onclick = () => switchView(b.dataset.view));
    document.querySelectorAll('[data-go]').forEach(b => b.onclick = () => switchView(b.dataset.go));
    document.querySelectorAll('#missionFilters .segment').forEach(b => b.onclick = () => { filter = b.dataset.filter; document.querySelectorAll('#missionFilters .segment').forEach(x => x.classList.toggle('active', x === b)); renderTables(); });

    $('#newMissionBtn').onclick = () => openNew();
    $('#resetBtn').onclick = resetData;
    $('#monitorBtn').onclick = () => setMonitor(true);
    $('#tvExit').onclick = () => setMonitor(false);
    $('#closeModal').onclick = closeModal;
    $('#cancelBtn').onclick = closeModal;
    $('#deleteBtn').onclick = deleteMission;
    $('#missionForm').onsubmit = submitForm;
    $('#missionForm').status.onchange = toggleCompletedBy;
    $('#modalBackdrop').onclick = e => { if (e.target.id === 'modalBackdrop') closeModal(); };

    $('#prevWeek').onclick = () => { calMonday = addDays(calMonday, -7); renderCalendar(); };
    $('#nextWeek').onclick = () => { calMonday = addDays(calMonday, 7); renderCalendar(); };
    $('#todayWeek').onclick = () => { calMonday = mondayOf(new Date()); renderCalendar(); };

    // delegação de cliques dinâmicos
    document.body.addEventListener('click', e => {
      const editEl = e.target.closest('[data-edit]');
      if (editEl && !e.target.closest('select')) { openEdit(Number(editEl.dataset.edit)); return; }
      const newEl = e.target.closest('[data-new]');
      if (newEl && e.detail === 2) { const [d, t] = newEl.dataset.new.split('|'); openNew(d, t); return; }
      const reopenEl = e.target.closest('[data-reopen]');
      if (reopenEl) { reopen(Number(reopenEl.dataset.reopen)); }
    });
    document.body.addEventListener('change', e => {
      const sel = e.target.closest('[data-status]');
      if (sel) changeStatus(Number(sel.dataset.status), sel.value);
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') { if ($('#modalBackdrop').classList.contains('open')) closeModal(); else if (document.body.classList.contains('monitor-mode')) setMonitor(false); }
      if (e.key.toLowerCase() === 'n' && !/input|textarea|select/i.test(e.target.tagName) && !document.body.classList.contains('monitor-mode')) openNew();
    });
    document.addEventListener('fullscreenchange', () => { if (!document.fullscreenElement && document.body.classList.contains('monitor-mode')) document.body.classList.remove('monitor-mode'); });
  }

  // ---------- init ----------
  async function init() {
    // preenche selects de pessoas
    ['#f-responsible', '#f-completed_by'].forEach(sel => {
      $(sel).innerHTML = PEOPLE.map(p => '<option value="' + esc(p) + '">' + esc(p) + '</option>').join('');
    });
    $('#omName').textContent = OM_NAME; $('#omSigla').textContent = OM_SIGLA;
    $('#omNameProfile').textContent = OM_NAME;
    $('#tvOmName').textContent = OM_NAME; $('#tvOmSigla').textContent = OM_SIGLA;
    bind();
    tick(); setInterval(tick, 1000);
    try { await loadMissions(); render(); }
    catch (err) { toast('Não foi possível carregar as missões: ' + err.message); }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
