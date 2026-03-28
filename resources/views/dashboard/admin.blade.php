@extends('layouts.app')

@section('title', 'Dashboard Admin')

@push('head')
<style>
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
    .wrap { max-width: 1400px; margin: 0 auto; }
    .header {
        background: white;
        border-radius: 15px;
        padding: 20px 30px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .header h1 { color: #667eea; font-size: 1.8em; }
    .user-info { display: flex; align-items: center; gap: 15px; }
    .user-info span { color: #666; }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-sm { padding: 6px 12px; font-size: 0.9em; }
    .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
    .tab {
        padding: 12px 24px;
        background: white;
        border: none;
        border-radius: 10px 10px 0 0;
        cursor: pointer;
        font-weight: 600;
        color: #666;
    }
    .tab.active { color: #667eea; border-bottom: 3px solid #667eea; }
    .tab-content {
        display: none;
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .tab-content.active { display: block; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
    th { background: #f8f9fa; font-weight: 600; }
    .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .badge-primary { background: #cce5ff; color: #004085; }
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    .modal.show { display: flex; }
    .modal-content {
        background: white;
        border-radius: 15px;
        padding: 30px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-header h2 { color: #667eea; }
    .close { font-size: 2em; cursor: pointer; color: #999; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1em;
    }
    .actions { display: flex; gap: 10px; }
</style>
@endpush

@section('nav_links')
    <a href="/admin/dashboard">Dashboard</a>
@endsection

@section('content')
<div class="wrap">
    <div class="header">
        <h1>Dashboard Administrateur</h1>
        <div class="user-info">
            <span id="userName"></span>
        </div>
    </div>

    <div class="tabs">
        <button type="button" class="tab active" data-tab="users">Utilisateurs</button>
        <button type="button" class="tab" data-tab="trainings">Formations</button>
    </div>

    <div id="usersTab" class="tab-content active">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px; align-items:center;">
            <h2>Gestion des Utilisateurs</h2>
            <button type="button" class="btn btn-primary" onclick="openUserModal()">+ Nouvel Utilisateur</button>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody"></tbody>
            </table>
        </div>
    </div>

    <div id="trainingsTab" class="tab-content">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px; align-items:center;">
            <h2>Gestion des Formations</h2>
            <button type="button" class="btn btn-primary" onclick="openTrainingModal()">+ Nouvelle Formation</button>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Créateur</th>
                        <th>Participants</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="trainingsTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="userModalTitle">Nouvel Utilisateur</h2>
            <span class="close" onclick="closeUserModal()">&times;</span>
        </div>
        <form id="userForm">
            <input type="hidden" id="userId">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" id="userNameInput" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="userEmailInput" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" id="userPasswordInput">
                <small style="color:#666;">Laisser vide pour ne pas modifier</small>
            </div>
            <div class="form-group">
                <label>Rôle</label>
                <select id="userRoleInput" required>
                    <option value="admin">Admin</option>
                    <option value="trainer">Formateur</option>
                    <option value="trainee">Apprenant</option>
                </select>
            </div>
            <div class="form-group">
                <label>Statut</label>
                <select id="userActiveInput">
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Enregistrer</button>
                <button type="button" class="btn btn-danger" style="flex:1;" onclick="closeUserModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<div id="trainingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="trainingModalTitle">Nouvelle Formation</h2>
            <span class="close" onclick="closeTrainingModal()">&times;</span>
        </div>
        <form id="trainingForm">
            <input type="hidden" id="trainingId">
            <div class="form-group">
                <label>Titre</label>
                <input type="text" id="trainingTitleInput" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="trainingDescriptionInput" rows="4" style="min-height:100px;"></textarea>
            </div>
            <div class="form-group">
                <label>Date de début</label>
                <input type="datetime-local" id="trainingDateInput">
            </div>
            <div class="form-group">
                <label>Date de fin</label>
                <input type="date" id="trainingEndDateInput">
            </div>
            <div class="form-group">
                <label>Statut</label>
                <select id="trainingStatusInput">
                    <option value="draft">Brouillon</option>
                    <option value="published">Publié</option>
                    <option value="archived">Archivé</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Enregistrer</button>
                <button type="button" class="btn btn-danger" style="flex:1;" onclick="closeTrainingModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token');
    let user = {};
    try { user = JSON.parse(localStorage.getItem('user') || '{}'); } catch (e) {}
    if (!token || user.role !== 'admin') {
        window.location.href = '/';
    }

    document.getElementById('userName').textContent = user.name || user.email;

    document.querySelectorAll('.tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.getAttribute('data-tab');
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(tab + 'Tab').classList.add('active');
        });
    });

    async function api(url, opts = {}) {
        const headers = Object.assign({
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }, opts.headers || {});
        if (opts.body instanceof FormData) {
            delete headers['Content-Type'];
        }
        return fetch(url, Object.assign({}, opts, { headers }));
    }

    async function loadUsers() {
        const res = await api('/api/users');
        const json = await res.json();
        if (!json.success) return;
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = json.data.map(u => `
            <tr>
                <td>${u.id}</td>
                <td>${escapeHtml(u.name)}</td>
                <td>${escapeHtml(u.email)}</td>
                <td>${u.role}</td>
                <td><span class="badge ${u.is_active ? 'badge-success' : 'badge-danger'}">${u.is_active ? 'Actif' : 'Inactif'}</span></td>
                <td class="actions">
                    <button type="button" class="btn btn-primary btn-sm" onclick="editUser(${u.id})">Modifier</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})">Supprimer</button>
                </td>
            </tr>
        `).join('');
    }

    async function loadTrainings() {
        const res = await api('/api/trainings');
        const json = await res.json();
        if (!json.success) return;
        const tbody = document.getElementById('trainingsTableBody');
        tbody.innerHTML = json.data.map(t => {
            const dStart = t.training_date ? new Date(t.training_date).toLocaleDateString('fr-FR') : '-';
            const dEnd = t.end_date ? new Date(t.end_date + 'T12:00:00').toLocaleDateString('fr-FR') : '-';
            return `
            <tr>
                <td>${t.id}</td>
                <td>${escapeHtml(t.title)}</td>
                <td>${dStart}</td>
                <td>${dEnd}</td>
                <td>${escapeHtml(t.creator_name || '-')}</td>
                <td>${t.participants_count ?? 0}</td>
                <td><span class="badge badge-primary">${t.status}</span></td>
                <td class="actions">
                    <button type="button" class="btn btn-primary btn-sm" onclick="viewTrainingDetails(${t.id})">Détails</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="editTraining(${t.id})">Modifier</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteTraining(${t.id})">Supprimer</button>
                </td>
            </tr>`;
        }).join('');
    }

    function escapeHtml(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function openUserModal(userId = null) {
        document.getElementById('userModalTitle').textContent = userId ? 'Modifier Utilisateur' : 'Nouvel Utilisateur';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = userId || '';
        document.getElementById('userPasswordInput').required = !userId;
        document.getElementById('userModal').classList.add('show');
        if (userId) {
            api('/api/users/' + userId).then(r => r.json()).then(data => {
                if (data.success) {
                    const u = data.data;
                    document.getElementById('userNameInput').value = u.name;
                    document.getElementById('userEmailInput').value = u.email;
                    document.getElementById('userRoleInput').value = u.role;
                    document.getElementById('userActiveInput').value = u.is_active ? '1' : '0';
                }
            });
        }
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.remove('show');
    }

    function openTrainingModal(trainingId = null) {
        document.getElementById('trainingModalTitle').textContent = trainingId ? 'Modifier Formation' : 'Nouvelle Formation';
        document.getElementById('trainingForm').reset();
        document.getElementById('trainingId').value = trainingId || '';
        document.getElementById('trainingModal').classList.add('show');
        if (trainingId) {
            api('/api/trainings/' + trainingId + '?with_participants=0').then(r => r.json()).then(data => {
                if (data.success) {
                    const t = data.data;
                    document.getElementById('trainingTitleInput').value = t.title;
                    document.getElementById('trainingDescriptionInput').value = t.description || '';
                    document.getElementById('trainingDateInput').value = t.training_date ? t.training_date.substring(0, 16) : '';
                    document.getElementById('trainingEndDateInput').value = t.end_date || '';
                    document.getElementById('trainingStatusInput').value = t.status;
                }
            });
        }
    }

    function closeTrainingModal() {
        document.getElementById('trainingModal').classList.remove('show');
    }

    document.getElementById('userForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = document.getElementById('userId').value;
        const payload = {
            name: document.getElementById('userNameInput').value,
            email: document.getElementById('userEmailInput').value,
            role: document.getElementById('userRoleInput').value,
            is_active: document.getElementById('userActiveInput').value === '1'
        };
        const pw = document.getElementById('userPasswordInput').value;
        if (pw) payload.password = pw;

        const method = userId ? 'PUT' : 'POST';
        const url = userId ? '/api/users/' + userId : '/api/users';

        const res = await api(url, { method, body: JSON.stringify(payload) });
        const result = await res.json();
        if (result.success) {
            closeUserModal();
            loadUsers();
        } else {
            alert(result.error || result.message || 'Erreur');
        }
    });

    document.getElementById('trainingForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const trainingId = document.getElementById('trainingId').value;
        const payload = {
            title: document.getElementById('trainingTitleInput').value,
            description: document.getElementById('trainingDescriptionInput').value,
            training_date: document.getElementById('trainingDateInput').value || null,
            end_date: document.getElementById('trainingEndDateInput').value || null,
            status: document.getElementById('trainingStatusInput').value
        };
        const method = trainingId ? 'PUT' : 'POST';
        const url = trainingId ? '/api/trainings/' + trainingId : '/api/trainings';
        const res = await api(url, { method, body: JSON.stringify(payload) });
        const result = await res.json();
        if (result.success) {
            closeTrainingModal();
            loadTrainings();
        } else {
            alert(result.error || result.message || 'Erreur');
        }
    });

    async function editUser(id) { openUserModal(id); }

    async function deleteUser(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) return;
        const res = await api('/api/users/' + id, { method: 'DELETE' });
        const result = await res.json();
        if (result.success) loadUsers();
        else alert(result.error || 'Erreur');
    }

    function viewTrainingDetails(id) {
        window.location.href = '/trainings/' + id + '/details';
    }

    async function editTraining(id) { openTrainingModal(id); }

    async function deleteTraining(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette formation ?')) return;
        const res = await api('/api/trainings/' + id, { method: 'DELETE' });
        const result = await res.json();
        if (result.success) loadTrainings();
        else alert(result.error || 'Erreur');
    }

    loadUsers();
    loadTrainings();
</script>
@endsection
