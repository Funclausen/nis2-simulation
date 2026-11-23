// NIS2 MySQL/PHP transport for hannestad.dk/private/nis2
// Replaces Firebase transport only; simulation/scoring logic stays unchanged.
(() => {
  const API = './api';
  const POLL_MS = 1500;
  const browserId = sessionStorage.getItem('nis2_sid') || ('dx' + crypto.randomUUID());
  sessionStorage.setItem('nis2_sid', browserId);

  let code = new URLSearchParams(location.search).get('s') || sessionStorage.getItem('nis2_session_code') || '';
  let lastReset = null;
  let timer = null;
  let polling = false;

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
    if (!code || polling) return;
    polling = true;
    try {
      const data = await request('/session.php?code=' + encodeURIComponent(code), {headers:{}});
      const s = data.session;
      const resetVersion = Number(s.reset_version);
      const reset = lastReset !== null && resetVersion !== lastReset;
      lastReset = resetVersion;
      if (reset && s.status === 'active') window.restart();
      window.dispatchEvent(new CustomEvent('nis2:session', {detail:s}));
      if (reset && s.status !== 'active') window.restart();
    } catch(e) {
      window.dispatchEvent(new CustomEvent('nis2:error', {detail:e}));
    } finally {
      polling = false;
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
        body:JSON.stringify({code,browserId,teamName,round,quality,shortText,resetVersion:lastReset})
      });
    },
    startPolling() {
      clearInterval(timer); poll(); timer=setInterval(poll,POLL_MS);
    },
    stopPolling(){ clearInterval(timer); timer=null; }
  };
})();
