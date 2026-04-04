import{j as e,O as c,N as l}from"./main-BtAAf4u_.js";import{c as s}from"./createLucideIcon-B0RfSTxY.js";import{S as r}from"./search-BxOtLtI2.js";/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const o=s("House",[["path",{d:"M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8",key:"5wwlr5"}],["path",{d:"M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z",key:"1d0kgt"}]]);/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const n=s("Rss",[["path",{d:"M4 11a9 9 0 0 1 9 9",key:"pv89mb"}],["path",{d:"M4 4a16 16 0 0 1 16 16",key:"k0647b"}],["circle",{cx:"5",cy:"19",r:"1",key:"bfqh0e"}]]);/**
 * @license lucide-react v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const i=s("User",[["path",{d:"M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2",key:"975kel"}],["circle",{cx:"12",cy:"7",r:"4",key:"17ys0d"}]]),x=[{label:"Home",path:"/",icon:o,exact:!0},{label:"Search",path:"/sermons",icon:r},{label:"Feed",path:"/feed",icon:n},{label:"Account",path:"/login",icon:i}];function m(){return e.jsxs("div",{className:"flex flex-col min-h-screen bg-[#0C0E12]",children:[e.jsx("main",{className:"flex-1 pb-16 sm:pb-0",children:e.jsx(c,{})}),e.jsx("nav",{className:"sm:hidden fixed bottom-0 left-0 right-0 bg-[#161920] border-t border-white/5 flex z-50",children:x.map(a=>e.jsx(l,{to:a.path,end:"exact"in a?a.exact:void 0,className:({isActive:t})=>`flex-1 flex flex-col items-center justify-center py-2 text-[10px] transition-colors ${t?"text-white":"text-gray-500"}`,children:({isActive:t})=>e.jsxs(e.Fragment,{children:[e.jsx(a.icon,{size:20,className:t?"text-white":"text-gray-500","aria-hidden":"true"}),e.jsx("span",{className:"mt-1",children:a.label})]})},a.path))})]})}export{m as MobileLayout};
