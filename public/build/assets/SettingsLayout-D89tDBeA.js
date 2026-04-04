import{j as e,N as s,O as n}from"./main-9lw1HJ4i.js";import{S as r,B as l}from"./settings-B-r8_xRE.js";import{c as i}from"./createLucideIcon-CeIQex99.js";import{V as o,S as c}from"./video-D5VOE5jS.js";import{P as h}from"./palette-7DdPFhgk.js";/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const m=i("Lock",[["rect",{width:"18",height:"11",x:"3",y:"11",rx:"2",ry:"2",key:"1w4ew1"}],["path",{d:"M7 11V7a5 5 0 0 1 10 0v4",key:"fwvmzm"}]]);/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const d=i("Mail",[["rect",{width:"20",height:"16",x:"2",y:"4",rx:"2",key:"18n3k1"}],["path",{d:"m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7",key:"1ocrg3"}]]),p=[{label:"General",path:"/admin/settings/general",icon:r},{label:"Authentication",path:"/admin/settings/auth",icon:m},{label:"Email",path:"/admin/settings/email",icon:d},{label:"Notifications",path:"/admin/settings/notifications",icon:l},{label:"Live Meetings",path:"/admin/settings/live-meetings",icon:o},{label:"Appearance",path:"/admin/settings/appearance",icon:h},{label:"System",path:"/admin/system",icon:c}];function y(){return e.jsxs("div",{className:"flex gap-0 h-full min-h-0",children:[e.jsxs("aside",{className:"w-48 flex-shrink-0 border-r border-white/5 pr-2 mr-6",children:[e.jsx("h1",{className:"text-lg font-bold text-white px-3 mb-4",children:"Settings"}),e.jsx("nav",{className:"space-y-0.5",children:p.map(t=>e.jsx(s,{to:t.path,className:({isActive:a})=>`flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors ${a?"bg-white/10 text-white font-medium":"text-gray-400 hover:text-white hover:bg-white/5"}`,children:({isActive:a})=>e.jsxs(e.Fragment,{children:[e.jsx(t.icon,{size:15,className:a?"text-white":"text-gray-500","aria-hidden":"true"}),t.label]})},t.path))})]}),e.jsx("div",{className:"flex-1 min-w-0 overflow-auto",children:e.jsx(n,{})})]})}export{y as SettingsLayout};
