// Facilitator transport for the Hannestad portal session.
(() => {
  const API='./api';
  async function req(path, method='GET', body=null, csrf='') {
    const res=await fetch(API+path,{method,credentials:'same-origin',cache:'no-store',
      headers:{'Content-Type':'application/json',...(csrf?{'X-CSRF-Token':csrf}:{})},
      body:body===null?null:JSON.stringify(body)});
    const data=await res.json().catch(()=>({}));
    if(!res.ok) throw new Error(data.error||'request_failed');
    return data;
  }
  window.nis2AdminTransport={
    create:(title,expectedParticipants,csrf)=>req('/session-create.php','POST',{title,expectedParticipants},csrf),
    setState:(code,status,csrf)=>req('/session-state.php','POST',{code,status},csrf),
    setRound:(code,round,csrf)=>req('/session-round.php','POST',{code,round},csrf),
    reset:(code,csrf)=>req('/session-reset.php','POST',{code},csrf),
    answers:code=>req('/answers.php?code='+encodeURIComponent(code))
  };
})();
