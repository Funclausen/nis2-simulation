// NIS2 MySQL/PHP transport for hannestad.dk/private/nis2
// Replaces Firebase transport only; simulation/scoring logic stays unchanged.
(() => {
  const API = './api';
  const POLL_MS = 1500;
  const browserId = sessionStorage.getItem('nis2_sid') || ('dx' + crypto.getRandomValues(new Uint32Array(1))[0].toString(36));
  sessionStorage.setItem('nis2_sid', browserId);

  let code = new URLSearchParams(location.search).get('s') || sessionStorage.getItem('nis2_session_code') || '';
  let lastReset = null;
  let timer = null;

  async function request(path, options={}) {
    const res = await fetch(API + path, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {'Content-Type':'application/json', ...(options.headers||{})},
      ...options
    });
    const data = await res.json().catch(()=>({}));
    if (!res.ok) throw Object.assign(new Error(data.error || 'request_failed'), {status:res.status, data});
    return data;
  }

  async function poll() {
    if (!code) return;
    try {
      const data = await request('/session.php?code=' + encodeURIComponent(code), {headers:{}});
      const s = data.session;
      if (lastReset !== null && Number(s.reset_version) !== Number(lastReset) && typeof window.restart === 'function') window.restart();
      lastReset = Number(s.reset_version);
      window.dispatchEvent(new CustomEvent('nis2:session', {detail:s}));
    } catch(e) {
      window.dispatchEvent(new CustomEvent('nis2:error', {detail:e}));
    }
  }

  window.nis2Transport = {
    setSessionCode(v) {
      code = String(v||'').trim().toUpperCase();
      if (code) sessionStorage.setItem('nis2_session_code', code);
      else sessionStorage.removeItem('nis2_session_code');
      poll();
    },
    getSessionCode(){ return code; },
    getBrowserId(){ return browserId; },
    async sendAnswer(round, quality, shortText, teamName='') {
      return request('/answer.php', {
        method:'POST',
        body:JSON.stringify({code,browserId,teamName,round,quality,shortText})
      });
    },
    startPolling() {
      clearInterval(timer); poll(); timer=setInterval(poll,POLL_MS);
    },
    stopPolling(){ clearInterval(timer); timer=null; }
  };
})();
