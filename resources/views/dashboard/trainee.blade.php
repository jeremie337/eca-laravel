@extends('layouts.app')

@section('title', 'Dashboard Apprenant')

@push('head')
<style>
    body { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); min-height: 100vh; padding: 20px; }
    .wrap { max-width: 1200px; margin: 0 auto; }
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
    .header h1 { color: #17a2b8; font-size: 1.8em; }
    .user-info { display: flex; align-items: center; gap: 15px; }
    .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
    .btn-primary { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-success:disabled { opacity: 0.6; cursor: not-allowed; }
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: white; border-radius: 15px; padding: 25px; text-align: center; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
    .stat-card h3 { color: #17a2b8; font-size: 2.5em; margin-bottom: 10px; }
    .trainings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .training-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
    .training-card h3 { color: #17a2b8; margin-bottom: 10px; }
    .training-meta { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.9em; color: #999; }
    .progress-bar { width: 100%; height: 25px; background: #e0e0e0; border-radius: 15px; overflow: hidden; margin-bottom: 15px; }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #17a2b8 0%, #138496 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.85em;
    }
    .materials-section { margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0; }
    .material-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; }
    .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-info { background: #cce5ff; color: #004085; }
    .section-title { color: white; margin: 30px 0 20px; font-size: 1.4em; }
    .section-title:first-of-type { margin-top: 0; }
    .empty-state { text-align: center; padding: 60px 20px; color: white; grid-column: 1 / -1; }
</style>
@endpush

@section('nav_links')
    <a href="/trainee/dashboard">Dashboard</a>
@endsection

@section('content')
<div class="wrap">
    <div class="header">
        <h1>Dashboard Apprenant</h1>
        <div class="user-info">
            <span id="userName"></span>
        </div>
    </div>

    <div class="stats" id="statsContainer"></div>

    <h2 class="section-title">Formations disponibles</h2>
    <p style="color: rgba(255,255,255,0.9); margin-bottom: 15px;">Inscrivez-vous aux formations publiées pour y accéder dans « Mes Formations ».</p>
    <div class="trainings-grid" id="availableTrainingsGrid"></div>

    <h2 class="section-title">Mes Formations</h2>
    <div class="trainings-grid" id="trainingsGrid"></div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token');
    let user = {};
    try { user = JSON.parse(localStorage.getItem('user') || '{}'); } catch (e) {}
    if (!token || user.role !== 'trainee') {
        window.location.href = '/';
    }

    document.getElementById('userName').textContent = user.name || user.email;

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    async function loadAvailableTrainings() {
        const res = await fetch('/api/trainings/available', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        const result = await res.json();
        const grid = document.getElementById('availableTrainingsGrid');
        if (!result.success) {
            grid.innerHTML = '<p style="color:rgba(255,255,255,0.8);">Impossible de charger le catalogue.</p>';
            return;
        }
        const list = result.data || [];
        if (list.length === 0) {
            grid.innerHTML = '<p style="color:rgba(255,255,255,0.9); font-style:italic;">Aucune formation disponible pour le moment (ou vous êtes déjà inscrit à toutes).</p>';
            return;
        }
        grid.innerHTML = list.map(t => {
            const startStr = t.training_date ? new Date(t.training_date).toLocaleDateString('fr-FR', { dateStyle: 'medium' }) : '-';
            const endStr = t.end_date ? new Date(t.end_date + 'T12:00:00').toLocaleDateString('fr-FR', { dateStyle: 'medium' }) : '-';
            return `
            <div class="training-card">
                <h3>${escapeHtml(t.title)}</h3>
                <div class="training-meta">
                    <span>Début : ${startStr}</span>
                    <span>Fin : ${endStr}</span>
                </div>
                <p style="color:#666;">${escapeHtml(t.description || 'Aucune description')}</p>
                <p style="font-size:0.9em; color:#999;">Documents : ${t.materials_count || 0}</p>
                <button type="button" class="btn btn-primary" onclick="selfEnroll(${t.id})" id="btn-enroll-${t.id}">S'inscrire</button>
            </div>`;
        }).join('');
    }

    async function selfEnroll(trainingId) {
        trainingId = parseInt(trainingId, 10);
        const btn = document.getElementById('btn-enroll-' + trainingId);
        if (btn) { btn.disabled = true; btn.textContent = 'Inscription...'; }
        try {
            const res = await fetch('/api/trainings/self-enroll', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ training_id: trainingId })
            });
            const result = await res.json();
            if (result.success) {
                loadAvailableTrainings();
                loadUserTrainings();
            } else {
                alert(result.error || 'Erreur lors de l\'inscription');
                if (btn) { btn.disabled = false; btn.textContent = 'S\'inscrire'; }
            }
        } catch (e) {
            alert('Erreur réseau: ' + e.message);
            if (btn) { btn.disabled = false; btn.textContent = 'S\'inscrire'; }
        }
    }

    function getTrainingStatus(t) {
        if (t.completed) return { label: 'Terminée', badge: 'badge-success' };
        const now = new Date();
        const start = t.training_date ? new Date(t.training_date) : null;
        const end = t.end_date ? new Date(t.end_date + 'T23:59:59') : null;
        if (end && now > end) return { label: 'Terminée', badge: 'badge-success' };
        if (start && now < start) return { label: 'À venir', badge: 'badge-info' };
        return { label: 'En cours', badge: 'badge-warning' };
    }

    async function loadUserTrainings() {
        const res = await fetch('/api/trainings/my?user_id=' + encodeURIComponent(user.id), {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (!data.success) return;

        const totalTrainings = data.data.length;
        const completedTrainings = data.data.filter(t => t.completed).length;
        const totalProgress = data.data.reduce((sum, t) => sum + (parseInt(t.progress, 10) || 0), 0);
        const avgProgress = totalTrainings > 0 ? Math.round(totalProgress / totalTrainings) : 0;

        document.getElementById('statsContainer').innerHTML = `
            <div class="stat-card"><h3>${totalTrainings}</h3><p>Formations</p></div>
            <div class="stat-card"><h3>${completedTrainings}</h3><p>Terminées</p></div>
            <div class="stat-card"><h3>${avgProgress}%</h3><p>Progression moyenne</p></div>
        `;

        const grid = document.getElementById('trainingsGrid');
        if (data.data.length === 0) {
            grid.innerHTML = '<div class="empty-state"><h2>Aucune formation</h2><p>Vous n\'êtes inscrit à aucune formation pour le moment.</p></div>';
            return;
        }
        grid.innerHTML = data.data.map(training => {
            const status = getTrainingStatus(training);
            const endDateStr = training.end_date ? new Date(training.end_date + 'T12:00:00').toLocaleDateString('fr-FR', { dateStyle: 'medium' }) : null;
            const prog = parseInt(training.progress, 10) || 0;
            return `
            <div class="training-card">
                <h3>${escapeHtml(training.title)}</h3>
                <div class="training-meta">
                    <span>${training.training_date ? new Date(training.training_date).toLocaleDateString('fr-FR') : 'Date non définie'}</span>
                    <span class="badge ${status.badge}">${status.label}</span>
                </div>
                ${endDateStr ? '<p style="font-size:0.9em; color:#666; margin-bottom:10px;">Se termine le : ' + endDateStr + '</p>' : ''}
                <p style="color:#666;">${escapeHtml(training.description || 'Aucune description')}</p>
                <div style="margin-bottom:15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <span style="font-size:0.9em; color:#666;">Progression</span>
                        <span style="font-size:0.9em; color:#666; font-weight:600;">${prog}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:${prog}%">${prog}%</div>
                    </div>
                </div>
                ${!training.completed ? `
                <div style="margin-bottom:15px;">
                    <button type="button" class="btn btn-success" onclick="markAsCompleted(${training.id})" id="btn-complete-${training.id}">Marquer comme terminé</button>
                </div>` : ''}
                <div class="materials-section">
                    <h4 style="color:#333; margin-bottom:15px;">Documents (${training.materials_count || 0})</h4>
                    <div id="materials-${training.id}">Chargement...</div>
                </div>
            </div>`;
        }).join('');

        data.data.forEach(training => loadMaterials(training.id));
    }

    async function markAsCompleted(trainingId) {
        const btn = document.getElementById('btn-complete-' + trainingId);
        if (btn) btn.disabled = true;
        try {
            const res = await fetch('/api/trainings/complete', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ training_id: trainingId })
            });
            const result = await res.json();
            if (result.success) loadUserTrainings();
            else {
                alert(result.error || 'Erreur');
                if (btn) btn.disabled = false;
            }
        } catch (e) {
            alert('Erreur: ' + e.message);
            if (btn) btn.disabled = false;
        }
    }

    async function loadMaterials(trainingId) {
        const res = await fetch('/api/trainings/' + trainingId + '/materials', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        const json = await res.json();
        const container = document.getElementById('materials-' + trainingId);
        if (!container) return;
        if (!json.success) return;
        if (json.data.length === 0) {
            container.innerHTML = '<p style="color:#999; font-style:italic;">Aucun document disponible</p>';
            return;
        }
        container.innerHTML = json.data.map(material => `
            <div class="material-item">
                <span>${escapeHtml(material.file_name)}</span>
                <a href="/api/materials/${material.id}/download?token=${encodeURIComponent(token)}"
                   class="btn btn-primary" style="padding:5px 15px;">Télécharger</a>
            </div>
        `).join('');
    }

    loadAvailableTrainings();
    loadUserTrainings();
</script>
@endsection
