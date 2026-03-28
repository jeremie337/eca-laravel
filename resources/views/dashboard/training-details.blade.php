@extends('layouts.app')

@section('title', 'Détails formation')

@push('head')
<style>
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
    .wrap { max-width: 1000px; margin: 0 auto; }
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
    .header h1 { color: #667eea; font-size: 1.5em; }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .card h2 { color: #333; margin-bottom: 15px; font-size: 1.2em; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .info-item label { display: block; color: #666; font-size: 0.9em; margin-bottom: 4px; }
    .info-item span { font-weight: 600; color: #333; }
    .description { color: #555; line-height: 1.6; white-space: pre-wrap; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
    th { background: #f8f9fa; font-weight: 600; }
    .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .empty-state { text-align: center; padding: 40px; color: #666; }
</style>
@endpush

@section('nav_links')
    <a href="/admin/dashboard">Dashboard</a>
@endsection

@section('content')
<div class="wrap">
    <div class="header">
        <h1 id="pageTitle">Détails de la formation</h1>
        <a href="/admin/dashboard" class="btn">← Retour au dashboard</a>
    </div>

    <div class="card" id="trainingCard">
        <div class="info-grid">
            <div class="info-item">
                <label>Date de début</label>
                <span id="startDate">-</span>
            </div>
            <div class="info-item">
                <label>Date de fin</label>
                <span id="endDate">-</span>
            </div>
            <div class="info-item">
                <label>Créateur</label>
                <span id="creatorName">-</span>
            </div>
            <div class="info-item">
                <label>Statut</label>
                <span id="status">-</span>
            </div>
        </div>
        <h2>Description</h2>
        <p class="description" id="description">-</p>
    </div>

    <div class="card">
        <h2>Participants</h2>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Progression</th>
                        <th>Statut</th>
                        <th>Date d'inscription</th>
                        <th>Date de complétion</th>
                    </tr>
                </thead>
                <tbody id="participantsBody"></tbody>
            </table>
        </div>
        <div id="participantsEmpty" class="empty-state" style="display:none;">
            Aucun participant inscrit à cette formation.
        </div>
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

    const trainingId = {{ (int) $trainingId }};

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async function loadTrainingDetails() {
        try {
            const res = await fetch('/api/trainings/' + trainingId + '?with_participants=1', {
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
            });
            const result = await res.json();
            if (!result.success) {
                document.getElementById('pageTitle').textContent = 'Formation non trouvée';
                document.getElementById('trainingCard').innerHTML =
                    '<p class="empty-state">' + (result.error || 'Erreur') + '. <a href="/admin/dashboard">Retour</a></p>';
                return;
            }
            const t = result.data;
            document.getElementById('pageTitle').textContent = t.title || 'Détails formation';
            document.getElementById('startDate').textContent = t.training_date
                ? new Date(t.training_date).toLocaleDateString('fr-FR', { dateStyle: 'medium' }) : '-';
            document.getElementById('endDate').textContent = t.end_date
                ? new Date(t.end_date + 'T12:00:00').toLocaleDateString('fr-FR', { dateStyle: 'medium' }) : '-';
            document.getElementById('creatorName').textContent = t.creator_name || '-';
            document.getElementById('status').textContent = t.status || '-';
            document.getElementById('description').textContent = t.description || 'Aucune description.';

            const participants = t.participants || [];
            const tbody = document.getElementById('participantsBody');
            const emptyEl = document.getElementById('participantsEmpty');
            if (participants.length === 0) {
                tbody.innerHTML = '';
                emptyEl.style.display = 'block';
            } else {
                emptyEl.style.display = 'none';
                tbody.innerHTML = participants.map(p => `
                    <tr>
                        <td>${escapeHtml(p.name)}</td>
                        <td>${escapeHtml(p.email)}</td>
                        <td>${p.progress ?? 0}%</td>
                        <td><span class="badge ${p.completed ? 'badge-success' : 'badge-warning'}">${p.completed ? 'Terminé' : 'En cours'}</span></td>
                        <td>${p.enrolled_at ? new Date(p.enrolled_at).toLocaleDateString('fr-FR', { dateStyle: 'medium' }) : '-'}</td>
                        <td>${p.completion_date ? new Date(p.completion_date).toLocaleDateString('fr-FR', { dateStyle: 'medium' }) : '-'}</td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error(e);
            document.getElementById('trainingCard').innerHTML =
                '<p class="empty-state">Erreur de chargement. <a href="/admin/dashboard">Retour</a></p>';
        }
    }

    loadTrainingDetails();
</script>
@endsection
