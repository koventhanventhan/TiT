import{C as Z,y as Q,f as s,s as e,U as Y,i as ee,aB as te,aZ as re,Y as S,Q as B,_ as H,ad as F,J as G,a_ as ie,a1 as ae,o as le,h as se,l as ne,j as oe,as as M,m as U,V as de,a$ as ce,b0 as pe,b1 as xe}from"./index-aVv9ynXX.js";function ue(){const l=Z(),{t:n}=Q(),[$,f]=s.useState([]),[O,z]=s.useState(!0),[y,C]=s.useState("grid"),[x,K]=s.useState("all"),[_,V]=s.useState(""),[E,b]=s.useState(!1),[i,c]=s.useState({title:"",type:"note",subject:"",grade:"",medium:"english",url:""}),[W,I]=s.useState(null),[g,m]=s.useState(!1),[v,R]=s.useState(null),[N,T]=s.useState(null),j=async()=>{z(!0);try{const t=await ie();f(Array.isArray(t)?t:[])}catch(t){console.error(t),f([])}finally{z(!1)}};s.useEffect(()=>{j()},[]);const D=t=>{let r=e.jsx(S,{});switch((t||"").toLowerCase()){case"video":r=e.jsx(se,{});break;case"note":case"past_paper":case"pdf":case"document":r=e.jsx(le,{});break;case"link":r=e.jsx(ae,{});break;default:r=e.jsx(S,{});break}return{icon:r,color:"var(--t-primary)",bg:"var(--t-primary-soft)"}},J=[{id:"all",label:"All Files"},{id:"note",label:"Notes"},{id:"past_paper",label:"Past Papers"},{id:"pdf",label:"PDFs"},{id:"document",label:"Documents"},{id:"video",label:"Videos"},{id:"link",label:"Links"}],w=s.useRef({});s.useEffect(()=>{w.current[x]&&w.current[x].scrollIntoView({inline:"center",behavior:"smooth",block:"nearest"})},[x]);const k=($||[]).filter(t=>{const r=x==="all"||t.type&&t.type.toLowerCase()===x,a=_.toLowerCase(),o=t.title?t.title.toLowerCase().includes(a):!1,h=t.subject?t.subject.toLowerCase().includes(a):!1,d=t.grade?t.grade.toLowerCase().includes(a):!1;return r&&(!a||o||h||d)}),u=t=>{const r=(t||"").toLowerCase();return["note","past_paper","paper","pdf","document"].includes(r)},X=async t=>{if(t.preventDefault(),!g){if(!i.title.trim())return l.info("Title is required");if(u(i.type)){if(!i.grade.trim())return l.info("Grade is required for study materials");if(!i.subject.trim())return l.info("Subject is required for study materials");if(!i.medium)return l.info("Medium is required for study materials")}m(!0);try{const r=new FormData;if(r.append("title",i.title.trim()),r.append("type",i.type),i.subject&&r.append("subject",i.subject.trim()),i.grade&&r.append("grade",i.grade.trim()),i.medium&&r.append("medium",i.medium),i.type.toLowerCase()==="link"){if(!i.url.trim())return m(!1),l.info("URL is required for link type");r.append("url",i.url.trim())}else{if(!W)return m(!1),l.info("File is required");r.append("file",W)}const a=await pe(r);a&&a.warning?l.warning(a.warning):l.success("Material uploaded successfully"),b(!1),c({title:"",type:"note",subject:"",grade:"",medium:"english",url:""}),I(null),j()}catch(r){console.error(r),l.error(r.message||"Error uploading material")}finally{m(!1)}}},L=async t=>{if(!N){T(t);try{await xe(t),l.success("Re-indexing scheduled"),f(r=>r.map(a=>a.id===t?{...a,rag_status:"pending",rag_error:null}:a))}catch(r){console.error(r),l.error(r.message||"Failed to retry indexing")}finally{T(null)}}},P=t=>{var r;if(((r=t.type)==null?void 0:r.toLowerCase())==="link"&&t.url)window.open(t.url,"_blank");else if(t.file_path)if(t.file_path.startsWith("https://")||t.file_path.startsWith("http://"))window.open(t.file_path,"_blank");else{const a="https://titjaffna.lk/laravel_api/backend/public/api".replace("/api","")||"";window.open(`${a}/api/materials/download?path=${encodeURIComponent(t.file_path)}`,"_blank")}else l.info("File not available")},q=async t=>{if(!v&&window.confirm(`Are you sure you want to delete "${t.title}"?`)){R(t.id);try{await ce(t.id),l.success("Deleted successfully"),j()}catch(r){console.error(r),l.error(r.message||"Failed to delete material")}finally{R(null)}}},A=t=>{const r=(t.rag_status||"").toLowerCase(),a=N===t.id;return r==="ready"?e.jsx("div",{style:{display:"flex",alignItems:"center",gap:6,marginTop:8},children:e.jsxs("span",{style:{display:"inline-flex",alignItems:"center",gap:4,padding:"3px 8px",borderRadius:6,fontSize:12,fontWeight:600,background:"rgba(16, 185, 129, 0.15)",color:"#059669",border:"1px solid rgba(16, 185, 129, 0.3)"},children:[e.jsx(ne,{size:12})," ",n("mat_status_ready","Ready")]})}):r==="processing"||r==="pending"?e.jsx("div",{style:{display:"flex",alignItems:"center",gap:6,marginTop:8},children:e.jsxs("span",{style:{display:"inline-flex",alignItems:"center",gap:4,padding:"3px 8px",borderRadius:6,fontSize:12,fontWeight:600,background:"rgba(59, 130, 246, 0.15)",color:"#2563eb",border:"1px solid rgba(59, 130, 246, 0.3)"},children:[e.jsx(oe,{size:12})," ",n("mat_status_processing","Processing")]})}):r==="low_quality"||r==="quality_pending"?e.jsxs("div",{style:{marginTop:8},children:[e.jsxs("div",{style:{display:"flex",alignItems:"center",gap:6},children:[e.jsxs("span",{style:{display:"inline-flex",alignItems:"center",gap:4,padding:"3px 8px",borderRadius:6,fontSize:12,fontWeight:600,background:"rgba(245, 158, 11, 0.15)",color:"#d97706",border:"1px solid rgba(245, 158, 11, 0.3)"},children:[e.jsx(M,{size:12})," ",n("mat_status_low_quality","Needs clearer file")]}),e.jsxs("button",{type:"button",onClick:()=>L(t.id),disabled:a,title:n("mat_action_retry","Retry indexing"),style:{display:"inline-flex",alignItems:"center",gap:4,padding:"3px 8px",borderRadius:6,fontSize:11,fontWeight:600,background:"var(--t-surface)",border:"1px solid var(--t-border)",color:"var(--t-text)",cursor:a?"not-allowed":"pointer",opacity:a?.6:1},children:[e.jsx(U,{size:11,className:a?"spin":""}),n("mat_action_retry","Retry")]})]}),e.jsx("div",{style:{fontSize:11,color:"#d97706",marginTop:4,lineHeight:1.3},children:n("mat_status_low_quality_hint","The text could not be read well. Please upload a clearer PDF or typed text.")})]}):r==="failed"?e.jsxs("div",{style:{marginTop:8},children:[e.jsxs("div",{style:{display:"flex",alignItems:"center",gap:6},children:[e.jsxs("span",{style:{display:"inline-flex",alignItems:"center",gap:4,padding:"3px 8px",borderRadius:6,fontSize:12,fontWeight:600,background:"rgba(239, 68, 68, 0.15)",color:"#dc2626",border:"1px solid rgba(239, 68, 68, 0.3)"},children:[e.jsx(G,{size:12})," ",n("mat_status_failed","Failed")]}),e.jsxs("button",{type:"button",onClick:()=>L(t.id),disabled:a,title:n("mat_action_retry","Retry indexing"),style:{display:"inline-flex",alignItems:"center",gap:4,padding:"3px 8px",borderRadius:6,fontSize:11,fontWeight:600,background:"var(--t-surface)",border:"1px solid var(--t-border)",color:"var(--t-text)",cursor:a?"not-allowed":"pointer",opacity:a?.6:1},children:[e.jsx(U,{size:11,className:a?"spin":""}),n("mat_action_retry","Retry")]})]}),t.rag_error&&e.jsx("div",{style:{fontSize:11,color:"#dc2626",marginTop:4,lineHeight:1.3},children:t.rag_error})]}):r==="unsupported"?e.jsxs("div",{style:{marginTop:8},children:[e.jsxs("span",{style:{display:"inline-flex",alignItems:"center",gap:4,padding:"3px 8px",borderRadius:6,fontSize:12,fontWeight:600,background:"rgba(239, 68, 68, 0.15)",color:"#dc2626",border:"1px solid rgba(239, 68, 68, 0.3)"},children:[e.jsx(M,{size:12})," ",n("mat_status_unsupported","Unsupported format")]}),e.jsx("div",{style:{fontSize:11,color:"#dc2626",marginTop:4,lineHeight:1.3},children:t.rag_error||"Legacy file format is unsupported, please upload PDF or DOCX."})]}):e.jsx("div",{style:{display:"flex",alignItems:"center",gap:6,marginTop:8},children:e.jsxs("span",{style:{display:"inline-flex",alignItems:"center",gap:4,padding:"3px 8px",borderRadius:6,fontSize:12,fontWeight:600,background:"rgba(148, 163, 184, 0.15)",color:"#64748b",border:"1px solid rgba(148, 163, 184, 0.3)"},children:[e.jsx(de,{size:12})," ",n("mat_status_not_included","Not included")]})})},p={width:"100%",padding:"10px 14px",borderRadius:8,border:"1px solid var(--t-border)",outline:"none",fontSize:14,background:"var(--t-bg)",boxSizing:"border-box",color:"var(--t-text)"};return e.jsxs("div",{style:{paddingBottom:88},children:[e.jsxs("div",{className:"teacher-page-header",children:[e.jsxs("div",{children:[e.jsx("h1",{style:{fontSize:24,fontWeight:800,color:"var(--t-text)",margin:"0 0 4px 0",letterSpacing:"-0.5px"},children:"Teaching Materials"}),e.jsx("p",{style:{color:"var(--t-text-3)",margin:0,fontSize:14},children:"Manage and share resources with your students and study assistant"})]}),e.jsxs("button",{className:"t-btn",id:"teacher-upload-material-btn",style:{display:"flex",alignItems:"center",gap:8,padding:"10px 20px",minHeight:44,background:"var(--t-primary)",color:"#fff",border:"none",borderRadius:12,fontWeight:600,fontSize:14,cursor:"pointer",width:"fit-content"},onClick:()=>b(!0),children:[e.jsx(Y,{style:{fontSize:18}})," Upload Material"]})]}),e.jsxs("div",{className:"materials-toolbar-card",children:[e.jsx("div",{className:"materials-tab-strip",role:"tablist","aria-label":"Teaching material categories",children:J.map(t=>{const r=x===t.id;return e.jsx("button",{ref:a=>{w.current[t.id]=a},role:"tab","aria-selected":r?"true":"false",className:`materials-tab-pill ${r?"active":""}`,onClick:()=>K(t.id),children:t.label},t.id)})}),e.jsxs("div",{className:"materials-controls-row",children:[e.jsxs("div",{className:"materials-search-wrap",children:[e.jsx(ee,{className:"materials-search-icon"}),e.jsx("input",{type:"text",className:"materials-search-input",placeholder:"Search materials...",value:_,onChange:t=>V(t.target.value)})]}),e.jsxs("div",{className:"materials-view-toggle",children:[e.jsx("button",{type:"button",className:`t-icon-btn ${y==="grid"?"active":""}`,onClick:()=>C("grid"),"aria-label":"Grid view",children:e.jsx(te,{size:18})}),e.jsx("button",{type:"button",className:`t-icon-btn ${y==="list"?"active":""}`,onClick:()=>C("list"),"aria-label":"List view",children:e.jsx(re,{size:18})})]})]})]}),e.jsx("style",{children:`
                .materials-toolbar-card {
                    display: flex;
                    flex-direction: column;
                    gap: 16px;
                    margin-bottom: 24px;
                    background: var(--t-surface);
                    padding: 16px;
                    border-radius: 16px;
                    border: 1px solid var(--t-border);
                    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
                }
                @media (min-width: 1024px) {
                    .materials-toolbar-card {
                        flex-direction: row;
                        align-items: center;
                        justify-content: space-between;
                    }
                }
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
                    flex: 1 1 auto;
                    min-width: 0;
                    max-width: 100%;
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
                .materials-controls-row {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    width: 100%;
                    box-sizing: border-box;
                }
                @media (min-width: 1024px) {
                    .materials-controls-row {
                        width: auto;
                        justify-content: flex-end;
                        flex: 0 0 auto;
                    }
                }
                .materials-search-wrap {
                    position: relative;
                    flex: 1 1 auto;
                    min-width: 0;
                    height: 44px;
                }
                @media (min-width: 1024px) {
                    .materials-search-wrap {
                        width: 260px;
                        flex: 0 0 260px;
                    }
                }
                .materials-search-icon {
                    position: absolute;
                    left: 14px;
                    top: 14px;
                    color: var(--t-text-3, #94a3b8);
                    pointer-events: none;
                }
                .materials-search-input {
                    width: 100%;
                    height: 44px;
                    min-height: 44px;
                    box-sizing: border-box;
                    padding: 10px 14px 10px 38px;
                    border-radius: 10px;
                    border: 1px solid var(--t-border);
                    background: var(--t-bg);
                    font-size: 14px;
                    outline: none;
                    color: var(--t-text);
                    transition: border-color 0.2s;
                }
                .materials-search-input:focus {
                    border-color: var(--t-primary, #4f0bd9);
                }
                .materials-view-toggle {
                    display: inline-flex;
                    align-items: center;
                    height: 44px;
                    min-height: 44px;
                    background: var(--t-bg);
                    border-radius: 10px;
                    padding: 2px;
                    border: 1px solid var(--t-border);
                    box-sizing: border-box;
                    flex-shrink: 0;
                    gap: 2px;
                }
                .materials-view-toggle button {
                    width: 40px;
                    height: 40px;
                    min-width: 40px;
                    min-height: 40px;
                    border: none;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    background: transparent;
                    color: var(--t-text-3, #94a3b8);
                    transition: background 0.15s, color 0.15s;
                    box-sizing: border-box;
                }
                .materials-view-toggle button.active {
                    background: var(--t-surface);
                    color: var(--t-primary, #4f0bd9);
                    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
                }
                .materials-empty-state {
                    background: var(--t-surface);
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
            `}),O?e.jsx("div",{style:{display:"flex",alignItems:"center",justifyContent:"center",height:200},children:e.jsx("div",{style:{width:40,height:40,border:"4px solid var(--t-border)",borderTopColor:"var(--t-primary)",borderRadius:"50%",animation:"spin 0.8s linear infinite"}})}):k.length===0?e.jsxs("div",{className:"materials-empty-state",children:[e.jsx("div",{style:{width:64,height:64,borderRadius:16,background:"var(--t-primary-soft)",color:"var(--t-primary)",display:"flex",alignItems:"center",justifyContent:"center",fontSize:28,marginBottom:16},children:e.jsx(S,{})}),e.jsx("h3",{style:{margin:"0 0 8px 0",fontSize:18,fontWeight:700,color:"var(--t-text)"},children:"No materials found"}),e.jsx("p",{style:{margin:0,color:"var(--t-text-3)",fontSize:14},children:"Upload documents or add links for your students."})]}):y==="grid"?e.jsx("div",{style:{display:"grid",gridTemplateColumns:"repeat(auto-fill, minmax(min(280px, 100%), 1fr))",gap:20},children:k.map(t=>{var r,a;const o=D(t.type),h=t.created_at||t.date||new Date,d=v===t.id;return e.jsxs("div",{className:"t-card",style:{background:"var(--t-surface)",borderRadius:16,border:"1px solid var(--t-border)",padding:20,display:"flex",flexDirection:"column",boxShadow:"0 2px 8px rgba(0,0,0,0.02)"},children:[e.jsxs("div",{style:{display:"flex",justifyContent:"space-between",alignItems:"flex-start",marginBottom:14},children:[e.jsx("div",{style:{width:48,height:48,borderRadius:12,background:o.bg,color:o.color,display:"flex",alignItems:"center",justifyContent:"center",fontSize:24},children:o.icon}),e.jsx("span",{style:{fontSize:12,fontWeight:600,color:"var(--t-text-3)",textTransform:"uppercase",background:"var(--t-bg)",padding:"2px 8px",borderRadius:4},children:t.type})]}),e.jsx("h3",{style:{margin:"0 0 6px 0",fontSize:16,fontWeight:700,color:"var(--t-text)",lineHeight:1.3,display:"-webkit-box",WebKitLineClamp:2,WebKitBoxOrient:"vertical",overflow:"hidden"},children:t.title}),e.jsxs("p",{style:{margin:"0 0 10px 0",color:"var(--t-text-3)",fontSize:13,fontWeight:500},children:[t.subject||"All Subjects"," • ",t.grade||"All Grades"," ",t.medium?`&bull; ${t.medium}`:""]}),A(t),e.jsxs("div",{style:{marginTop:"auto",paddingTop:16,borderTop:"1px solid var(--t-border)",display:"flex",justifyContent:"space-between",alignItems:"center"},children:[e.jsxs("div",{style:{fontSize:12,color:"var(--t-text-3)",fontWeight:500},children:[new Date(h).toLocaleDateString(void 0,{month:"short",day:"numeric",year:"numeric"}),t.file_size?e.jsxs("span",{style:{margin:"0 6px"},children:["• ",t.file_size]}):null]}),e.jsxs("div",{style:{display:"flex",gap:8,alignItems:"center"},children:[e.jsx("button",{onClick:()=>P(t),className:"t-icon-btn","aria-label":((r=t.type)==null?void 0:r.toLowerCase())==="link"?"Open link":"Download file",style:{width:44,height:44,minWidth:44,minHeight:44,borderRadius:8,background:"var(--t-surface)",border:"1px solid var(--t-border)",display:"flex",alignItems:"center",justifyContent:"center",color:"var(--t-text-3)",cursor:"pointer"},children:((a=t.type)==null?void 0:a.toLowerCase())==="link"?e.jsx(B,{}):e.jsx(H,{})}),e.jsx("button",{onClick:()=>q(t),disabled:d,className:"t-icon-btn","aria-label":"Delete material",style:{width:44,height:44,minWidth:44,minHeight:44,borderRadius:8,background:"var(--t-surface)",border:"1px solid var(--t-border)",display:"flex",alignItems:"center",justifyContent:"center",color:"var(--t-danger)",cursor:d?"not-allowed":"pointer",opacity:d?.5:1},children:e.jsx(F,{})})]})]})]},t.id)})}):e.jsx("div",{style:{background:"var(--t-surface)",borderRadius:16,border:"1px solid var(--t-border)",overflow:"hidden",boxShadow:"0 1px 3px rgba(0,0,0,0.02)"},children:e.jsxs("table",{style:{width:"100%",borderCollapse:"collapse",textAlign:"left"},children:[e.jsx("thead",{children:e.jsxs("tr",{style:{borderBottom:"1px solid var(--t-border)",background:"var(--t-bg)"},children:[e.jsx("th",{style:{padding:"16px 20px",fontSize:13,fontWeight:700,color:"var(--t-text-2)"},children:"Material"}),e.jsx("th",{style:{padding:"16px 20px",fontSize:13,fontWeight:700,color:"var(--t-text-2)"},children:"Type"}),e.jsx("th",{style:{padding:"16px 20px",fontSize:13,fontWeight:700,color:"var(--t-text-2)"},children:"Grade & Subject"}),e.jsx("th",{style:{padding:"16px 20px",fontSize:13,fontWeight:700,color:"var(--t-text-2)"},children:"AI Search Status"}),e.jsx("th",{style:{padding:"16px 20px",fontSize:13,fontWeight:700,color:"var(--t-text-2)"},children:"Date"}),e.jsx("th",{style:{padding:"16px 20px",fontSize:13,fontWeight:700,color:"var(--t-text-2)",textAlign:"right"},children:"Actions"})]})}),e.jsx("tbody",{children:k.map(t=>{var r,a;const o=D(t.type),h=t.created_at||t.date||new Date,d=v===t.id;return e.jsxs("tr",{style:{borderBottom:"1px solid var(--t-border)"},children:[e.jsx("td",{style:{padding:"16px 20px"},children:e.jsxs("div",{style:{display:"flex",alignItems:"center",gap:12},children:[e.jsx("div",{style:{width:36,height:36,borderRadius:8,background:o.bg,color:o.color,display:"flex",alignItems:"center",justifyContent:"center",fontSize:18},children:o.icon}),e.jsxs("div",{children:[e.jsx("div",{style:{fontWeight:600,color:"var(--t-text)",fontSize:14},children:t.title}),e.jsx("div",{style:{fontSize:12,color:"var(--t-text-3)"},children:t.file_size||t.size||""})]})]})}),e.jsx("td",{style:{padding:"16px 20px",fontSize:13,color:"var(--t-text-2)",textTransform:"capitalize"},children:t.type}),e.jsxs("td",{style:{padding:"16px 20px",fontSize:13,color:"var(--t-text-2)"},children:[t.grade||"All"," - ",t.subject||"All"," ",t.medium?`(${t.medium})`:""]}),e.jsx("td",{style:{padding:"16px 20px"},children:A(t)}),e.jsx("td",{style:{padding:"16px 20px",fontSize:13,color:"var(--t-text-3)"},children:new Date(h).toLocaleDateString(void 0,{month:"short",day:"numeric",year:"numeric"})}),e.jsx("td",{style:{padding:"16px 20px",textAlign:"right"},children:e.jsxs("div",{style:{display:"inline-flex",gap:8,alignItems:"center"},children:[e.jsx("button",{onClick:()=>P(t),className:"t-icon-btn","aria-label":((r=t.type)==null?void 0:r.toLowerCase())==="link"?"Open link":"Download file",style:{width:44,height:44,minWidth:44,minHeight:44,borderRadius:8,background:"var(--t-surface)",border:"1px solid var(--t-border)",display:"inline-flex",alignItems:"center",justifyContent:"center",color:"var(--t-text-3)",cursor:"pointer"},children:((a=t.type)==null?void 0:a.toLowerCase())==="link"?e.jsx(B,{}):e.jsx(H,{})}),e.jsx("button",{onClick:()=>q(t),disabled:d,className:"t-icon-btn","aria-label":"Delete material",style:{width:44,height:44,minWidth:44,minHeight:44,borderRadius:8,background:"var(--t-surface)",border:"1px solid var(--t-border)",display:"inline-flex",alignItems:"center",justifyContent:"center",color:"var(--t-danger)",cursor:d?"not-allowed":"pointer",opacity:d?.5:1},children:e.jsx(F,{})})]})})]},t.id)})})]})}),E&&e.jsx("div",{className:"teacher-modal-overlay",children:e.jsxs("div",{className:"teacher-modal-dialog",style:{maxWidth:520},children:[e.jsxs("div",{style:{display:"flex",justifyContent:"space-between",alignItems:"center",padding:"20px 24px",borderBottom:"1px solid var(--t-border)",background:"var(--t-surface)"},children:[e.jsx("h2",{style:{margin:0,fontSize:18,fontWeight:700,color:"var(--t-text)"},children:"Upload Material"}),e.jsx("button",{onClick:()=>b(!1),className:"t-icon-btn","aria-label":"Close upload modal",style:{width:44,height:44,minWidth:44,minHeight:44,background:"none",border:"none",color:"var(--t-text-3)",cursor:"pointer",display:"flex",alignItems:"center",justifyContent:"center"},children:e.jsx(G,{size:20})})]}),e.jsx("div",{className:"teacher-modal-body",style:{padding:"20px 24px"},children:e.jsx("form",{onSubmit:X,children:e.jsxs("div",{style:{display:"flex",flexDirection:"column",gap:16},children:[e.jsxs("div",{children:[e.jsxs("label",{style:{display:"block",fontSize:13,fontWeight:600,color:"var(--t-text-2)",marginBottom:6},children:["Title ",e.jsx("span",{style:{color:"var(--t-danger)"},children:"*"})]}),e.jsx("input",{type:"text",required:!0,style:p,value:i.title,onChange:t=>c({...i,title:t.target.value}),placeholder:"e.g., Chapter 1 Physics Notes"})]}),e.jsxs("div",{style:{display:"flex",gap:16,flexWrap:"wrap"},children:[e.jsxs("div",{style:{flex:"1 1 200px"},children:[e.jsxs("label",{style:{display:"block",fontSize:13,fontWeight:600,color:"var(--t-text-2)",marginBottom:6},children:["Type ",e.jsx("span",{style:{color:"var(--t-danger)"},children:"*"})]}),e.jsxs("select",{style:p,value:i.type,onChange:t=>c({...i,type:t.target.value}),children:[e.jsx("option",{value:"note",children:"Study Note"}),e.jsx("option",{value:"past_paper",children:"Past Paper"}),e.jsx("option",{value:"pdf",children:"PDF Document"}),e.jsx("option",{value:"document",children:"Other Document (Word/PPT)"}),e.jsx("option",{value:"video",children:"Video"}),e.jsx("option",{value:"link",children:"Web Link"})]})]}),e.jsxs("div",{style:{flex:"1 1 200px"},children:[e.jsxs("label",{style:{display:"block",fontSize:13,fontWeight:600,color:"var(--t-text-2)",marginBottom:6},children:["Grade ",u(i.type)&&e.jsx("span",{style:{color:"var(--t-danger)"},children:"*"})]}),e.jsx("input",{type:"text",required:u(i.type),style:p,value:i.grade,onChange:t=>c({...i,grade:t.target.value}),placeholder:"e.g., Grade 10, 10, grade-10"}),e.jsx("small",{style:{display:"block",color:"var(--t-text-3)",fontSize:11,marginTop:4},children:n("mat_grade_hint","Grade controls which students can see the file and AI search.")})]})]}),e.jsxs("div",{style:{display:"flex",gap:16,flexWrap:"wrap"},children:[e.jsxs("div",{style:{flex:"1 1 200px"},children:[e.jsxs("label",{style:{display:"block",fontSize:13,fontWeight:600,color:"var(--t-text-2)",marginBottom:6},children:["Subject ",u(i.type)&&e.jsx("span",{style:{color:"var(--t-danger)"},children:"*"})]}),e.jsx("input",{type:"text",required:u(i.type),style:p,value:i.subject,onChange:t=>c({...i,subject:t.target.value}),placeholder:"e.g., Physics, Mathematics"})]}),e.jsxs("div",{style:{flex:"1 1 200px"},children:[e.jsxs("label",{style:{display:"block",fontSize:13,fontWeight:600,color:"var(--t-text-2)",marginBottom:6},children:["Medium ",u(i.type)&&e.jsx("span",{style:{color:"var(--t-danger)"},children:"*"})]}),e.jsxs("select",{style:p,value:i.medium,onChange:t=>c({...i,medium:t.target.value}),children:[e.jsx("option",{value:"tamil",children:"Tamil"}),e.jsx("option",{value:"english",children:"English"}),e.jsx("option",{value:"sinhala",children:"Sinhala"}),e.jsx("option",{value:"all",children:"All Mediums"})]})]})]}),i.type.toLowerCase()==="link"?e.jsxs("div",{children:[e.jsxs("label",{style:{display:"block",fontSize:13,fontWeight:600,color:"var(--t-text-2)",marginBottom:6},children:["URL ",e.jsx("span",{style:{color:"var(--t-danger)"},children:"*"})]}),e.jsx("input",{type:"url",required:!0,style:p,value:i.url,onChange:t=>c({...i,url:t.target.value}),placeholder:"https://..."})]}):e.jsxs("div",{children:[e.jsxs("label",{style:{display:"block",fontSize:13,fontWeight:600,color:"var(--t-text-2)",marginBottom:6},children:["File ",e.jsx("span",{style:{color:"var(--t-danger)"},children:"*"})]}),e.jsx("input",{type:"file",required:!0,onChange:t=>I(t.target.files[0]),style:{...p,padding:8},accept:".pdf,.docx,.pptx,.jpg,.jpeg,.png,.webp,.mp4"}),e.jsx("small",{style:{display:"block",color:"var(--t-text-3)",fontSize:11,marginTop:4},children:"Supported: PDF, DOCX, PPTX, JPG, PNG. Note: legacy .doc and .ppt are unsupported."})]}),e.jsxs("div",{style:{display:"flex",justifyContent:"flex-end",gap:12,marginTop:12},children:[e.jsx("button",{type:"button",className:"t-btn",onClick:()=>b(!1),style:{padding:"10px 18px",borderRadius:8,border:"1px solid var(--t-border)",background:"var(--t-surface)",color:"var(--t-text-2)",cursor:"pointer",minHeight:44,fontWeight:600},children:"Cancel"}),e.jsxs("button",{type:"submit",className:"t-btn",disabled:g,style:{padding:"10px 24px",borderRadius:8,border:"none",background:"var(--t-primary)",color:"#fff",cursor:g?"not-allowed":"pointer",minHeight:44,fontWeight:600,opacity:g?.7:1,display:"flex",alignItems:"center",gap:8},children:[g&&e.jsx("div",{style:{width:16,height:16,border:"2px solid #fff",borderTopColor:"transparent",borderRadius:"50%",animation:"spin 0.6s linear infinite"}}),g?"Uploading...":"Upload"]})]})]})})})]})})]})}export{ue as default};
