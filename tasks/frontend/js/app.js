// State Data Aplikasi Sementara (Mock Data untuk simulasi API)
let state = {
    projects: [
        { id: 1, name: "Web Toko Online" }
    ],
    tasks: [
        { id: 101, project_id: 1, title: "Desain Database", description: "Buat tabel menggunakan prefiks to_", status: "In Progress", due_date: "2026-07-25" }
    ],
    subtasks: [
        { id: 1001, task_id: 101, title: "Tabel to_projects", is_completed: true },
        { id: 1002, task_id: 101, title: "Tabel to_tasks", is_completed: false }
    ],
    activeProjectId: null
};

// DOM Elements
const projectList = document.getElementById('project-list');
const projectForm = document.getElementById('project-form');
const projectInput = document.getElementById('project-input');
const activeProjectTitle = document.getElementById('active-project-title');
const taskSection = document.getElementById('task-section');
const taskList = document.getElementById('task-list');
const taskForm = document.getElementById('task-form');

// --- PENGATURAN PROJECT ---
projectForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const newProject = {
        id: Date.now(),
        name: projectInput.value
    };
    state.projects.push(newProject);
    projectInput.value = '';
    renderProjects();
});

function renderProjects() {
    projectList.innerHTML = '';
    state.projects.forEach(proj => {
        const li = document.createElement('li');
        li.textContent = proj.name;
        if(state.activeProjectId === proj.id) li.classList.add('active');
        li.addEventListener('click', () => selectProject(proj));
        projectList.appendChild(li);
    });
}

function selectProject(project) {
    state.activeProjectId = project.id;
    activeProjectTitle.textContent = project.name;
    taskSection.classList.remove('hidden');
    renderProjects();
    renderTasks();
}

// --- PENGATURAN TUGAS ---
taskForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const taskId = document.getElementById('task-id').value;
    
    if(taskId) {
        // Mode Edit
        const task = state.tasks.find(t => t.id == taskId);
        task.title = document.getElementById('task-title').value;
        task.description = document.getElementById('task-desc').value;
        task.due_date = document.getElementById('task-duedate').value;
        task.status = document.getElementById('task-status').value;
    } else {
        // Mode Tambah Baru
        const newTask = {
            id: Date.now(),
            project_id: state.activeProjectId,
            title: document.getElementById('task-title').value,
            description: document.getElementById('task-desc').value,
            due_date: document.getElementById('task-duedate').value,
            status: document.getElementById('task-status').value
        };
        state.tasks.push(newTask);
    }
    
    taskForm.reset();
    document.getElementById('task-id').value = '';
    renderTasks();
});

function renderTasks() {
    taskList.innerHTML = '';
    const filteredTasks = state.tasks.filter(t => t.project_id === state.activeProjectId);
    
    filteredTasks.forEach(task => {
        const card = document.createElement('div');
        card.className = `task-card ${task.status.replace(' ', '-')}`;
        
        // Cek tenggat waktu
        const today = new Date().toISOString().split('T')[0];
        const isOverdue = task.due_date < today && task.status !== 'Completed';

        card.innerHTML = `
            <span class="badge">${task.status}</span>
            <h3>${task.title}</h3>
            <p>${task.description || '<i>Tidak ada keterangan</i>'}</p>
            <p class="due-date" style="color: ${isOverdue ? '#ef4444' : '#64748b'}">
                📅 Tenggat: ${task.due_date} ${isOverdue ? '(Terlambat!)' : ''}
            </p>
            <button onclick="editTask(${task.id})">Edit</button>
            <button onclick="deleteTask(${task.id})" style="background:#ef4444; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">Hapus</button>
            
            <div class="subtask-list">
                <h4>Sub-tugas:</h4>
                <div id="subtasks-for-${task.id}"></div>
                <input type="text" placeholder="Tambah sub-tugas..." onkeypress="addSubTask(event, ${task.id})" style="width:100%; margin-top:8px; padding:4px;">
            </div>
        `;
        taskList.appendChild(card);
        renderSubTasks(task.id);
    });
}

function editTask(id) {
    const task = state.tasks.find(t => t.id === id);
    document.getElementById('task-id').value = task.id;
    document.getElementById('task-title').value = task.title;
    document.getElementById('task-desc').value = task.description;
    document.getElementById('task-duedate').value = task.due_date;
    document.getElementById('task-status').value = task.status;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function deleteTask(id) {
    state.tasks = state.tasks.filter(t => t.id !== id);
    renderTasks();
}

// --- PENGATURAN SUB-TUGAS ---
function renderSubTasks(taskId) {
    const container = document.getElementById(`subtasks-for-${taskId}`);
    container.innerHTML = '';
    const filteredSubs = state.subtasks.filter(s => s.task_id === taskId);
    
    filteredSubs.forEach(sub => {
        const div = document.createElement('div');
        div.innerHTML = `
            <label style="text-decoration: ${sub.is_completed ? 'line-through' : 'none'}">
                <input type="checkbox" ${sub.is_completed ? 'checked' : ''} onclick="toggleSubTask(${sub.id})">
                ${sub.title}
            </label>
        `;
        container.appendChild(div);
    });
}

function addSubTask(e, taskId) {
    if (e.key === 'Enter' && e.target.value.trim() !== '') {
        const newSub = {
            id: Date.now(),
            task_id: taskId,
            title: e.target.value,
            is_completed: false
        };
        state.subtasks.push(newSub);
        e.target.value = '';
        renderSubTasks(taskId);
    }
}

function toggleSubTask(subId) {
    const sub = state.subtasks.find(s => s.id === subId);
    sub.is_completed = !sub.is_completed;
    renderSubTasks(sub.task_id);
}

// Inisialisasi Awal
renderProjects();