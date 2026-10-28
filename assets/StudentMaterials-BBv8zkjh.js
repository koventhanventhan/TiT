import{u as L,r as i,G as T,j as t,W as _,s as I,H as A,b as D,a0 as b,Y as N,a1 as B,a2 as E,a3 as M,l as m,k as H}from"./index-CVLlLWtk.js";function G(){const y=L(),[v,j]=i.useState([]),[p,w]=i.useState([]),[k,x]=i.useState(!0),[g,u]=i.useState(null),[s,S]=i.useState("all"),[l,C]=i.useState(""),f=async()=>{x(!0),u(null);try{const e=await E();j(Array.isArray(e)?e:(e==null?void 0:e.data)||[])}catch(e){console.error("Failed to load materials:",e),u(e.message||"Failed to load study materials")}finally{x(!1)}};i.useEffect(()=>{T().then(e=>{e&&Array.isArray(e.locked_subjects)&&w(e.locked_subjects)}).catch(()=>{}),f()},[]);const z=e=>{const a=(e||"").toLowerCase(),r={color:"var(--t-primary)",bg:"var(--t-primary-soft)"};switch(a){case"video":case"recording":return{icon:t.jsx(H,{}),...r};case"pdf":return{icon:t.jsx(m,{}),...r};case"document":return{icon:t.jsx(m,{}),...r};case"link":return{icon:t.jsx(M,{}),...r};default:return{icon:t.jsx(b,{}),...r}}},F=e=>{const a=(e.type||"").toLowerCase();if(["link","recording"].includes(a)&&e.url)window.open(e.url,"_blank","noopener");else if(e.file_path)if(e.file_path.startsWith("http://")||e.file_path.startsWith("https://"))window.open(e.file_path,"_blank","noopener");else{const r="https://titjaffna.lk/laravel_api/backend/public/api".replace("/api","")||"";window.open(`${r}/api/materials/download?path=${encodeURIComponent(e.file_path)}`,"_blank")}else y.info("File not available")},W=[{id:"all",label:"All Files"},{id:"pdf",label:"PDFs"},{id:"video",label:"Videos"},{id:"recording",label:"Recordings"},{id:"document",label:"Documents"}],d=i.useRef({});i.useEffect(()=>{d.current[s]&&d.current[s].scrollIntoView({inline:"center",behavior:"smooth",block:"nearest"})},[s]);const o=l.trim().toLowerCase(),h=(v||[]).filter(e=>{const a=(e.type||"").toLowerCase();if(!(s==="all"||a===s))return!1;if(!o)return!0;const n=(e.title||"").toLowerCase(),c=(e.subject||e.group||"").toLowerCase(),R=(e.grade||"").toLowerCase();return n.includes(o)||c.includes(o)||R.includes(o)});return t.jsxs("div",{style:{paddingBottom:88},children:[t.jsxs("div",{style:{display:"flex",flexDirection:"column",gap:16,marginBottom:24},children:[t.jsxs("div",{children:[t.jsx("h1",{style:{fontSize:24,fontWeight:800,color:"var(--t-text)",margin:"0 0 4px 0",letterSpacing:"-0.5px"},children:"Study Materials"}),t.jsx("p",{style:{color:"var(--t-text-muted)",margin:0,fontSize:14},children:"Access your class notes, videos, and resources"})]}),p.length>0&&t.jsx("div",{style:{display:"flex",gap:8,flexWrap:"wrap",alignItems:"center"},children:p.map(e=>t.jsxs("span",{"data-testid":`chip-subject-locked-${e}`,style:{display:"inline-flex",alignItems:"center",gap:6,padding:"6px 14px",borderRadius:20,background:"#fef3c7",color:"#92400e",border:"1px solid #fde68a",fontSize:12,fontWeight:700},children:[t.jsx(_,{size:13})," ",e,": Locked (Top-up pending)"]},e))})]}),t.jsx("div",{style:{display:"flex",flexDirection:"column",gap:16,marginBottom:24,background:"#fff",padding:16,borderRadius:16,border:"1px solid var(--t-border)",boxShadow:"var(--t-shadow-sm)"},children:t.jsxs("div",{style:{display:"flex",flexWrap:"wrap",gap:16,alignItems:"center",justifyContent:"space-between"},children:[t.jsx("div",{className:"materials-tab-strip",role:"tablist","aria-label":"Study material categories",style:{flex:"1 1 auto",minWidth:0,maxWidth:"100%"},children:W.map(e=>{const a=s===e.id;return t.jsx("button",{ref:r=>{d.current[e.id]=r},role:"tab","aria-selected":a?"true":"false",onClick:()=>S(e.id),className:`materials-tab-pill ${a?"active":""}`,children:e.label},e.id)})}),t.jsxs("div",{className:"materials-search-wrap",children:[t.jsx(I,{style:{position:"absolute",left:12,top:14,color:"var(--t-text-muted)",pointerEvents:"none"}}),t.jsx("input",{type:"text",className:"materials-search-input",placeholder:"Search materials...",value:l,onChange:e=>C(e.target.value),style:{width:"100%",boxSizing:"border-box",minHeight:44,padding:"10px 12px 10px 36px",borderRadius:8,border:"1px solid var(--t-border)",background:"var(--t-bg)",fontSize:16,outline:"none",transition:"border-color 0.2s",color:"var(--t-text)"}})]})]})}),t.jsx("style",{children:`
                .materials-tab-strip {
                    display: flex;
                    gap: 8px;
                    overflow-x: auto;
                    scroll-snap-type: x proximity;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                    mask-image: linear-gradient(to right, black calc(100% - 24px), transparent 100%);
                    -webkit-mask-image: linear-gradient(to right, black calc(100% - 24px), transparent 100%);
                    padding: 4px 0;
                }
                .materials-tab-strip::-webkit-scrollbar {
                    display: none !important;
                    width: 0 !important;
                    height: 0 !important;
                }
                .materials-tab-pill {
                    flex: 0 0 auto;
                    min-height: 44px;
                    height: 44px;
                    padding: 0 16px;
                    border-radius: 999px;
                    white-space: nowrap;
                    font-size: 14px;
                    line-height: 1;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    box-sizing: border-box;
                    transition: background 0.2s, color 0.2s, border-color 0.2s;
                }
                .materials-tab-pill.active {
                    background: var(--t-primary-soft, #f5f3ff);
                    border: 1px solid var(--t-primary-border, #ddd6fe);
                    color: var(--t-primary, #4f0bd9);
                    font-weight: 600;
                }
                .materials-tab-pill:not(.active) {
                    background: transparent;
                    border: 1px solid transparent;
                    color: var(--t-text-muted, #64748b);
                    font-weight: 500;
                }
                .materials-search-wrap {
                    position: relative;
                    width: 220px;
                    max-width: 100%;
                }
                @media (max-width: 639px) {
                    .materials-search-wrap {
                        width: 100%;
                    }
                }
                .materials-empty-state {
                    background: #fff;
                    border-radius: 16px;
                    border: 1px dashed var(--t-border);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    box-sizing: border-box;
                    padding: 32px 16px;
                }
                @media (min-width: 640px) {
                    .materials-empty-state {
                        padding: 56px 24px;
                    }
                }
            `}),k?t.jsx("div",{style:{display:"flex",alignItems:"center",justifyContent:"center",height:200},children:t.jsx("div",{style:{width:40,height:40,border:"4px solid var(--t-border)",borderTopColor:"var(--t-primary)",borderRadius:"50%",animation:"spin 0.8s linear infinite"}})}):g?t.jsxs("div",{style:{background:"#fff",borderRadius:16,border:"1px solid var(--t-danger)",padding:"40px 20px",textAlign:"center"},children:[t.jsx(A,{size:36,style:{color:"var(--t-danger)",marginBottom:12}}),t.jsx("h3",{style:{margin:"0 0 8px",fontSize:18,fontWeight:700,color:"var(--t-danger)"},children:"Failed to load materials"}),t.jsx("p",{style:{margin:"0 0 20px",color:"var(--t-text-muted)",fontSize:14},children:g}),t.jsxs("button",{onClick:f,style:{minHeight:44,padding:"10px 20px",borderRadius:10,background:"var(--t-primary)",color:"#fff",border:"none",fontWeight:600,cursor:"pointer",display:"inline-flex",alignItems:"center",gap:8},children:[t.jsx(D,{})," Retry"]})]}):h.length===0?t.jsxs("div",{className:"materials-empty-state",children:[t.jsx("div",{style:{width:64,height:64,borderRadius:16,background:"var(--t-bg)",color:"var(--t-text-muted)",display:"flex",alignItems:"center",justifyContent:"center",fontSize:28,marginBottom:16},children:t.jsx(b,{})}),t.jsx("h3",{style:{margin:"0 0 8px 0",fontSize:18,fontWeight:700,color:"var(--t-text)"},children:"No materials found"}),t.jsx("p",{style:{margin:0,color:"var(--t-text-muted)",fontSize:14},children:l?"No materials match your search criteria.":"Try selecting a different category or check back later."})]}):t.jsx("div",{style:{display:"grid",gridTemplateColumns:"repeat(auto-fill, minmax(min(260px, 100%), 1fr))",gap:20},children:h.map(e=>{var r,n;const a=z(e.type);return t.jsxs("div",{className:"t-card",style:{background:"#fff",borderRadius:16,border:"1px solid var(--t-border)",padding:20,display:"flex",flexDirection:"column",boxShadow:"var(--t-shadow-sm)",cursor:"pointer"},children:[t.jsxs("div",{style:{display:"flex",justifyContent:"space-between",alignItems:"flex-start",marginBottom:16},children:[t.jsx("div",{style:{width:48,height:48,borderRadius:12,background:a.bg,color:a.color,display:"flex",alignItems:"center",justifyContent:"center",fontSize:24},children:a.icon}),t.jsx("div",{style:{fontSize:11,fontWeight:700,color:"var(--t-text-muted)",background:"var(--t-bg)",padding:"4px 8px",borderRadius:6,textTransform:"uppercase"},children:e.type||"File"})]}),t.jsx("h3",{style:{margin:"0 0 6px 0",fontSize:16,fontWeight:700,color:"var(--t-text)",lineHeight:1.3,display:"-webkit-box",WebkitLineClamp:2,WebkitBoxOrient:"vertical",overflow:"hidden"},children:e.title}),t.jsx("p",{style:{margin:"0 0 4px 0",color:"var(--t-text-muted)",fontSize:13,fontWeight:500},children:e.group||e.subject||"General Material"}),t.jsxs("p",{style:{margin:"0 0 16px 0",color:"var(--t-text-muted)",fontSize:12},children:["Grade: ",e.grade||"All"]}),t.jsxs("div",{style:{marginTop:"auto",paddingTop:16,borderTop:"1px solid var(--t-border)",display:"flex",justifyContent:"space-between",alignItems:"center"},children:[t.jsxs("div",{style:{fontSize:12,color:"var(--t-text-muted)",fontWeight:500},children:[new Date(e.created_at||e.date||Date.now()).toLocaleDateString(void 0,{month:"short",day:"numeric",year:"numeric"}),e.file_size&&e.file_size!=="--"&&t.jsxs(t.Fragment,{children:[t.jsx("span",{style:{margin:"0 6px"},children:"•"}),e.file_size]})]}),t.jsx("button",{onClick:c=>{c.stopPropagation(),F(e)},title:((r=e.type)==null?void 0:r.toLowerCase())==="recording"?"Watch recording":void 0,"aria-label":"Download material",style:{width:44,height:44,borderRadius:8,background:"var(--t-bg)",border:"1px solid var(--t-border)",display:"flex",alignItems:"center",justifyContent:"center",color:"var(--t-text-muted)",cursor:"pointer",transition:"opacity 0.2s, transform 0.1s"},children:["link","recording"].includes((n=e.type)==null?void 0:n.toLowerCase())?t.jsx(N,{}):t.jsx(B,{})})]})]},e.id)})})]})}export{G as default};
