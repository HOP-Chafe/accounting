
const DENOMS=[1000,500,100,50,20,10,5,2,1];
const IE_DEFAULT_INCOME=['เงินหน้าร้าน','เงินโอน','Lineman','Grab','Shopee','ไทยช่วยไทย'];
const IE_DEFAULT_EXPENSE=['ค่าเช่าที่','ค่าน้ำแข็ง','ค่าแรง'];
const state={months:[],cache:{},inventory:[],closings:[],db:false,dailyExtraExpenseItems:[],ieMonths:[],ieCache:{},ieIncomeItems:[],ieExpenseItems:[],ieDaily:null,ieEditingId:null};
const $=id=>document.getElementById(id);const n=v=>Number(v)||0;const money=v=>'฿'+n(v).toLocaleString('th-TH',{minimumFractionDigits:2,maximumFractionDigits:2});

async function api(action,{method='GET',data=null,query={}}={}){
  const qs=new URLSearchParams({action,...query});
  const options={method,headers:{'Accept':'application/json'}};
  if(method==='POST'){
    options.headers['Content-Type']='application/json';
    options.body=JSON.stringify({...data,csrf:CSRF});
  }
  const res=await fetch(APP_URLS.api+'?'+qs.toString(),options);
  if(res.status===401){window.location.href=APP_URLS.login;throw new Error('กรุณาเข้าสู่ระบบอีกครั้ง')}
  let json={};try{json=await res.json()}catch(e){}
  if(!res.ok||!json.ok)throw new Error(json.message||'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้');
  return json;
}
function toast(msg,error=false){const t=$('toast');t.textContent=msg;t.className='toast show'+(error?' error':'');clearTimeout(window.__toast);window.__toast=setTimeout(()=>t.className='toast',2600)}
function setDbStatus(ok,name=''){state.db=ok;const b=$('dbBadge');b.classList.toggle('offline',!ok);b.textContent=ok?`● MySQL: ${name}`:'● ยังไม่เชื่อมฐานข้อมูล'}
function ym(d){return String(d||'').slice(0,7)}
function cups(r){return n(r.front_cups)+n(r.lineman_cups)+n(r.grab_cups)+n(r.shopee_cups)}
function revenue(r){return n(r.cash_revenue)+n(r.transfer_revenue)+n(r.other_front_revenue)+n(r.lineman_revenue)+n(r.grab_revenue)+n(r.shopee_revenue)}
function dailyExtraExpenseItems(r){
  const raw=r?.extra_expense_items;
  if(Array.isArray(raw))return raw;
  if(typeof raw==='string'&&raw.trim()){try{const parsed=JSON.parse(raw);return Array.isArray(parsed)?parsed:[]}catch(e){return []}}
  return [];
}
function dailyExtraExpenseTotal(r){return dailyExtraExpenseItems(r).reduce((sum,item)=>sum+n(item?.amount),0)}
function expense(r){return n(r.ice_expense)+n(r.wage_expense)+n(r.cup_expense)+n(r.other_expense)+dailyExtraExpenseTotal(r)}
function thDate(iso){if(!iso)return '';return new Intl.DateTimeFormat('th-TH',{day:'numeric',month:'short',year:'2-digit'}).format(new Date(iso+'T12:00:00'))}
function monthLabel(m){const [y,mo]=m.split('-').map(Number);return new Intl.DateTimeFormat('th-TH',{month:'long',year:'numeric'}).format(new Date(y,mo-1,1))}
function today(){const d=new Date();d.setMinutes(d.getMinutes()-d.getTimezoneOffset());return d.toISOString().slice(0,10)}

const APP_PAGE_TITLES=window.ACCOUNTING_PAGE_TITLES||{dashboard:'Dashboard',daily:'บันทึกยอดประจำวัน',close:'ปิดรอบ',inventory:'วัตถุดิบ',reports:'รายงานย้อนหลัง','income-expense':'สรุปบัญชีรายรับ - รายจ่าย'};
const APP_PAGE_URLS=window.ACCOUNTING_PAGE_URLS||{};
const CURRENT_PAGE=window.ACCOUNTING_CURRENT_PAGE||'dashboard';
const APP_PAGES=Object.keys(APP_PAGE_TITLES);
function gotoPage(p){
  if(!APP_PAGES.includes(p))p='dashboard';
  const url=APP_PAGE_URLS[p];
  if(url) window.location.href=url;
}

async function refreshMonths(prefer=''){
  const data=await api('months');
  state.months=data.months||[];
  const current=prefer||today().slice(0,7);
  if(!state.months.includes(current))state.months.unshift(current);
  state.months=[...new Set(state.months)].sort().reverse();
  ['dashMonth','reportMonth'].forEach(id=>{
    const sel=$(id);if(!sel)return;
    const old=sel.value||current;
    sel.innerHTML=state.months.map(m=>`<option value="${m}">${monthLabel(m)}</option>`).join('');
    sel.value=state.months.includes(old)?old:state.months[0];
  });
}
async function loadRecords(month,force=false){if(!force&&state.cache[month])return state.cache[month];const data=await api('records',{query:{month}});state.cache[month]=data.records||[];return state.cache[month]}

