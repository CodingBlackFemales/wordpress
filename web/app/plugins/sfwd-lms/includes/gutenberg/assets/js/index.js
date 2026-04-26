(()=>{"use strict";var e={n:t=>{var a=t&&t.__esModule?()=>t.default:()=>t;return e.d(a,{a}),a},d:(t,a)=>{for(var l in a)e.o(a,l)&&!e.o(t,l)&&Object.defineProperty(t,l,{enumerable:!0,get:a[l]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.wp.element,a=window.wp.i18n,l=window.wp.blocks,r=window.wp.blockEditor,s=window.wp.components,n=window.wp.serverSideRender;var o=e.n(n);const i="learndash/ld-login",d=(0,a.__)("LearnDash Login","learndash");(0,l.registerBlockType)(i,{title:d,description:(0,a.__)("This block adds the login button on any page","learndash"),icon:"admin-network",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{login_url:{type:"string",default:""},login_label:{type:"string",default:""},login_placement:{type:"string",default:""},login_button:{type:"string",default:""},logout_url:{type:"string",default:""},logout_label:{type:"string",default:""},logout_placement:{type:"string",default:"right"},logout_button:{type:"string",default:""},preview_show:{type:"boolean",default:!0},preview_action:{type:"string",default:""},example_show:{type:"boolean",default:0}},edit:function(e){const{attributes:{login_url:l,login_label:n,login_placement:u,login_button:c,logout_url:p,logout_label:_,logout_placement:h,logout_button:g,preview_show:m,preview_action:b,example_show:f},setAttributes:y}=e,w=(0,r.useBlockProps)(),v=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Login Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.__)("Login URL","learndash"),help:(0,a.__)("Override default login URL","learndash"),value:l||"",onChange:e=>y({login_url:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Login Label","learndash"),help:(0,a.__)('Override default label "Login"',"learndash"),value:n||"",onChange:e=>y({login_label:e})}),(0,t.createElement)(s.SelectControl,{key:"login_placement",label:(0,a.__)("Login Icon Placement","learndash"),value:u,options:[{label:(0,a.__)("Left - To left of label","learndash"),value:""},{label:(0,a.__)("Right - To right of label","learndash"),value:"right"},{label:(0,a.__)("None - No icon","learndash"),value:"none"}],onChange:e=>y({login_placement:e})}),(0,t.createElement)(s.SelectControl,{key:"login_button",label:(0,a.__)("Login Displayed as","learndash"),help:(0,a.__)("Display as Button or link","learndash"),value:c,options:[{label:(0,a.__)("Button","learndash"),value:""},{label:(0,a.__)("Link","learndash"),value:"link"}],onChange:e=>y({login_button:e})})),C=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Logout Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.__)("Logout URL","learndash"),help:(0,a.__)("Override default logout URL","learndash"),value:p||"",onChange:e=>y({logout_url:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Logout Label","learndash"),help:(0,a.__)('Override default label "Logout"',"learndash"),value:_||"",onChange:e=>y({logout_label:e})}),(0,t.createElement)(s.SelectControl,{key:"logout_placement",label:(0,a.__)("Logout Icon Placement","learndash"),value:h,options:[{label:(0,a.__)("Left - To left of label","learndash"),value:"left"},{label:(0,a.__)("Right - To right of label","learndash"),value:"right"},{label:(0,a.__)("None - No icon","learndash"),value:"none"}],onChange:e=>y({logout_placement:e})}),(0,t.createElement)(s.SelectControl,{key:"logout_button",label:(0,a.__)("Logout Displayed as","learndash"),help:(0,a.__)("Display as Button or link","learndash"),value:g,options:[{label:(0,a.__)("Button","learndash"),value:""},{label:(0,a.__)("Link","learndash"),value:"link"}],onChange:e=>y({logout_button:e})})),E=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!m,onChange:e=>y({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.SelectControl,{key:"preview_action",label:(0,a.__)("Preview Action","learndash"),value:b,options:[{label:(0,a.__)("Login","learndash"),value:"login"},{label:(0,a.__)("Logout","learndash"),value:"logout"}],onChange:e=>y({preview_action:e})})),x=(0,t.createElement)(r.InspectorControls,{key:"controls"},v,C,E);function k(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),d)}function T(e){return k()}return(0,t.createElement)("div",w,x,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(0,t.createElement)(o(),{block:i,attributes:a,key:i,EmptyResponsePlaceholder:T}):k();var a},[e.attributes])))},save:e=>{}});const u=window.learndash||{};u.global=u.global||{},u.global.scrollOffset=u.global.scrollOffset||0,window.learndash=window.learndash||{},learndash.customLabel=learndash.customLabel||{},learndash.customLabel={get:function(e=""){return void 0!==ldlms_settings.meta.post&&""!==e&&void 0!==ldlms_settings.settings.custom_labels[e]?ldlms_settings.settings.custom_labels[e]:e},getLower:function(e=""){return void 0!==ldlms_settings.meta.post&&""!==e&&void 0!==ldlms_settings.settings.custom_labels[e+"_lower"]?ldlms_settings.settings.custom_labels[e+"_lower"]:e},getSlug:function(e=""){return""!==e&&void 0!==ldlms_settings.settings.custom_labels[e+"_slug"]?ldlms_settings.settings.custom_labels[e+"_slug"]:e}},learndash.scrollTo=function(e){const t=function(){const e=document.querySelector("#wpadminbar");return e&&e.offsetHeight||0}(),a=e.getBoundingClientRect().top+window.scrollY;window.scroll({top:a-t-parseInt(u.global.scrollOffset,10)})};const c=learndash.customLabel.get;function p(e=""){return""!==e&&void 0!==ldlms_settings.meta.post[e]?ldlms_settings.meta.post[e]:void 0!==ldlms_settings.meta.post?ldlms_settings.meta.post:void 0}function _(e=""){if(""!==e){if(void 0!==ldlms_settings.settings.per_page[e])return ldlms_settings.settings.per_page[e]}else if(void 0!==ldlms_settings.meta.posts_per_page)return ldlms_settings.meta.posts_per_page}function h(e){if(void 0===e)return 0;const t=parseInt(e);return isNaN(t)?0:t}function g(){return void 0!==ldlms_settings.templates.active?ldlms_settings.templates.active:""}function m(){let e=function(){if("legacy"==g()){let e=function(){if(void 0!==ldlms_settings.templates.list){let e=g();if(void 0!==e&&""!==e&&void 0!==ldlms_settings.templates.list[e])return ldlms_settings.templates.list[e]}return""}();return sprintf(
// translators: placeholder: current template name.
// translators: placeholder: current template name.
(0,a._x)('The current LearnDash template "%s" does not support this block. Please select a different template.',"placeholder: current template name","learndash"),e)}return""}();return""!==e?(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Warning","learndash"),opened:!0},(0,t.createElement)(s.TextControl,{help:e,value:"",type:"hidden",className:"notice notice-error"})):""}learndash.customLabel.getLower,learndash.customLabel.getSlug;const b="learndash/ld-profile",f=(0,a.__)("LearnDash Profile","learndash");(0,l.registerBlockType)(b,{title:f,description:(0,a.sprintf)(
// translators: placeholders: Courses, Course, Quiz.
// translators: placeholders: Courses, Course, Quiz.
(0,a._x)("Displays user's enrolled %1$s, %2$s progress, %3$s scores, and achieved certificates.","placeholders: Courses, Course, Quiz","learndash"),c("courses"),c("course"),c("quiz")),icon:"id-alt",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{per_page:{type:"string",default:""},orderby:{type:"string",default:"ID"},order:{type:"string",default:"DESC"},course_points_user:{type:"boolean",default:1},expand_all:{type:"boolean",default:0},profile_link:{type:"boolean",default:1},show_header:{type:"boolean",default:1},show_search:{type:"boolean",default:1},show_quizzes:{type:"boolean",default:1},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},example_show:{type:"boolean",default:0},quiz_num:{type:"string",default:""},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{per_page:l,orderby:n,order:i,course_points_user:d,expand_all:u,profile_link:h,show_header:g,show_search:m,show_quizzes:y,preview_user_id:w,preview_show:v,quiz_num:C,example_show:E},setAttributes:x}=e,k=(0,r.useBlockProps)(),T=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("%s per page","placeholder: Courses","learndash"),c("courses")),help:(0,a.sprintf)(
// translators: placeholder: per_page.
// translators: placeholder: per_page.
(0,a._x)("Leave empty for default (%d) or 0 to show all items.","placeholder: per_page","learndash"),_("per_page")),value:l||"",type:"number",onChange:function(e){x(""!=e&&e<0?{per_page:"0"}:{per_page:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholders: Quiz, Course.
// translators: placeholders: Quiz, Course.
(0,a._x)("%1$s attempts per %2$s","placeholders: Quiz, Course","learndash"),c("quiz"),c("course")),help:(0,a.sprintf)(
// translators: placeholder: per_page.
// translators: placeholder: per_page.
(0,a._x)("Leave empty for default (%d) or 0 to show all attempts.","placeholder: per_page","learndash"),_("per_page")),value:C||"",type:"number",onChange:function(e){x(""!=e&&e<0?{quiz_num:"0"}:{quiz_num:e})}}),(0,t.createElement)(s.SelectControl,{key:"orderby",label:(0,a.__)("Order by","learndash"),value:n,options:[{label:(0,a.__)("ID - Order by post id. (default)","learndash"),value:"ID"},{label:(0,a.__)("Title - Order by post title","learndash"),value:"title"},{label:(0,a.__)("Date - Order by post date","learndash"),value:"date"},{label:(0,a.__)("Menu - Order by Page Order Value","learndash"),value:"menu_order"}],onChange:e=>x({orderby:e})}),(0,t.createElement)(s.SelectControl,{key:"order",label:(0,a.__)("Order","learndash"),value:i,options:[{label:(0,a.__)("DESC - highest to lowest values (default)","learndash"),value:"DESC"},{label:(0,a.__)("ASC - lowest to highest values","learndash"),value:"ASC"}],onChange:e=>x({order:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Search","learndash"),checked:!!m,onChange:e=>x({show_search:e}),help:(0,a.__)("LD30 template only","learndash")}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Profile Header","learndash"),checked:!!g,onChange:e=>x({show_header:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("Show Earned %s Points","placeholder: Course","learndash"),c("course")),checked:!!d,onChange:e=>x({course_points_user:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Profile Link","learndash"),checked:!!h,onChange:e=>x({profile_link:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("Show User %s Attempts","placeholder: Quiz","learndash"),c("quiz")),checked:!!y,onChange:e=>x({show_quizzes:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("Expand All %s Sections","placeholder: Course","learndash"),c("course")),checked:!!u,onChange:e=>x({expand_all:e})})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!v,onChange:e=>x({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:w||"",type:"number",onChange:function(e){x(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function P(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),f)}function D(e){return P()}return(0,t.createElement)("div",k,T,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:b,attributes:a,key:b,EmptyResponsePlaceholder:D})):P();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const y="learndash/ld-course-list",w=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s List","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)(y,{title:w,description:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("This block shows a list of %s.","placeholder: Courses","learndash"),c("courses")),icon:"list-view",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{orderby:{type:"string",default:"ID"},order:{type:"string",default:"DESC"},per_page:{type:"string",default:""},mycourses:{type:"string",default:""},status:{type:"array",default:["not_started","in_progress","completed"]},show_content:{type:"boolean",default:!0},show_thumbnail:{type:"boolean",default:!0},course_category_name:{type:"string",default:""},course_cat:{type:"string",default:""},course_categoryselector:{type:"boolean",default:!1},course_tag:{type:"string",default:""},course_tag_id:{type:"string",default:""},category_name:{type:"string",default:""},cat:{type:"string",default:""},categoryselector:{type:"boolean",default:!1},tag:{type:"string",default:""},tag_id:{type:"string",default:""},course_grid:{type:"boolean"},progress_bar:{type:"boolean",default:!1},col:{type:"integer",default:ldlms_settings.plugins["learndash-course-grid"].col_default||3},price_type:{type:"array",default:["open","free","paynow","subscribe","closed"]},preview_show:{type:"boolean",default:!0},preview_user_id:{type:"string",default:""},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{orderby:l,order:n,per_page:i,mycourses:d,status:u,show_content:h,show_thumbnail:g,course_category_name:m,course_cat:b,course_categoryselector:f,course_tag:v,course_tag_id:C,category_name:E,cat:x,categoryselector:k,tag:T,tag_id:P,course_grid:D,progress_bar:I,col:S,preview_user_id:z,preview_show:B,example_show:q,price_type:L},setAttributes:O}=e,U=(0,r.useBlockProps)();let N="",A="",$="",G=!0;if(!0===ldlms_settings.plugins["learndash-course-grid"].enabled){void 0===D||1!=D&&0!=D||(G=D);let e=!1;1==G&&(e=!0),$=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Grid Settings","learndash"),initialOpen:e},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Grid","learndash"),checked:!!G,onChange:e=>O({course_grid:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Progress Bar","learndash"),checked:!!I,onChange:e=>O({progress_bar:e})}),(0,t.createElement)(s.RangeControl,{label:(0,a.__)("Columns","learndash"),value:S||ldlms_settings.plugins["learndash-course-grid"].col_default,min:1,max:ldlms_settings.plugins["learndash-course-grid"].col_max,step:1,onChange:e=>O({col:e})}))}N=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Content","learndash"),checked:!!h,onChange:e=>O({show_content:e})}),A=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Thumbnail","learndash"),checked:!!g,onChange:e=>O({show_thumbnail:e})});const R=(0,t.createElement)(s.PanelBody,{className:"learndash-block-controls-panel learndash-block-controls-panel-ld-course-list",title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.SelectControl,{key:"orderby",label:(0,a.__)("Order by","learndash"),value:l,options:[{label:(0,a.__)("ID - Order by post id. (default)","learndash"),value:"ID"},{label:(0,a.__)("Title - Order by post title","learndash"),value:"title"},{label:(0,a.__)("Date - Order by post date","learndash"),value:"date"},{label:(0,a.__)("Menu - Order by Page Order Value","learndash"),value:"menu_order"}],onChange:e=>O({orderby:e})}),(0,t.createElement)(s.SelectControl,{key:"order",label:(0,a.__)("Order","learndash"),value:n,options:[{label:(0,a.__)("DESC - highest to lowest values (default)","learndash"),value:"DESC"},{label:(0,a.__)("ASC - lowest to highest values","learndash"),value:"ASC"}],onChange:e=>O({order:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("%s per page","placeholder: Courses","learndash"),c("courses")),help:(0,a.sprintf)(
// translators: placeholder: default per page.
// translators: placeholder: default per page.
(0,a._x)("Leave empty for default (%d) or 0 to show all items.","placeholder: default per page","learndash"),_("per_page")),value:i||"",type:"number",onChange:function(e){O(""!=e&&e<0?{per_page:"0"}:{per_page:e})}}),(0,t.createElement)(s.SelectControl,{multiple:!0,key:"price_type",label:(0,a.sprintf)(
// translators: placeholder: Course Access Mode(s).
// translators: placeholder: Course Access Mode(s).
(0,a._x)("%s Access Mode(s)","placeholder: Course Access Mode(s)","learndash"),c("course")),help:(0,a.__)("Ctrl+click to deselect selected items.","learndash"),value:L,options:[{label:(0,a.__)("Open","learndash"),value:"open"},{label:(0,a.__)("Free","learndash"),value:"free"},{label:(0,a.__)("Buy Now","learndash"),value:"paynow"},{label:(0,a.__)("Recurring","learndash"),value:"subscribe"},{label:(0,a.__)("Closed","learndash"),value:"closed"}],onChange:e=>O({price_type:e})}),(0,t.createElement)(s.SelectControl,{key:"mycourses",label:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("My %s","placeholder: Courses","learndash"),c("courses")),value:d,options:[{label:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("Show All %s (default)","placeholder: Courses","learndash"),c("courses")),value:""},{label:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("Show Enrolled %s only","placeholder: Courses","learndash"),c("courses")),value:"enrolled"},{label:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("Show not-Enrolled %s only","placeholder: Courses","learndash"),c("courses")),value:"not-enrolled"}],onChange:e=>O({mycourses:e})}),"enrolled"===d&&(0,t.createElement)(s.SelectControl,{multiple:!0,key:"status",label:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("Enrolled %s Status","placeholder: Courses","learndash"),c("courses")),help:(0,a.__)("Ctrl+click to deselect selected items.","learndash"),value:u,options:[{label:(0,a.__)("Not Started","learndash"),value:"not_started"},{label:(0,a.__)("In Progress","learndash"),value:"in_progress"},{label:(0,a.__)("Completed","learndash"),value:"completed"}],onChange:e=>O({status:e})}),N,A);let Q="";if("yes"===ldlms_settings.settings.courses_taxonomies.ld_course_category){let e=!1;""==m&&""==b||(e=!0),Q=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Category Settings","placeholder: Course","learndash"),c("course")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Category Slug","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("shows %s with mentioned category slug.","placeholder: Courses","learndash"),c("courses")),value:m||"",onChange:e=>O({course_category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Category ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Courses","learndash"),c("courses")),value:b||"",type:"number",onChange:function(e){O(""!=e&&e<0?{course_cat:"0"}:{course_cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Category Selector","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("shows a %s category dropdown.","placeholder: Courses","learndash"),c("courses")),checked:!!f,onChange:e=>O({course_categoryselector:e})}))}let M="";if("yes"===ldlms_settings.settings.courses_taxonomies.ld_course_tag){let e=!1;""==v&&""==C||(e=!0),M=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Tag Settings","placeholder: Course","learndash"),c("course")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Tag Slug","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("shows %s with mentioned tag slug.","placeholder: Courses","learndash"),c("courses")),value:v||"",onChange:e=>O({course_tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Tag ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("shows %s with mentioned tag ID.","placeholder: Courses","learndash"),c("courses")),value:C||"",type:"number",onChange:function(e){O(""!=e&&e<0?{course_tag_id:"0"}:{course_tag_id:e})}}))}let V="";if("yes"===ldlms_settings.settings.courses_taxonomies.wp_post_category){let e=!1;""==E&&""==x||(e=!0),V=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Category Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Category Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("shows %s with mentioned WP Category slug.","placeholder: Courses","learndash"),c("courses")),value:E||"",onChange:e=>O({category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Category ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Courses","learndash"),c("courses")),value:x||"",type:"number",onChange:function(e){O(""!=e&&e<0?{cat:"0"}:{cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("WP Category Selector","learndash"),help:(0,a.__)("shows a WP category dropdown.","learndash"),checked:!!k,onChange:e=>O({categoryselector:e})}))}let W="";if("yes"===ldlms_settings.settings.courses_taxonomies.wp_post_tag){let e=!1;""==T&&""==P||(e=!0),W=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Tag Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("shows %s with mentioned WP tag slug.","placeholder: Courses","learndash"),c("courses")),value:T||"",onChange:e=>O({tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag ID","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("shows %s with mentioned WP tag ID.","placeholder: Courses","learndash"),c("courses")),value:P||"",type:"number",onChange:function(e){O(""!=e&&e<0?{tag_id:"0"}:{tag_id:e})}}))}const j=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!B,onChange:e=>O({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:z||"",type:"number",onChange:function(e){O(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})),F=(0,t.createElement)(r.InspectorControls,{key:"controls"},R,$,Q,M,V,W,j);function H(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),w)}function Y(e){return H()}return(0,t.createElement)("div",U,F,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:y,attributes:a,key:y,EmptyResponsePlaceholder:Y})):H();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const v="learndash/ld-lesson-list",C=(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("LearnDash %s List","placeholder: Lesson","learndash"),c("lesson"));(0,l.registerBlockType)(v,{title:C,description:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("This block shows a list of %s.","placeholder: Lessons","learndash"),c("lessons")),icon:"list-view",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{orderby:{type:"string",default:"ID"},order:{type:"string",default:"DESC"},per_page:{type:"string",default:""},course_id:{type:"string",default:""},show_content:{type:"boolean",default:!0},show_thumbnail:{type:"boolean",default:!0},lesson_category_name:{type:"string",default:""},lesson_cat:{type:"string",default:""},lesson_categoryselector:{type:"boolean",default:!1},lesson_tag:{type:"string",default:""},lesson_tag_id:{type:"string",default:""},category_name:{type:"string",default:""},cat:{type:"string",default:""},categoryselector:{type:"boolean",default:!1},tag:{type:"string",default:""},tag_id:{type:"string",default:""},course_grid:{type:"boolean"},col:{type:"integer",default:ldlms_settings.plugins["learndash-course-grid"].enabled.col_default||3},preview_show:{type:"boolean",default:!0},preview_user_id:{type:"string",default:""},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{orderby:l,order:n,per_page:i,course_id:d,show_content:u,show_thumbnail:h,lesson_category_name:g,lesson_cat:m,lesson_categoryselector:b,lesson_tag:f,lesson_tag_id:y,category_name:w,cat:E,categoryselector:x,tag:k,tag_id:T,course_grid:P,col:D,preview_show:I,preview_user_id:S,example_show:z},setAttributes:B}=e,q=(0,r.useBlockProps)();let L="",O="",U="",N=!0;if(!0===ldlms_settings.plugins["learndash-course-grid"].enabled){void 0===P||1!=P&&0!=P||(N=P);let e=!1;1==N&&(e=!0),U=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Grid Settings","learndash"),initialOpen:e},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Grid","learndash"),checked:!!N,onChange:e=>B({course_grid:e})}),(0,t.createElement)(s.RangeControl,{label:(0,a.__)("Columns","learndash"),value:D||ldlms_settings.plugins["learndash-course-grid"].enabled.col_default,min:1,max:ldlms_settings.plugins["learndash-course-grid"].enabled.col_max,step:1,onChange:e=>B({col:e})}))}L=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Content","learndash"),checked:!!u,onChange:e=>B({show_content:e})}),O=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Thumbnail","learndash"),checked:!!h,onChange:e=>B({show_thumbnail:e})});const A=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID to limit listing. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:d||"",type:"number",onChange:function(e){B(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.SelectControl,{key:"orderby",label:(0,a.__)("Order by","learndash"),value:l,options:[{label:(0,a.__)("ID - Order by post id. (default)","learndash"),value:"ID"},{label:(0,a.__)("Title - Order by post title","learndash"),value:"title"},{label:(0,a.__)("Date - Order by post date","learndash"),value:"date"},{label:(0,a.__)("Menu - Order by Page Order Value","learndash"),value:"menu_order"}],onChange:e=>B({orderby:e})}),(0,t.createElement)(s.SelectControl,{key:"order",label:(0,a.__)("Order","learndash"),value:n,options:[{label:(0,a.__)("DESC - highest to lowest values (default)","learndash"),value:"DESC"},{label:(0,a.__)("ASC - lowest to highest values","learndash"),value:"ASC"}],onChange:e=>B({order:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("%s per page","placeholder: Lessons","learndash"),c("lessons")),help:(0,a.sprintf)(
// translators: placeholder: per_page.
// translators: placeholder: per_page.
(0,a._x)("Leave empty for default (%d) or 0 to show all items.","placeholder: per_page","learndash"),_("per_page")),value:i||"",type:"number",onChange:function(e){B(""!=e&&e<0?{per_page:"0"}:{per_page:e})}}),L,O);let $="";if("yes"===ldlms_settings.settings.lessons_taxonomies.ld_lesson_category){let e=!1;""==g&&""==m||(e=!0),$=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s Category Settings","placeholder: Lesson","learndash"),c("lesson")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s Category Slug","placeholder: Lesson","learndash"),c("lesson")),help:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("shows %s with mentioned category slug.","placeholder: Lessons","learndash"),c("lessons")),value:g||"",onChange:e=>B({lesson_category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s Category ID","placeholder: Lesson","learndash"),c("lesson")),help:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Lessons","learndash"),c("lessons")),value:m||"",type:"number",onChange:function(e){B(""!=e&&e<0?{lesson_cat:"0"}:{lesson_cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s Category Selector","placeholder: Lesson","learndash"),c("lesson")),help:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("shows a %s category dropdown.","placeholder: Lessons","learndash"),c("lessons")),checked:!!b,onChange:e=>B({lesson_categoryselector:e})}))}let G="";if("yes"===ldlms_settings.settings.lessons_taxonomies.ld_lesson_tag){let e=!1;""==f&&""==y||(e=!0),G=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s Tag Settings","placeholder: Lesson","learndash"),c("lesson")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s Tag Slug","placeholder: Lesson","learndash"),c("lesson")),help:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("shows %s with mentioned tag slug.","placeholder: Lessons","learndash"),c("lessons")),value:f||"",onChange:e=>B({lesson_tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s Tag ID","placeholder: Lesson","learndash"),c("lesson")),help:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("shows %s with mentioned tag ID.","placeholder: Lessons","learndash"),c("lessons")),value:y||"",type:"number",onChange:function(e){B(""!=e&&e<0?{lesson_tag_id:"0"}:{lesson_tag_id:e})}}))}let R="";if("yes"===ldlms_settings.settings.lessons_taxonomies.wp_post_category){let e=!1;""==w&&""==E||(e=!0),R=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Category Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Category Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("shows %s with mentioned WP Category slug.","placeholder: Lessons","learndash"),c("lessons")),value:w||"",onChange:e=>B({category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s Category ID","placeholder: Lesson","learndash"),c("lesson")),help:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Lessons","learndash"),c("lessons")),value:E||"",type:"number",onChange:function(e){B(""!=e&&e<0?{cat:"0"}:{cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("WP Category Selector","learndash"),help:(0,a.__)("shows a WP category dropdown.","learndash"),checked:!!x,onChange:e=>B({categoryselector:e})}))}let Q="";if("yes"===ldlms_settings.settings.lessons_taxonomies.wp_post_tag){let e=!1;""==k&&""==T||(e=!0),Q=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Tag Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("shows %s with mentioned WP tag slug.","placeholder: Lessons","learndash"),c("lessons")),value:k||"",onChange:e=>B({tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag ID","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Lessons.
// translators: placeholder: Lessons.
(0,a._x)("shows %s with mentioned WP tag ID.","placeholder: Lessons","learndash"),c("lessons")),value:T||"",type:"number",onChange:function(e){B(""!=e&&e<0?{tag_id:"0"}:{tag_id:e})}}))}const M=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!I,onChange:e=>B({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:S||"",type:"number",onChange:function(e){B(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})),V=(0,t.createElement)(r.InspectorControls,{key:"controls"},A,U,$,G,R,Q,M);function W(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),C)}function j(e){return W()}return(0,t.createElement)("div",q,V,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:v,attributes:a,key:v,EmptyResponsePlaceholder:j})):W();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const E="learndash/ld-topic-list",x=(0,a.sprintf)(
// translators: placeholder: Topic.
// translators: placeholder: Topic.
(0,a._x)("LearnDash %s List","placeholder: Topic","learndash"),c("topic"));(0,l.registerBlockType)(E,{title:x,description:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("This block shows a list of %s.","placeholder: Topics","learndash"),c("topics")),icon:"list-view",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{orderby:{type:"string",default:"ID"},order:{type:"string",default:"DESC"},per_page:{type:"string",default:""},course_id:{type:"string",default:""},lesson_id:{type:"string",default:""},show_content:{type:"boolean",default:!0},show_thumbnail:{type:"boolean",default:!0},topic_category_name:{type:"string",default:""},topic_cat:{type:"string",default:""},topic_categoryselector:{type:"boolean",default:!1},topic_tag:{type:"string",default:""},topic_tag_id:{type:"string",default:""},category_name:{type:"string",default:""},cat:{type:"string",default:""},categoryselector:{type:"boolean",default:!1},tag:{type:"string",default:""},tag_id:{type:"string",default:""},course_grid:{type:"boolean"},col:{type:"integer",default:ldlms_settings.plugins["learndash-course-grid"].enabled.col_default||3},example_show:{type:"boolean",default:0},preview_show:{type:"boolean",default:!0},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{orderby:l,order:n,per_page:i,course_id:d,lesson_id:u,show_content:h,show_thumbnail:g,topic_category_name:m,topic_cat:b,topic_categoryselector:f,topic_tag:y,topic_tag_id:w,category_name:v,cat:C,categoryselector:k,tag:T,tag_id:P,course_grid:D,col:I,preview_show:S,example_show:z},setAttributes:B}=e,q=(0,r.useBlockProps)();let L="",O="",U="",N=!0;if(!0===ldlms_settings.plugins["learndash-course-grid"].enabled){void 0===D||1!=D&&0!=D||(N=D);let e=!1;1==N&&(e=!0),U=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Grid Settings","learndash"),initialOpen:e},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Grid","learndash"),checked:!!N,onChange:e=>B({course_grid:e})}),(0,t.createElement)(s.RangeControl,{label:(0,a.__)("Columns","learndash"),value:I||ldlms_settings.plugins["learndash-course-grid"].enabled.col_default,min:1,max:ldlms_settings.plugins["learndash-course-grid"].enabled.col_max,step:1,onChange:e=>B({col:e})}))}L=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Content","learndash"),checked:!!h,onChange:e=>B({show_content:e})}),O=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Thumbnail","learndash"),checked:!!g,onChange:e=>B({show_thumbnail:e})});const A=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID to limit listing. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:d||"",type:"number",onChange:function(e){B(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s ID","placeholder: Lesson","learndash"),c("lesson")),help:(0,a.sprintf)(
// translators: placeholders: Lesson, Course.
// translators: placeholders: Lesson, Course.
(0,a._x)("Enter single %1$s ID to limit listing. Leave blank if used within a %2$s.","placeholders: Lesson, Course","learndash"),c("lesson"),c("course")),value:u||"",type:"number",onChange:function(e){B(""!=e&&e<0?{lesson_id:"0"}:{lesson_id:e})}}),(0,t.createElement)(s.SelectControl,{key:"orderby",label:(0,a.__)("Order by","learndash"),value:l,options:[{label:(0,a.__)("ID - Order by post id. (default)","learndash"),value:"ID"},{label:(0,a.__)("Title - Order by post title","learndash"),value:"title"},{label:(0,a.__)("Date - Order by post date","learndash"),value:"date"},{label:(0,a.__)("Menu - Order by Page Order Value","learndash"),value:"menu_order"}],onChange:e=>B({orderby:e})}),(0,t.createElement)(s.SelectControl,{key:"order",label:(0,a.__)("Order","learndash"),value:n,options:[{label:(0,a.__)("DESC - highest to lowest values (default)","learndash"),value:"DESC"},{label:(0,a.__)("ASC - lowest to highest values","learndash"),value:"ASC"}],onChange:e=>B({order:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("%s per page","placeholder: Topics","learndash"),c("topics")),help:(0,a.sprintf)(
// translators: placeholder: per_page.
// translators: placeholder: per_page.
(0,a._x)("Leave empty for default (%d) or 0 to show all items.","placeholder: per_page","learndash"),_("per_page")),value:i||"",type:"number",onChange:function(e){B(""!=e&&e<0?{per_page:"0"}:{per_page:e})}}),L,O);let $="";if("yes"===ldlms_settings.settings.topics_taxonomies.ld_topic_category){let e=!1;""==m&&""==b||(e=!0),$=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Topic.
// translators: placeholder: Topic.
(0,a._x)("%s Category Settings","placeholder: Topic","learndash"),c("topic")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Topic.
// translators: placeholder: Topic.
(0,a._x)("%s Category Slug","placeholder: Topic","learndash"),c("topic")),help:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("shows %s with mentioned category slug.","placeholder: Topics","learndash"),c("topics")),value:m||"",onChange:e=>B({topic_category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Topic.
// translators: placeholder: Topic.
(0,a._x)("%s Category ID","placeholder: Topic","learndash"),c("topic")),help:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Topics","learndash"),c("topics")),value:b||"",type:"number",onChange:function(e){B(""!=e&&e<0?{topic_cat:"0"}:{topic_cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: topic.
// translators: placeholder: topic.
(0,a._x)("%s Category Selector","placeholder: topic","learndash"),c("topic")),help:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("shows a %s category dropdown.","placeholder: Topics","learndash"),c("topics")),checked:!!f,onChange:e=>B({topic_categoryselector:e})}))}let G="";if("yes"===ldlms_settings.settings.topics_taxonomies.ld_topic_tag){let e=!1;""==y&&""==w||(e=!0),G=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Topic.
// translators: placeholder: Topic.
(0,a._x)("%s Tag Settings","placeholder: Topic","learndash"),c("topic")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Topic.
// translators: placeholder: Topic.
(0,a._x)("%s Tag Slug","placeholder: Topic","learndash"),c("topic")),help:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("shows %s with mentioned tag slug.","placeholder: Topics","learndash"),c("topics")),value:y||"",onChange:e=>B({topic_tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Topic.
// translators: placeholder: Topic.
(0,a._x)("%s Tag ID","placeholder: Topic","learndash"),c("topic")),help:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("shows %s with mentioned tag ID.","placeholder: Topics","learndash"),c("topics")),value:w||"",type:"number",onChange:function(e){B(""!=e&&e<0?{topic_tag_id:"0"}:{topic_tag_id:e})}}))}let R="";if("yes"===ldlms_settings.settings.topics_taxonomies.wp_post_category){let e=!1;""==v&&""==C||(e=!0),R=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Category Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Category Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("shows %s with mentioned WP Category slug.","placeholder: Topics","learndash"),c("topics")),value:v||"",onChange:e=>B({category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Topic.
// translators: placeholder: Topic.
(0,a._x)("%s Category ID","placeholder: Topic","learndash"),c("topic")),help:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Topics","learndash"),c("topics")),value:C||"",type:"number",onChange:function(e){B(""!=e&&e<0?{cat:"0"}:{cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("WP Category Selector","learndash"),help:(0,a.__)("shows a WP category dropdown.","learndash"),checked:!!k,onChange:e=>B({categoryselector:e})}))}let Q="";if("yes"===ldlms_settings.settings.topics_taxonomies.wp_post_tag){let e=!1;""==T&&""==P||(e=!0),Q=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Tag Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("shows %s with mentioned WP tag slug.","placeholder: Topics","learndash"),c("topics")),value:T||"",onChange:e=>B({tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag ID","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Topics.
// translators: placeholder: Topics.
(0,a._x)("shows %s with mentioned WP tag ID.","placeholder: Topics","learndash"),c("topics")),value:P||"",type:"number",onChange:function(e){B(""!=e&&e<0?{tag_id:"0"}:{tag_id:e})}}))}const M=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!S,onChange:e=>B({preview_show:e})})),V=(0,t.createElement)(r.InspectorControls,{key:"controls"},A,U,$,G,R,Q,M);function W(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),x)}function j(e){return W()}return(0,t.createElement)("div",q,V,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:E,attributes:a,key:E,EmptyResponsePlaceholder:j})):W();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const k="learndash/ld-quiz-list",T=(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("LearnDash %s List","placeholder: Quiz","learndash"),c("quiz"));(0,l.registerBlockType)(k,{title:T,description:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("This block shows a list of %s.","placeholder: Quizzes","learndash"),c("quizzes")),icon:"list-view",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{orderby:{type:"string",default:"ID"},order:{type:"string",default:"DESC"},per_page:{type:"string",default:""},course_id:{type:"string",default:""},lesson_id:{type:"string",default:""},show_content:{type:"boolean",default:!0},show_thumbnail:{type:"boolean",default:!0},quiz_category_name:{type:"string",default:""},quiz_cat:{type:"string",default:""},quiz_categoryselector:{type:"boolean",default:!1},quiz_tag:{type:"string",default:""},quiz_tag_id:{type:"string",default:""},category_name:{type:"string",default:""},cat:{type:"string",default:""},categoryselector:{type:"boolean",default:!1},tag:{type:"string",default:""},tag_id:{type:"string",default:""},course_grid:{type:"boolean"},col:{type:"integer",default:ldlms_settings.plugins["learndash-course-grid"].enabled.col_default||3},preview_show:{type:"boolean",default:!0},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{orderby:l,order:n,per_page:i,course_id:d,lesson_id:u,show_content:h,show_thumbnail:g,quiz_category_name:m,quiz_cat:b,quiz_categoryselector:f,quiz_tag:y,quiz_tag_id:w,category_name:v,cat:C,categoryselector:E,tag:x,tag_id:P,course_grid:D,col:I,preview_show:S,example_show:z},setAttributes:B}=e,q=(0,r.useBlockProps)();let L="",O="",U="",N=!0;if(!0===ldlms_settings.plugins["learndash-course-grid"].enabled){void 0===D||1!=D&&0!=D||(N=D);let e=!1;1==N&&(e=!0),U=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Grid Settings","learndash"),initialOpen:e},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Grid","learndash"),checked:!!N,onChange:e=>B({course_grid:e})}),(0,t.createElement)(s.RangeControl,{label:(0,a.__)("Columns","learndash"),value:I||ldlms_settings.plugins["learndash-course-grid"].enabled.col_default,min:1,max:ldlms_settings.plugins["learndash-course-grid"].enabled.col_max,step:1,onChange:e=>B({col:e})}))}L=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Content","learndash"),checked:!!h,onChange:e=>B({show_content:e})}),O=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Thumbnail","learndash"),checked:!!g,onChange:e=>B({show_thumbnail:e})});const A=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID to limit listing. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:d||"",type:"number",onChange:function(e){B(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Lesson.
// translators: placeholder: Lesson.
(0,a._x)("%s ID","placeholder: Lesson","learndash"),c("lesson")),help:(0,a.sprintf)(
// translators: placeholders: Lesson, Course.
// translators: placeholders: Lesson, Course.
(0,a._x)("Enter single %1$s ID to limit listing. Leave blank if used within a %2$s. Zero for global.","placeholders: Lesson, Course","learndash"),c("lesson"),c("course")),value:u||"",type:"number",onChange:function(e){B(""!=e&&e<0?{lesson_id:"0"}:{lesson_id:e})}}),(0,t.createElement)(s.SelectControl,{key:"orderby",label:(0,a.__)("Order by","learndash"),value:l,options:[{label:(0,a.__)("ID - Order by post id. (default)","learndash"),value:"ID"},{label:(0,a.__)("Title - Order by post title","learndash"),value:"title"},{label:(0,a.__)("Date - Order by post date","learndash"),value:"date"},{label:(0,a.__)("Menu - Order by Page Order Value","learndash"),value:"menu_order"}],onChange:e=>B({orderby:e})}),(0,t.createElement)(s.SelectControl,{key:"order",label:(0,a.__)("Order","learndash"),value:n,options:[{label:(0,a.__)("DESC - highest to lowest values (default)","learndash"),value:"DESC"},{label:(0,a.__)("ASC - lowest to highest values","learndash"),value:"ASC"}],onChange:e=>B({order:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("%s per page","placeholder: Quizzes","learndash"),c("quizzes")),help:(0,a.sprintf)(
// translators: placeholder: per_page.
// translators: placeholder: per_page.
(0,a._x)("Leave empty for default (%d) or 0 to show all items.","placeholder: per_page","learndash"),_("per_page")),value:i||"",type:"number",onChange:function(e){B(""!=e&&e<0?{per_page:"0"}:{per_page:e})}}),L,O);let $="";if("yes"===ldlms_settings.settings.quizzes_taxonomies.ld_quiz_category){let e=!1;""==m&&""==b||(e=!0),$=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Category Settings","placeholder: Quiz","learndash"),c("quiz")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Category Slug","placeholder: Quiz","learndash"),c("quiz")),help:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("shows %s with mentioned category slug.","placeholder: Quizzes","learndash"),c("quizzes")),value:m||"",onChange:e=>B({quiz_category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Category ID","placeholder: Quiz","learndash"),c("quiz")),help:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Quizzes","learndash"),c("quizzes")),value:b||"",type:"number",onChange:function(e){B(""!=e&&e<0?{quiz_cat:"0"}:{quiz_cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Category Selector","placeholder: Quiz","learndash"),c("quiz")),help:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("shows a %s category dropdown.","placeholder: Quizzes","learndash"),c("quizzes")),checked:!!f,onChange:e=>B({quiz_categoryselector:e})}))}let G="";if("yes"===ldlms_settings.settings.quizzes_taxonomies.ld_quiz_tag){let e=!1;""==y&&""==w||(e=!0),G=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Tag Settings","placeholder: Quiz","learndash"),c("quiz")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Tag Slug","placeholder: Quiz","learndash"),c("quiz")),help:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("shows %s with mentioned tag slug.","placeholder: Quizzes","learndash"),c("quizzes")),value:y||"",onChange:e=>B({quiz_tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Tag ID","placeholder: Quiz","learndash"),c("quiz")),help:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("shows %s with mentioned tag ID.","placeholder: Quizzes","learndash"),c("quizzes")),value:w||"",type:"number",onChange:function(e){B(""!=e&&e<0?{quiz_tag_id:"0"}:{quiz_tag_id:e})}}))}let R="";if("yes"===ldlms_settings.settings.quizzes_taxonomies.wp_post_category){let e=!1;""==v&&""==C||(e=!0),R=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Category Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Category Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("shows %s with mentioned WP Category slug.","placeholder: Quizzes","learndash"),c("quizzes")),value:v||"",onChange:e=>B({category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Category ID","placeholder: Quiz","learndash"),c("quiz")),help:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Quizzes","learndash"),c("quizzes")),value:C||"",type:"number",onChange:function(e){B(""!=e&&e<0?{cat:"0"}:{cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("WP Category Selector","learndash"),help:(0,a.__)("shows a WP category dropdown.","learndash"),checked:!!E,onChange:e=>B({categoryselector:e})}))}let Q="";if("yes"===ldlms_settings.settings.quizzes_taxonomies.wp_post_tag){let e=!1;""==x&&""==P||(e=!0),Q=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Tag Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("shows %s with mentioned WP tag slug.","placeholder: Quizzes","learndash"),c("quizzes")),value:x||"",onChange:e=>B({tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag ID","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("shows %s with mentioned WP tag ID.","placeholder: Quizzes","learndash"),c("quizzes")),value:P||"",type:"number",onChange:function(e){B(""!=e&&e<0?{tag_id:"0"}:{tag_id:e})}}))}const M=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!S,onChange:e=>B({preview_show:e})})),V=(0,t.createElement)(r.InspectorControls,{key:"controls"},A,U,$,G,R,Q,M);function W(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),T)}function j(e){return W()}return(0,t.createElement)("div",q,V,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:k,attributes:a,key:k,EmptyResponsePlaceholder:j})):W();var a},[e.attributes])))},save:e=>{}});const P=wp.element.createElement,D=P("svg",{width:300,height:300,viewBox:"0 0 50 10"},P("path",{d:"M47.1,0h-44c-1.7,0-3,1.3-3,3v4c0,1.7,1.3,3,3,3h44c1.7,0,3-1.3,3-3V3C50.1,1.3,48.7,0,47.1,0z M48.1,7c0,0.6-0.4,1-1,1h-12 V2h12c0.6,0,1,0.4,1,1V7z"})),I="learndash/ld-course-progress",S=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s Progress","placeholders: Course","learndash"),c("course"));(0,l.registerBlockType)(I,{title:S,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block displays users progress bar for the %s.","placeholders: Course","learndash"),c("course")),icon:D,category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{course_id:{type:"string",default:""},user_id:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string"},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:e=>{let{attributes:{course_id:l}}=e;const{attributes:{user_id:n,preview_show:i,preview_user_id:d,example_show:u},setAttributes:_}=e,h=(0,r.useBlockProps)(),g=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:l||"",type:"number",onChange:function(e){_(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:n||"",type:"number",onChange:function(e){_(""!=e&&e<0?{user_id:"0"}:{user_id:e})}})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!i,onChange:e=>_({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:d||"",type:"number",onChange:function(e){_(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function m(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),S)}function b(e){return m()}return(0,t.createElement)("div",h,g,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:I,attributes:a,key:I,EmptyResponsePlaceholder:b})):m();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const z=(0,a.__)("LearnDash Visitor","learndash");(0,l.registerBlockType)("learndash/ld-visitor",{title:z,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block shows the content if the user is not enrolled into the %s.","placeholder: Course","learndash"),c("course")),icon:"visibility",supports:{customClassName:!1},category:"learndash-blocks",apiVersion:3,attributes:{display_type:{type:"string",default:""},course_id:{type:"string",default:""},group_id:{type:"string",default:""},user_id:{type:"string",default:""},autop:{type:"boolean",default:!0}},edit:e=>{const{attributes:{display_type:l,course_id:n,group_id:o,user_id:i,autop:d},setAttributes:u}=e,_=(0,r.useBlockProps)();var g,m;g=(0,t.createElement)(s.SelectControl,{key:"display_type",label:(0,a.__)("Display Type","learndash"),value:l,options:[{label:(0,a.__)("Select a Display Type","learndash"),value:""},{label:c("course"),value:"sfwd-courses"},{label:c("group"),value:"groups"}],help:(0,a.sprintf)(
// translators: placeholders: Course, Group.
// translators: placeholders: Course, Group.
(0,a._x)("Leave blank to show the default %1$s or %2$s content table.","placeholders: Course, Group","learndash"),c("course"),c("group")),onChange:e=>u({display_type:e})}),"sfwd-courses"===l?(u({group_id:""}),m=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:n||"",type:"number",onChange:function(e){u(""!=e&&e<0?{course_id:"0"}:{course_id:e})}})):"groups"===l&&(u({course_id:""}),m=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholders: Group, Group.
// translators: placeholders: Group, Group.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Group, Group","learndash"),c("group"),c("group")),value:o||"",type:"number",onChange:function(e){u(""!=e&&e<0?{group_id:"0"}:{group_id:e})}}));const b=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},g,m,(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:i||"",type:"number",onChange:function(e){u(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Auto Paragraph","learndash"),checked:!!d,onChange:e=>u({autop:e})})));let f=l;if(""===f){let e=p();void 0!==e.post_type&&("sfwd-courses"===e.post_type?f="sfwd-courses":"groups"===e.post_type&&(f="groups"))}let y="";if("sfwd-courses"===f){let e=h(n);0===e&&(e=p("course_id"),e=h(e),0==e&&(y=(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("%1$s ID is required when not used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course"))))}else if("groups"===f){let e=h(o);0===e&&(e=p("post_id"),e=h(e),0==e&&(y=(0,a.sprintf)(
// translators: placeholders: Group, Group.
// translators: placeholders: Group, Group.
(0,a._x)("%1$s ID is required when not used within a %2$s.","placeholders: Group, Group","learndash"),c("group"),c("group"))))}return y.length&&(y=(0,t.createElement)("span",{className:"learndash-block-error-message"},y)),(0,t.createElement)("div",_,b,(0,t.createElement)("span",{className:"learndash-inner-header"},z),(0,t.createElement)("div",{className:"learndash-block-inner"},y,(0,t.createElement)(r.InnerBlocks,null)))},save:e=>(0,t.createElement)(r.InnerBlocks.Content,null)});const B=(0,a.__)("LearnDash Student","learndash");(0,l.registerBlockType)("learndash/ld-student",{title:B,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block shows the content if the user is enrolled in the %s.","placeholders: Course","learndash"),c("course")),icon:"welcome-learn-more",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{display_type:{type:"string",default:""},course_id:{type:"string",default:""},group_id:{type:"string",default:""},user_id:{type:"string",default:""},autop:{type:"boolean",default:!0}},edit:e=>{const{attributes:{display_type:l,course_id:n,group_id:o,user_id:i,autop:d},setAttributes:u}=e,_=(0,r.useBlockProps)();var g,m;g=(0,t.createElement)(s.SelectControl,{key:"display_type",label:(0,a.__)("Display Type","learndash"),value:l,options:[{label:(0,a.__)("Select a Display Type","learndash"),value:""},{label:c("course"),value:"sfwd-courses"},{label:c("group"),value:"groups"}],help:(0,a.sprintf)(
// translators: placeholders: Course, Group.
// translators: placeholders: Course, Group.
(0,a._x)("Leave blank to show the default %1$s or %2$s content table.","placeholders: Course, Group","learndash"),c("course"),c("group")),onChange:e=>u({display_type:e})}),"sfwd-courses"===l?(u({group_id:""}),m=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:n||"",type:"number",onChange:function(e){u(""!=e&&e<0?{course_id:"0"}:{course_id:e})}})):"groups"===l&&(u({course_id:""}),m=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholders: Group, Group.
// translators: placeholders: Group, Group.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Group, Group","learndash"),c("group"),c("group")),value:o||"",type:"number",onChange:function(e){u(""!=e&&e<0?{group_id:"0"}:{group_id:e})}}));const b=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},g,m,(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:i||"",type:"number",onChange:function(e){u(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Auto Paragraph","learndash"),checked:!!d,onChange:e=>u({autop:e})})));let f=l;if(""===f){let e=p();void 0!==e.post_type&&("sfwd-courses"===e.post_type?f="sfwd-courses":"groups"===e.post_type&&(f="groups"))}let y="";if("sfwd-courses"===f){let e=h(n);0===e&&(e=p("course_id"),e=h(e),0==e&&(y=(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("%1$s ID is required when not used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course"))))}else if("groups"===f){let e=h(o);0===e&&(e=p("post_id"),e=h(e),0==e&&(y=(0,a.sprintf)(
// translators: placeholders: Group, Group.
// translators: placeholders: Group, Group.
(0,a._x)("%1$s ID is required when not used within a %2$s.","placeholders: Group, Group","learndash"),c("group"),c("group"))))}return y.length&&(y=(0,t.createElement)("span",{className:"learndash-block-error-message"},y)),(0,t.createElement)("div",_,b,(0,t.createElement)("span",{className:"learndash-inner-header"},B),(0,t.createElement)("div",{className:"learndash-block-inner"},y,(0,t.createElement)(r.InnerBlocks,null)))},save:e=>(0,t.createElement)(r.InnerBlocks.Content,null)});const q=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s Complete","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)("learndash/ld-course-complete",{title:q,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block shows the content if the user is enrolled into the %s and it is completed.","placeholders: Course","learndash"),c("course")),icon:"star-filled",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{course_id:{type:"string",default:""},user_id:{type:"string",default:""},autop:{type:"boolean",default:!0}},edit:e=>{const{attributes:{course_id:l,user_id:n,autop:o},setAttributes:i}=e,d=(0,r.useBlockProps)(),u=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:l||"",type:"number",onChange:function(e){i(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:n||"",type:"number",onChange:function(e){i(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Auto Paragraph","learndash"),checked:!!o,onChange:e=>i({autop:e})})));let _="",g=h(l);return 0===g&&(g=h(p("course_id"))),0==g&&(_=(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("%1$s ID is required when not used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course"))),_.length&&(_=(0,t.createElement)("span",{className:"learndash-block-error-message"},_)),(0,t.createElement)("div",d,u,(0,t.createElement)("span",{className:"learndash-inner-header"},q),(0,t.createElement)("div",{className:"learndash-block-inner"},_,(0,t.createElement)(r.InnerBlocks,null)))},save:e=>(0,t.createElement)(r.InnerBlocks.Content,null)});const L=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s In Progress","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)("learndash/ld-course-inprogress",{title:L,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block shows the content if the user is enrolled into the %s but not yet completed.","placeholder: Course","learndash"),c("course")),icon:"star-half",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{course_id:{type:"string",default:""},user_id:{type:"string",default:""},autop:{type:"boolean",default:!0}},edit:e=>{const{attributes:{course_id:l,user_id:n,autop:o},setAttributes:i}=e,d=(0,r.useBlockProps)(),u=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:l||"",type:"number",onChange:function(e){i(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:n||"",type:"number",onChange:function(e){i(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Auto Paragraph","learndash"),checked:!!o,onChange:e=>i({autop:e})})));let _="",g=h(l);return 0===g&&(g=p("course_id"),g=h(g),0==g&&(_=(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("%1$s ID is required when not used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")))),_.length&&(_=(0,t.createElement)("span",{className:"learndash-block-error-message"},_)),(0,t.createElement)("div",d,u,(0,t.createElement)("span",{className:"learndash-inner-header"},L),(0,t.createElement)("div",{className:"learndash-block-inner"},_,(0,t.createElement)(r.InnerBlocks,null)))},save:e=>(0,t.createElement)(r.InnerBlocks.Content,null)});const O=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s Not Started","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)("learndash/ld-course-notstarted",{title:O,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block shows the content if the user is enrolled into the %s but not yet started.","placeholder: Course","learndash"),c("course")),icon:"star-empty",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{course_id:{type:"string",default:""},user_id:{type:"string",default:""},autop:{type:"boolean",default:!0}},edit:e=>{const{attributes:{course_id:l,user_id:n,autop:o},setAttributes:i}=e,d=(0,r.useBlockProps)(),u=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:l||"",type:"number",onChange:function(e){i(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:n||"",type:"number",onChange:function(e){i(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Auto Paragraph","learndash"),checked:!!o,onChange:e=>i({autop:e})})));let _="",g=h(l);return 0===g&&(g=p("course_id"),g=h(g),0==g&&(_=(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("%1$s ID is required when not used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")))),_.length&&(_=(0,t.createElement)("span",{className:"learndash-block-error-message"},_)),(0,t.createElement)("div",d,u,(0,t.createElement)("span",{className:"learndash-inner-header"},O),(0,t.createElement)("div",{className:"learndash-block-inner"},_,(0,t.createElement)(r.InnerBlocks,null)))},save:e=>(0,t.createElement)(r.InnerBlocks.Content,null)});const U="learndash/ld-course-resume",N=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Resume","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)(U,{title:N,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("Return to %s link/button.","placeholder: Course","learndash"),c("course")),icon:"welcome-learn-more",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,example:{attributes:{example_show:1}},attributes:{course_id:{type:"string",default:""},user_id:{type:"string",default:""},label:{type:"string",default:""},html_class:{type:"string",default:""},button:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{course_id:l,user_id:n,label:i,html_class:d,button:u,preview_show:_,preview_user_id:h,example_show:g},setAttributes:m}=e,b=(0,r.useBlockProps)(),f=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:l||"",type:"number",onChange:function(e){m(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:n||"",type:"number",onChange:function(e){m(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.SelectControl,{key:"button",label:(0,a.__)("Show as button","learndash"),value:u,options:[{label:(0,a.__)("Yes","learndash"),value:"true"},{label:(0,a.__)("No","learndash"),value:"false"}],onChange:e=>m({button:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Label","learndash"),help:(0,a.__)("Label for link shown to user","learndash"),value:i||"",onChange:e=>m({label:e})}),(0,t.createElement)(s.TextControl,{key:"html_class",label:(0,a.__)("Class","learndash"),help:(0,a.__)("HTML class for link element","learndash"),value:d||"",onChange:e=>m({html_class:e})})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!_,onChange:e=>m({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:h||"",type:"number",onChange:function(e){m(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function y(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),N)}function w(e){return y()}return(0,t.createElement)("div",b,f,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:U,attributes:a,key:U,EmptyResponsePlaceholder:w})):y();var a},[e.attributes])))},save:function(e){delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const A="learndash/ld-course-info",$=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s Info [ld_course_info]","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)(A,{title:$,description:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("This block shows the %s and progress for the user.","placeholder: Courses","learndash"),c("course")),icon:"analytics",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{user_id:{type:"string",default:0},registered_show:{type:"boolean"},registered_show_thumbnail:{type:"boolean",default:!0},registered_num:{type:"string",default:""},registered_orderby:{type:"string",default:"title"},registered_order:{type:"string",default:"ASC"},progress_show:{type:"boolean"},progress_num:{type:"string",default:""},progress_orderby:{type:"string",default:"title"},progress_order:{type:"string",default:"ASC"},quiz_show:{type:"boolean"},quiz_num:{type:"string",default:""},quiz_orderby:{type:"string",default:"taken"},quiz_order:{type:"string",default:"DESC"},preview_show:{type:"boolean",default:!0},preview_user_id:{type:"string",default:""},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{user_id:l,registered_show:n,registered_show_thumbnail:i,registered_num:d,registered_orderby:u,registered_order:h,progress_show:g,progress_num:m,progress_orderby:b,progress_order:f,quiz_show:y,quiz_num:w,quiz_orderby:v,quiz_order:C,preview_user_id:E,preview_show:x},setAttributes:k}=e,T=(0,r.useBlockProps)();void 0===n&&k({registered_show:!0}),void 0===g&&k({progress_show:!0}),void 0===y&&k({quiz_show:!0}),!1===n&&!1===g&&!1===y&&(k({registered_show:!0}),k({progress_show:!0}),k({quiz_show:!0}));const P=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:l||"",type:"number",onChange:function(e){k(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("Show Registered %s","placeholder: Courses","learndash"),c("courses")),checked:!!n,onChange:e=>k({registered_show:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("Show %s Progress","placeholder: Course","learndash"),c("course")),checked:!!g,onChange:e=>k({progress_show:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("Show %s Attempts","placeholder: Quiz","learndash"),c("quiz")),checked:!!y,onChange:e=>k({quiz_show:e})}));var D="";!0===n&&(D=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Courses.
// translators: placeholder: Courses.
(0,a._x)("Registered %s","placeholder: Courses","learndash"),c("courses")),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Thumbnail","learndash"),checked:!!i,onChange:e=>k({registered_show_thumbnail:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("per page","learndash"),help:(0,a.sprintf)(
// translators: placeholder: per_page.
// translators: placeholder: per_page.
(0,a._x)("Leave empty for default (%d) or 0 to show all items.","placeholder: per_page","learndash"),_("per_page")),value:d||"",min:0,max:100,type:"number",onChange:function(e){k(""!=e&&e<0?{registered_num:"0"}:{registered_num:e})}}),(0,t.createElement)(s.SelectControl,{key:"registered_orderby",label:(0,a.__)("Order by","learndash"),value:u,options:[{label:(0,a.__)("Title - Order by post title (default)","learndash"),value:"title"},{label:(0,a.__)("ID - Order by post id","learndash"),value:"ID"},{label:(0,a.__)("Date - Order by post date","learndash"),value:"date"},{label:(0,a.__)("Menu - Order by Page Order Value","learndash"),value:"menu_order"}],onChange:e=>k({registered_orderby:e})}),(0,t.createElement)(s.SelectControl,{key:"registered_order",label:(0,a.__)("Order","learndash"),value:h,options:[{label:(0,a.__)("ASC - lowest to highest values (default)","learndash"),value:"ASC"},{label:(0,a.__)("DESC - highest to lowest values","learndash"),value:"DESC"}],onChange:e=>k({registered_order:e})})));var I="";!0===g&&(I=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Progress","placeholder: Course","learndash"),c("course")),initialOpen:!1},(0,t.createElement)(s.TextControl,{label:(0,a.__)("per page","learndash"),help:(0,a.sprintf)(
// translators: placeholder: progress_num.
// translators: placeholder: progress_num.
(0,a._x)("Leave empty for default (%d) or 0 to show all items.","placeholder: progress_num","learndash"),_("progress_num")),value:m||"",min:0,max:100,type:"number",onChange:function(e){k(""!=e&&e<0?{progress_num:"0"}:{progress_num:e})}}),(0,t.createElement)(s.SelectControl,{key:"progress_orderby",label:(0,a.__)("Order by","learndash"),value:b,options:[{label:(0,a.__)("Title - Order by post title (default)","learndash"),value:"title"},{label:(0,a.__)("ID - Order by post id","learndash"),value:"ID"},{label:(0,a.__)("Date - Order by post date","learndash"),value:"date"},{label:(0,a.__)("Menu - Order by Page Order Value","learndash"),value:"menu_order"}],onChange:e=>k({progress_orderby:e})}),(0,t.createElement)(s.SelectControl,{key:"progress_order",label:(0,a.__)("Order","learndash"),value:f,options:[{label:(0,a.__)("ASC - lowest to highest values (default)","learndash"),value:"ASC"},{label:(0,a.__)("DESC - highest to lowest values","learndash"),value:"DESC"}],onChange:e=>k({progress_order:e})})));var S="";!0===y&&(S=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Attempts","placeholder: Quiz","learndash"),c("quiz")),initialOpen:!1},(0,t.createElement)(s.TextControl,{label:(0,a.__)("per page","learndash"),help:(0,a.sprintf)(
// translators: placeholder: quiz_num.
// translators: placeholder: quiz_num.
(0,a._x)("Leave empty for default (%d) or 0 to show all items.","placeholder: quiz_num","learndash"),_("quiz_num")),value:w||"",min:0,max:100,type:"number",onChange:function(e){k(""!=e&&e<0?{quiz_num:"0"}:{quiz_num:e})}}),(0,t.createElement)(s.SelectControl,{key:"quiz_orderby",label:(0,a.__)("Order by","learndash"),value:v,options:[{label:(0,a.__)("Date Taken (default) - Order by date taken","learndash"),value:"taken"},{label:(0,a.__)("Title - Order by post title","learndash"),value:"title"},{label:(0,a.__)("ID - Order by post id. (default)","learndash"),value:"ID"},{label:(0,a.__)("Date - Order by post date","learndash"),value:"date"},{label:(0,a.__)("Menu - Order by Page Order Value","learndash"),value:"menu_order"}],onChange:e=>k({quiz_orderby:e})}),(0,t.createElement)(s.SelectControl,{key:"quiz_order",label:(0,a.__)("Order","learndash"),value:C,options:[{label:(0,a.__)("DESC - highest to lowest values (default)","learndash"),value:"DESC"},{label:(0,a.__)("ASC - lowest to highest values","learndash"),value:"ASC"}],onChange:e=>k({quiz_order:e})})));const z=(0,t.createElement)(r.InspectorControls,{key:"controls"},P,D,I,S,(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!x,onChange:e=>k({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:E||"",type:"number",onChange:function(e){k(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function B(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),$)}function q(e){return B()}return(0,t.createElement)("div",T,z,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:A,attributes:a,key:A,EmptyResponsePlaceholder:q})):B();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const G="learndash/ld-user-course-points",R=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash User %s Points","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)(G,{title:R,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block shows the earned %s points for the user.","placeholder: Course","learndash"),c("course")),icon:"chart-area",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{user_id:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string"},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{user_id:l,preview_show:n,preview_user_id:i},setAttributes:d}=e,u=(0,r.useBlockProps)(),c=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:l||"",type:"number",onChange:function(e){d(""!=e&&e<0?{user_id:"0"}:{user_id:e})}})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!n,onChange:e=>d({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:i||"",type:"number",onChange:function(e){d(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function _(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),R)}function h(e){return _()}return(0,t.createElement)("div",u,c,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:G,attributes:a,key:G,EmptyResponsePlaceholder:h})):_();var a},[e.attributes])))},save:e=>{delete e.attributes.preview_user_id}});const Q="learndash/ld-group-list",M=(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("LearnDash %s List","placeholder: Group","learndash"),c("group"));(0,l.registerBlockType)(Q,{title:M,description:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("This block shows a list of %s.","placeholder: Groups","learndash"),c("groups")),icon:"list-view",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{orderby:{type:"string",default:"ID"},order:{type:"string",default:"DESC"},per_page:{type:"string",default:""},mygroups:{type:"string",default:""},status:{type:"array",default:["not_started","in_progress","completed"]},show_content:{type:"boolean",default:!0},show_thumbnail:{type:"boolean",default:!0},group_category_name:{type:"string",default:""},group_cat:{type:"string",default:""},group_categoryselector:{type:"boolean",default:!1},group_tag:{type:"string",default:""},group_tag_id:{type:"string",default:""},category_name:{type:"string",default:""},cat:{type:"string",default:""},categoryselector:{type:"boolean",default:!1},tag:{type:"string",default:""},tag_id:{type:"string",default:""},course_grid:{type:"boolean"},progress_bar:{type:"boolean",default:!1},col:{type:"integer",default:ldlms_settings.plugins["learndash-course-grid"].enabled.col_default||3},price_type:{type:"array",default:["free","paynow","subscribe","closed"]},preview_show:{type:"boolean",default:!0},preview_user_id:{type:"string",default:""},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{orderby:l,order:n,per_page:i,mygroups:d,status:u,show_content:h,show_thumbnail:g,group_category_name:m,group_cat:b,group_categoryselector:f,group_tag:y,group_tag_id:w,category_name:v,cat:C,categoryselector:E,tag:x,tag_id:k,course_grid:T,progress_bar:P,col:D,preview_user_id:I,preview_show:S,example_show:z,price_type:B},setAttributes:q}=e,L=(0,r.useBlockProps)();let O="",U="",N="",A=!0;if(!0===ldlms_settings.plugins["learndash-course-grid"].enabled){void 0===T||1!=T&&0!=T||(A=T);let e=!1;1==A&&(e=!0),N=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Grid Settings","learndash"),initialOpen:e},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Grid","learndash"),checked:!!A,onChange:e=>q({course_grid:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Progress Bar","learndash"),checked:!!P,onChange:e=>q({progress_bar:e})}),(0,t.createElement)(s.RangeControl,{label:(0,a.__)("Columns","learndash"),value:D||ldlms_settings.plugins["learndash-course-grid"].enabled.col_default,min:1,max:ldlms_settings.plugins["learndash-course-grid"].enabled.col_max,step:1,onChange:e=>q({col:e})}))}O=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Content","learndash"),checked:!!h,onChange:e=>q({show_content:e})}),U=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Thumbnail","learndash"),checked:!!g,onChange:e=>q({show_thumbnail:e})});let $="";""===ldlms_settings.settings.groups_cpt.public&&($=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Warning","learndash"),opened:!0},(0,t.createElement)(s.TextControl,{help:(0,a.sprintf)(
// translators: placeholders: Groups, Groups.
// translators: placeholders: Groups, Groups.
(0,a._x)("%1$s are not public, please visit the %2$s Settings page and set them to Public to enable access on the front end.","placeholders: Groups, Groups","learndash"),c("groups"),c("groups")),value:"",type:"hidden",className:"notice notice-error"})));const G=(0,t.createElement)(s.PanelBody,{className:"learndash-block-controls-panel learndash-block-controls-panel-ld-group-list",title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.SelectControl,{key:"orderby",label:(0,a.__)("Order by","learndash"),value:l,options:[{label:(0,a.__)("ID - Order by post id. (default)","learndash"),value:"ID"},{label:(0,a.__)("Title - Order by post title","learndash"),value:"title"},{label:(0,a.__)("Date - Order by post date","learndash"),value:"date"},{label:(0,a.__)("Menu - Order by Page Order Value","learndash"),value:"menu_order"}],onChange:e=>q({orderby:e})}),(0,t.createElement)(s.SelectControl,{key:"order",label:(0,a.__)("Order","learndash"),value:n,options:[{label:(0,a.__)("DESC - highest to lowest values (default)","learndash"),value:"DESC"},{label:(0,a.__)("ASC - lowest to highest values","learndash"),value:"ASC"}],onChange:e=>q({order:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("%s per page","placeholder: Groups","learndash"),c("groups")),help:(0,a.sprintf)(
// translators: placeholder: default per page.
// translators: placeholder: default per page.
(0,a._x)("Leave empty for default (%d) or 0 to show all items.","placeholder: default per page","learndash"),_("per_page")),value:i||"",type:"number",onChange:function(e){q(""!=e&&e<0?{per_page:"0"}:{per_page:e})}}),(0,t.createElement)(s.SelectControl,{multiple:!0,key:"price_type",label:(0,a.sprintf)(
// translators: placeholder: Group Access Mode(s).
// translators: placeholder: Group Access Mode(s).
(0,a._x)("%s Access Mode(s)","placeholder: Group Access Mode(s)","learndash"),c("group")),help:(0,a.__)("Ctrl+click to deselect selected items.","learndash"),value:B,options:[{label:(0,a.__)("Free","learndash"),value:"free"},{label:(0,a.__)("Buy Now","learndash"),value:"paynow"},{label:(0,a.__)("Recurring","learndash"),value:"subscribe"},{label:(0,a.__)("Closed","learndash"),value:"closed"}],onChange:e=>q({price_type:e})}),(0,t.createElement)(s.SelectControl,{key:"mygroups",label:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("My %s","placeholder: Groups","learndash"),c("groups")),value:d,options:[{label:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("Show All %s (default)","placeholder: Groups","learndash"),c("groups")),value:""},{label:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("Show Enrolled %s only","placeholder: Groups","learndash"),c("groups")),value:"enrolled"},{label:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("Show not-Enrolled %s only","placeholder: Groups","learndash"),c("Groups")),value:"not-enrolled"}],onChange:e=>q({mygroups:e})}),"enrolled"===d&&(0,t.createElement)(s.SelectControl,{multiple:!0,key:"status",label:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("Enrolled %s Status","placeholder: Groups","learndash"),c("groups")),value:u,options:[{label:(0,a.__)("Not Started","learndash"),value:"not_started"},{label:(0,a.__)("In Progress","learndash"),value:"in_progress"},{label:(0,a.__)("Completed","learndash"),value:"completed"}],onChange:e=>q({status:e})}),O,U);let R="";if("yes"===ldlms_settings.settings.groups_taxonomies.ld_group_category){let e=!1;""==m&&""==b||(e=!0),R=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Category Settings","placeholder: Group","learndash"),c("group")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Category Slug","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("shows %s with mentioned category slug.","placeholder: Groups","learndash"),c("groups")),value:m||"",onChange:e=>q({group_category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Category ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Groups","learndash"),c("groups")),value:b||"",type:"number",onChange:function(e){q(""!=e&&e<0?{group_cat:"0"}:{group_cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Category Selector","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("shows a %s category dropdown.","placeholder: Groups","learndash"),c("groups")),checked:!!f,onChange:e=>q({group_categoryselector:e})}))}let V="";if("yes"===ldlms_settings.settings.groups_taxonomies.ld_group_tag){let e=!1;""==y&&""==w||(e=!0),V=(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Tag Settings","placeholder: Group","learndash"),c("group")),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Tag Slug","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("shows %s with mentioned tag slug.","placeholder: Groups","learndash"),c("groups")),value:y||"",onChange:e=>q({group_tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Tag ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("shows %s with mentioned tag ID.","placeholder: Groups","learndash"),c("groups")),value:w||"",type:"number",onChange:function(e){q(""!=e&&e<0?{group_tag_id:"0"}:{group_tag_id:e})}}))}let W="";if("yes"===ldlms_settings.settings.groups_taxonomies.wp_post_category){let e=!1;""==v&&""==C||(e=!0),W=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Category Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Category Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("shows %s with mentioned WP Category slug.","placeholder: Groups","learndash"),c("groups")),value:v||"",onChange:e=>q({category_name:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Category ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("shows %s with mentioned category ID.","placeholder: Groups","learndash"),c("groups")),value:C||"",type:"number",onChange:function(e){q(""!=e&&e<0?{cat:"0"}:{cat:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("WP Category Selector","learndash"),help:(0,a.__)("shows a WP category dropdown.","learndash"),checked:!!E,onChange:e=>q({categoryselector:e})}))}let j="";if("yes"===ldlms_settings.settings.groups_taxonomies.wp_post_tag){let e=!1;""==x&&""==k||(e=!0),j=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("WP Tag Settings","learndash"),initialOpen:e},(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag Slug","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("shows %s with mentioned WP tag slug.","placeholder: Groups","learndash"),c("groups")),value:x||"",onChange:e=>q({tag:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("WP Tag ID","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("shows %s with mentioned WP tag ID.","placeholder: Groups","learndash"),c("groups")),value:k||"",type:"number",onChange:function(e){q(""!=e&&e<0?{tag_id:"0"}:{tag_id:e})}}))}const F=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!S,onChange:e=>q({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:I||"",type:"number",onChange:function(e){q(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})),H=(0,t.createElement)(r.InspectorControls,{key:"controls"},$,G,N,R,V,W,j,F);function Y(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),M)}function Z(e){return Y()}return(0,t.createElement)("div",L,H,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:Q,attributes:a,key:Q,EmptyResponsePlaceholder:Z})):Y();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const V="learndash/ld-user-groups",W=(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("LearnDash User %s","placeholder: Groups","learndash"),c("groups"));(0,l.registerBlockType)(V,{title:W,description:(0,a.sprintf)(
// translators: placeholder: Groups.
// translators: placeholder: Groups.
(0,a._x)("This block displays the list of %s users are assigned to as users or leaders.","placeholder: Groups","learndash"),c("groups")),icon:"groups",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{user_id:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string"},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{user_id:l,preview_user_id:n,preview_show:i},setAttributes:d}=e,u=(0,r.useBlockProps)();let _="";""===ldlms_settings.settings.groups_cpt.public&&(_=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Warning","learndash"),opened:!0},(0,t.createElement)(s.TextControl,{help:(0,a.sprintf)(
// translators: placeholders: Groups, Groups.
// translators: placeholders: Groups, Groups.
(0,a._x)("%1$s are not public, please visit the %2$s Settings page and set them to Public to enable access on the front end.","placeholders: Groups, Groups","learndash"),c("groups"),c("groups")),value:"",type:"hidden",className:"notice notice-error"})));const h=(0,t.createElement)(r.InspectorControls,{key:"controls"},_,(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:l||"",type:"number",onChange:function(e){d(""!=e&&e<0?{user_id:"0"}:{user_id:e})}})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!i,onChange:e=>d({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:n||"",type:"number",onChange:function(e){d(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function g(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),W)}function m(e){return g()}return(0,t.createElement)("div",u,h,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:V,attributes:a,key:V,EmptyResponsePlaceholder:m})):g();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const j=(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("LearnDash %s","placeholder: Group","learndash"),c("group"));(0,l.registerBlockType)("learndash/ld-group",{title:j,description:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("This block shows the content if the user is enrolled into the %s.","placeholder: Group","learndash"),c("group")),icon:"groups",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{group_id:{type:"string"},user_id:{type:"string",default:""},autop:{type:"boolean",default:!0}},edit:e=>{const{attributes:{group_id:l,user_id:n,autop:o},setAttributes:i}=e,d=(0,r.useBlockProps)(),u=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID (required)","placeholder: Group","learndash"),c("group")),value:l||"",type:"number",onChange:function(e){i(""!=e&&e<0?{group_id:"0"}:{group_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:n||"",type:"number",onChange:function(e){i(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Auto Paragraph","learndash"),checked:!!o,onChange:e=>i({autop:e})})));let p="";return 0==h(l)&&(p=(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID is required.","placeholder: Group","learndash"),c("group"))),p.length&&(p=(0,t.createElement)("span",{className:"learndash-block-error-message"},p)),(0,t.createElement)("div",d,u,(0,t.createElement)("span",{className:"learndash-inner-header"},j),(0,t.createElement)("div",{className:"learndash-block-inner"},p,(0,t.createElement)(r.InnerBlocks,null)))},save:e=>(0,t.createElement)(r.InnerBlocks.Content,null)});const F="learndash/ld-payment-buttons",H=(0,a.__)("LearnDash Payment Buttons","learndash");(0,l.registerBlockType)(F,{title:H,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block displays the %s payment buttons","placeholder: Course","learndash"),c("course")),icon:"cart",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{display_type:{type:"string",default:""},course_id:{type:"string"},group_id:{type:"string"},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{display_type:l,course_id:n,group_id:i,preview_show:d,preview_user_id:u},setAttributes:_}=e,h=(0,r.useBlockProps)();var g,m;g=(0,t.createElement)(s.SelectControl,{key:"display_type",label:(0,a.__)("Display Type","learndash"),value:l,options:[{label:(0,a.__)("Select a Display Type","learndash"),value:""},{label:c("course"),value:"sfwd-courses"},{label:c("group"),value:"groups"}],help:(0,a.sprintf)(
// translators: placeholders: Course, Group.
// translators: placeholders: Course, Group.
(0,a._x)("Leave blank to show the default %1$s or %2$s content table.","placeholders: Course, Group","learndash"),c("course"),c("group")),onChange:e=>_({display_type:e})}),"sfwd-courses"===l?(_({group_id:""}),m=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:n||"",type:"number",onChange:function(e){_(""!=e&&e<0?{course_id:"0"}:{course_id:e})}})):"groups"===l&&(_({course_id:""}),m=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholders: Group, Group.
// translators: placeholders: Group, Group.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Group, Group","learndash"),c("group"),c("group")),value:i||"",type:"number",onChange:function(e){_(""!=e&&e<0?{group_id:"0"}:{group_id:e})}}));const b=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},g,m),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!d,onChange:e=>_({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID for preview.","learndash"),value:u||"",type:"number",onChange:function(e){_(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function f(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),H)}function y(e){return f()}return(0,t.createElement)("div",h,b,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:F,attributes:a,key:F,EmptyResponsePlaceholder:y})):f();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const Y="learndash/ld-course-content",Z=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s Content","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)(Y,{title:Z,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block displays the %s Content table.","placeholder: Course","learndash"),c("course")),icon:"format-aside",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{display_type:{type:"string",default:""},course_id:{type:"string",default:""},post_id:{type:"string",default:""},group_id:{type:"string",default:""},per_page:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{display_type:l,course_id:n,post_id:i,group_id:d,per_page:u,preview_show:_,preview_user_id:h,example_show:g},setAttributes:b}=e,f=(0,r.useBlockProps)();var y,w;y=(0,t.createElement)(s.SelectControl,{key:"display_type",label:(0,a.__)("Display Type","learndash"),value:l,options:[{label:(0,a.__)("Select a Display Type","learndash"),value:""},{label:c("course"),value:"sfwd-courses"},{label:c("group"),value:"groups"}],help:(0,a.sprintf)(
// translators: placeholders: Course, Group.
// translators: placeholders: Course, Group.
(0,a._x)("Leave blank to show the default %1$s or %2$s content table.","placeholders: Course, Group","learndash"),c("course"),c("group")),onChange:e=>b({display_type:e})}),"sfwd-courses"===l?(b({group_id:""}),w=(0,t.createElement)(React.Fragment,null,(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:n||"",type:"number",onChange:function(e){b(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Step ID","learndash"),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single Step ID. Leave blank if used within a %1$s or 0 to always show %2$s content table.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:i||"",type:"number",onChange:function(e){b(""!=e&&e<0?{post_id:"0"}:{post_id:e})}}))):"groups"===l&&(b({course_id:""}),b({post_id:""}),w=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholders: Group, Group.
// translators: placeholders: Group, Group.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Group, Group","learndash"),c("group"),c("group")),value:d||"",type:"number",onChange:function(e){b(""!=e&&e<0?{group_id:"0"}:{group_id:e})}}));const v=(0,t.createElement)(r.InspectorControls,{key:"controls"},m(),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},y,w,(0,t.createElement)(s.TextControl,{label:(0,a.__)("Items per page","learndash"),help:(0,a.__)("Leave empty for default or 0 to show all items.","learndash"),value:u||"",type:"number",onChange:function(e){b(""!=e&&e<0?{per_page:"0"}:{per_page:e})}})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!_,onChange:e=>b({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID for preview.","learndash"),value:h||"",type:"number",onChange:function(e){b(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function C(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),Z)}function E(e){return C()}return(0,t.createElement)("div",f,v,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:Y,attributes:a,key:Y,EmptyResponsePlaceholder:E})):C();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const J="learndash/ld-course-expire-status",K=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s Expire Status","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)(J,{title:K,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block displays the user %s access expire date.","placeholders: Course","learndash"),c("course")),icon:"clock",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{course_id:{type:"string",default:""},user_id:{type:"string",default:""},label_before:{type:"string",default:""},label_after:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:function(e){let{attributes:{course_id:l}}=e;const{attributes:{user_id:n,label_before:i,label_after:d,preview_show:u,preview_user_id:_,example_show:h},setAttributes:g}=e,m=(0,r.useBlockProps)(),b=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:l||"",type:"number",onChange:function(e){g(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:n||"",type:"number",onChange:function(e){g(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Label Before Expire","learndash"),help:(0,a.__)("The label prefix shown before the access expires","learndash"),value:i||"",onChange:e=>g({label_before:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Label After Expire","learndash"),help:(0,a.__)("The label prefix shown after access has expired","learndash"),value:d||"",onChange:e=>g({label_after:e})})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!u,onChange:e=>g({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:_||"",type:"number",onChange:function(e){g(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function f(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),K)}function y(e){return f()}return(0,t.createElement)("div",m,b,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:J,attributes:a,key:J,EmptyResponsePlaceholder:y})):f();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const X="learndash/ld-certificate",ee=(0,a.__)("LearnDash Certificate","learndash");(0,l.registerBlockType)(X,{title:ee,description:(0,a.__)("This shortcode shows a Certificate download link.","learndash"),icon:"welcome-learn-more",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,example:{attributes:{example_show:1}},attributes:{display_type:{type:"string",default:""},course_id:{type:"string",default:""},group_id:{type:"string",default:""},quiz_id:{type:"string",default:""},user_id:{type:"string",default:""},display_as:{type:"string",default:""},label:{type:"string",default:""},class_html:{type:"string",default:""},context:{type:"string",default:""},callback:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{display_type:l,course_id:n,group_id:i,quiz_id:d,user_id:u,display_as:_,label:h,class_html:g,context:b,callback:f,preview_show:y,preview_user_id:w,example_show:v},title:C,setAttributes:E}=e,x=(0,r.useBlockProps)();var k,T,P;""==_&&("sfwd-courses"!=p("post_type")&&"groups"!=p("post_type")||E({display_as:"banner"})),k=(0,t.createElement)(s.SelectControl,{key:"display_type",label:(0,a.__)("Display Type","learndash"),value:l,help:(0,a.sprintf)(
// translators: placeholders: Course, Group, Quiz.
// translators: placeholders: Course, Group, Quiz.
(0,a._x)("Require if not used within a %1$s, %2$s, or %3$s. Or to override default display.","placeholders: Course, Group, Quiz","learndash"),c("course"),c("group"),c("quiz")),options:[{label:(0,a.__)("Select a Display Type","learndash"),value:""},{label:c("course"),value:"sfwd-courses"},{label:c("group"),value:"groups"},{label:c("quiz"),value:"sfwd-quiz"}],onChange:e=>E({display_type:e})}),"sfwd-courses"===l?(E({group_id:""}),E({quiz_id:""}),T=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("Enter single %s ID.","placeholder: Course","learndash"),c("course")),value:n||"",type:"number",onChange:function(e){E(""!=e&&e<0?{course_id:"0"}:{course_id:e})}})):"groups"===l?(E({course_id:""}),E({quiz_id:""}),T=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("Enter single %s ID.","placeholder: Group","learndash"),c("group")),value:i||"",type:"number",onChange:function(e){E(""!=e&&e<0?{group_id:"0"}:{group_id:e})}})):"sfwd-quiz"===l&&(E({group_id:""}),T=(0,t.createElement)(React.Fragment,null,(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s ID","placeholder: Quiz","learndash"),c("quiz")),help:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("Enter single %s ID.","placeholder: Quiz","learndash"),c("quiz")),value:d||"",type:"number",onChange:function(e){E(""!=e&&e<0?{quiz_id:"0"}:{quiz_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Quiz, Course.
// translators: placeholders: Course, Quiz, Course.
(0,a._x)("Enter single %1$s ID. Required if %2$s is within a %3$s","placeholders: Course, Quiz, Course","learndash"),c("course"),c("quiz"),c("course")),value:n||"",type:"number",onChange:function(e){E(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}))),"button"==_&&(P=(0,t.createElement)(React.Fragment,null,(0,t.createElement)(s.TextControl,{label:(0,a.__)("Label","learndash"),help:(0,a.__)("Label for link shown to user","learndash"),value:h||"",onChange:e=>E({label:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Class","learndash"),help:(0,a.__)("HTML class for link element","learndash"),value:g||"",onChange:e=>E({class_html:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Context","learndash"),help:(0,a.__)("User defined value to be passed into shortcode handler","learndash"),value:b||"",onChange:e=>E({context:e})}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Callback","learndash"),help:(0,a.__)("Custom callback function to be used instead of default output","learndash"),value:f||"",onChange:e=>E({callback:e})})));const D=(0,t.createElement)(r.InspectorControls,{key:"controls"},m(),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},k,T,(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:u||"",type:"number",onChange:function(e){E(""!=e&&e<0?{user_id:"0"}:{user_id:e})}})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Advanced","learndash"),initialOpen:!1},(0,t.createElement)(s.SelectControl,{key:"display_as",label:(0,a.__)("Displayed as","learndash"),help:(0,a.__)("Display as Button or Banner","learndash"),value:_||"button",options:[{label:(0,a.__)("Button","learndash"),value:"button"},{label:(0,a.sprintf)(
// translators: placeholders: Course, Group.
// translators: placeholders: Course, Group.
(0,a._x)("Banner (%1$s or %2$s only)","placeholders: Course, Group","learndash"),c("course"),c("group")),value:"banner"}],onChange:e=>E({display_as:e})}),P),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!y,onChange:e=>E({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID for preview.","learndash"),value:w||"",type:"number",onChange:function(e){E(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function I(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),ee)}function S(e){return I()}return(0,t.createElement)("div",x,D,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:X,attributes:a,key:X,EmptyResponsePlaceholder:S})):I();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const te=(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("LearnDash %s Complete","placeholder: Quiz","learndash"),c("quiz"));(0,l.registerBlockType)("learndash/ld-quiz-complete",{title:te,description:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("This block shows the content if the user has completed the %s.","placeholder: Quiz","learndash"),c("quiz")),icon:"star-filled",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{course_id:{type:"string",default:""},quiz_id:{type:"string",default:""},user_id:{type:"string",default:""},autop:{type:"boolean",default:!0}},edit:e=>{const{attributes:{course_id:l,quiz_id:n,user_id:o,autop:i},setAttributes:d}=e,u=(0,r.useBlockProps)(),_=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s ID","placeholder: Quiz","learndash"),c("quiz")),help:(0,a.sprintf)(
// translators: placeholders: Quiz, Quiz.
// translators: placeholders: Quiz, Quiz.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Quiz, Quiz","learndash"),c("quiz"),c("quiz")),value:n||"",type:"number",onChange:function(e){d(""!=e&&e<0?{quiz_id:"0"}:{quiz_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:l||"",type:"number",onChange:function(e){d(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:o||"",type:"number",onChange:function(e){d(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Auto Paragraph","learndash"),checked:!!i,onChange:e=>d({autop:e})})));let g="",m=h(n);return 0===m&&("sfwd-quiz"===p("post_type")&&(m=p("post_id"),m=h(m)),0==m&&(g=(0,a.sprintf)(
// translators: placeholders: Quiz, Quiz.
// translators: placeholders: Quiz, Quiz.
(0,a._x)("%1$s ID is required when not used within a %2$s.","placeholders: Quiz, Quiz","learndash"),c("quiz"),c("quiz")))),g.length&&(g=(0,t.createElement)("span",{className:"learndash-block-error-message"},g)),(0,t.createElement)("div",u,_,(0,t.createElement)("span",{className:"learndash-inner-header"},te),(0,t.createElement)("div",{className:"learndash-block-inner"},g,(0,t.createElement)(r.InnerBlocks,null)))},save:e=>(0,t.createElement)(r.InnerBlocks.Content,null)});const ae="learndash/ld-courseinfo",le=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s Info [courseinfo]","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)(ae,{title:le,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block displays %s related information","placeholder: Course","learndash"),c("course")),icon:"analytics",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{show:{type:"string"},course_id:{type:"string",default:""},user_id:{type:"string",default:""},format:{type:"string"},seconds_format:{type:"string"},decimals:{type:"string"},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{course_id:l,show:n,user_id:i,format:d,seconds_format:u,decimals:_,preview_show:h,preview_user_id:g},setAttributes:m}=e,b=(0,r.useBlockProps)(),f=(0,t.createElement)(s.SelectControl,{key:"show",value:n||"course_title",label:(0,a.__)("Show","learndash"),options:[{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Title","placeholder: Course","learndash"),c("course")),value:"course_title"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s URL","placeholder: Course","learndash"),c("course")),value:"course_url"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Points","placeholder: Course","learndash"),c("course")),value:"course_points"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Price","placeholder: Course","learndash"),c("course")),value:"course_price"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Price Type","placeholder: Course","learndash"),c("course")),value:"course_price_type"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Enrolled Users Count","placeholder: Course","learndash"),c("course")),value:"course_users_count"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("Total User %s Points","placeholder: Course","learndash"),c("course")),value:"user_course_points"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("Total User %s Time","placeholder: Course","learndash"),c("course")),value:"user_course_time"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Completed On (date)","placeholder: Course","learndash"),c("course")),value:"completed_on"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Enrolled On (date)","placeholder: Course","learndash"),c("course")),value:"enrolled_on"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Cumulative %s Score","placeholder: Quizzes","learndash"),c("quizzes")),value:"cumulative_score"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Cumulative %s Points","placeholder: Quizzes","learndash"),c("quizzes")),value:"cumulative_points"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Possible Cumulative %s Total Points","placeholder: Quizzes","learndash"),c("quizzes")),value:"cumulative_total_points"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Cumulative %s Percentage","placeholder: Quizzes","learndash"),c("quizzes")),value:"cumulative_percentage"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Cumulative %s Time Spent","placeholder: Quizzes","learndash"),c("quizzes")),value:"cumulative_timespent"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Aggregate %s Percentage","placeholder: Quizzes","learndash"),c("quizzes")),value:"aggregate_percentage"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Aggregate %s Score","placeholder: Quizzes","learndash"),c("quizzes")),value:"aggregate_score"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Aggregate %s Points","placeholder: Quizzes","learndash"),c("quizzes")),value:"aggregate_points"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Possible Aggregate %s Total Points","placeholder: Quizzes","learndash"),c("quizzes")),value:"aggregate_total_points"},{label:(0,a.sprintf)(
// translators: placeholder: Quizzes.
// translators: placeholder: Quizzes.
(0,a._x)("Aggregate %s Time Spent","placeholder: Quizzes","learndash"),c("quizzes")),value:"aggregate_timespent"}],onChange:e=>m({show:e})}),y=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s or certificate.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:l||"",type:"number",onChange:function(e){m(""!=e&&e<0?{course_id:"0"}:{course_id:e})}});let w="";["user_course_points","user_course_time","completed_on","enrolled_on","cumulative_score","cumulative_points","cumulative_total_points","cumulative_percentage","cumulative_timespent","aggregate_percentage","aggregate_score","aggregate_points","aggregate_total_points","aggregate_timespent"].includes(n)&&(w=(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:i||"",type:"number",onChange:function(e){m(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}));let v="";"completed_on"!=n&&"enrolled_on"!=n||(v=(0,t.createElement)(s.TextControl,{label:(0,a.__)("Format","learndash"),help:(0,a.__)('This can be used to change the date format. Default: "F j, Y, g:i a.',"learndash"),value:d||"",onChange:e=>m({format:e})}));let C="";"user_course_time"==n&&(C=(0,t.createElement)(s.SelectControl,{key:"seconds_format",value:u,label:(0,a.__)("Seconds Format","learndash"),options:[{label:(0,a.__)("Time - 20min 49sec","learndash"),value:"time"},{label:(0,a.__)("Seconds - 1436","learndash"),value:"seconds"}],onChange:e=>m({seconds_format:e})}));let E="";"course_points"!=n&&"user_course_points"!=n||(E=(0,t.createElement)(s.TextControl,{label:(0,a.__)("Decimals","learndash"),help:(0,a.__)("Number of decimal places to show. Default is 2.","learndash"),value:_||"",type:"number",onChange:function(e){m(""!=e&&e<0?{decimals:"0"}:{decimals:e})}}));const x=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!h,onChange:e=>m({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:g||"",type:"number",onChange:function(e){m(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})),k=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},y,w,f,v,C,E),x);function T(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),le)}function P(e){return T()}return(0,t.createElement)("div",b,k,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:ae,attributes:a,key:ae,EmptyResponsePlaceholder:P})):T();var a},[e.attributes])))},save:function(e){delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const re="learndash/ld-quizinfo",se=(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("LearnDash %s Info [quizinfo]","placeholder: Quiz","learndash"),c("quiz"));(0,l.registerBlockType)(re,{title:se,description:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("This block displays %s related information","placeholder: Quiz","learndash"),c("quiz")),icon:"analytics",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{show:{type:"string",default:"quiz_title"},quiz_id:{type:"string",default:""},user_id:{type:"string",default:""},format:{type:"string"},field_id:{type:"string"},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{quiz_id:l,user_id:n,timestamp:i,show:d,format:u,field_id:_,preview_show:h,preview_user_id:g},setAttributes:m}=e,b=(0,r.useBlockProps)(),f=(0,t.createElement)(s.SelectControl,{key:"show",value:d||"quiz_title",label:(0,a.__)("Show","learndash"),options:[{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Title","placeholder: Quiz","learndash"),c("quiz")),value:"quiz_title"},{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Score","placeholder: Quiz","learndash"),c("quiz")),value:"score"},{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Count","placeholder: Quiz","learndash"),c("quiz")),value:"count"},{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Pass","placeholder: Quiz","learndash"),c("quiz")),value:"pass"},{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Timestamp","placeholder: Quiz","learndash"),c("quiz")),value:"timestamp"},{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Points","placeholder: Quiz","learndash"),c("quiz")),value:"points"},{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Total Points","placeholder: Quiz","learndash"),c("quiz")),value:"total_points"},{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Percentage","placeholder: Quiz","learndash"),c("quiz")),value:"percentage"},{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s Title","placeholder: Course","learndash"),c("course")),value:"course_title"},{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Time Spent","placeholder: Quiz","learndash"),c("quiz")),value:"timespent"},{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s Form Field","placeholder: Quiz","learndash"),c("quiz")),value:"field"}],onChange:e=>m({show:e})});let y="";"field"==d&&(y=(0,t.createElement)(s.TextControl,{label:(0,a.__)("Custom Field ID","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("The Field ID is shown on the %s Custom Fields table.","placeholder: Quiz","learndash"),c("quiz")),value:_||"",onChange:e=>m({field_id:e})}));let w="";"timestamp"!=d&&"field"!=d||(w=(0,t.createElement)(s.TextControl,{label:(0,a.__)("Format","learndash"),help:(0,a.__)("This can be used to change the date format. Default: F j, Y, g:i a.","learndash"),value:u||"",onChange:e=>m({format:e})}));const v=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},f,y,w,(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("%s ID","placeholder: Quiz","learndash"),c("quiz")),help:(0,a.sprintf)(
// translators: placeholders: Quiz, Quiz.
// translators: placeholders: Quiz, Quiz.
(0,a._x)("Enter a single %1$s ID. Leave blank if used within a %2$s or Certificate.","placeholders: Quiz, Quiz","learndash"),c("quiz"),c("quiz")),value:l||"",type:"number",onChange:function(e){m(""!=e&&e<0?{quiz_id:"0"}:{quiz_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)("Enter a single User ID. Leave blank if used within a %s or Certificate.","placeholder: Quiz","learndash"),c("quiz")),value:n||"",type:"number",onChange:function(e){m(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Attempt timestamp","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Quiz.
// translators: placeholder: Quiz.
(0,a._x)('Single %s attempt timestamp. See WP user profile "#" link on attempt row. Leave blank to use latest attempt or within a Certificate.',"placeholder: Quiz","learndash"),c("quiz")),value:i||"",onChange:function(e){if(e.length&&e.startsWith("data:quizinfo:",0)){var t=e.split(":");if(t.length>2){var a="";for(let e=2;e<t.length;e++)""!=a?("quiz_id"==a?m({quiz_id:t[e]}):"user_id"==a?m({user_id:t[e]}):"time"==a&&m({timestamp:t[e]}),a=""):"quiz"==t[e]?a="quiz_id":"user"==t[e]?a="user_id":"time"==t[e]&&(a="time")}}}})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!h,onChange:e=>m({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:g||"",type:"number",onChange:function(e){m(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function C(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),se)}function E(e){return C()}return(0,t.createElement)("div",b,v,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:re,attributes:a,key:re,EmptyResponsePlaceholder:E})):C();var a},[e.attributes])))},save:function(e){delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const ne="learndash/ld-groupinfo",oe=(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("LearnDash %s Info [groupinfo]","placeholder: Group","learndash"),c("group"));(0,l.registerBlockType)(ne,{title:oe,description:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("This block displays %s related information","placeholder: Group","learndash"),c("group")),icon:"analytics",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{show:{type:"string"},group_id:{type:"string",default:""},user_id:{type:"string",default:""},format:{type:"string"},decimals:{type:"string"},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{group_id:l,show:n,user_id:i,format:d,decimals:u,preview_show:_,preview_user_id:h},setAttributes:g}=e,m=(0,r.useBlockProps)(),b=(0,t.createElement)(s.SelectControl,{key:"show",value:n,label:(0,a.__)("Show","learndash"),options:[{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Title","placeholder: Group","learndash"),c("group")),value:"group_title"},{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s URL","placeholder: Group","learndash"),c("group")),value:"group_url"},{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Price","placeholder: Group","learndash"),c("group")),value:"group_price"},{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Price Type","placeholder: Group","learndash"),c("group")),value:"group_price_type"},{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Enrolled Users Count","placeholder: Group","learndash"),c("group")),value:"group_users_count"},{label:(0,a.sprintf)(
// translators: placeholders: Group, Courses.
// translators: placeholders: Group, Courses.
(0,a._x)("%1$s %2$s Count","placeholders: Group, Courses","learndash"),c("group"),c("courses")),value:"group_courses_count"},{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("User %s Status","placeholder: Group","learndash"),c("group")),value:"user_group_status"},{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Completed On (date)","placeholder: Group","learndash"),c("group")),value:"completed_on"},{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Enrolled On (date)","placeholder: Group","learndash"),c("group")),value:"enrolled_on"},{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s Completed Percentage","placeholder: Group","learndash"),c("group")),value:"percent_completed"}],onChange:e=>g({show:e})}),f=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholders: Group, Group.
// translators: placeholders: Group, Group.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Group, Group","learndash"),c("group"),c("group")),value:l||"",type:"number",onChange:function(e){g(""!=e&&e<0?{group_id:"0"}:{group_id:e})}});let y="";["user_group_status","completed_on","enrolled_on","percent_completed"].includes(n)&&(y=(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:i||"",type:"number",onChange:function(e){g(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}));let w="";["completed_on","enrolled_on"].includes(n)&&(w=(0,t.createElement)(s.TextControl,{label:(0,a.__)("Format","learndash"),help:(0,a.__)('This can be used to change the date format. Default: "F j, Y, g:i a.',"learndash"),value:d||"",onChange:e=>g({format:e})}));let v="";["percent_completed"].includes(n)&&(v=(0,t.createElement)(s.TextControl,{label:(0,a.__)("Decimals","learndash"),help:(0,a.__)("Number of decimal places to show. Default is 2.","learndash"),value:u||"",type:"number",onChange:function(e){g(""!=e&&e<0?{decimals:"0"}:{decimals:e})}}));const C=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!_,onChange:e=>g({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:h||"",type:"number",onChange:function(e){g(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})),E=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},f,y,b,w,v),C);function x(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),oe)}function k(e){return x()}return(0,t.createElement)("div",m,E,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:ne,attributes:a,key:ne,EmptyResponsePlaceholder:k})):x();var a},[e.attributes])))},save:function(e){delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const ie="learndash/ld-usermeta",de=(0,a.__)("LearnDash User meta","learndash");(0,l.registerBlockType)(ie,{title:de,description:(0,a.__)("This block displays User meta field","learndash"),icon:"id",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{field:{type:"string",default:"user_login"},user_id:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{field:l,user_id:n,preview_show:i,preview_user_id:d},setAttributes:u}=e,c=(0,r.useBlockProps)(),_=(0,t.createElement)(s.SelectControl,{key:"field",value:l,label:(0,a.__)("Field","learndash"),options:[{label:(0,a.__)("User Login","learndash"),value:"user_login"},{label:(0,a.__)("User First Name","learndash"),value:"first_name"},{label:(0,a.__)("User Last Name","learndash"),value:"last_name"},{label:(0,a.__)("User First and Last Name","learndash"),value:"first_last_name"},{label:(0,a.__)("User Display Name","learndash"),value:"display_name"},{label:(0,a.__)("User Nicename","learndash"),value:"user_nicename"},{label:(0,a.__)("User Nickname","learndash"),value:"nickname"},{label:(0,a.__)("User Email","learndash"),value:"user_email"},{label:(0,a.__)("User URL","learndash"),value:"user_url"},{label:(0,a.__)("User Description","learndash"),value:"description"}],onChange:e=>u({field:e})}),h=(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:n||"",type:"number",onChange:function(e){u(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),g=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!i,onChange:e=>u({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:d||"",type:"number",onChange:function(e){u(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})),m=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},h,_),g);function b(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),de)}function f(e){return b()}return(0,t.createElement)("div",c,m,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:ie,attributes:a,key:ie,EmptyResponsePlaceholder:f})):b();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const ue="learndash/ld-registration",ce=(0,a.__)("LearnDash Registration","learndash");(0,l.registerBlockType)(ue,{title:ce,description:(0,a.__)("Shows the registration form","learndash"),icon:"id-alt",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{width:{type:"string"},example_show:{type:"boolean",default:1},preview_show:{type:"boolean",default:!0},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{preview_show:l,example_show:n,width:i},setAttributes:d}=e,u=(0,r.useBlockProps)(),c=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Styling","learndash"),initialOpen:!0},(0,t.createElement)(s.TextControl,{label:(0,a.__)("Form Width","learndash"),help:(0,a.__)("Sets the width of the registration form.","learndash"),value:i||"",type:"string",onChange:e=>d({width:e})})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!l,onChange:e=>d({preview_show:e})})));function _(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),ce)}function h(e){return _()}return(0,t.createElement)("div",u,c,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:ue,attributes:a,key:ue,EmptyResponsePlaceholder:h})):_();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const pe="learndash/ld-infobar",_e=(0,a.__)("LearnDash Infobar","learndash");(0,l.registerBlockType)(pe,{title:_e,description:(0,a.__)("This block displays an Infobar for a specific LearnDash related post.","learndash"),icon:"welcome-widgets-menus",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{display_type:{type:"string",default:""},course_id:{type:"string",default:""},post_id:{type:"string",default:""},group_id:{type:"string",default:""},user_id:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_user_id:{type:"string",default:""},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{display_type:l,course_id:n,post_id:i,group_id:d,user_id:u,preview_show:_,preview_user_id:h},setAttributes:g}=e,b=(0,r.useBlockProps)();var f,y;f=(0,t.createElement)(s.SelectControl,{key:"display_type",label:(0,a.__)("Display Type","learndash"),value:l,help:(0,a.sprintf)(
// translators: placeholders: Course, Group.
// translators: placeholders: Course, Group.
(0,a._x)("Require if not used within a %1$s or %2$s. Or to override default display.","placeholders: Course, Group","learndash"),c("course"),c("group")),options:[{label:(0,a.__)("Select a Display Type","learndash"),value:""},{label:c("course"),value:"sfwd-courses"},{label:c("group"),value:"groups"}],onChange:e=>g({display_type:e})}),"sfwd-courses"===l?(g({group_id:""}),y=(0,t.createElement)(React.Fragment,null,(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:n||"",type:"number",onChange:function(e){g(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Step ID","learndash"),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single Step ID. Leave blank if used within a %1$s step.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:i||"",type:"number",onChange:function(e){g(""!=e&&e<0?{post_id:"0"}:{post_id:e})}}))):"groups"===l&&(g({course_id:""}),g({post_id:""}),y=(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Group.
// translators: placeholder: Group.
(0,a._x)("%s ID","placeholder: Group","learndash"),c("group")),help:(0,a.sprintf)(
// translators: placeholders: Group, Group.
// translators: placeholders: Group, Group.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Group, Group","learndash"),c("group"),c("group")),value:d||"",type:"number",onChange:function(e){g(""!=e&&e<0?{group_id:"0"}:{group_id:e})}}));const w=(0,t.createElement)(r.InspectorControls,{key:"controls"},m(),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},f,y,(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("Enter specific User ID. Leave blank for current User.","learndash"),value:u||"",type:"number",onChange:function(e){g(""!=e&&e<0?{user_id:"0"}:{user_id:e})}})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!_,onChange:e=>g({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID for preview.","learndash"),value:h||"",type:"number",onChange:function(e){g(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})));function v(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),_e)}function C(e){return v()}return(0,t.createElement)("div",b,w,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:pe,attributes:a,key:pe,EmptyResponsePlaceholder:C})):v();var a},[e.attributes])))},save:function(e){delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const he="learndash/ld-materials",ge=(0,a.__)("LearnDash Materials","learndash");(0,l.registerBlockType)(he,{title:ge,description:(0,a.__)("This block displays the materials for a specific LearnDash related post.","learndash"),icon:"text",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{post_id:{type:"string",default:""},autop:{type:"string",default:"true"},preview_show:{type:"boolean",default:1},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{post_id:l,autop:n,preview_show:i},setAttributes:d}=e,u=(0,r.useBlockProps)(),c=(0,t.createElement)(s.TextControl,{label:(0,a.__)("Post ID","learndash"),help:(0,a.__)("Enter a Post ID of the LearnDash post that you want to display materials for.","learndash"),value:l||"",type:"number",onChange:function(e){d(""!=e&&e<0?{post_id:"0"}:{post_id:e})}}),_=(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Auto Paragraph","learndash"),help:(0,a.__)("Whether to format materials content using wpautop.","learndash"),checked:!!n,onChange:e=>d({autop:e})}),h=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!i,onChange:e=>d({preview_show:e})})),g=(0,t.createElement)(r.InspectorControls,{key:"controls"},m(),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},c,_),h);function b(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),ge)}function f(e){return b()}return(0,t.createElement)("div",u,g,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:he,attributes:a,key:he,EmptyResponsePlaceholder:f})):b();var a},[e.attributes])))},save:function(e){delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const me="learndash/ld-user-status",be=(0,a.__)("LearnDash User Status");(0,l.registerBlockType)(me,{title:be,description:(0,a.__)("This block displays information of enrolled courses and their progress for a user. Defaults to current logged in user if no ID specified.","learndash"),icon:"analytics",category:"learndash-blocks",supports:{customClassName:!1},apiVersion:3,attributes:{user_id:{type:"string",default:""},registered_num:{type:"string",default:""},registered_order_by:{type:"string"},registered_order:{type:"string"},preview_show:{type:"boolean",default:!0},preview_user_id:{type:"string",default:""},isblock:{type:"boolean",default:1},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{user_id:l,registered_num:n,registered_order_by:i,registered_order:d,preview_show:u,preview_user_id:c,isblock:_},setAttributes:h}=e,g=(0,r.useBlockProps)(),b=(0,t.createElement)(s.TextControl,{label:(0,a.__)("User ID","learndash"),help:(0,a.__)("ID of the user to display information for.","learndash"),value:l||"",type:"number",onChange:function(e){h(""!=e&&e<0?{user_id:"0"}:{user_id:e})}}),f=(0,t.createElement)(s.TextControl,{label:(0,a.__)("Courses per page","learndash"),help:(0,a.__)("Number of courses to display per page. Set to 0 for no pagination.","learndash"),value:n||"",type:"number",onChange:function(e){h(""!=e&&e<0?{registered_num:"0"}:{registered_num:e})}}),y=(0,t.createElement)(s.SelectControl,{key:"registered_order_by",value:i,label:(0,a.__)("Order By","learndash"),options:[{label:(0,a.__)("Title","learndash"),value:"post_title"},{label:(0,a.__)("ID","learndash"),value:"post_id"},{label:(0,a.__)("Date","learndash"),value:"post_date"},{label:(0,a.__)("Menu","learndash"),value:"menu_order"}],onChange:e=>h({registered_order_by:e})}),w=(0,t.createElement)(s.SelectControl,{key:"registered_order",value:d,label:(0,a.__)("Order","learndash"),options:[{label:(0,a.__)("ASC (default)","learndash"),value:"ASC"},{label:(0,a.__)("DESC","learndash"),value:"DESC"}],onChange:e=>h({registered_order:e})}),v=(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!u,onChange:e=>h({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Preview User ID","learndash"),help:(0,a.__)("Enter a User ID to test preview","learndash"),value:c||"",type:"number",onChange:function(e){h(""!=e&&e<0?{preview_user_id:"0"}:{preview_user_id:e})}})),C=(0,t.createElement)(r.InspectorControls,{key:"controls"},m(),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},b,f,y,w),v);function E(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),be)}function x(e){return E()}return(0,t.createElement)("div",g,C,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:me,attributes:a,key:me,EmptyResponsePlaceholder:x})):E();var a},[e.attributes])))},save:function(e){delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const fe="learndash/ld-navigation",ye=(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("LearnDash %s Navigation","placeholder: Course","learndash"),c("course"));(0,l.registerBlockType)(fe,{title:ye,description:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("This block displays the %s Navigation.","placeholder: Course","learndash"),c("course")),icon:"format-aside",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{course_id:{type:"string",default:""},post_id:{type:"string",default:""},preview_show:{type:"boolean",default:1},preview_post_id:{type:"string",default:""},example_show:{type:"boolean",default:0},editing_post_meta:{type:"object"}},edit:e=>{const{attributes:{course_id:l,post_id:n,preview_show:i,preview_post_id:d,example_show:u},setAttributes:_}=e,h=(0,r.useBlockProps)(),g=(0,t.createElement)(r.InspectorControls,{key:"controls"},m(),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Settings","learndash")},(0,t.createElement)(s.TextControl,{label:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("%s ID","placeholder: Course","learndash"),c("course")),help:(0,a.sprintf)(
// translators: placeholders: Course, Course.
// translators: placeholders: Course, Course.
(0,a._x)("Enter single %1$s ID. Leave blank if used within a %2$s.","placeholders: Course, Course","learndash"),c("course"),c("course")),value:l||"",type:"number",onChange:function(e){_(""!=e&&e<0?{course_id:"0"}:{course_id:e})}}),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Step ID","learndash"),help:(0,a.sprintf)(
// translators: placeholder: Course.
// translators: placeholder: Course.
(0,a._x)("Enter single Step ID. Leave blank if used within a %s.","placeholder: Course","learndash"),c("course")),value:n||"",type:"number",onChange:function(e){_(""!=e&&e<0?{post_id:"0"}:{post_id:e})}})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!i,onChange:e=>_({preview_show:e})}),(0,t.createElement)(s.PanelRow,{className:"learndash-block-error-message"},(0,a.__)("Preview settings are not saved.","learndash")),(0,t.createElement)(s.TextControl,{label:(0,a.__)("Step ID","learndash"),help:(0,a.__)("Enter a Step ID to test preview","learndash"),value:d||"",type:"number",onChange:function(e){_(""!=e&&e<0?{preview_post_id:"0"}:{preview_post_id:e})}})));function b(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),ye)}function f(e){return b()}return(0,t.createElement)("div",h,g,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:fe,attributes:a,key:fe,EmptyResponsePlaceholder:f})):b();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const we="learndash/ld-reset-password",ve=(0,a.__)("LearnDash Reset Password","learndash");(0,l.registerBlockType)(we,{title:ve,description:(0,a.__)("Shows the reset password form","learndash"),icon:"id-alt",category:"learndash-blocks",example:{attributes:{example_show:1}},supports:{customClassName:!1},apiVersion:3,attributes:{width:{type:"string"},example_show:{type:"boolean",default:1},preview_show:{type:"boolean",default:!0},editing_post_meta:{type:"object"}},edit:function(e){const{attributes:{preview_show:l,example_show:n,width:i},setAttributes:d}=e,u=(0,r.useBlockProps)(),c=(0,t.createElement)(r.InspectorControls,{key:"controls"},(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Styling","learndash"),initialOpen:!0},(0,t.createElement)(s.TextControl,{label:(0,a.__)("Form Width","learndash"),help:(0,a.__)("Sets the width of the reset password form.","learndash"),value:i||"",type:"string",onChange:e=>d({width:e})})),(0,t.createElement)(s.PanelBody,{title:(0,a.__)("Preview","learndash"),initialOpen:!1},(0,t.createElement)(s.ToggleControl,{label:(0,a.__)("Show Preview","learndash"),checked:!!l,onChange:e=>d({preview_show:e})})));function _(){return(0,a.sprintf)(
// translators: placeholder: block_title.
// translators: placeholder: block_title.
(0,a._x)("%s block output shown here","placeholder: block_title","learndash"),ve)}function h(e){return _()}return(0,t.createElement)("div",u,c,(0,t.createElement)(s.Disabled,null,(0,t.useMemo)(()=>{return 1==(a=e.attributes).preview_show?(a.editing_post_meta=p(),(0,t.createElement)(o(),{block:we,attributes:a,key:we,EmptyResponsePlaceholder:h})):_();var a},[e.attributes])))},save:e=>{delete e.attributes.example_show,delete e.attributes.editing_post_meta}});const Ce=window.wp.data,Ee=window.React;var xe=e.n(Ee);const ke=(0,Ee.createContext)({});(0,l.registerBlockType)("learndash/ld-exam",{title:(0,a.sprintf)(
// translators: placeholder: Challenge Exam.
// translators: placeholder: Challenge Exam.
(0,a._x)("LearnDash %s","placeholder: Challenge Exam","learndash"),c("exam")),description:(0,a.sprintf)(
// translators: placeholder: Create a Challenge Exam.
// translators: placeholder: Create a Challenge Exam.
(0,a._x)("Create a %s","placeholder: Create a Challenge Exam","learndash"),c("exam")),icon:"editor-help",category:"learndash-blocks",supports:{html:!1},apiVersion:3,attributes:{ld_version:{type:"string"}},edit:e=>{const{attributes:{ld_version:a=""},setAttributes:l,clientId:s}=e,n=(0,Ce.useSelect)(e=>e("core/block-editor").getBlockOrder(s),[]),o=(0,t.useMemo)(()=>({blockOrder:n}),[s,n]);return""===a&&l({ld_version:ldlms_settings.version}),(0,t.createElement)(ke.Provider,{value:o},(0,t.createElement)(r.InnerBlocks,{allowedBlocks:["learndash/ld-exam-question"],template:[["learndash/ld-exam-question",{}]],renderAppender:()=>(0,t.createElement)(r.ButtonBlockAppender,{className:"ld-exam-block-appender",rootClientId:s}),templateInsertUpdatesSelection:!0}))},save:()=>(0,t.createElement)(r.InnerBlocks.Content,null)});var Te={color:void 0,size:void 0,className:void 0,style:void 0,attr:void 0},Pe=xe().createContext&&xe().createContext(Te),De=function(){return De=Object.assign||function(e){for(var t,a=1,l=arguments.length;a<l;a++)for(var r in t=arguments[a])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},De.apply(this,arguments)};function Ie(e){return e&&e.map(function(e,t){return xe().createElement(e.tag,De({key:t},e.attr),Ie(e.child))})}function Se(e){return function(t){return xe().createElement(ze,De({attr:De({},e.attr)},t),Ie(e.child))}}function ze(e){var t=function(t){var a,l=e.attr,r=e.size,s=e.title,n=function(e,t){var a={};for(var l in e)Object.prototype.hasOwnProperty.call(e,l)&&t.indexOf(l)<0&&(a[l]=e[l]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var r=0;for(l=Object.getOwnPropertySymbols(e);r<l.length;r++)t.indexOf(l[r])<0&&Object.prototype.propertyIsEnumerable.call(e,l[r])&&(a[l[r]]=e[l[r]])}return a}(e,["attr","size","title"]),o=r||t.size||"1em";return t.className&&(a=t.className),e.className&&(a=(a?a+" ":"")+e.className),xe().createElement("svg",De({stroke:"currentColor",fill:"currentColor",strokeWidth:"0"},t.attr,l,n,{className:a,style:De(De({color:e.color||t.color},t.style),e.style),height:o,width:o,xmlns:"http://www.w3.org/2000/svg"}),s&&xe().createElement("title",null,s),e.children)};return void 0!==Pe?xe().createElement(Pe.Consumer,null,function(e){return t(e)}):t(Te)}function Be(e){return Se({tag:"svg",attr:{viewBox:"0 0 24 24"},child:[{tag:"path",attr:{fill:"none",d:"M0 0h24v24H0z"}},{tag:"path",attr:{d:"M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"}}]})(e)}function qe(e){return Se({tag:"svg",attr:{viewBox:"0 0 24 24"},child:[{tag:"path",attr:{fill:"none",d:"M0 0h24v24H0z"}},{tag:"path",attr:{d:"M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"}}]})(e)}function Le(e){return Se({tag:"svg",attr:{viewBox:"0 0 24 24"},child:[{tag:"path",attr:{fill:"none",d:"M0 0h24v24H0V0z"}},{tag:"path",attr:{d:"M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6z"}},{tag:"path",attr:{d:"M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-5.99 13c-.59 0-1.05-.47-1.05-1.05 0-.59.47-1.04 1.05-1.04.59 0 1.04.45 1.04 1.04-.01.58-.45 1.05-1.04 1.05zm2.5-6.17c-.63.93-1.23 1.21-1.56 1.81-.13.24-.18.4-.18 1.18h-1.52c0-.41-.06-1.08.26-1.65.41-.73 1.18-1.16 1.63-1.8.48-.68.21-1.94-1.14-1.94-.88 0-1.32.67-1.5 1.23l-1.37-.57C11.51 5.96 12.52 5 13.99 5c1.23 0 2.08.56 2.51 1.26.37.61.58 1.73.01 2.57z"}}]})(e)}function Oe(e){return Se({tag:"svg",attr:{viewBox:"0 0 24 24"},child:[{tag:"path",attr:{fill:"none",d:"M0 0h24v24H0z"}},{tag:"path",attr:{d:"M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"}}]})(e)}function Ue(e){return Se({tag:"svg",attr:{viewBox:"0 0 24 24"},child:[{tag:"path",attr:{fill:"none",d:"M0 0h24v24H0z"}},{tag:"path",attr:{d:"M19 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"}}]})(e)}function Ne(e){return Se({tag:"svg",attr:{viewBox:"0 0 1024 1024"},child:[{tag:"path",attr:{d:"M955.7 856l-416-720c-6.2-10.7-16.9-16-27.7-16s-21.6 5.3-27.7 16l-416 720C56 877.4 71.4 904 96 904h832c24.6 0 40-26.6 27.7-48zM480 416c0-4.4 3.6-8 8-8h48c4.4 0 8 3.6 8 8v184c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8V416zm32 352a48.01 48.01 0 0 1 0-96 48.01 48.01 0 0 1 0 96z"}}]})(e)}const Ae=[{label:(0,a.__)("Single","learndash"),value:"single"},{label:(0,a.__)("Multiple","learndash"),value:"multiple"}],$e=(0,a.__)("The Question is empty.","learndash");function Ge(e){return Se({tag:"svg",attr:{viewBox:"0 0 24 24"},child:[{tag:"path",attr:{d:"M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z"}}]})(e)}function Re(e){return Se({tag:"svg",attr:{viewBox:"0 0 24 24"},child:[{tag:"path",attr:{d:"M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM11.0026 16L18.0737 8.92893L16.6595 7.51472L11.0026 13.1716L8.17421 10.3431L6.75999 11.7574L11.0026 16Z"}}]})(e)}(0,l.registerBlockType)("learndash/ld-exam-question",{title:(0,a.sprintf)(
// translators: placeholder: Challenge Exam Question.
// translators: placeholder: Challenge Exam Question.
(0,a._x)("%s Question","placeholder: Challenge Exam Question","learndash"),c("exam")),description:(0,a.sprintf)(
// translators: placeholder: Create a question for your Challenge Exam.
// translators: placeholder: Create a question for your Challenge Exam.
(0,a._x)("Create a question for your %s","placeholder: Create a question for your Challenge Exam","learndash"),c("exam")),icon:(0,t.createElement)(Be,null),category:"learndash-blocks",parent:["learndash/ld-exam"],supports:{html:!1},apiVersion:3,providesContext:{"learndash/question_type":"question_type"},attributes:{question_title:{type:"string"},question_type:{type:"string"}},edit:e=>{const{attributes:{question_title:l="",question_type:n=""},setAttributes:o,clientId:i}=e,[d,u]=(0,t.useState)(!1),[p,_]=(0,t.useState)(!1),{innerBlocksClientIds:h,selectedBlockClientId:g}=(0,Ce.useSelect)(e=>({innerBlocksClientIds:e("core/block-editor").getClientIdsOfDescendants([i]),selectedBlockClientId:e("core/block-editor").getSelectedBlockClientId()})),m=h.includes(g)||g===i,{blockOrder:b}=(0,t.useContext)(ke),f=b.lastIndexOf(i)===b.length-1;!1===p&&!0===m&&_(!0),!0===p&&!1===d&&!1===m&&u(!0),""===n&&o({question_type:"single"});const y=d?"learndash-exam-question-allow-validations":"";return(0,t.createElement)(t.Fragment,null,(0,t.createElement)(r.InspectorControls,null,(0,t.createElement)(s.PanelBody,{title:(0,a.sprintf)(
// translators: placeholder: Question type.
// translators: placeholder: Question type.
(0,a._x)("%s type","placeholder: Question type","learndash"),c("question")),initialOpen:!0},(0,t.createElement)(s.PanelRow,null,(0,t.createElement)(s.SelectControl,{value:n,options:Ae,onChange:e=>o({question_type:e})})))),(0,t.createElement)(r.PlainText,{className:"learndash-exam-question",value:l,placeholder:(0,a.__)("Question","learndash"),onChange:e=>o({question_title:e})}),0===l.length&&(0,t.createElement)("div",{className:`${y} learndash-exam-question-empty-title`},(0,t.createElement)(Ne,{fill:"red"}),(0,t.createElement)("span",null,$e)),(0,t.createElement)("div",{className:`${y} learndash-exam-question-flexbox`},(0,t.createElement)(r.InnerBlocks,{template:[["learndash/ld-question-description",{}],["learndash/ld-question-answers-block",{}],["learndash/ld-correct-answer-message-block",{}],["learndash/ld-incorrect-answer-message-block",{}]],templateLock:"all"})),!f&&(0,t.createElement)("hr",null))},save:()=>(0,t.createElement)(r.InnerBlocks.Content,null)});const Qe=e=>{const{isMultiple:l,attributes:{answer_label:n="",answer_correct:o=!1},setAttributes:i}=e;return(0,t.createElement)(t.Fragment,null,(0,t.createElement)("span",{className:"learndash-exam-question-answer-select"},l?o?(0,t.createElement)(Ue,null):(0,t.createElement)(Oe,null):o?(0,t.createElement)(Re,null):(0,t.createElement)(Ge,null)),(0,t.createElement)("span",{className:"learndash-exam-question-answer-input"},(0,t.createElement)(r.RichText,{value:n,placeholder:(0,a.__)("Add Answer","learndash"),onChange:e=>i({answer_label:e})})),(0,t.createElement)("span",{className:"learndash-exam-question-answer-toggle"},(0,t.createElement)(s.Button,{isSmall:!0,className:"learndash-exam-question-answer-toggle-button",variant:o?"primary":"secondary",disabled:0===n.length,onClick:()=>i({answer_correct:!o}),text:o?(0,a.__)("Correct","learndash"):(0,a.__)("Incorrect","learndash")})))},Me=e=>{const{type:l,attributes:r,setAttributes:n}=e,o=(0,a.__)("Answer is missing.","learndash"),i=(0,a.__)("Required correct answer is missing.","learndash"),d="multiple"===l,u=r.map(({answer_correct:e})=>null!=e&&e).lastIndexOf(!0);let c=0,p=!1;const _=Array.isArray(r)&&r.length>=1?[...r.filter(e=>""!==e.label).map((e,t)=>(c=!0===e.answer_correct?c+1:c,p=!0,d||t===u?{...e}:{...e,answer_correct:!1}))]:[{answer_label:"",answer_correct:!0},{answer_label:"",answer_correct:!1}];!d&&1<c&&n(_),""!==_[_.length-1].answer_label&&_.push({answer_label:"",answer_correct:!1});const h=e=>{if(e>_.length)return;const t=[..._];t.splice(e,1),n(t.filter(({answer_label:e})=>""!==e))};return(0,t.createElement)(t.Fragment,null,(0,t.createElement)("div",{className:"learndash-exam-question-answers-list"},(0,t.createElement)("div",{className:"learndash-exam-question-single-answer"},!p&&(0,t.createElement)("div",{className:"learndash-exam-question-empty-answers"},(0,t.createElement)(Ne,{fill:"red"}),(0,t.createElement)("span",null,o)),p&&0===c&&(0,t.createElement)("div",{className:"learndash-exam-question-empty-correct"},(0,t.createElement)(Ne,{fill:"red"}),(0,t.createElement)("span",null,i))),_.length&&_.map((e,a)=>(0,t.createElement)("div",{className:"learndash-exam-question-single-answer",key:a},(0,t.createElement)("span",{className:"learndash-exam-question-single-answer-delete"},_.length-1!==a&&(0,t.createElement)(s.Button,{isSmall:!0,onClick:()=>h(a),icon:(0,t.createElement)(qe,null)})),(0,t.createElement)(Qe,{isMultiple:d,attributes:e,setAttributes:e=>((e,t)=>{if(""===t.answer_label)h(e);else{const a=_.map(e=>!0!==t.answer_correct||d?{...e}:{...e,answer_correct:!1});a[e]={..._[e],...t},n(a.filter(({answer_label:e})=>""!==e))}})(a,e)})))))},Ve={single:e=>(0,t.createElement)(Me,e),multiple:e=>(0,t.createElement)(Me,e)},We=(0,a.sprintf)(
// translators: placeholder: Challenge Exam Question Answers.
// translators: placeholder: Challenge Exam Question Answers.
(0,a._x)("%s Question Answers","placeholder: Challenge Exam Question Answers","learndash"),c("exam")),je=(0,a.sprintf)(
// translators: placeholder: Challenge Exam Question Answers.
// translators: placeholder: Challenge Exam Question Answers.
(0,a._x)("%s Question Answers","placeholder: Challenge Exam Question Answers","learndash"),c("exam"));(0,l.registerBlockType)("learndash/ld-question-answers-block",{title:We,description:je,icon:(0,t.createElement)(Le,null),category:"learndash-blocks",parent:["learndash/ld-exam-question"],usesContext:["learndash/question_type"],attributes:{question_type:{type:"string",default:""},answers:{type:"array",default:[]}},supports:{inserter:!1,html:!1},apiVersion:3,edit:e=>{const{attributes:{answers:a},setAttributes:l,context:r,clientId:s}=e,n="learndash/question_type"in r&&r["learndash/question_type"]?r["learndash/question_type"]:"single",o=Ve[n];return l({question_type:n}),(0,t.createElement)(o,{clientId:s,type:n,attributes:[...a],setAttributes:e=>l({answers:[...e]})})},save:()=>(0,t.createElement)(r.InnerBlocks.Content,null)});const Fe={icon:(0,t.createElement)(Le,null),parent:["learndash/ld-exam-question"],category:"learndash-blocks",supports:{inserter:!1,html:!1},apiVersion:3,save:()=>(0,t.createElement)(r.InnerBlocks.Content,null)},He=["core/image","core/paragraph"],Ye=((0,l.registerBlockType)("learndash/ld-incorrect-answer-message-block",{...Fe,title:(0,a.__)("Incorrect answer message","learndash"),description:(0,a.__)("Incorrect answer message","learndash"),edit:()=>{const e=[["core/paragraph",{placeholder:(0,a.__)("Add a message for incorrect answer (Optional)","learndash")}]];return(0,t.createElement)(t.Fragment,null,(0,t.createElement)("div",null,(0,a.__)("Incorrect Answer Message","learndash")),(0,t.createElement)(r.InnerBlocks,{allowedBlocks:He,template:e,templateLock:!1}))}}),(0,l.registerBlockType)("learndash/ld-correct-answer-message-block",{...Fe,title:(0,a.__)("Correct answer message","learndash"),description:(0,a.__)("Correct answer message","learndash"),edit:()=>{const e=[["core/paragraph",{placeholder:(0,a.__)("Add a message for correct answer (Optional)","learndash")}]];return(0,t.createElement)(t.Fragment,null,(0,t.createElement)("div",null,(0,a.__)("Correct Answer Message","learndash")),(0,t.createElement)(r.InnerBlocks,{allowedBlocks:He,template:e,templateLock:!1}))}}),(0,a.__)("Question Notes","learndash")),Ze=(0,a.sprintf)(
// translators: placeholder: Write a description for the Challenge Exam question.
// translators: placeholder: Write a description for the Challenge Exam question.
(0,a._x)("Write a description for the %s question.","placeholder: Write a description for the Challenge Exam question","learndash"),c("exam"));(0,l.registerBlockType)("learndash/ld-question-description",{title:Ye,description:Ze,icon:(0,t.createElement)(Be,null),parent:["learndash/ld-exam-question"],category:"learndash-blocks",supports:{inserter:!1,html:!1},apiVersion:3,edit:()=>{const e=[["core/paragraph",{placeholder:(0,a.__)("Add a Description or type '/' to choose a block (Optional)","learndash")}]];return(0,t.createElement)(r.InnerBlocks,{templateLock:!1,template:e})},save:()=>(0,t.createElement)(r.InnerBlocks.Content,null)})})();