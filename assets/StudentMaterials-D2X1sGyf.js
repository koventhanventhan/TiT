import{u as R,r as i,j as t,s as W,H as T,b as L,_ as g,W as _,$ as D,a0 as I,a1 as A,l as b,k as B}from"./index-CViz80YL.js";function N(){const m=R(),[y,v]=i.useState([]),[j,p]=i.useState(!0),[x,u]=i.useState(null),[o,w]=i.useState("all"),[l,k]=i.useState(""),f=async()=>{p(!0),u(null);try{const e=await I();v(Array.isArray(e)?e:(e==null?void 0:e.data)||[])}catch(e){console.error("Failed to load materials:",e),u(e.message||"Failed to load study materials")}finally{p(!1)}};i.useEffect(()=>{f()},[]);const S=e=>{const a=(e||"").toLowerCase(),r={color:"var(--t-primary)",bg:"var(--t-primary-soft)"};switch(a){case"video":case"recording":return{icon:t.jsx(B,{}),...r};case"pdf":return{icon:t.jsx(b,{}),...r};case"document":return{icon:t.jsx(b,{}),...r};case"link":return{icon:t.jsx(A,{}),...r};default:return{icon:t.jsx(g,{}),...r}}},C=e=>{const a=(e.type||"").toLowerCase();if(["link","recording"].includes(a)&&e.url)window.open(e.url,"_blank","noopener");else if(e.file_path)if(e.file_path.startsWith("http://")||e.file_path.startsWith("https://"))window.open(e.file_path,"_blank","noopener");else{const r="https://titjaffna.lk/laravel_api/backend/public/api".replace("/api","")||"";window.open(`${r}/api/materials/download?path=${encodeURIComponent(e.file_path)}`,"_blank")}else m.info("File not available")},z=[{id:"all",label:"All Files"},{id:"pdf",label:"PDFs"},{id:"video",label:"Videos"},{id:"recording",label:"Recordings"},{id:"document",label:"Documents"}],d=i.useRef({});i.useEffect(()=>{d.current[o]&&d.current[o].scrollIntoView({inline:"center",behavior:"smooth",block:"nearest"})},[o]);const s=l.trim().toLowerCase(),h=(y||[]).filter(e=>{const a=(e.type||"").toLowerCase();if(!(o==="all"||a===o))return!1;if(!s)return!0;const n=(e.title||"").toLowerCase(),c=(e.subject||e.group||"").toLowerCase(),F=(e.grade||"").toLowerCase();return n.includes(s)||c.includes(s)||F.includes(s)});return t.jsxs("div",{style:{paddingBottom:40},children:[t.jsx("div",{style:{display:"flex",flexDirection:"column",gap:16,marginBottom:24},children:t.jsxs("div",{children:[t.jsx("h1",{style:{fontSize:24,fontWeight:800,color:"var(--t-text)",margin:"0 0 4px 0",letterSpacing:"-0.5px"},children:"Study Materials"}),t.jsx("p",{style:{color:"var(--t-text-muted)",margin:0,fontSize:14},children:"Access your class notes, videos, and resources"})]})}),t.jsx("div",{style:{display:"flex",flexDirection:"column",gap:16,marginBottom:24,background:"#fff",padding:16,borderRadius:16,border:"1px solid var(--t-border)",boxShadow:"var(--t-shadow-sm)"},children:t.jsxs("div",{style:{display:"flex",flexWrap:"wrap",gap:16,alignItems:"center",justifyContent:"space-between"},children:[t.jsx("div",{className:"materials-tab-strip",style:{flex:"1 1 auto",minWidth:0,maxWidth:"100%"},children:z.map(e=>{const a=o===e.id;return t.jsx("button",{ref:r=>{d.current[e.id]=r},onClick:()=>w(e.id),className:`materials-tab-pill ${a?"active":""}`,children:e.label},e.id)})}),t.jsxs("div",{className:"materials-search-wrap",children:[t.jsx(W,{style:{position:"absolute",left:12,top:14,color:"var(--t-text-muted)",pointerEvents:"none"}}),t.jsx("input",{type:"text",className:"materials-search-input",placeholder:"Search materials...",value:l,onChange:e=>k(e.target.value),style:{width:"100%",boxSizing:"border-box",minHeight:44,padding:"10px 12px 10px 36px",borderRadius:8,border:"1px solid var(--t-border)",background:"var(--t-bg)",fontSize:16,outline:"none",transition:"border-color 0.2s",color:"var(--t-text)"}})]})]})}),t.jsx("style",{children:`
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
                    padding: 2px 0;
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
            `}),j?t.jsx("div",{style:{display:"flex",alignItems:"center",justifyContent:"center",height:200},children:t.jsx("div",{style:{width:40,height:40,border:"4px solid var(--t-border)",borderTopColor:"var(--t-primary)",borderRadius:"50%",animation:"spin 0.8s linear infinite"}})}):x?t.jsxs("div",{style:{background:"#fff",borderRadius:16,border:"1px solid var(--t-danger)",padding:"40px 20px",textAlign:"center"},children:[t.jsx(T,{size:36,style:{color:"var(--t-danger)",marginBottom:12}}),t.jsx("h3",{style:{margin:"0 0 8px",fontSize:18,fontWeight:700,color:"var(--t-danger)"},children:"Failed to load materials"}),t.jsx("p",{style:{margin:"0 0 20px",color:"var(--t-text-muted)",fontSize:14},children:x}),t.jsxs("button",{onClick:f,style:{minHeight:44,padding:"10px 20px",borderRadius:10,background:"var(--t-primary)",color:"#fff",border:"none",fontWeight:600,cursor:"pointer",display:"inline-flex",alignItems:"center",gap:8},children:[t.jsx(L,{})," Retry"]})]}):h.length===0?t.jsxs("div",{style:{background:"#fff",borderRadius:16,border:"1px dashed var(--t-border)",padding:"60px 20px",display:"flex",flexDirection:"column",alignItems:"center",justifyContent:"center",textAlign:"center"},children:[t.jsx("div",{style:{width:64,height:64,borderRadius:16,background:"var(--t-bg)",color:"var(--t-text-muted)",display:"flex",alignItems:"center",justifyContent:"center",fontSize:28,marginBottom:16},children:t.jsx(g,{})}),t.jsx("h3",{style:{margin:"0 0 8px 0",fontSize:18,fontWeight:700,color:"var(--t-text)"},children:"No materials found"}),t.jsx("p",{style:{margin:0,color:"var(--t-text-muted)",fontSize:14},children:l?"No materials match your search criteria.":"Try selecting a different category or check back later."})]}):t.jsx("div",{style:{display:"grid",gridTemplateColumns:"repeat(auto-fill, minmax(min(260px, 100%), 1fr))",gap:20},children:h.map(e=>{var r,n;const a=S(e.type);return t.jsxs("div",{className:"t-card",style:{background:"#fff",borderRadius:16,border:"1px solid var(--t-border)",padding:20,display:"flex",flexDirection:"column",boxShadow:"var(--t-shadow-sm)",cursor:"pointer"},children:[t.jsxs("div",{style:{display:"flex",justifyContent:"space-between",alignItems:"flex-start",marginBottom:16},children:[t.jsx("div",{style:{width:48,height:48,borderRadius:12,background:a.bg,color:a.color,display:"flex",alignItems:"center",justifyContent:"center",fontSize:24},children:a.icon}),t.jsx("div",{style:{fontSize:11,fontWeight:700,color:"var(--t-text-muted)",background:"var(--t-bg)",padding:"4px 8px",borderRadius:6,textTransform:"uppercase"},children:e.type||"File"})]}),t.jsx("h3",{style:{margin:"0 0 6px 0",fontSize:16,fontWeight:700,color:"var(--t-text)",lineHeight:1.3,display:"-webkit-box",WebkitLineClamp:2,WebkitBoxOrient:"vertical",overflow:"hidden"},children:e.title}),t.jsx("p",{style:{margin:"0 0 4px 0",color:"var(--t-text-muted)",fontSize:13,fontWeight:500},children:e.group||e.subject||"General Material"}),t.jsxs("p",{style:{margin:"0 0 16px 0",color:"var(--t-text-muted)",fontSize:12},children:["Grade: ",e.grade||"All"]}),t.jsxs("div",{style:{marginTop:"auto",paddingTop:16,borderTop:"1px solid var(--t-border)",display:"flex",justifyContent:"space-between",alignItems:"center"},children:[t.jsxs("div",{style:{fontSize:12,color:"var(--t-text-muted)",fontWeight:500},children:[new Date(e.created_at||e.date||Date.now()).toLocaleDateString(void 0,{month:"short",day:"numeric",year:"numeric"}),e.file_size&&e.file_size!=="--"&&t.jsxs(t.Fragment,{children:[t.jsx("span",{style:{margin:"0 6px"},children:"•"}),e.file_size]})]}),t.jsx("button",{onClick:c=>{c.stopPropagation(),C(e)},title:((r=e.type)==null?void 0:r.toLowerCase())==="recording"?"Watch recording":void 0,"aria-label":"Download material",style:{width:44,height:44,borderRadius:8,background:"var(--t-bg)",border:"1px solid var(--t-border)",display:"flex",alignItems:"center",justifyContent:"center",color:"var(--t-text-muted)",cursor:"pointer",transition:"opacity 0.2s, transform 0.1s"},children:["link","recording"].includes((n=e.type)==null?void 0:n.toLowerCase())?t.jsx(_,{}):t.jsx(D,{})})]})]},e.id)})})]})}export{N as default};