async function refreshIncomeExpenseMonths(prefer=''){
  const data=await api('income_expense_months');
  state.ieMonths=data.months||[];
  const current=prefer||today().slice(0,7);
  if(!state.ieMonths.includes(current))state.ieMonths.unshift(current);
  state.ieMonths=[...new Set(state.ieMonths)].sort().reverse();
  const sel=$('ieMonth');if(!sel)return;
  const old=sel.value||current;
  sel.innerHTML=state.ieMonths.map(m=>`<option value="${m}">${monthLabel(m)}</option>`).join('');
  sel.value=state.ieMonths.includes(old)?old:state.ieMonths[0];
  if($('ieReportFrom')&&!$('ieReportFrom').value)syncIncomeExpenseReportRangeFromMonth();
}
async function loadIncomeExpenseRecords(month,force=false){if(!force&&state.ieCache[month])return state.ieCache[month];const data=await api('income_expense_records',{query:{month}});state.ieCache[month]=data.records||[];return state.ieCache[month]}

function nextIncomeExpenseDateInMonth(lastDate,month){
  if(!lastDate||!/^\d{4}-\d{2}-\d{2}$/.test(lastDate))return `${month}-01`;
  const [y,m,d]=lastDate.split('-').map(Number),dt=new Date(y,m-1,d+1);
  const next=`${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}-${String(dt.getDate()).padStart(2,'0')}`;
  return next.startsWith(month)?next:lastDate;
}
async function changeIncomeExpenseMonth(){
  const sel=$('ieMonth'),m=sel?.value;if(!m)return;
  syncIncomeExpenseReportRangeFromMonth();
  try{
    const rs=await loadIncomeExpenseRecords(m);
    await renderIncomeExpenseHistory();
    // เดือนที่เลือกเป็นบริบทของทั้งหน้า: ฟอร์มด้านบนและตารางประวัติต้องอ้างอิงเดือนเดียวกัน
    // ถ้ามีประวัติแล้ว ให้เตรียมวันที่ถัดจากรายการล่าสุด (แต่ไม่ข้ามเดือน) เพื่อให้ยอดยกมา = ยอดคงเหลือล่าสุดของเดือนนั้น
    let targetDate;
    if(rs.length){
      const latest=rs[rs.length-1];
      targetDate=nextIncomeExpenseDateInMonth(latest.record_date,m);
    }else{
      targetDate=today().startsWith(m)?today():`${m}-01`;
    }
    if($('ieDate'))$('ieDate').value=targetDate;
    state.ieEditingId=null;
    await loadIncomeExpenseDate();
  }catch(e){toast(e.message,true)}
}

async function renderDashboard(){
  const m=$('dashMonth').value||state.months[0];if(!m)return;
  try{
    const rs=await loadRecords(m),tot=rs.reduce((a,r)=>a+cups(r),0),front=rs.reduce((a,r)=>a+n(r.front_cups),0),line=rs.reduce((a,r)=>a+n(r.lineman_cups),0),grab=rs.reduce((a,r)=>a+n(r.grab_cups),0),shop=rs.reduce((a,r)=>a+n(r.shopee_cups),0),delivery=line+grab+shop,rev=rs.reduce((a,r)=>a+revenue(r),0),exp=rs.reduce((a,r)=>a+expense(r),0);
    $('kpiCups').textContent=tot.toLocaleString();$('kpiDays').textContent=`${rs.length} วันที่มีข้อมูล`;$('kpiFront').textContent=front.toLocaleString();$('kpiFrontPct').textContent=tot?`${(front/tot*100).toFixed(1)}% ของทั้งหมด`:'0%';$('kpiDelivery').textContent=delivery.toLocaleString();$('kpiDeliveryPct').textContent=tot?`${(delivery/tot*100).toFixed(1)}% ของทั้งหมด`:'0%';$('kpiRevenue').textContent=rev?money(rev):'—';$('kpiRevenueHint').textContent=rev?`รายจ่าย ${money(exp)} • สุทธิ ${money(rev-exp)}`:'ยังไม่มีข้อมูลยอดเงิน';
    const vals=[['หน้าร้าน',front],['LINE MAN',line],['GRAB',grab],['SHOPEE',shop]],mx=Math.max(...vals.map(x=>x[1]),1);$('channelBars').innerHTML=vals.map(([lab,v])=>`<div class="bar-row"><span>${lab}</span><div class="bar-track"><div class="bar-fill" style="width:${v/mx*100}%"></div></div><span class="bar-value">${v.toLocaleString()}</span></div>`).join('');
    const maxDay=Math.max(...rs.map(cups),1),peak=rs.reduce((best,r)=>!best||cups(r)>cups(best)?r:best,null);$('trend').innerHTML=rs.length?rs.map(r=>`<div class="trend-col"><i style="height:${cups(r)/maxDay*100}%"></i><em>${thDate(r.record_date)} • ${cups(r)} แก้ว</em></div>`).join(''):'<div class="empty" style="width:100%">ยังไม่มีข้อมูลเดือนนี้</div>';$('peakLabel').textContent=peak?`สูงสุด ${cups(peak).toLocaleString()} แก้ว • ${thDate(peak.record_date)}`:'—';
    $('financialSummary').innerHTML=rev||exp?`<div class="stat-list"><div class="stat-line"><span>รายรับรวม</span><b>${money(rev)}</b></div><div class="stat-line"><span>รายจ่ายรวม</span><b>${money(exp)}</b></div><div class="stat-line"><span>คงเหลือสุทธิ</span><b style="color:${rev-exp>=0?'var(--brand)':'var(--danger)'}">${money(rev-exp)}</b></div></div>`:`<div class="notice"><b>ยังไม่มีข้อมูลยอดเงิน</b><br>เมื่อบันทึกรายรับ/รายจ่าย ระบบจะรวมยอดจากฐานข้อมูลให้ทันที</div>`;
  }catch(e){toast(e.message,true)}
}

