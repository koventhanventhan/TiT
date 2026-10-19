import{p as c,i as e,x as g,e as D,a0 as m,m as T,T as N,F as R}from"./index-CxY-ORCu.js";import{f as _}from"./useJoinClass-2Ggo_szr-Drfbtj-j.js";function L(){const[y,b]=c.useState([]),[v,j]=c.useState(!0),{joinClass:w,isJoining:S}=_();c.useEffect(()=>{async function r(){try{let i=await R().catch(()=>null);(!i||i.length===0)&&(i=[]);const t=Array.isArray(i)?i:i.data||[];t.sort((n,s)=>new Date(n.scheduled_at||n.start_time)-new Date(s.scheduled_at||s.start_time)),b(t)}catch(i){console.error("Error loading schedule:",i)}finally{j(!1)}}r()},[]);const p=y.reduce((r,i)=>{const t=new Date(i.scheduled_at||i.start_time).toLocaleDateString("en-US",{weekday:"long",year:"numeric",month:"long",day:"numeric"});return r[t]||(r[t]=[]),r[t].push(i),r},{});return e.jsxs("div",{style:{paddingBottom:40},children:[e.jsx("div",{style:{display:"flex",flexDirection:"column",gap:16,marginBottom:24},children:e.jsxs("div",{children:[e.jsx("h1",{style:{fontSize:24,fontWeight:800,color:"var(--t-text)",margin:"0 0 4px 0",letterSpacing:"-0.5px"},children:"My Schedule"}),e.jsx("p",{style:{color:"var(--t-text-muted)",margin:0,fontSize:14},children:"View your upcoming physical and online classes"})]})}),v?e.jsx("div",{style:{display:"flex",alignItems:"center",justifyContent:"center",height:200},children:e.jsx("div",{style:{width:40,height:40,border:"4px solid var(--t-border)",borderTopColor:"var(--t-primary)",borderRadius:"50%",animation:"spin 0.8s linear infinite"}})}):Object.keys(p).length===0?e.jsxs("div",{style:{background:"#fff",borderRadius:16,border:"1px dashed var(--t-border)",padding:"60px 20px",display:"flex",flexDirection:"column",alignItems:"center",justifyContent:"center",textAlign:"center"},children:[e.jsx("div",{style:{width:64,height:64,borderRadius:16,background:"var(--t-bg)",color:"var(--t-text-muted)",display:"flex",alignItems:"center",justifyContent:"center",fontSize:28,marginBottom:16},children:e.jsx(g,{})}),e.jsx("h3",{style:{margin:"0 0 8px 0",fontSize:18,fontWeight:700,color:"var(--t-text)"},children:"No upcoming classes"}),e.jsx("p",{style:{margin:"0 0 20px 0",color:"var(--t-text-muted)",fontSize:14},children:"You don't have any classes scheduled in the near future."})]}):e.jsx("div",{className:"schedule schedule-container",style:{display:"flex",flexDirection:"column",gap:32},children:Object.entries(p).map(([r,i])=>e.jsxs("div",{children:[e.jsxs("h3",{style:{fontSize:16,color:"var(--t-text)",fontWeight:800,marginBottom:16,display:"flex",alignItems:"center",gap:10},children:[e.jsx("span",{style:{width:32,height:32,borderRadius:8,background:"var(--t-primary-soft)",color:"var(--t-primary)",display:"flex",alignItems:"center",justifyContent:"center",fontSize:16},children:e.jsx(g,{})}),r]}),e.jsx("div",{style:{display:"flex",flexDirection:"column",gap:14},children:i.map(t=>{const n=new Date(t.scheduled_at||t.start_time),s=new Date,x=t.duration||60,h=n.getTime()+x*6e4,l=h<=s.getTime(),o=n.getTime()<=s.getTime()&&!l,k=!l&&s.getTime()>=n.getTime()-10*60*1e3,a=t.type==="online"||!!(t.zoom_link||t.join_url),u=!!(t.zoom_link||t.join_url),d=S(t.id),f=n.toLocaleTimeString("en-US",{hour:"numeric",minute:"2-digit",hour12:!0}),z=new Date(h).toLocaleTimeString("en-US",{hour:"numeric",minute:"2-digit",hour12:!0}),W=`${f} – ${z}`,C=n.toLocaleDateString("en-US",{weekday:"short"}),I=n.getDate();return e.jsxs("div",{className:`schedule-card t-card ${o?"is-live":""} ${l?"is-ended":""}`,children:[e.jsxs("div",{className:"schedule-side-chip",children:[e.jsx("span",{style:{fontSize:11,fontWeight:700,textTransform:"uppercase",opacity:.85},children:C}),e.jsx("span",{style:{fontSize:20,fontWeight:800,lineHeight:1.1},children:I})]}),e.jsxs("div",{className:"schedule-content-wrap",style:{display:"flex",flexDirection:"column",gap:10},children:[e.jsxs("div",{style:{display:"flex",alignItems:"flex-start",justifyContent:"space-between",gap:10},children:[e.jsxs("div",{style:{flex:1,minWidth:0},children:[e.jsxs("div",{style:{display:"flex",alignItems:"center",gap:6,marginBottom:4,flexWrap:"wrap"},children:[e.jsx("span",{style:{padding:"2px 8px",borderRadius:6,background:"var(--t-bg)",color:"var(--t-text-muted)",fontSize:11,fontWeight:700,textTransform:"uppercase",whiteSpace:"nowrap"},children:t.subject||"Subject"}),e.jsxs("span",{style:{padding:"2px 8px",borderRadius:6,background:a?"var(--t-primary-soft)":"var(--t-success-bg)",color:a?"var(--t-primary)":"var(--t-success)",fontSize:11,fontWeight:700,textTransform:"uppercase",display:"inline-flex",alignItems:"center",gap:4,whiteSpace:"nowrap"},children:[a?e.jsx(D,{}):e.jsx(m,{})," ",a?"Online":"Physical"]})]}),e.jsx("h4",{style:{margin:0,fontSize:16,fontWeight:700,color:"var(--t-text)",lineHeight:1.3,display:"-webkit-box",WebkitLineClamp:2,WebkitBoxOrient:"vertical",overflow:"hidden"},children:t.title||"Class Session"})]}),e.jsx("div",{style:{flexShrink:0,whiteSpace:"nowrap"},children:l?e.jsx("span",{className:"schedule-status-badge",style:{padding:"4px 10px",borderRadius:8,background:"var(--t-bg)",color:"var(--t-text-muted)",fontSize:11,fontWeight:700,textTransform:"uppercase",whiteSpace:"nowrap",display:"inline-block"},children:"ENDED"}):o?e.jsxs("span",{className:"schedule-status-badge",style:{padding:"4px 10px",borderRadius:8,background:"var(--t-danger, #ef4444)",color:"#ffffff",fontSize:11,fontWeight:800,textTransform:"uppercase",letterSpacing:"0.5px",whiteSpace:"nowrap",display:"inline-flex",alignItems:"center",gap:5},children:[e.jsx("span",{style:{width:6,height:6,borderRadius:"50%",background:"#fff"}}),"LIVE NOW"]}):e.jsx("span",{className:"schedule-status-badge",style:{padding:"4px 10px",borderRadius:8,background:"var(--t-primary-soft)",color:"var(--t-primary)",fontSize:11,fontWeight:700,textTransform:"uppercase",whiteSpace:"nowrap",display:"inline-block"},children:"UPCOMING"})})]}),e.jsxs("div",{style:{display:"flex",flexDirection:"column",gap:4},children:[e.jsxs("div",{style:{display:"flex",alignItems:"center",gap:6,color:"var(--t-text)",fontSize:14,fontWeight:600},children:[e.jsx(T,{style:{color:"var(--t-primary)",flexShrink:0}}),e.jsx("time",{className:"schedule-time-text",style:{whiteSpace:"nowrap",fontVariantNumeric:"tabular-nums",lineHeight:1.2},children:W}),e.jsxs("span",{style:{color:"var(--t-text-muted)",fontSize:12,fontWeight:500,whiteSpace:"nowrap"},children:["(",x," mins)"]})]}),e.jsxs("div",{style:{display:"flex",alignItems:"center",gap:10,color:"var(--t-text-muted)",fontSize:13,flexWrap:"wrap"},children:[t.teacher&&e.jsxs("span",{children:["Instructor: ",e.jsx("strong",{style:{color:"var(--t-text)"},children:t.teacher})]}),!a&&t.location&&e.jsxs("span",{style:{display:"inline-flex",alignItems:"center",gap:4},children:[e.jsx(m,{})," ",t.location]})]})]})]}),e.jsx("div",{className:"schedule-action-wrap",children:l?e.jsx("button",{type:"button",disabled:!0,style:{width:"100%",minHeight:48,borderRadius:10,background:"var(--t-bg)",border:"1px solid var(--t-border)",color:"var(--t-text-muted)",fontWeight:600,fontSize:13,cursor:"not-allowed",display:"flex",alignItems:"center",justifyContent:"center"},children:"Class Ended"}):a?k?e.jsxs("button",{type:"button",className:"schedule-join-btn",onClick:()=>w(t),disabled:d||!u,style:{width:"100%",minHeight:48,borderRadius:10,background:o?"var(--t-primary, #4f0bd9)":"var(--t-primary)",color:"#ffffff",border:"none",fontWeight:700,fontSize:14,cursor:d||!u?"not-allowed":"pointer",display:"flex",alignItems:"center",justifyContent:"center",gap:8,boxShadow:o?"0 4px 14px rgba(79, 11, 217, 0.25)":"none",opacity:d?.7:1,transition:"all 0.15s ease"},children:[e.jsx(N,{})," ",d?"Joining Class...":o?"Join Live Class":"Join Class"]}):e.jsxs("button",{type:"button",disabled:!0,style:{width:"100%",minHeight:48,borderRadius:10,background:"var(--t-bg)",border:"1px solid var(--t-border)",color:"var(--t-text-muted)",fontWeight:600,fontSize:13,cursor:"not-allowed",display:"flex",alignItems:"center",justifyContent:"center"},children:["Starts at ",f]}):e.jsx("div",{style:{minHeight:48,borderRadius:10,background:"var(--t-bg)",border:"1px solid var(--t-border)",display:"flex",alignItems:"center",justifyContent:"center",color:"var(--t-text-muted)",fontSize:13,fontWeight:600,padding:"0 12px",textAlign:"center"},children:"Physical Class"})})]},t.id)})})]},r))}),e.jsx("style",{children:`
                .schedule-card {
                    display: flex;
                    flex-direction: column;
                    gap: 14px;
                    background: #ffffff;
                    border-radius: 16px;
                    padding: 16px;
                    border: 1px solid var(--t-border);
                    box-shadow: var(--t-shadow-sm);
                    box-sizing: border-box;
                    width: 100%;
                    overflow: hidden;
                    transition: border-color 0.2s, box-shadow 0.2s;
                }
                .schedule-card.is-live {
                    border-color: var(--t-primary-border);
                    box-shadow: 0 4px 16px rgba(79, 11, 217, 0.12);
                }
                .schedule-card.is-ended {
                    opacity: 0.65;
                }
                .schedule-side-chip {
                    display: none;
                }
                .schedule-action-wrap {
                    width: 100%;
                    margin-top: 4px;
                }
                @media (min-width: 640px) {
                    .schedule-card {
                        flex-direction: row;
                        align-items: center;
                        gap: 16px;
                        padding: 16px 20px;
                    }
                    .schedule-side-chip {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        min-width: 56px;
                        padding: 10px 8px;
                        border-radius: 12px;
                        background: var(--t-bg);
                        border: 1px solid var(--t-border);
                        color: var(--t-text);
                        flex-shrink: 0;
                    }
                    .schedule-card.is-live .schedule-side-chip {
                        background: var(--t-primary);
                        color: #ffffff;
                        border-color: var(--t-primary);
                    }
                    .schedule-content-wrap {
                        flex: 1;
                        min-width: 0;
                    }
                    .schedule-action-wrap {
                        width: auto;
                        min-width: 160px;
                        margin-top: 0;
                        flex-shrink: 0;
                    }
                }
            `})]})}export{L as default};
