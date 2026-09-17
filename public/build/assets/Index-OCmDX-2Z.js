import{D as n,i as d,o as p,j as c,k as t,a as s,P as u,A as m,B as v}from"./app-DQYwCfjj.js";const x={class:"grid grid-cols-1 md:grid-cols-3 gap-6"},b={class:"mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6"},g={class:"grid grid-cols-1 md:grid-cols-2 gap-3"},f={components:{ReportCard:{props:["title","description","color"],emits:["click"],template:`
        <div
          class="bg-white dark:bg-gray-800 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer border-2 border-transparent hover:-translate-y-1"
          :class="'hover:border-' + color + '-500'"
          @click="$emit('click')"
        >
          <div class="p-6">
            <div class="flex justify-between items-center mb-4">
              <div class="icon-container" :class="'icon-container--gradient-' + color">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19c-5 0-8-3-8-8s4-8 9-8 8 3 8 8-3 8-8 8zm0-13a1 1 0 011 1v1h1a1 1 0 010 2h-2a1 1 0 01-1-1V8a1 1 0 011-1z"/>
                </svg>
              </div>
              <div class="icon-container icon-container--sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-gray-100">{{ title }}</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">{{ description }}</p>
            <div class="flex justify-between text-sm">
              <span :class="'text-' + color + '-600 dark:text-' + color + '-400 font-medium'">View Report</span>
              <span class="text-gray-500 dark:text-gray-400">→</span>
            </div>
          </div>
        </div>
      `},FeatureItem:{props:["text"],template:`
        <li class="flex items-start">
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span class="text-sm text-gray-700 dark:text-gray-300">{{ text }}</span>
        </li>
      `}}},k=Object.assign(f,{__name:"Index",setup(y){const r=i=>{v.visit(`/reports/${i}`)};return(i,e)=>{const o=n("ReportCard"),a=n("FeatureItem");return p(),d(m,null,{default:c(()=>[t(u,{title:"Reports & Analytics",subtitle:"View and export comprehensive business reports"}),s("div",x,[s("div",{class:"md:col-span-3 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-700 rounded-xl shadow-lg hover:shadow-xl p-6 text-white cursor-pointer transition-all duration-300 hover:-translate-y-1 relative overflow-hidden",onClick:e[0]||(e[0]=l=>r("financial-audit"))},[...e[11]||(e[11]=[s("div",{class:"flex flex-col md:flex-row md:items-center justify-between gap-4"},[s("div",null,[s("div",{class:"inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm mb-2 text-white border border-white/20"},[s("span",null,"⭐"),s("span",null,"ALL-IN-ONE BUSINESS AUDIT")]),s("h3",{class:"text-2xl font-black text-white"},"Executive Financial & Audit Report (مکمل مالیاتی و آڈٹ سمری)"),s("p",{class:"text-blue-100 text-sm mt-1 max-w-2xl"}," Complete consolidated business audit: Net Profit, Gross Profit, Sales, Purchases, Cash In/Out (Galla & Bank), Market Receivables & Payables, Expenses, and Godown Stock Value with Daily/Weekly/15-Day/Monthly/Yearly audit timelines. ")]),s("div",{class:"flex items-center gap-2 bg-white text-blue-900 font-bold px-5 py-2.5 rounded-xl shadow-md text-sm whitespace-nowrap self-start md:self-auto hover:bg-blue-50 transition"},[s("span",null,"View Executive Audit"),s("span",null,"→")])],-1)])]),t(o,{title:"Sales Report",description:"View detailed sales analytics, customer insights, and revenue trends",color:"blue",onClick:e[1]||(e[1]=l=>r("sales"))}),t(o,{title:"Purchase Report",description:"Track purchase orders, supplier performance, and inventory costs",color:"green",onClick:e[2]||(e[2]=l=>r("purchases"))}),t(o,{title:"Profit Report",description:"Analyze profit margins, expenses, and overall business profitability",color:"purple",onClick:e[3]||(e[3]=l=>r("profit"))}),t(o,{title:"Customer Reports",description:"View customer ledger with sales, payments, and outstanding balances",color:"blue",onClick:e[4]||(e[4]=l=>r("customers"))}),t(o,{title:"Receivables Report",description:"Overview of all customer balances, receivables, and advances",color:"indigo",onClick:e[5]||(e[5]=l=>r("receivables"))}),t(o,{title:"Supplier Reports",description:"Track supplier ledger with purchases, payments, and prepayments",color:"green",onClick:e[6]||(e[6]=l=>r("suppliers"))}),t(o,{title:"Payables Report",description:"Overview of all supplier balances, payables, and prepaid advances",color:"amber",onClick:e[7]||(e[7]=l=>r("payables"))}),t(o,{title:"Receipt Report (Cash In)",description:"Comprehensive customer collections, cash receipts, and inflow audit",color:"emerald",onClick:e[8]||(e[8]=l=>r("receipts"))}),t(o,{title:"Payment Report (Cash Out)",description:"Comprehensive supplier payments, cash disbursements, and outflow audit",color:"rose",onClick:e[9]||(e[9]=l=>r("payments"))}),t(o,{title:"All Parties Ledger",description:"Combined summary of all Customers and Suppliers with balances",color:"purple",onClick:e[10]||(e[10]=l=>r("all-parties-ledger"))})]),s("div",b,[e[12]||(e[12]=s("h3",{class:"text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4"},"📊 Available Features",-1)),s("ul",g,[t(a,{text:"Multiple filter options (Daily, Weekly, Monthly, Yearly, Custom Range)"}),t(a,{text:"Visual charts and graphs for trend analysis"}),t(a,{text:"Export to PDF, Excel, and CSV formats"}),t(a,{text:"Detailed transaction tables with complete data"})])])]),_:1})}}});export{k as default};