function renderDailyExtraExpenses(){
  const box=$('dailyExtraExpenseRows');if(!box)return;
  box.innerHTML=state.dailyExtraExpenseItems.map((item,i)=>`<div class="daily-extra-expense-row">
    <input class="daily-extra-expense-label" maxlength="120" value="${escapeAttr(item.label||'')}" placeholder="ชื่อรายการ เช่น ค่าส่งของ" oninput="state.dailyExtraExpenseItems[${i}].label=this.value">
    <div class="daily-extra-expense-money"><input type="number" min="0" step="0.01" inputmode="decimal" value="${n(item.amount)}" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="state.dailyExtraExpenseItems[${i}].amount=this.value;updateDailyCalc()"><span>บาท</span></div>
    <button type="button" class="daily-extra-expense-remove" title="ลบรายการ" aria-label="ลบรายการ" onclick="removeDailyExpenseItem(${i})">×</button>
  </div>`).join('');
}
function addDailyExpenseItem(){
  if(state.dailyExtraExpenseItems.length>=40)return toast('เพิ่มรายจ่ายเพิ่มเติมได้สูงสุด 40 รายการต่อวัน',true);
  state.dailyExtraExpenseItems.push({label:'',amount:0});renderDailyExtraExpenses();updateDailyCalc();
  const labels=$('dailyExtraExpenseRows')?.querySelectorAll('.daily-extra-expense-label');labels?.[labels.length-1]?.focus();
}
function removeDailyExpenseItem(index){state.dailyExtraExpenseItems.splice(index,1);renderDailyExtraExpenses();updateDailyCalc()}
function updateDailyCalc(){
  const total=n($('dFront').value)+n($('dLine').value)+n($('dGrab').value)+n($('dShopee').value);$('dailyCupTotal').textContent=total.toLocaleString()+' แก้ว';
  const rev=['dCash','dTransfer','dThai','dLineRev','dGrabRev','dShopeeRev'].reduce((a,id)=>a+n($(id).value),0);
  const fixedExp=['dIce','dWage','dCupCost','dOtherExp'].reduce((a,id)=>a+n($(id).value),0),extraExp=state.dailyExtraExpenseItems.reduce((a,item)=>a+n(item.amount),0),exp=fixedExp+extraExp;
  $('dailyNet').textContent=money(rev-exp);$('dailyNet').style.color=rev-exp>=0?'var(--brand)':'var(--danger)';
}
function resetDailyForm(){['dFront','dLine','dGrab','dShopee','dCash','dTransfer','dThai','dLineRev','dGrabRev','dShopeeRev','dIce','dWage','dCupCost','dOtherExp'].forEach(id=>$(id).value=0);state.dailyExtraExpenseItems=[];renderDailyExtraExpenses();$('dNote').value='';$('dDate').value=today();$('dailySaveBtn').textContent='บันทึกรายการ';updateDailyCalc()}
async function saveDaily(e){
  e.preventDefault();const btn=$('dailySaveBtn');btn.disabled=true;
  const rec={date:$('dDate').value,front:n($('dFront').value),line:n($('dLine').value),grab:n($('dGrab').value),shopee:n($('dShopee').value),cash:n($('dCash').value),transfer:n($('dTransfer').value),thai:n($('dThai').value),lineRev:n($('dLineRev').value),grabRev:n($('dGrabRev').value),shopeeRev:n($('dShopeeRev').value),ice:n($('dIce').value),wage:n($('dWage').value),cupCost:n($('dCupCost').value),otherExp:n($('dOtherExp').value),extraExpenseItems:state.dailyExtraExpenseItems,note:$('dNote').value};
  try{
    await api('save_daily',{method:'POST',data:rec});
    const m=ym(rec.date);delete state.cache[m];
    window.location.href=APP_PAGE_URLS.reports+'?month='+encodeURIComponent(m)+'&saved=1';
  }catch(err){toast(err.message,true);btn.disabled=false}
}
function editRecord(date){
  window.location.href=APP_PAGE_URLS.daily+'?edit='+encodeURIComponent(date);
}
async function loadDailyEditFromQuery(){
  const date=new URLSearchParams(window.location.search).get('edit');
  if(!date)return;
  const m=ym(date);
  try{
    const rs=await loadRecords(m,true),r=rs.find(x=>x.record_date===date);
    if(!r)return toast('ไม่พบรายการที่ต้องการแก้ไข',true);
    $('dDate').value=r.record_date;$('dFront').value=r.front_cups;$('dLine').value=r.lineman_cups;$('dGrab').value=r.grab_cups;$('dShopee').value=r.shopee_cups;$('dCash').value=r.cash_revenue;$('dTransfer').value=r.transfer_revenue;$('dThai').value=r.other_front_revenue;$('dLineRev').value=r.lineman_revenue;$('dGrabRev').value=r.grab_revenue;$('dShopeeRev').value=r.shopee_revenue;$('dIce').value=r.ice_expense;$('dWage').value=r.wage_expense;$('dCupCost').value=r.cup_expense;$('dOtherExp').value=r.other_expense;state.dailyExtraExpenseItems=dailyExtraExpenseItems(r).map(x=>({label:x.label||'',amount:n(x.amount)}));renderDailyExtraExpenses();$('dNote').value=r.note||'';$('dailySaveBtn').textContent='อัปเดตรายการ';updateDailyCalc();
  }catch(e){toast(e.message,true)}
}
async function deleteRecord(date){if(!confirm('ลบข้อมูลวันที่ '+thDate(date)+' ออกจากฐานข้อมูล?'))return;try{await api('delete_daily',{method:'POST',data:{date}});delete state.cache[ym(date)];await refreshMonths(ym(date));toast('ลบรายการแล้ว');await renderReports()}catch(e){toast(e.message,true)}}

