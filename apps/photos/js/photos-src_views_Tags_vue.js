/*! For license information please see photos-src_views_Tags_vue.js.LICENSE.txt */
"use strict";(self.webpackChunkphotos=self.webpackChunkphotos||[]).push([["src_views_Tags_vue"],{69363:(n,t,e)=>{e.d(t,{Z:()=>r});const r={name:"AbortControllerMixin",data:function(){return{abortController:new AbortController}},beforeDestroy:function(){this.abortController.abort()},beforeRouteLeave:function(n,t,e){this.abortController.abort(),this.abortController=new AbortController,e()}}},20156:(n,t,e)=>{e.d(t,{Z:()=>s});var r=e(87537),a=e.n(r),o=e(23645),i=e.n(o)()(a());i.push([n.id,".tag-cover[data-v-50343eda]{display:flex;flex-direction:column;padding:16px;border-radius:12px}.tag-cover[data-v-50343eda]:hover,.tag-cover[data-v-50343eda]:focus{background:var(--color-background-dark)}.tag-cover__image[data-v-50343eda]{width:350px;height:350px;object-fit:cover;border-radius:12px}@media only screen and (max-width: 1200px){.tag-cover__image[data-v-50343eda]{width:250px;height:250px}}.tag-cover__image--placeholder[data-v-50343eda]{background:var(--color-primary-light)}.tag-cover__image--placeholder[data-v-50343eda]  .material-design-icon{width:100%;height:100%}.tag-cover__image--placeholder[data-v-50343eda]  .material-design-icon .material-design-icon__svg{fill:var(--color-primary)}.tag-cover__details[data-v-50343eda]{display:flex;flex-direction:column;margin-top:16px;width:350px}@media only screen and (max-width: 1200px){.tag-cover__details[data-v-50343eda]{width:250px}}.tag-cover__details__first-line[data-v-50343eda]{display:flex}.tag-cover__details__second-line[data-v-50343eda]{display:flex;color:var(--color-text-lighter)}.tag-cover__details__name[data-v-50343eda]{flex-grow:1;margin:0;font-weight:normal;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}","",{version:3,sources:["webpack://./src/components/TagCover.vue"],names:[],mappings:"AA4HA,4BACC,YAAA,CACA,qBAAA,CACA,YAAA,CACA,kBAAA,CAEA,oEACC,uCAAA,CAGD,mCACC,WAAA,CACA,YAAA,CACA,gBAAA,CACA,kBAAA,CAEA,2CAND,mCAOE,WAAA,CACA,YAAA,CAAA,CAGD,gDACC,qCAAA,CAEA,uEACC,UAAA,CACA,WAAA,CAEA,kGACC,yBAAA,CAMJ,qCACC,YAAA,CACA,qBAAA,CACA,eAAA,CACA,WAAA,CAEA,2CAND,qCAOE,WAAA,CAAA,CAGD,iDACC,YAAA,CAGD,kDACC,YAAA,CACA,+BAAA,CAGD,2CACC,WAAA,CACA,QAAA,CACA,kBAAA,CACA,eAAA,CACA,kBAAA,CACA,sBAAA",sourcesContent:['$sizes: ("400": ("count": 3, "marginTop": 66, "marginW": 8), "700": ("count": 4, "marginTop": 66, "marginW": 8), "1024": ("count": 5, "marginTop": 66, "marginW": 44), "1280": ("count": 4, "marginTop": 66, "marginW": 44), "1440": ("count": 5, "marginTop": 88, "marginW": 66), "1600": ("count": 6, "marginTop": 88, "marginW": 66), "2048": ("count": 7, "marginTop": 88, "marginW": 66), "2560": ("count": 8, "marginTop": 88, "marginW": 88), "3440": ("count": 9, "marginTop": 88, "marginW": 88), "max": ("count": 10, "marginTop": 88, "marginW": 88));\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n.tag-cover {\n\tdisplay: flex;\n\tflex-direction: column;\n\tpadding: 16px;\n\tborder-radius: 12px;\n\n\t&:hover, &:focus {\n\t\tbackground: var(--color-background-dark);\n\t}\n\n\t&__image {\n\t\twidth: 350px;\n\t\theight: 350px;\n\t\tobject-fit: cover;\n\t\tborder-radius: 12px;\n\n\t\t@media only screen and (max-width: 1200px) {\n\t\t\twidth: 250px;\n\t\t\theight: 250px;\n\t\t}\n\n\t\t&--placeholder {\n\t\t\tbackground: var(--color-primary-light);\n\n\t\t\t::v-deep .material-design-icon {\n\t\t\t\twidth: 100%;\n\t\t\t\theight: 100%;\n\n\t\t\t\t.material-design-icon__svg {\n\t\t\t\t\tfill: var(--color-primary);\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\t}\n\n\t&__details {\n\t\tdisplay: flex;\n\t\tflex-direction: column;\n\t\tmargin-top: 16px;\n\t\twidth: 350px;\n\n\t\t@media only screen and (max-width: 1200px) {\n\t\t\twidth: 250px;\n\t\t}\n\n\t\t&__first-line {\n\t\t\tdisplay: flex;\n\t\t}\n\n\t\t&__second-line {\n\t\t\tdisplay: flex;\n\t\t\tcolor: var(--color-text-lighter);\n\t\t}\n\n\t\t&__name {\n\t\t\tflex-grow: 1;\n\t\t\tmargin: 0;\n\t\t\tfont-weight: normal;\n\t\t\toverflow: hidden;\n\t\t\twhite-space: nowrap;\n\t\t\ttext-overflow: ellipsis;\n\n\t\t}\n\t}\n\n}\n'],sourceRoot:""}]);const s=i},33298:(n,t,e)=>{e.d(t,{Z:()=>s});var r=e(87537),a=e.n(r),o=e(23645),i=e.n(o)()(a());i.push([n.id,".loader[data-v-0f063876]{margin-top:30vh}.container[data-v-0f063876]{padding-left:44px}.container>h2[data-v-0f063876]{margin-left:14px;margin-top:40px}.popular-tags[data-v-0f063876],.tags[data-v-0f063876]{display:flex;flex-direction:row;gap:8px;flex-wrap:wrap}","",{version:3,sources:["webpack://./src/views/Tags.vue"],names:[],mappings:"AAqIA,yBACC,eAAA,CAGD,4BACC,iBAAA,CAEA,+BACC,gBAAA,CACA,eAAA,CAIF,sDACC,YAAA,CACA,kBAAA,CACA,OAAA,CACA,cAAA",sourcesContent:['$sizes: ("400": ("count": 3, "marginTop": 66, "marginW": 8), "700": ("count": 4, "marginTop": 66, "marginW": 8), "1024": ("count": 5, "marginTop": 66, "marginW": 44), "1280": ("count": 4, "marginTop": 66, "marginW": 44), "1440": ("count": 5, "marginTop": 88, "marginW": 66), "1600": ("count": 6, "marginTop": 88, "marginW": 66), "2048": ("count": 7, "marginTop": 88, "marginW": 66), "2560": ("count": 8, "marginTop": 88, "marginW": 88), "3440": ("count": 9, "marginTop": 88, "marginW": 88), "max": ("count": 10, "marginTop": 88, "marginW": 88));\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n.loader {\n\tmargin-top: 30vh;\n}\n\n.container {\n\tpadding-left: 44px;\n\n\t> h2 {\n\t\tmargin-left: 14px;\n\t\tmargin-top: 40px;\n\t}\n}\n\n.popular-tags, .tags {\n\tdisplay: flex;\n\tflex-direction: row;\n\tgap: 8px;\n\tflex-wrap: wrap;\n}\n'],sourceRoot:""}]);const s=i},36288:(n,t,e)=>{e.d(t,{Z:()=>a});const r={name:"ImageMultipleIcon",emits:["click"],props:{title:{type:String},fillColor:{type:String,default:"currentColor"},size:{type:Number,default:24}}};const a=(0,e(51900).Z)(r,(function(){var n=this,t=n.$createElement,e=n._self._c||t;return e("span",n._b({staticClass:"material-design-icon image-multiple-icon",attrs:{"aria-hidden":!n.title,"aria-label":n.title,role:"img"},on:{click:function(t){return n.$emit("click",t)}}},"span",n.$attrs,!1),[e("svg",{staticClass:"material-design-icon__svg",attrs:{fill:n.fillColor,width:n.size,height:n.size,viewBox:"0 0 24 24"}},[e("path",{attrs:{d:"M22,16V4A2,2 0 0,0 20,2H8A2,2 0 0,0 6,4V16A2,2 0 0,0 8,18H20A2,2 0 0,0 22,16M11,12L13.03,14.71L16,11L20,16H8M2,6V20A2,2 0 0,0 4,22H18V20H4V6"}},[n.title?e("title",[n._v(n._s(n.title))]):n._e()])])])}),[],!1,null,null,null).exports},72461:(n,t,e)=>{e.r(t),e.d(t,{default:()=>S});var r=e(20629),a=e(33476),o=e(79954),i=e(36288),s=e(79753),l=e(69363);function c(n,t){var e=Object.keys(n);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(n);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(n,t).enumerable}))),e.push.apply(e,r)}return e}function g(n){for(var t=1;t<arguments.length;t++){var e=null!=arguments[t]?arguments[t]:{};t%2?c(Object(e),!0).forEach((function(t){A(n,t,e[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(n,Object.getOwnPropertyDescriptors(e)):c(Object(e)).forEach((function(t){Object.defineProperty(n,t,Object.getOwnPropertyDescriptor(e,t))}))}return n}function A(n,t,e){return t in n?Object.defineProperty(n,t,{value:e,enumerable:!0,configurable:!0,writable:!0}):n[t]=e,n}const p={name:"TagCover",components:{ImageMultipleIcon:i.Z},mixins:[l.Z],props:{tag:{type:Object,required:!0}},data:function(){return{loadCover:!1,observer:null,tagCounts:(0,o.j)("photos","tag-counts")}},computed:g(g({},(0,r.Se)(["files","tags"])),{},{coverUrl:function(){return this.loadCover?(0,s.generateUrl)("/core/preview?fileId=".concat(this.tag.files[this.tag.files.length-1],"&x=",512,"&y=",512,"&forceIcon=0&a=1")):""},count:function(){return this.tag.files.length||this.tagCounts[this.tag.displayName]}}),watch:{loadCover:function(){this.tag.files.length||this.$store.dispatch("fetchTagFiles",{id:this.tag.id,signal:this.abortController.signal})}},mounted:function(){var n=this;this.observer=new IntersectionObserver((function(t){t[0].isIntersecting&&(n.loadCover=!0,n.observer.disconnect())})),this.observer.observe(this.$el)}};var u=e(93379),d=e.n(u),m=e(7795),f=e.n(m),v=e(90569),C=e.n(v),h=e(3565),_=e.n(h),b=e(19216),x=e.n(b),w=e(44589),y=e.n(w),O=e(20156),T={};T.styleTagTransform=y(),T.setAttributes=_(),T.insert=C().bind(null,"head"),T.domAPI=f(),T.insertStyleElement=x();d()(O.Z,T);O.Z&&O.Z.locals&&O.Z.locals;var k=e(51900);const j=(0,k.Z)(p,(function(){var n=this,t=n.$createElement,e=n._self._c||t;return e("router-link",{staticClass:"tag-cover",attrs:{to:"/tags/"+n.tag.displayName}},[0!==n.tag.files.length?e("img",{staticClass:"tag-cover__image",attrs:{src:n.coverUrl}}):e("div",{staticClass:"tag-cover__image tag-cover__image--placeholder"},[e("ImageMultipleIcon",{attrs:{size:128}})],1),n._v(" "),e("div",{staticClass:"tag-cover__details"},[e("div",{staticClass:"tag-cover__details__first-line"},[e("h3",{staticClass:"tag-cover__details__name"},[n._v("\n\t\t\t\t"+n._s(n.t("recognize",n.tag.displayName))+"\n\t\t\t")])]),n._v(" "),e("div",{staticClass:"tag-cover__details__second-line"},[n._v("\n\t\t\t"+n._s(n.n("photos","%n photo","%n photos",n.count))+"\n\t\t")])])])}),[],!1,null,"50343eda",null).exports;var W=e(25108);function P(n,t,e,r,a,o,i){try{var s=n[o](i),l=s.value}catch(n){return void e(n)}s.done?t(l):Promise.resolve(l).then(r,a)}function E(n){return function(){var t=this,e=arguments;return new Promise((function(r,a){var o=n.apply(t,e);function i(n){P(o,r,a,i,s,"next",n)}function s(n){P(o,r,a,i,s,"throw",n)}i(void 0)}))}}function N(n,t){var e=Object.keys(n);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(n);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(n,t).enumerable}))),e.push.apply(e,r)}return e}function D(n){for(var t=1;t<arguments.length;t++){var e=null!=arguments[t]?arguments[t]:{};t%2?N(Object(e),!0).forEach((function(t){B(n,t,e[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(n,Object.getOwnPropertyDescriptors(e)):N(Object(e)).forEach((function(t){Object.defineProperty(n,t,Object.getOwnPropertyDescriptor(e,t))}))}return n}function B(n,t,e){return t in n?Object.defineProperty(n,t,{value:e,enumerable:!0,configurable:!0,writable:!0}):n[t]=e,n}const Z={name:"Tags",components:{TagCover:j,NcLoadingIcon:a.NcLoadingIcon,NcEmptyContent:a.NcEmptyContent},mixins:[l.Z],data:function(){return{error:null,loading:!1,showTags:!1,tagCounts:(0,o.j)("photos","tag-counts")}},computed:D(D({},(0,r.Se)(["files","tags","tagsNames"])),{},{tagsList:function(){var n=this;return Object.keys(this.tagsNames).map((function(t){return n.tags[n.tagsNames[t]]})).filter((function(n){return n&&n.id}))},popularTags:function(){var n=this;return Object.keys(this.tagsNames).filter((function(t){return(n.tags[n.tagsNames[t]].files.length||n.tagCounts[t])>50})).sort((function(t,e){return(n.tags[n.tagsNames[e]].files.length||n.tagCounts[e])-(n.tags[n.tagsNames[t]].files.length||n.tagCounts[t])})).slice(0,9).map((function(t){return n.tags[n.tagsNames[t]]}))}}),beforeMount:function(){var n=this;return E(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,n.fetchRootContent();case 2:case"end":return t.stop()}}),t)})))()},methods:{fetchRootContent:function(){var n=this;return E(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:if(OCA.Viewer.close(),n.error=null,t.prev=2,n.tagsList.length){t.next=7;break}return n.loading=!0,t.next=7,n.$store.dispatch("fetchAllTags",{signal:n.abortController.signal});case 7:t.next=13;break;case 9:t.prev=9,t.t0=t.catch(2),W.error(t.t0),n.error=!0;case 13:return t.prev=13,n.loading=!1,t.finish(13);case 16:case"end":return t.stop()}}),t,null,[[2,9,13,16]])})))()}}};var I=e(33298),L={};L.styleTagTransform=y(),L.setAttributes=_(),L.insert=C().bind(null,"head"),L.domAPI=f(),L.insertStyleElement=x();d()(I.Z,L);I.Z&&I.Z.locals&&I.Z.locals;const S=(0,k.Z)(Z,(function(){var n=this,t=n.$createElement,e=n._self._c||t;return e("div",[n.error?e("NcEmptyContent",{attrs:{title:n.t("photos","An error occurred")}}):n._e(),n._v(" "),n.loading||0!==n.tagsList.length?n._e():e("NcEmptyContent",{attrs:{title:n.t("photos","No tags yet"),description:n.t("photos","Photos with tags will show up here")}}),n._v(" "),n.loading?e("NcLoadingIcon",{staticClass:"loader"}):e("div",{staticClass:"container"},[n.popularTags.length?e("h2",[n._v("\n\t\t\t"+n._s(n.t("photos","Popular tags"))+"\n\t\t")]):n._e(),n._v(" "),e("div",{staticClass:"popular-tags"},n._l(n.popularTags,(function(n){return e("TagCover",{key:n.id,attrs:{tag:n}})})),1),n._v(" "),n.tagsList.length?e("h2",[n._v("\n\t\t\tAll tags\n\t\t")]):n._e(),n._v(" "),e("div",{staticClass:"tags"},n._l(n.tagsList,(function(n){return e("TagCover",{key:n.id,attrs:{tag:n}})})),1)])],1)}),[],!1,null,"0f063876",null).exports}}]);
//# sourceMappingURL=photos-src_views_Tags_vue.js.map?v=18211b0aa7a5140a9545