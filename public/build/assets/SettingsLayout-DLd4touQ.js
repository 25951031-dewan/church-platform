import{r as l,j as e,O as o,N as r}from"./main-Cl_sFPP0.js";import{X as c}from"./x-Cz-rzUg5.js";import{M as h,V as d,S as m}from"./video-BlcqeLM5.js";import{S as p,B as x}from"./settings-COT2hFKg.js";import{P as g}from"./palette-Bi_IH5co.js";import{c as a}from"./createLucideIcon-iHiLluRD.js";import{U as y}from"./upload-BMlqFSpd.js";import{S as b}from"./search-fg3XAHeb.js";/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const u=a("Bot",[["path",{d:"M12 8V4H8",key:"hb8ula"}],["rect",{width:"16",height:"12",x:"4",y:"8",rx:"2",key:"enze0r"}],["path",{d:"M2 14h2",key:"vft8re"}],["path",{d:"M20 14h2",key:"4cs60a"}],["path",{d:"M15 13v2",key:"1xurst"}],["path",{d:"M9 13v2",key:"rq6x2g"}]]);/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const k=a("ChartNoAxesColumn",[["line",{x1:"18",x2:"18",y1:"20",y2:"10",key:"1xfpm4"}],["line",{x1:"12",x2:"12",y1:"20",y2:"4",key:"be30l9"}],["line",{x1:"6",x2:"6",y1:"20",y2:"14",key:"1r4le6"}]]);/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const f=a("CodeXml",[["path",{d:"m18 16 4-4-4-4",key:"1inbqp"}],["path",{d:"m6 8-4 4 4 4",key:"15zrgr"}],["path",{d:"m14.5 4-5 16",key:"e7oirm"}]]);/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const v=a("Globe",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20",key:"13o1zl"}],["path",{d:"M2 12h20",key:"9i4pu4"}]]);/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const w=a("Lock",[["rect",{width:"18",height:"11",x:"3",y:"11",rx:"2",ry:"2",key:"1w4ew1"}],["path",{d:"M7 11V7a5 5 0 0 1 10 0v4",key:"fwvmzm"}]]);/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const j=a("Mail",[["rect",{width:"20",height:"16",x:"2",y:"4",rx:"2",key:"18n3k1"}],["path",{d:"m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7",key:"1ocrg3"}]]);/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const N=a("ShieldCheck",[["path",{d:"M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z",key:"oel41y"}],["path",{d:"m9 12 2 2 4-4",key:"dzmm74"}]]),C=[{label:"General",path:"/admin/settings/general",icon:p},{label:"Appearance",path:"/admin/settings/appearance",icon:g},{label:"Authentication",path:"/admin/settings/auth",icon:w},{label:"Email",path:"/admin/settings/email",icon:j},{label:"Notifications",path:"/admin/settings/notifications",icon:x},{label:"Uploading",path:"/admin/settings/uploading",icon:y},{label:"Localization",path:"/admin/settings/localization",icon:v},{label:"SEO",path:"/admin/settings/seo",icon:b},{label:"Analytics",path:"/admin/settings/analytics",icon:k},{label:"Custom Code",path:"/admin/settings/custom-code",icon:f},{label:"GDPR",path:"/admin/settings/gdpr",icon:N},{label:"Captcha",path:"/admin/settings/captcha",icon:u},{label:"Live Meetings",path:"/admin/settings/live-meetings",icon:d},{label:"System",path:"/admin/system",icon:m}];function n({onNavigate:i}){return e.jsxs(e.Fragment,{children:[e.jsx("h1",{className:"text-lg font-bold text-white px-3 mb-4",children:"Settings"}),e.jsx("nav",{className:"space-y-0.5",children:C.map(t=>e.jsx(r,{to:t.path,onClick:i,className:({isActive:s})=>`flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors ${s?"bg-white/10 text-white font-medium":"text-gray-400 hover:text-white hover:bg-white/5"}`,children:({isActive:s})=>e.jsxs(e.Fragment,{children:[e.jsx(t.icon,{size:15,className:s?"text-white":"text-gray-500","aria-hidden":"true"}),t.label]})},t.path))})]})}function E(){const[i,t]=l.useState(!1);return e.jsxs("div",{className:"flex gap-0 h-full min-h-0",children:[e.jsx("aside",{className:"hidden lg:block w-48 flex-shrink-0 border-r border-white/5 pr-2 mr-6",children:e.jsx(n,{})}),i&&e.jsxs("div",{className:"lg:hidden fixed inset-0 z-50 flex",children:[e.jsx("div",{className:"fixed inset-0 bg-black/60 backdrop-blur-sm",onClick:()=>t(!1),"aria-hidden":"true"}),e.jsxs("aside",{className:"relative w-48 flex-shrink-0 bg-[#161920] p-4 border-r border-white/5 z-10",children:[e.jsx("button",{type:"button","aria-label":"Close menu",onClick:()=>t(!1),className:"absolute top-3 right-3 p-1 rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition-colors",children:e.jsx(c,{size:16})}),e.jsx(n,{onNavigate:()=>t(!1)})]})]}),e.jsxs("div",{className:"flex-1 min-w-0 overflow-auto",children:[e.jsxs("div",{className:"lg:hidden flex items-center gap-3 mb-4",children:[e.jsx("button",{type:"button","aria-label":"Open menu",onClick:()=>t(!0),className:"p-1.5 rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition-colors",children:e.jsx(h,{size:20})}),e.jsx("h1",{className:"text-lg font-bold text-white",children:"Settings"})]}),e.jsx(o,{})]})]})}export{E as SettingsLayout};