async function renderReports(){
  const m=$('reportMonth').value||state.months[0];if(!m)return;
  try{const rs=await loadRecords(m);if(!rs.length){$('reportBody').innerHTML='<tr><td colspan="10" class="empty">ยังไม่มีข้อมูลเดือนนี้</td></tr>';$('reportFoot').innerHTML='';return}let tf=0,tl=0,tg=0,ts=0,tc=0,tr=0,te=0;$('reportBody').innerHTML=rs.map(r=>{const rv=revenue(r),ex=expense(r),cp=cups(r);tf+=n(r.front_cups);tl+=n(r.lineman_cups);tg+=n(r.grab_cups);ts+=n(r.shopee_cups);tc+=cp;tr+=rv;te+=ex;return `<tr><td>${thDate(r.record_date)}</td><td>${n(r.front_cups)}</td><td>${n(r.lineman_cups)}</td><td>${n(r.grab_cups)}</td><td>${n(r.shopee_cups)}</td><td class="total">${cp}</td><td>${rv?money(rv):'—'}</td><td>${ex?money(ex):'—'}</td><td style="color:${rv-ex>=0?'var(--brand)':'var(--danger)'}">${rv||ex?money(rv-ex):'—'}</td><td><button class="btn ghost small" onclick="editRecord('${r.record_date}')">แก้ไข</button> <button class="btn danger small" onclick="deleteRecord('${r.record_date}')">ลบ</button></td></tr>`}).join('');$('reportFoot').innerHTML=`<tr><th>รวม</th><th>${tf.toLocaleString()}</th><th>${tl.toLocaleString()}</th><th>${tg.toLocaleString()}</th><th>${ts.toLocaleString()}</th><th>${tc.toLocaleString()}</th><th>${tr?money(tr):'—'}</th><th>${te?money(te):'—'}</th><th>${tr||te?money(tr-te):'—'}</th><th></th></tr>`}catch(e){toast(e.message,true)}
}


