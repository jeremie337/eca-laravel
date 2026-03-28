@extends('layouts.app')

@section('title', 'Dashboard Formateur')

@push('head')
<style>
    body { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); min-height: 100vh; padding: 20px; }
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
    .header h1 { color: #28a745; font-size: 1.8em; }
    .user-info { display: flex; align-items: center; gap: 15px; }
    .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .btn-primary { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-sm { padding: 5px 10px; font-size: 0.9em; }
    .upload-area { background: white; border-radius: 15px; padding: 30px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
    .upload-area h2 { color: #28a745; margin-bottom: 20px; }
    .drop-zone {
        border: 3px dashed #28a745;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        background: #f8f9fa;
    }
    .drop-zone.dragover { background: #e8f5e9; border-color: #20c997; }
    #fileInput { display: none; }
    .file-info { margin-top: 15px; padding: 15px; background: #e8f5e9; border-radius: 8px; display: none; }
    .file-info.show { display: block; }
    .loading { display: inline-block; width: 16px; height: 16px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 0.8s linear infinite; margin-right: 8px; vertical-align: middle; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .trainings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .training-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .training-card h3 { color: #28a745; margin-bottom: 10px; }
    .training-meta { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.9em; color: #999; }
    .materials-section { margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0; }
    .material-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; }
    .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-info { background: #cce5ff; color: #004085; }
    h2.section { color: white; margin-bottom: 20px; }
</style>
@endpush

@section('nav_links')
    <a href="/trainer/dashboard">Dashboard</a>
@endsection

@section('content')
<div class="wrap">
    <div class="header">
        <h1>Dashboard Formateur</h1>
        <div class="user-info">
            <span id="userName"></span>
        </div>
    </div>

    <div class="upload-area">
        <h2>Upload de Document</h2>
        <div class="drop-zone" id="dropZone">
            <p>Glissez-déposez un fichier ici</p>
            <p>ou</p>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">Sélectionner un fichier</button>
            <p><small>(Max 50MB)</small></p>
            <input type="file" id="fileInput">
        </div>
        <div style="margin-top:20px;">
            <label style="display:block; margin-bottom:8px; font-weight:500;">Formation</label>
            <select id="trainingSelect" style="width:100%; padding:10px; border:2px solid #e0e0e0; border-radius:8px;">
                <option value="">Sélectionner une formation</option>
            </select>
        </div>
        <div class="file-info" id="fileInfo"></div>
        <button type="button" class="btn btn-primary" id="uploadBtn" onclick="uploadFile()" style="margin-top:15px; width:100%;" disabled>Uploader</button>
    </div>

    <h2 class="section">Formations disponibles</h2>
    <div class="trainings-grid" id="trainingsGrid"></div>
</div>
@endsection

@section('scripts')
<script>
    const token = localStorage.getItem('token');
    let user = {};
    try { user = JSON.parse(localStorage.getItem('user') || '{}'); } catch (e) {}
    if (!token || user.role !== 'trainer') {
        window.location.href = '/';
    }

    document.getElementById('userName').textContent = user.name || user.email;

    let selectedFile = null;
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) handleFileSelect(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) handleFileSelect(e.target.files[0]);
    });

    function handleFileSelect(file) {
        if (file.size > 52428800) {
            alert('Le fichier est trop volumineux (max 50MB)');
            return;
        }
        selectedFile = file;
        document.getElementById('fileInfo').innerHTML =
            '<strong>Fichier sélectionné:</strong> ' + escapeHtml(file.name) + '<br><strong>Taille:</strong> ' +
            (file.size / 1024 / 1024).toFixed(2) + ' MB';
        document.getElementById('fileInfo').classList.add('show');
        document.getElementById('uploadBtn').disabled = !document.getElementById('trainingSelect').value;
    }

    document.getElementById('trainingSelect').addEventListener('change', function () {
        document.getElementById('uploadBtn').disabled = !this.value || !selectedFile;
    });

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    async function loadTrainings() {
        const res = await fetch('/api/trainings', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (!json.success) return;

        const select = document.getElementById('trainingSelect');
        select.innerHTML = '<option value="">Sélectionner une formation</option>';
        json.data.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.title;
            select.appendChild(opt);
        });

        const grid = document.getElementById('trainingsGrid');
        const now = new Date();
        grid.innerHTML = json.data.map(training => {
            const startStr = training.training_date ? new Date(training.training_date).toLocaleDateString('fr-FR', { dateStyle: 'medium' }) : '-';
            const endStr = training.end_date ? new Date(training.end_date + 'T12:00:00').toLocaleDateString('fr-FR', { dateStyle: 'medium' }) : '-';
            const end = training.end_date ? new Date(training.end_date + 'T23:59:59') : null;
            const start = training.training_date ? new Date(training.training_date) : null;
            let statusLabel = training.status;
            let statusClass = 'badge-success';
            if (end && now > end) { statusLabel = 'Terminée'; statusClass = 'badge-success'; }
            else if (start && now < start) { statusLabel = 'À venir'; statusClass = 'badge-info'; }
            else { statusLabel = 'En cours'; statusClass = 'badge-warning'; }
            return `
            <div class="training-card">
                <h3>${escapeHtml(training.title)}</h3>
                <div class="training-meta">
                    <span>Début : ${startStr}</span>
                    <span>Fin : ${endStr}</span>
                </div>
                <div class="training-meta"><span class="badge ${statusClass}">${statusLabel}</span></div>
                <p style="color:#666; line-height:1.6;">${escapeHtml(training.description || 'Aucune description')}</p>
                <div class="materials-section">
                    <h4>Documents (${training.materials_count || 0})</h4>
                    <div id="materials-${training.id}">Chargement...</div>
                </div>
            </div>`;
        }).join('');

        json.data.forEach(t => loadMaterials(t.id));
    }

    async function loadMaterials(trainingId) {
        const res = await fetch('/api/trainings/' + trainingId + '/materials', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        const json = await res.json();
        const container = document.getElementById('materials-' + trainingId);
        if (!container) return;
        if (!json.success) {
            container.innerHTML = '<p style="color:#999;">Erreur de chargement</p>';
            return;
        }
        if (json.data.length === 0) {
            container.innerHTML = '<p style="color:#999; font-style:italic;">Aucun document</p>';
            return;
        }
        container.innerHTML = json.data.map(material => `
            <div class="material-item">
                <span>${escapeHtml(material.file_name)}</span>
                <div>
                    <a href="/api/materials/${material.id}/download?token=${encodeURIComponent(token)}"
                       class="btn btn-primary btn-sm" style="text-decoration:none; margin-right:5px;">Télécharger</a>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteMaterial(${material.id}, ${trainingId})">Supprimer</button>
                </div>
            </div>
        `).join('');
    }

    async function uploadFile() {
        const trainingId = document.getElementById('trainingSelect').value;
        if (!trainingId || !selectedFile) {
            alert('Veuillez sélectionner une formation et un fichier');
            return;
        }
        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('training_id', trainingId);
        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="loading"></span>Upload en cours...';
        try {
            const res = await fetch('/api/materials', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
                body: formData
            });
            const result = await res.json();
            if (result.success) {
                alert('Fichier uploadé avec succès !');
                selectedFile = null;
                fileInput.value = '';
                document.getElementById('fileInfo').classList.remove('show');
                document.getElementById('trainingSelect').value = '';
                loadMaterials(trainingId);
                loadTrainings();
            } else {
                alert(result.error || 'Erreur lors de l\'upload');
            }
        } catch (e) {
            alert('Erreur: ' + e.message);
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Uploader';
        }
    }

    async function deleteMaterial(materialId, trainingId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce document ?')) return;
        const res = await fetch('/api/materials/' + materialId, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        const result = await res.json();
        if (result.success) loadMaterials(trainingId);
        else alert(result.error || 'Erreur');
    }

    loadTrainings();
</script>
@endsection
