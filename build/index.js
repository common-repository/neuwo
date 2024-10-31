(()=>{"use strict";var e={n:t=>{var a=t&&t.__esModule?()=>t.default:()=>t;return e.d(a,{a}),a},d:(t,a)=>{for(var n in a)e.o(a,n)&&!e.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:a[n]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};e.r(t),e.d(t,{getData:()=>g});var a={};e.r(a),e.d(a,{addTag:()=>w,populateData:()=>p});var n={};e.r(n),e.d(n,{getData:()=>_});const r=window.React,o=window.wp.plugins,i=window.wp.editPost,s=window.wp.apiFetch;var l=e.n(s);const c=window.wp.components,d=window.wp.element,u=window.wp.data,g=e=>e,w=e=>({type:"ADD_TAG",tag:e}),p=e=>({type:"POPULATE_DATA",neuwoData:e}),m={FETCH_DATA:({postId:e})=>l()({path:"/neuwo/v1/getData?postId="+e}).then((e=>e))};function*_(){const e=yield wp.data.select("core/editor").getCurrentPostId(),t=yield(e=>({type:"FETCH_DATA",postId:e}))(e);if(""!==t)return p(t);console.log("Neuwo data not set.")}const v={tags:[],brand_safety:{BS_score:"",BS_indication:""},marketing_categories:{iab_tier_1:[],iab_tier_2:[]}},b=(0,u.createReduxStore)("neuwo",{reducer(e=v,t){switch(t.type){case"ADD_TAG":return{...e,tags:[...e.tags,t.tag]};case"POPULATE_DATA":return{...t.neuwoData};default:return e}},actions:a,selectors:t,resolvers:n,controls:m});(0,u.register)(b);const E=()=>{const[e,t]=(0,d.useState)(!1),a=(0,u.useSelect)((e=>{const t=e("neuwo");return t&&t.getData()}),[]),[n,o]=(0,d.useState)([]),[s,g]=(0,d.useState)(!1);return(0,r.createElement)(i.PluginDocumentSettingPanel,{title:"Neuwo.ai Keywords",icon:"tag"},(0,r.createElement)(c.Button,{variant:"secondary",disabled:e,onClick:function(){t(!0),g(!1),wp.data.dispatch("core/editor").savePost().then((function(){const e=wp.data.select("core/editor").getCurrentPost().id;l()({path:"/neuwo/v1/getAiTopics?postId="+e}).then((e=>{e.error?(console.warn(e),e.error.errors&&e.error.errors.post_content_is_empty&&g(!0),t(!1)):wp.data.dispatch("neuwo").invalidateResolution("getData").then((()=>{t(!1)}))}))}))}},"Get keywords"),e&&(0,r.createElement)(c.Spinner,null),s&&(0,r.createElement)("div",null,"Blogipostauksen tekstisisältö vaikuttaa tyhjältä."),(0,r.createElement)("div",{id:"neuwoDataDiv"},a.tags&&0!==a.tags.length&&(0,r.createElement)("div",{id:"neuwoAiTopics"},(0,r.createElement)("h4",null,(0,r.createElement)("strong",null,"AI Topics")),(0,r.createElement)("ul",null,a.tags.map((e=>(0,r.createElement)("li",{key:e.value},e.value," ",(0,r.createElement)("span",{className:"neuwo_tag_score"},e.score),(0,r.createElement)(c.Button,{className:"button gutenberg_neuwo_add_keyword_as_tag_btn",disabled:e.addedToPost||n.includes(e.value),onClick:function(){return t(!0),new Promise(((t,n)=>{if(e.WPTagId)t(e.WPTagId);else{if(a.allWPTags){const n=a.allWPTags.find((t=>t.name===e.value));if(n&&n.term_id)return void t(n.term_id)}wp.data.dispatch("core").saveEntityRecord("taxonomy","post_tag",{name:e.value}).then((e=>{e?t(e.id):n("Problem creating a new post_tag via WP Rest API, check the POST request from web browser's Developer Tools' network tab for errors.")}),n)}})).then((e=>{const t=wp.data.select("core/editor").getEditedPostAttribute("tags").slice();return t.indexOf(e)<0&&t.push(e),wp.data.dispatch("core/editor").editPost({tags:t})})).then((()=>(o(n.concat(e.value)),wp.data.dispatch("neuwo").invalidateResolution("getData").then((()=>{t(!1)}))))).catch((e=>{console.warn("[neuwo/add_keyword_as_tag_btn]",e),t(!1)}))}},"+")))))),a.brand_safety&&a.brand_safety.BS_indication&&(0,r.createElement)("div",{id:"neuwoBrandSafety"},(0,r.createElement)("h4",null,"Brand Safety"),(0,r.createElement)("p",null,a.brand_safety.BS_indication),(0,r.createElement)("p",null,(0,r.createElement)("span",{className:"neuwo_tag_score"},a.brand_safety.BS_score))),a.marketing_categories&&a.marketing_categories.iab_tier_1&&0!==a.marketing_categories.iab_tier_1.length&&(0,r.createElement)("div",{id:"neuwoMarketingCategories"},(0,r.createElement)("h4",null,"IAB Marketing Categories"),(0,r.createElement)("h5",null,"Tier 1"),(0,r.createElement)("ul",null,a.marketing_categories.iab_tier_1&&a.marketing_categories.iab_tier_1.map((e=>(0,r.createElement)("li",{key:e.value},e.ID," ",e.label," ",(0,r.createElement)("span",{className:"neuwo_tag_score"},e.relevance))))),(0,r.createElement)("h5",null,"Tier 2"),(0,r.createElement)("ul",null,a.marketing_categories.iab_tier_2&&a.marketing_categories.iab_tier_2.map((e=>(0,r.createElement)("li",{key:e.value},e.ID," ",e.label," ",(0,r.createElement)("span",{className:"neuwo_tag_score"},e.relevance))))))))},h=()=>{const e=(0,u.useSelect)((e=>e("core/editor").getEditedPostAttribute("meta"))),[t,a]=(0,d.useState)(!1),[n,o]=(0,d.useState)(e.neuwo_exclude_from_similarity);return(0,r.createElement)(i.PluginPostStatusInfo,null,(0,r.createElement)(c.CheckboxControl,{label:"Exclude from Neuwo suggestions",disabled:t,checked:n,onChange:e=>{const t=wp.data.select("core/editor").getCurrentPost().id;a(!0),l()({path:"/neuwo/v1/excludeFromSimilarity",method:"post",data:{exclude:e.toString(),postId:t.toString()}}).then((e=>{o(e),a(!1)}))}}))};function y(){const e=wp.data.select("core/editor").isSavingPost()||wp.data.select("core/editor").isAutosavingPost(),t=wp.data.select("core/editor").isEditedPostSaveable(),a=wp.data.select("core/editor").isPostSavingLocked(),n=wp.data.select("core/editor").hasNonPostEntityChanges();return!wp.data.select("core/editor").isAutosavingPost()&&e&&(e||!t||a)&&!n}(0,o.registerPlugin)("neuwo-gutenberg",{render:()=>(0,r.createElement)(r.Fragment,null,(0,r.createElement)(E,null),(0,r.createElement)(h,null))});let P=y();wp.data.subscribe((()=>{const e=y(),t=P&&!e;P=e,t&&wp.data.dispatch("neuwo").invalidateResolution("getData")}))})();