function blankLedgerItems(labels){return labels.map(label=>({label,amount:0}))}
function ledgerItems(type){return type==='income'?state.ieIncomeItems:state.ieExpenseItems}
function clearZeroOnFocus(el){
  const raw=String(el?.value??'').trim();
  if(raw!=='' && Number(raw)===0) el.value='';
}
function restoreZeroOnBlur(el){
  if(!el)return;
  if(String(el.value??'').trim()===''){
    el.value='0';
    el.dispatchEvent(new Event('input',{bubbles:true}));
  }
}
function setLedgerItems(type,items){if(type==='income')state.ieIncomeItems=items;else state.ieExpenseItems=items}
function renderLedgerRows(type){
  const box=$(type==='income'?'ieIncomeRows':'ieExpenseRows');if(!box)return;
  const items=ledgerItems(type);
  box.innerHTML=items.map((item,i)=>`<div class="ledger-entry">
    <input class="ledger-label" maxlength="120" value="${escapeAttr(item.label||'')}" placeholder="ชื่อรายการ" oninput="ledgerItems('${type}')[${i}].label=this.value">
    <div class="ledger-money"><input type="number" min="0" step="0.01" value="${n(item.amount)}" inputmode="decimal" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="ledgerItems('${type}')[${i}].amount=this.value;updateIncomeExpenseCalc()"><span>บาท</span></div>
    <button type="button" class="ledger-remove" title="ลบรายการ" onclick="removeLedgerRow('${type}',${i})">×</button>
  </div>`).join('');
}
function addLedgerRow(type,label='',amount=0){const items=ledgerItems(type);items.push({label,amount});renderLedgerRows(type);updateIncomeExpenseCalc();const box=$(type==='income'?'ieIncomeRows':'ieExpenseRows');const labels=box?.querySelectorAll('.ledger-label');labels?.[labels.length-1]?.focus()}
function removeLedgerRow(type,index){const items=ledgerItems(type);if(items.length<=1){items[0]={label:'',amount:0}}else items.splice(index,1);renderLedgerRows(type);updateIncomeExpenseCalc()}
function updateIncomeExpenseYear(){const d=$('ieDate')?.value;if(!d)return;$('ieYearLabel').textContent='ประจำปี '+(Number(d.slice(0,4))+543)}
function updateIncomeExpenseCalc(){
  if(!$('ieIncomeTotal'))return;
  const carry=n($('ieCarry').value),income=state.ieIncomeItems.reduce((a,x)=>a+n(x.amount),0),expense=state.ieExpenseItems.reduce((a,x)=>a+n(x.amount),0),grand=carry+income,balance=grand-expense;
  $('ieCarryDisplay').textContent=money(carry);$('ieIncomeTotal').textContent=money(income);$('ieGrandTotal').textContent=money(grand);$('ieExpenseTotal').textContent=money(expense);$('ieBalance').textContent=money(balance);$('ieBalance').style.color=balance>=0?'var(--brand)':'var(--danger)';updateIncomeExpenseYear();return{carry,income,expense,grand,balance}
}
function applyIncomeExpenseRecord(r){
  state.ieEditingId=n(r.id)||null;$('ieDate').value=r.record_date;$('ieCarry').value=n(r.carry_forward);$('ieNote').value=r.note||'';
  state.ieIncomeItems=Array.isArray(r.income_items)&&r.income_items.length?r.income_items.map(x=>({label:x.label||'',amount:n(x.amount)})):blankLedgerItems(IE_DEFAULT_INCOME);
  state.ieExpenseItems=Array.isArray(r.expense_items)&&r.expense_items.length?r.expense_items.map(x=>({label:x.label||'',amount:n(x.amount)})):blankLedgerItems(IE_DEFAULT_EXPENSE);
  renderLedgerRows('income');renderLedgerRows('expense');updateIncomeExpenseCalc();$('ieSaveBtn').textContent='อัปเดตสรุปบัญชี';$('ieCarryHint').textContent='กำลังแก้ไขรายการที่บันทึกไว้ เมื่ออัปเดตแล้วระบบจะเริ่มรายการใหม่ให้อัตโนมัติ';
}
async function resetIncomeExpenseForm(keepDate=false){
  const date=keepDate&&$('ieDate').value?$('ieDate').value:today();state.ieEditingId=null;$('ieDate').value=date;$('ieCarry').value=0;$('ieNote').value='';state.ieIncomeItems=blankLedgerItems(IE_DEFAULT_INCOME);state.ieExpenseItems=blankLedgerItems(IE_DEFAULT_EXPENSE);state.ieDaily=null;renderLedgerRows('income');renderLedgerRows('expense');$('ieSaveBtn').textContent='บันทึกสรุปบัญชี';$('ieCarryHint').textContent='ระบบจะค้นหายอดคงเหลือล่าสุดก่อนรายการนี้';updateIncomeExpenseCalc();await loadPreviousBalance(false)
}
async function loadIncomeExpenseDate(){
  const date=$('ieDate').value;if(!date)return;state.ieEditingId=null;updateIncomeExpenseYear();
  try{
    const data=await api('income_expense_date',{query:{date}});state.ieDaily=data.daily||null;
    $('ieCarry').value=data.previous?n(data.previous.balance):0;$('ieNote').value='';state.ieIncomeItems=blankLedgerItems(IE_DEFAULT_INCOME);state.ieExpenseItems=blankLedgerItems(IE_DEFAULT_EXPENSE);renderLedgerRows('income');renderLedgerRows('expense');$('ieSaveBtn').textContent='บันทึกสรุปบัญชี';$('ieCarryHint').textContent=data.previous?`ยอดยกมาจากรายการล่าสุดในเดือนนี้ ${thDate(data.previous.record_date)}`:'ยังไม่มีรายการก่อนหน้าในเดือนนี้ ยอดยกมาเริ่มที่ 0';updateIncomeExpenseCalc();
  }catch(e){toast(e.message,true)}
}
async function loadPreviousBalance(showToast=false){
  const date=$('ieDate').value;if(!date)return;
  try{const data=await api('income_expense_date',{query:{date}});state.ieDaily=data.daily||state.ieDaily;$('ieCarry').value=data.previous?n(data.previous.balance):0;$('ieCarryHint').textContent=data.previous?`ยอดยกมาจากรายการล่าสุดในเดือนนี้ ${thDate(data.previous.record_date)}`:'ยังไม่มีรายการก่อนหน้าในเดือนนี้ ยอดยกมาเริ่มที่ 0';updateIncomeExpenseCalc();if(showToast)toast(data.previous?'ดึงยอดยกมาภายในเดือนเรียบร้อย':'ไม่พบยอดคงเหลือก่อนหน้าในเดือนนี้')}catch(e){toast(e.message,true)}
}
function setLedgerAmount(type,label,value,addWhenMissing=false){const items=ledgerItems(type),key=String(label).trim().toLowerCase();let item=items.find(x=>String(x.label).trim().toLowerCase()===key);if(item)item.amount=n(value);else if(addWhenMissing&&n(value)!==0)items.push({label,amount:n(value)})}
async function prefillIncomeExpenseFromDaily(){
  const date=$('ieDate').value;if(!date)return;
  try{
    if(!state.ieDaily){const data=await api('income_expense_date',{query:{date}});state.ieDaily=data.daily||null}
    const r=state.ieDaily;if(!r)return toast('ไม่พบข้อมูลในเมนูบันทึกยอดประจำวันสำหรับวันที่นี้',true);
    setLedgerAmount('income','เงินหน้าร้าน',r.cash_revenue);setLedgerAmount('income','เงินโอน',r.transfer_revenue);setLedgerAmount('income','Lineman',r.lineman_revenue);setLedgerAmount('income','Grab',r.grab_revenue);setLedgerAmount('income','Shopee',r.shopee_revenue);setLedgerAmount('income','ไทยช่วยไทย',r.other_front_revenue);
    setLedgerAmount('expense','ค่าน้ำแข็ง',r.ice_expense);setLedgerAmount('expense','ค่าแรง',r.wage_expense);setLedgerAmount('expense','ค่าแก้ว',r.cup_expense,true);setLedgerAmount('expense','รายจ่ายอื่น ๆ',r.other_expense,true);
    dailyExtraExpenseItems(r).forEach(item=>{const label=String(item?.label||'').trim(),amount=n(item?.amount);if(label&&amount!==0)state.ieExpenseItems.push({label,amount})});
    renderLedgerRows('income');renderLedgerRows('expense');updateIncomeExpenseCalc();toast('ดึงยอดจากบันทึกประจำวันแล้ว');
  }catch(e){toast(e.message,true)}
}
async function saveIncomeExpense(e){
  e.preventDefault();const date=$('ieDate').value;if(!date)return toast('กรุณาระบุวันที่',true);const btn=$('ieSaveBtn');const isUpdate=state.ieEditingId!==null;btn.disabled=true;
  try{
    await api('save_income_expense',{method:'POST',data:{id:state.ieEditingId,date,carry_forward:n($('ieCarry').value),income_items:state.ieIncomeItems,expense_items:state.ieExpenseItems,note:$('ieNote').value}});delete state.ieCache[ym(date)];await refreshIncomeExpenseMonths(ym(date));$('ieMonth').value=ym(date);toast(isUpdate?'อัปเดตสรุปบัญชีเรียบร้อยแล้ว':'บันทึกสรุปบัญชีลงฐานข้อมูลแล้ว');await renderIncomeExpenseHistory(true);await resetIncomeExpenseForm(true);
  }catch(err){toast(err.message,true)}finally{btn.disabled=false}
}
async function editIncomeExpense(id){
  try{const data=await api('income_expense_record',{query:{id}});if(!data.record)return toast('ไม่พบรายการ',true);state.ieDaily=data.daily||null;applyIncomeExpenseRecord(data.record);window.scrollTo({top:0,behavior:'smooth'})}catch(e){toast(e.message,true)}
}
async function deleteIncomeExpense(id,date){if(!confirm('ลบสรุปบัญชีวันที่ '+thDate(date)+' ออกจากฐานข้อมูล?'))return;try{await api('delete_income_expense',{method:'POST',data:{id}});delete state.ieCache[ym(date)];await refreshIncomeExpenseMonths(ym(date));toast('ลบสรุปบัญชีแล้ว');await renderIncomeExpenseHistory(true);if(state.ieEditingId===n(id))await resetIncomeExpenseForm(true)}catch(e){toast(e.message,true)}}
async function renderIncomeExpenseHistory(force=false){
  const sel=$('ieMonth');if(!sel)return;const m=sel.value||state.ieMonths[0];if(!m)return;$('ieHistoryLabel').textContent=monthLabel(m);
  try{
    const rs=await loadIncomeExpenseRecords(m,force);if(!rs.length){$('ieHistoryBody').innerHTML='<tr><td colspan="8" class="empty">ยังไม่มีข้อมูลสรุปบัญชีเดือนนี้</td></tr>';$('ieHistoryFoot').innerHTML='';return}
    let income=0,expense=0;rs.forEach(r=>{income+=n(r.income_total);expense+=n(r.expense_total)});const ending=rs[rs.length-1];
    $('ieHistoryBody').innerHTML=rs.map(r=>`<tr><td>${thDate(r.record_date)}</td><td>${money(r.carry_forward)}</td><td>${money(r.income_total)}</td><td>${money(r.grand_total)}</td><td>${money(r.expense_total)}</td><td class="total" style="color:${n(r.balance)>=0?'var(--brand)':'var(--danger)'}">${money(r.balance)}</td><td class="ie-note-cell">${escapeHtml(r.note||'—')}</td><td><button class="btn ghost small" onclick="editIncomeExpense(${n(r.id)})">แก้ไข</button> <button class="btn danger small" onclick="deleteIncomeExpense(${n(r.id)},'${r.record_date}')">ลบ</button></td></tr>`).join('');
    $('ieHistoryFoot').innerHTML=`<tr><th>รวมเดือน</th><th>—</th><th>${money(income)}</th><th>—</th><th>${money(expense)}</th><th>${money(ending.balance)}</th><th>ยอดคงเหลือล่าสุด</th><th></th></tr>`;
  }catch(e){$('ieHistoryBody').innerHTML='<tr><td colspan="8" class="empty">ยังใช้งานตารางสรุปบัญชีไม่ได้</td></tr>';toast(e.message+' — กรุณาเปิด install.php เพื่ออัปเดตฐานข้อมูล',true)}
}

