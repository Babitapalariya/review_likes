const pages = [
    "dashboard", "create", "requests", "quotations", "approval", "hired", "messages", "submissions", "pay ments", "settings",
    "requestDetail", "taskDetail"
];

function go(page) {
    pages.forEach(p => {
        const el = document.getElementById("page-" + p);
        if (el) el.classList.add("hide");
    });
    const show = document.getElementById("page-" + page);
    if (show) show.classList.remove("hide");
    // nav active (only for main pages) 
    document.querySelectorAll("#nav button").forEach(b => b.classList.remove("active"));
    const btn = document.querySelector(`#nav button[data-page="${page}"]`);
    if (btn) btn.classList.add("active");
    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
}

function toast(msg) {
    const t = document.getElementById("toast");
    t.textContent = msg;
    t.classList.remove("hide");
    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(() => t.classList.add("hide"), 2200);
}
// Open request detail 
function openRequest(id) {
    document.getElementById("reqTitle").textContent = `Request Detail — ${id}`;
    document.getElementById("reqSub").textContent = `Track performance, applicants, hires, and submissions for ${id}.`;
    // demo numbers 
    if (id === "REQ-209") {
        document.getElementById("reqViews").textContent = "532";
        document.getElementById("reqApplicants").textContent = "17";
        document.getElementById("reqHired").textContent = "4";
        document.getElementById("reqSubmissions").textContent = "1";
    }
    go("requestDetail");
}
// Open task detail (or quotation chat) 
function openTask(id) {
    // message view card 
    const taskTitle = document.getElementById("taskTitle");
    const taskSub = document.getElementById("taskSub");
    const taskMeta = document.getElementById("taskMeta");
    const taskStatus = document.getElementById("taskStatus");
    if (taskTitle) {
        taskTitle.textContent = `${id} — Thread`;
        taskSub.textContent = `Chat + progress + files for ${id}`;
        taskMeta.textContent = (id.startsWith("QUOTE")) ? "Quotation" : "Task";
        taskStatus.textContent = (id.startsWith("QUOTE")) ? "Quote" : "Active";
    }
    // task detail page 
    document.getElementById("tskTitle").textContent = `Task Detail — ${id}`;
    document.getElementById("tskSub").textContent = `Milestones + chat + submission + approval for ${id}.`;
    if (id === "TSK-1001") {
        document.getElementById("tskDaysLeft").textContent = "10";
        document.getElementById("tskMilestone").textContent = "3/5";
    } else if (id === "TSK-1002") {
        document.getElementById("tskDaysLeft").textContent = "5";
        document.getElementById("tskMilestone").textContent = "5/5";
    } else {
        document.getElementById("tskDaysLeft").textContent = "7";
        document.getElementById("tskMilestone").textContent = "2/5";
    }
    // If user is currently in Messages page, don't force taskDetail—just show chat card populated. 
    const messagesVisible = !document.getElementById("page-messages").classList.contains("hide");
    if (!messagesVisible) {
        go("taskDetail");
    }
}

function sendMsg() {
    const input = document.getElementById("msgInput");
    if (!input.value.trim()) return;
    const chat = document.getElementById("chat");
    chat.insertAdjacentHTML("beforeend", ` 
<div class="msg"> 
<div class="avatar">M</div> 
<div class="bubble me"> 
<div class="meta"><span>Merchant</span><span>•</span><span>Now</span></div> <div class="text"></div> 
</div> 
</div> 
`);
    chat.lastElementChild.querySelector(".text").textContent = input.value.trim();
    input.value = "";
    chat.scrollTop = chat.scrollHeight;
}

function sendMsg2() {
    const input = document.getElementById("msgInput2");
    if (!input.value.trim()) return;
    const chat = document.getElementById("chat2");
    chat.insertAdjacentHTML("beforeend", ` 
<div class="msg"> 
<div class="avatar">M</div> 
<div class="bubble me"> 
<div class="meta"><span>Merchant</span><span>•</span><span>Now</span></div> <div class="text"></div> 
</div> 
</div> 
`);
    chat.lastElementChild.querySelector(".text").textContent = input.value.trim();
    input.value = "";
    chat.scrollTop = chat.scrollHeight;
}
// Wire nav 
document.querySelectorAll("#nav button").forEach(btn => {
    btn.addEventListener("click", () => go(btn.dataset.page));
});