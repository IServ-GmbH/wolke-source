/*! For license information please see 7462-7462.js.LICENSE.txt */
"use strict";(self.webpackChunknextcloud=self.webpackChunknextcloud||[]).push([[7462],{10396:(e,n,o)=>{o.d(n,{A:()=>d});var a=o(85168),i=o(70395),s=o(80486),r=o(10767),c=o(96689),m=o(96763);const d={props:{id:{type:Number,default:null},message:{type:String,default:""},resourceId:{type:[String,Number],required:!0},resourceType:{type:String,default:"files"}},data:()=>({deleted:!1,editing:!1,loading:!1}),methods:{onEdit(){this.editing=!0},onEditCancel(){this.editing=!1,this.updateLocalMessage(this.message)},async onEditComment(e){this.loading=!0;try{await(0,r.A)(this.resourceType,this.resourceId,this.id,e),c.A.debug("Comment edited",{resourceType:this.resourceType,resourceId:this.resourceId,id:this.id,message:e}),this.$emit("update:message",e),this.editing=!1}catch(e){(0,a.Qg)(t("comments","An error occurred while trying to edit the comment")),m.error(e)}finally{this.loading=!1}},onDeleteWithUndo(){this.deleted=!0;const e=setTimeout(this.onDelete,a.Br);(0,a._h)(t("comments","Comment deleted"),(()=>{clearTimeout(e),this.deleted=!1}))},async onDelete(){try{await(0,s.A)(this.resourceType,this.resourceId,this.id),c.A.debug("Comment deleted",{resourceType:this.resourceType,resourceId:this.resourceId,id:this.id}),this.$emit("delete",this.id)}catch(e){(0,a.Qg)(t("comments","An error occurred while trying to delete the comment")),m.error(e),this.deleted=!1}},async onNewComment(e){this.loading=!0;try{const t=await(0,i.A)(this.resourceType,this.resourceId,e);c.A.debug("New comment posted",{resourceType:this.resourceType,resourceId:this.resourceId,newComment:t}),this.$emit("new",t),this.$emit("update:message",""),this.localMessage=""}catch(e){(0,a.Qg)(t("comments","An error occurred while trying to create the comment")),m.error(e)}finally{this.loading=!1}}}}},80486:(t,e,n)=>{n.d(e,{A:()=>a});var o=n(35550);async function a(t,e,n){const a=["",t,e,n].join("/");await o.A.deleteFile(a)}},10767:(t,e,n)=>{n.d(e,{A:()=>a});var o=n(35550);async function a(t,e,n,a){const i=["",t,e,n].join("/");return await o.A.customRequest(i,Object.assign({method:"PROPPATCH",data:'<?xml version="1.0"?>\n\t\t\t<d:propertyupdate\n\t\t\t\txmlns:d="DAV:"\n\t\t\t\txmlns:oc="http://owncloud.org/ns">\n\t\t\t<d:set>\n\t\t\t\t<d:prop>\n\t\t\t\t\t<oc:message>'.concat(a,"</oc:message>\n\t\t\t\t</d:prop>\n\t\t\t</d:set>\n\t\t\t</d:propertyupdate>")}))}},70395:(t,e,n)=>{n.d(e,{A:()=>c});var o=n(21777),a=n(17003),i=n(51195),s=n(26287),r=n(35550);async function c(t,e,n){const c=["",t,e].join("/"),m=await s.A.post((0,a.e)()+c,{actorDisplayName:(0,o.HW)().displayName,actorId:(0,o.HW)().uid,actorType:"users",creationDateTime:(new Date).toUTCString(),message:n,objectType:t,verb:"comment"}),d=c+"/"+parseInt(m.headers["content-location"].split("/").pop()),l=await r.A.stat(d,{details:!0}),A=l.data.props;return A.actorDisplayName=(0,i.j)(A.actorDisplayName,2),A.message=(0,i.j)(A.message,2),l.data}},51195:(t,e,n)=>{function o(t){let e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:1;const n=new DOMParser;let o=t;for(let t=0;t<e;t++)o=n.parseFromString(o,"text/html").documentElement.textContent;return o}n.d(e,{j:()=>o})},70452:(t,e,n)=>{n.d(e,{A:()=>r});var o=n(26287),a=n(21777),i=n(32981),s=n(63814);const r=(0,n(85471).pM)({props:{resourceId:{type:Number,required:!0},resourceType:{type:String,default:"files"}},data:()=>({editorData:{actorDisplayName:(0,a.HW)().displayName,actorId:(0,a.HW)().uid,key:"editor"},userData:{}}),methods:{async autoComplete(t,e){const{data:n}=await o.A.get((0,s.KT)("core/autocomplete/get"),{params:{search:t,itemType:"files",itemId:this.resourceId,sorter:"commenters|share-recipients",limit:(0,i.C)("comments","maxAutoCompleteResults")}});return n.ocs.data.forEach((t=>{this.userData[t.id]=t})),e(Object.values(this.userData))},genMentionsData(t){return Object.values(t).flat().forEach((t=>{var e;this.userData[t.mentionId]={icon:"icon-user",id:t.mentionId,label:t.mentionDisplayName,source:"users",primary:(null===(e=(0,a.HW)())||void 0===e?void 0:e.uid)===t.mentionId}})),this.userData}}})},29369:(t,e,n)=>{n.d(e,{A:()=>f});var o=n(21777),a=n(53334),i=n(19231),s=n(89257),r=n(24764),c=n(80114),m=n(41944),d=n(54332),l=n(80701),A=n(9191),p=n(24325),u=n(11037),C=n(93919),g=n(16356),h=n(10396);const f={name:"Comment",components:{IconArrowRight:A.A,IconClose:p.A,IconDelete:u.A,IconEdit:C.A,Moment:g.A,NcActionButton:s.A,NcActions:r.A,NcActionSeparator:c.A,NcAvatar:m.A,NcButton:d.A,NcRichContenteditable:()=>Promise.all([n.e(4208),n.e(5528)]).then(n.bind(n,95528))},mixins:[l.Ay,h.A],inheritAttrs:!1,props:{actorDisplayName:{type:String,required:!0},actorId:{type:String,required:!0},creationDateTime:{type:String,default:null},editor:{type:Boolean,default:!1},autoComplete:{type:Function,required:!0},tag:{type:String,default:"div"}},data:()=>({expanded:!1,localMessage:"",submitted:!1}),computed:{isOwnComment(){return(0,o.HW)().uid===this.actorId},renderedContent(){return this.isEmptyMessage?"":this.renderContent(this.localMessage)},isEmptyMessage(){return!this.localMessage||""===this.localMessage.trim()},timestamp(){return parseInt((0,i.A)(this.creationDateTime).format("x"),10)/1e3}},watch:{message(t){this.updateLocalMessage(t)}},beforeMount(){this.updateLocalMessage(this.message)},methods:{t:a.Tl,updateLocalMessage(t){this.localMessage=t.toString(),this.submitted=!1},onSubmit(){if(""!==this.localMessage.trim())return this.editor?(this.onNewComment(this.localMessage.trim()),void this.$nextTick((()=>{this.$refs.editor.$el.focus()}))):void this.onEditComment(this.localMessage.trim())},onExpand(){this.expanded=!0}}}},28164:(t,e,n)=>{n.d(e,{A:()=>a});var o=n(19231);const a={name:"Moment",props:{timestamp:{type:Number,required:!0},format:{type:String,default:"LLL"}},computed:{title(){return o.A.unix(this.timestamp).format(this.format)},formatted(){return o.A.unix(this.timestamp).fromNow()}}}},79156:(t,e,n)=>{n.d(e,{X:()=>o,Y:()=>a});var o=function(){var t=this,e=t._self._c;return e(t.tag,{directives:[{name:"show",rawName:"v-show",value:!t.deleted,expression:"!deleted"}],tag:"component",staticClass:"comment",class:{"comment--loading":t.loading}},[e("div",{staticClass:"comment__side"},[e("NcAvatar",{staticClass:"comment__avatar",attrs:{"display-name":t.actorDisplayName,user:t.actorId,size:32}})],1),t._v(" "),e("div",{staticClass:"comment__body"},[e("div",{staticClass:"comment__header"},[e("span",{staticClass:"comment__author"},[t._v(t._s(t.actorDisplayName))]),t._v(" "),t.isOwnComment&&t.id&&!t.loading?e("NcActions",{staticClass:"comment__actions"},[t.editing?e("NcActionButton",{on:{click:t.onEditCancel},scopedSlots:t._u([{key:"icon",fn:function(){return[e("IconClose",{attrs:{size:20}})]},proxy:!0}],null,!1,2888946197)},[t._v("\n\t\t\t\t\t"+t._s(t.t("comments","Cancel edit"))+"\n\t\t\t\t")]):[e("NcActionButton",{attrs:{"close-after-click":""},on:{click:t.onEdit},scopedSlots:t._u([{key:"icon",fn:function(){return[e("IconEdit",{attrs:{size:20}})]},proxy:!0}],null,!1,649782975)},[t._v("\n\t\t\t\t\t\t"+t._s(t.t("comments","Edit comment"))+"\n\t\t\t\t\t")]),t._v(" "),e("NcActionSeparator"),t._v(" "),e("NcActionButton",{attrs:{"close-after-click":""},on:{click:t.onDeleteWithUndo},scopedSlots:t._u([{key:"icon",fn:function(){return[e("IconDelete",{attrs:{size:20}})]},proxy:!0}],null,!1,881161434)},[t._v("\n\t\t\t\t\t\t"+t._s(t.t("comments","Delete comment"))+"\n\t\t\t\t\t")])]],2):t._e(),t._v(" "),t.id&&t.loading?e("div",{staticClass:"comment_loading icon-loading-small"}):t.creationDateTime?e("Moment",{staticClass:"comment__timestamp",attrs:{timestamp:t.timestamp}}):t._e()],1),t._v(" "),t.editor||t.editing?e("form",{staticClass:"comment__editor",on:{submit:function(t){t.preventDefault()}}},[e("div",{staticClass:"comment__editor-group"},[e("NcRichContenteditable",{ref:"editor",attrs:{"auto-complete":t.autoComplete,contenteditable:!t.loading,label:t.editor?t.t("comments","New comment"):t.t("comments","Edit comment"),placeholder:t.t("comments","Write a comment …"),value:t.localMessage,"user-data":t.userData,"aria-describedby":"tab-comments__editor-description"},on:{"update:value":t.updateLocalMessage,submit:t.onSubmit}}),t._v(" "),e("div",{staticClass:"comment__submit"},[e("NcButton",{attrs:{type:"tertiary-no-background","native-type":"submit","aria-label":t.t("comments","Post comment"),disabled:t.isEmptyMessage},on:{click:t.onSubmit},scopedSlots:t._u([{key:"icon",fn:function(){return[t.loading?e("NcLoadingIcon"):e("IconArrowRight",{attrs:{size:20}})]},proxy:!0}],null,!1,758946661)})],1)],1),t._v(" "),e("div",{staticClass:"comment__editor-description",attrs:{id:"tab-comments__editor-description"}},[t._v("\n\t\t\t\t"+t._s(t.t("comments","@ for mentions, : for emoji, / for smart picker"))+"\n\t\t\t")])]):e("div",{staticClass:"comment__message",class:{"comment__message--expanded":t.expanded},domProps:{innerHTML:t._s(t.renderedContent)},on:{click:t.onExpand}})])])},a=[]},21295:(t,e,n)=>{n.d(e,{X:()=>o,Y:()=>a});var o=function(){var t=this;return(0,t._self._c)("span",{staticClass:"live-relative-timestamp",attrs:{"data-timestamp":1e3*t.timestamp,title:t.title}},[t._v(t._s(t.formatted))])},a=[]},72894:(t,e,n)=>{n.d(e,{A:()=>r});var o=n(71354),a=n.n(o),i=n(76314),s=n.n(i)()(a());s.push([t.id,".comment[data-v-39ff2081]{display:flex;gap:8px;padding:5px 10px}.comment__side[data-v-39ff2081]{display:flex;align-items:flex-start;padding-top:6px}.comment__body[data-v-39ff2081]{display:flex;flex-grow:1;flex-direction:column}.comment__header[data-v-39ff2081]{display:flex;align-items:center;min-height:44px}.comment__actions[data-v-39ff2081]{margin-left:10px !important}.comment__author[data-v-39ff2081]{overflow:hidden;white-space:nowrap;text-overflow:ellipsis;color:var(--color-text-maxcontrast)}.comment_loading[data-v-39ff2081],.comment__timestamp[data-v-39ff2081]{margin-left:auto;text-align:right;white-space:nowrap;color:var(--color-text-maxcontrast)}.comment__editor-group[data-v-39ff2081]{position:relative}.comment__editor-description[data-v-39ff2081]{color:var(--color-text-maxcontrast);padding-block:var(--default-grid-baseline)}.comment__submit[data-v-39ff2081]{position:absolute !important;bottom:0;right:0}.comment__message[data-v-39ff2081]{white-space:pre-wrap;word-break:break-word;max-height:70px;overflow:hidden;margin-top:-6px}.comment__message--expanded[data-v-39ff2081]{max-height:none;overflow:visible}.rich-contenteditable__input[data-v-39ff2081]{min-height:44px;margin:0;padding:10px}","",{version:3,sources:["webpack://./apps/comments/src/components/Comment.vue"],names:[],mappings:"AAKA,0BACC,YAAA,CACA,OAAA,CACA,gBAAA,CAEA,gCACC,YAAA,CACA,sBAAA,CACA,eAAA,CAGD,gCACC,YAAA,CACA,WAAA,CACA,qBAAA,CAGD,kCACC,YAAA,CACA,kBAAA,CACA,eAAA,CAGD,mCACC,2BAAA,CAGD,kCACC,eAAA,CACA,kBAAA,CACA,sBAAA,CACA,mCAAA,CAGD,uEAEC,gBAAA,CACA,gBAAA,CACA,kBAAA,CACA,mCAAA,CAGD,wCACC,iBAAA,CAGD,8CACC,mCAAA,CACA,0CAAA,CAGD,kCACC,4BAAA,CACA,QAAA,CACA,OAAA,CAGD,mCACC,oBAAA,CACA,qBAAA,CACA,eAAA,CACA,eAAA,CACA,eAAA,CACA,6CACC,eAAA,CACA,gBAAA,CAKH,8CACC,eAAA,CACA,QAAA,CACA,YA3EiB",sourcesContent:['\n@use "sass:math";\n\n$comment-padding: 10px;\n\n.comment {\n\tdisplay: flex;\n\tgap: 8px;\n\tpadding: 5px $comment-padding;\n\n\t&__side {\n\t\tdisplay: flex;\n\t\talign-items: flex-start;\n\t\tpadding-top: 6px;\n\t}\n\n\t&__body {\n\t\tdisplay: flex;\n\t\tflex-grow: 1;\n\t\tflex-direction: column;\n\t}\n\n\t&__header {\n\t\tdisplay: flex;\n\t\talign-items: center;\n\t\tmin-height: 44px;\n\t}\n\n\t&__actions {\n\t\tmargin-left: $comment-padding !important;\n\t}\n\n\t&__author {\n\t\toverflow: hidden;\n\t\twhite-space: nowrap;\n\t\ttext-overflow: ellipsis;\n\t\tcolor: var(--color-text-maxcontrast);\n\t}\n\n\t&_loading,\n\t&__timestamp {\n\t\tmargin-left: auto;\n\t\ttext-align: right;\n\t\twhite-space: nowrap;\n\t\tcolor: var(--color-text-maxcontrast);\n\t}\n\n\t&__editor-group {\n\t\tposition: relative;\n\t}\n\n\t&__editor-description {\n\t\tcolor: var(--color-text-maxcontrast);\n\t\tpadding-block: var(--default-grid-baseline);\n\t}\n\n\t&__submit {\n\t\tposition: absolute !important;\n\t\tbottom: 0;\n\t\tright: 0;\n\t}\n\n\t&__message {\n\t\twhite-space: pre-wrap;\n\t\tword-break: break-word;\n\t\tmax-height: 70px;\n\t\toverflow: hidden;\n\t\tmargin-top: -6px;\n\t\t&--expanded {\n\t\t\tmax-height: none;\n\t\t\toverflow: visible;\n\t\t}\n\t}\n}\n\n.rich-contenteditable__input {\n\tmin-height: 44px;\n\tmargin: 0;\n\tpadding: $comment-padding;\n}\n\n'],sourceRoot:""}]);const r=s},94177:(t,e,n)=>{var o=n(85072),a=n.n(o),i=n(97825),s=n.n(i),r=n(77659),c=n.n(r),m=n(55056),d=n.n(m),l=n(10540),A=n.n(l),p=n(41113),u=n.n(p),C=n(72894),g={};g.styleTagTransform=u(),g.setAttributes=d(),g.insert=c().bind(null,"head"),g.domAPI=s(),g.insertStyleElement=A(),a()(C.A,g),C.A&&C.A.locals&&C.A.locals},65463:(t,e,n)=>{n.d(e,{A:()=>i});var o=n(79156),a=n(54416);n(63148);const i=(0,n(14486).A)(a.A,o.X,o.Y,!1,null,"39ff2081",null).exports},16356:(t,e,n)=>{n.d(e,{A:()=>i});var o=n(21295),a=n(52547);const i=(0,n(14486).A)(a.A,o.X,o.Y,!1,null,null,null).exports},54416:(t,e,n)=>{n.d(e,{A:()=>o});const o=n(29369).A},52547:(t,e,n)=>{n.d(e,{A:()=>o});const o=n(28164).A},63148:(t,e,n)=>{n(94177)}}]);
//# sourceMappingURL=7462-7462.js.map?v=66d4eb71ff3b34fc1e67