function syncIncomeExpenseReportRangeFromMonth(showToast=false){
  const m=$('ieMonth')?.value||today().slice(0,7);if(!/^\d{4}-\d{2}$/.test(m))return;
  const [y,mo]=m.split('-').map(Number),lastDay=new Date(y,mo,0).getDate();
  const from=`${m}-01`,to=`${m}-${String(lastDay).padStart(2,'0')}`;
  if($('ieReportFrom'))$('ieReportFrom').value=from;
  if($('ieReportTo'))$('ieReportTo').value=to;
  if(showToast)toast('ตั้งช่วงรายงานตามเดือนที่เลือกแล้ว');
}
function openIncomeExpensePdfReport(){
  const from=$('ieReportFrom')?.value||'',to=$('ieReportTo')?.value||'';
  if(!from||!to)return toast('กรุณาเลือกช่วงวันที่สำหรับออกรายงาน',true);
  if(from>to)return toast('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด',true);
  const url=new URL(APP_URLS.incomeExpenseReport,window.location.origin);
  url.searchParams.set('from',from);url.searchParams.set('to',to);url.searchParams.set('autoprint','1');
  const win=window.open(url.toString(),'_blank');
  if(win){try{win.opener=null}catch(e){}}else{toast('Browser บล็อกหน้าต่างรายงาน กรุณาอนุญาต Pop-up สำหรับเว็บไซต์นี้',true);}
}

function exportIncomeExpenseCSV(){const m=$('ieMonth').value,rs=state.ieCache[m]||[];if(!rs.length)return toast('ไม่มีข้อมูลสรุปบัญชีสำหรับ Export',true);const rows=[['วันที่','ยอดยกมา','รายรับวันนี้','รวมทั้งสิ้น','รายจ่าย','ยอดคงเหลือ','หมายเหตุ'],...rs.map(r=>[r.record_date,r.carry_forward,r.income_total,r.grand_total,r.expense_total,r.balance,r.note||''])];const csv='\uFEFF'+rows.map(a=>a.map(v=>'"'+String(v??'').replaceAll('"','""')+'"').join(',')).join('\n'),blob=new Blob([csv],{type:'text/csv;charset=utf-8'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=`HOP_Chafe_Income_Expense_${m}.csv`;a.click();URL.revokeObjectURL(url)}

async function loadInventory(){const data=await api('inventory');state.inventory=data.items||[];renderInventory()}
async function addInventory(e){
  e.preventDefault();const name=$('newIngredientName').value.trim(),unit=$('newIngredientUnit').value.trim();
  if(!name){toast('กรุณาระบุชื่อวัตถุดิบ',true);$('newIngredientName').focus();return}
  const btn=$('addInventoryBtn');btn.disabled=true;
  try{const data=await api('add_inventory',{method:'POST',data:{name,unit}});state.inventory.push(data.item);renderInventory();$('addInventoryForm').reset();$('addInventoryForm').hidden=true;toast('เพิ่มวัตถุดิบลงฐานข้อมูลแล้ว')}catch(err){toast(err.message,true)}finally{btn.disabled=false}
}
function renderInventory(){$('inventoryBody').innerHTML=state.inventory.length?state.inventory.map((x,i)=>`<tr><td class="ingredient-name">${escapeHtml(x.name)}</td><td><input value="${x.used_qty??''}" inputmode="decimal" onchange="state.inventory[${i}].used_qty=this.value"></td><td><input value="${x.remaining_qty??''}" inputmode="decimal" onchange="state.inventory[${i}].remaining_qty=this.value"></td><td><input style="width:150px;text-align:left" value="${escapeAttr(x.unit||'')}" placeholder="เช่น ถุง / ขวด" onchange="state.inventory[${i}].unit=this.value"></td></tr>`).join(''):'<tr><td colspan="4" class="empty">ยังไม่มีรายการวัตถุดิบ</td></tr>';$('ingredientCount').textContent=state.inventory.length;$('usedCount').textContent=state.inventory.filter(x=>x.used_qty!==null&&x.used_qty!=='').length;$('remainCount').textContent=state.inventory.filter(x=>x.remaining_qty!==null&&x.remaining_qty!=='').length}
async function saveInventory(){try{await api('save_inventory',{method:'POST',data:{items:state.inventory}});toast('บันทึกวัตถุดิบลงฐานข้อมูลแล้ว');await loadInventory()}catch(e){toast(e.message,true)}}

function buildDenoms(){$('denomGrid').innerHTML=DENOMS.map(v=>`<div class="denom"><label>฿${v}</label><input type="number" min="0" value="0" inputmode="numeric" data-denom="${v}" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateCloseCalc()"></div>`).join('')}
function updateCloseCalc(){let total=0;document.querySelectorAll('[data-denom]').forEach(inp=>total+=n(inp.value)*n(inp.dataset.denom));const expected=n($('expectedCash').value),diff=total-expected;$('countedCash').textContent=money(total);$('expectedCashText').textContent=money(expected);$('cashVariance').textContent=(diff>0?'+':'')+money(diff);$('cashVariance').className='big '+(diff>=0?'good':'bad');$('cashStatus').textContent=diff===0?'ปกติ':diff>0?'เงินเกิน':'เงินขาด';$('cashStatus').className=diff>=0?'good':'bad';return{total,expected,diff}}
async function saveClose(){const c=updateCloseCalc(),denoms=Object.fromEntries([...document.querySelectorAll('[data-denom]')].map(x=>[x.dataset.denom,n(x.value)]));try{await api('save_close',{method:'POST',data:{business_date:$('cDate').value,counted:c.total,expected:c.expected,denoms}});toast('บันทึกการปิดรอบลงฐานข้อมูลแล้ว');document.querySelectorAll('[data-denom]').forEach(x=>x.value=0);$('expectedCash').value=0;updateCloseCalc();await loadClosings()}catch(e){toast(e.message,true)}}
async function loadClosings(){const data=await api('closings');state.closings=data.closings||[];renderCloseHistory()}
function renderCloseHistory(){const box=$('closeHistory');if(!box)return;if(!state.closings.length){box.innerHTML='<div class="empty">ยังไม่มีประวัติปิดรอบ</div>';return}box.innerHTML='<div class="stat-list">'+state.closings.slice(0,8).map(x=>`<div class="stat-line"><span>${thDate(x.business_date)}<small style="display:block;color:var(--muted)">${escapeHtml(x.closed_by||'ไม่ระบุผู้ปิดรอบ')}</small></span><b style="color:${n(x.variance)>=0?'var(--brand)':'var(--danger)'}">${n(x.variance)>0?'+':''}${money(x.variance)}</b></div>`).join('')+'</div>'}

function exportCSV(){
  if(CURRENT_PAGE==='income-expense')return exportIncomeExpenseCSV();
  const monthEl=CURRENT_PAGE==='reports'?$('reportMonth'):$('dashMonth');
  if(!monthEl)return toast('หน้านี้ไม่มีข้อมูลสำหรับ Export',true);
  const m=monthEl.value,stateRows=state.cache[m]||[];
  if(!stateRows.length)return toast('ไม่มีข้อมูลสำหรับ Export',true);
  const rows=[['วันที่','หน้าร้าน','LINEMAN','GRAB','SHOPEE','รวมแก้ว','รายรับ','รายจ่าย','สุทธิ','หมายเหตุ'],...stateRows.map(r=>[r.record_date,r.front_cups,r.lineman_cups,r.grab_cups,r.shopee_cups,cups(r),revenue(r),expense(r),revenue(r)-expense(r),r.note||''])];
  const csv='\uFEFF'+rows.map(a=>a.map(v=>'"'+String(v??'').replaceAll('"','""')+'"').join(',')).join('\n'),blob=new Blob([csv],{type:'text/csv;charset=utf-8'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=`HOP_Chafe_${m}.csv`;a.click();URL.revokeObjectURL(url)
}
function escapeHtml(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}
function escapeAttr(s){return escapeHtml(s)}

async function init(){
  try{
    const status=await api('status');
    setDbStatus(true,status.database);

    if(CURRENT_PAGE==='dashboard'){
      const qMonth=new URLSearchParams(window.location.search).get('month')||'';
      await refreshMonths(qMonth);
      await renderDashboard();
      return;
    }

    if(CURRENT_PAGE==='daily'){
      $('dDate').value=today();
      state.dailyExtraExpenseItems=[];renderDailyExtraExpenses();
      updateDailyCalc();
      await loadDailyEditFromQuery();
      return;
    }

    if(CURRENT_PAGE==='close'){
      $('cDate').value=today();
      buildDenoms();updateCloseCalc();
      await loadClosings();
      return;
    }

    if(CURRENT_PAGE==='inventory'){
      await loadInventory();
      return;
    }

    if(CURRENT_PAGE==='reports'){
      const q=new URLSearchParams(window.location.search),qMonth=q.get('month')||'';
      await refreshMonths(qMonth);
      if(qMonth&&$('reportMonth'))$('reportMonth').value=qMonth;
      await renderReports();
      if(q.get('saved')==='1')toast('บันทึกลงฐานข้อมูลแล้ว');
      return;
    }

    if(CURRENT_PAGE==='income-expense'){
      $('ieDate').value=today();
      state.ieIncomeItems=blankLedgerItems(IE_DEFAULT_INCOME);state.ieExpenseItems=blankLedgerItems(IE_DEFAULT_EXPENSE);
      renderLedgerRows('income');renderLedgerRows('expense');updateIncomeExpenseCalc();
      try{
        await refreshIncomeExpenseMonths();
        await Promise.all([loadIncomeExpenseDate(),renderIncomeExpenseHistory()]);
      }catch(ieErr){toast('เมนูสรุปบัญชีต้องอัปเดตฐานข้อมูล — เปิด install.php หนึ่งครั้ง',true)}
      return;
    }
  }catch(e){
    setDbStatus(false);
    toast(e.message+' — เปิด install.php เพื่อติดตั้ง',true);
    const financial=$('financialSummary');
    if(financial)financial.innerHTML=`<div class="notice"><b>ยังเชื่อมฐานข้อมูลไม่ได้</b><br>เปิด <a class="status-link" href="${APP_URLS.install}">install.php</a> เพื่อติดตั้งฐานข้อมูลก่อน</div>`;
  }
}
init();
